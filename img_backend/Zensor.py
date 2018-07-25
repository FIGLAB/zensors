#!/usr/bin/python

import log
logger = log.getLogger('Zensor', log.DEBUG)

import time
from enumerations import *

import os

from PIL import Image, ImageDraw, ImageFilter
import numpy as np

ObfuscationTypes = {
    ZensorObfuscation.NoFilter : lambda img: img,
    ZensorObfuscation.LightBlur: lambda img: img.filter(ImageFilter.GaussianBlur(2)),
    ZensorObfuscation.HeavyBlur: lambda img: img.filter(ImageFilter.GaussianBlur(8)),
    ZensorObfuscation.MedianFilter: lambda img: img.filter(ImageFilter.MedianFilter(7)),
    ZensorObfuscation.EdgeFilter: lambda img: img, # TODO
}

def applyObfuscation(img, obfuscationType):
    if obfuscationType not in ObfuscationTypes:
        print "Warning: obfuscation type %s not found" % obfuscationType
        return img
    
    return ObfuscationTypes[obfuscationType](img)

def areImagesDifferent(arr1, arr2, mask_count):
    ''' Evaluate whether the input images are different. Inputs must be float-formatted, single channel images. '''

    # TODO: Match original Zensors difference algo
    global_percentage_threshold = 0.1
    pixel_difference_threshold = 255 * 0.05

    diff_count = (np.abs(arr1 - arr2) > pixel_difference_threshold).sum()
    logger.debug("diff_count = %d, mask_count = %d", diff_count, mask_count)
    return float(diff_count) / mask_count > global_percentage_threshold

class Zensor:
    def __init__(self):
        # Data from the server, filled in by the database
        self.zensor_id = ''                                  # Unique Zensor ID
        self.device_id = ''                                  # Owning device ID
        self.activated = False                               # Whether the sensor is activated
        self.question = ''                                   # High-level Question
        self.question_type = ZensorQuestionType.YesNo        # Zensor Question Type, see ZensorQuestionType above
        self.obfuscation = ZensorObfuscation.NoFilter        # Obfuscation mechanism, see ZensoObfuscation above
        self.frequency = ZensorFrequency.EVERY_HOUR          # Polling Frequency, see ZensorFrequency above
        self.lasso_points = []                               # An array of (x,y) crop points

        # Current sensor state
        self.last_timestamp = 0 # The last time a screenshot was taken
        self.mask_bounds = None # Cached mask bounds
        self.mask_image = None # Cached mask
        self.mask_count = 0 # Cached number of unmasked pixels
        self.last_size = None # Cached image size (this better be constant...)
        self.last_image = None # Last image (as grayscale float NumPy array), for image differencing
        self.last_image_timestamp = 0 # Last image timestamp

    def _update_mask(self, size):
        if not self.lasso_points:
            self.mask_bounds = (0, 0, size[0], size[1])
            self.last_size = size
            self.mask_image = Image.new("L", size, 255)
            self.mask_count = 0
            return

        scaled_points = [(int(x*size[0]), int(y*size[1])) for x,y in self.lasso_points]
        xx, yy = zip(*scaled_points)
        minx = min(xx)
        maxx = max(xx)+1
        miny = min(yy)
        maxy = max(yy)+1

        mask_points = [(x - minx, y - miny) for x,y in scaled_points]

        mask = Image.new("L", (maxx - minx, maxy - miny), 255)
        draw = ImageDraw.Draw(mask)
        draw.polygon(mask_points, fill=0)

        self.mask_bounds = (minx, miny, maxx, maxy)
        self.last_size = size
        self.mask_image = mask
        self.mask_count = mask.histogram()[0]

    def _apply_mask(self, img):
        img = img.crop(self.mask_bounds).convert('RGBA')
        img.paste((0, 0, 0, 0), None, self.mask_image)
        return img

    def _save_image(self, img, timestamp, skipped=False):
        ts = time.strftime('%Y%m%d_%H%M%S', time.localtime(timestamp))
        dirname = 'sensor_images/%s/%s' % (self.device_id, self.zensor_id)
        try:
            os.makedirs(dirname)
        except EnvironmentError:
            pass
        fn = '%s/%s' % (dirname, ts)
        if skipped:
            fn += '_skipped'
        img.save(fn + '.png')

    def process_image(self, image, timestamp):
        ''' Called from a worker thread. The dispatcher has already checked the frequency. '''

        if self.last_size is None or self.last_size != image.size:
            logger.debug("Zensor ID %s: updating mask for image size %s", self.zensor_id, image.size)
            self._update_mask(image.size)
            self.last_image = None

        image = self._apply_mask(image)
        imarr = np.asarray(image.convert('RGB')).astype('float')
        if self.last_image is None or areImagesDifferent(imarr, self.last_image, self.mask_count):
            logger.debug("Zensor ID %s: last image None: %s, processing", self.zensor_id, self.last_image is None)
            image = applyObfuscation(image, self.obfuscation)

            # TODO: Ship Out for Labelling / Store in Database / Save to Disk
            self.last_image = imarr
            # TODO
            self._save_image(image, timestamp, skipped=False)
        else:        
            logger.debug("Zensor ID %s suppressed image", self.zensor_id)
