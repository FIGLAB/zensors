''' Functions to query and interact with the web front end. '''

import log
logger = log.getLogger('WebFrontEnd', log.DEBUG)

import urllib2, urlparse
import socket
import json

from ZensorDevice import ZensorDevice
from Zensor import Zensor

def get_interface_address(host):
    ''' Get the address of the network interface that would be used to connect to the target. '''
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.connect((host, 12345))
    return sock.getsockname()[0]

SENSOR_FREQUENCIES = {
    'EVERY_10_SECONDS': 10,
    'EVERY_30_SECONDS': 30,
    'EVERY_MINUTE': 60,
    'EVERY_2_MINUTES': 120,
    'EVERY_5_MINUTES': 300,
    'EVERY_10_MINUTES': 600,
    'EVERY_30_MINUTES': 1800,
    'EVERY_HOUR': 3600,
    'EVERY_2_HOURS': 7200,
    'EVERY_4_HOURS': 14400,
    'EVERY_8_HOURS': 28800,
    'EVERY_16_HOURS': 57600,
    'EVERY_DAY': 86400,
    'EVERY_3_DAYS': 259200,
    'EVERY_WEEK': 604800,
    'FOREVER': 1e30,
}

class ImageNotifier(object):
    def __init__(self, url, my_host, my_port):
        self.endpoint_url = url

        if my_host is None:
            urlbits = urlparse.urlparse(url)
            my_host = get_interface_address(urlbits.hostname)

        self.my_url = 'http://%s:%d' % (my_host, my_port)

    def notify_zensor_image(self, zensor, img, timestamp):
        import urllib, urllib2, time
        url = self.my_url + '/device/%s/zensor/%s/latest_image?t=%.6f' % (zensor.device_id, zensor.zensor_id, time.time())
        conn = urllib2.urlopen('%s/new_image/%s/%s?latestImage=%s' % (self.endpoint_url, zensor.device_id, zensor.zensor_id, urllib.quote(url)))
        resp = conn.read()
        logger.debug("Notify response: %s", resp)

class WebFrontEnd(object):
    def __init__(self, url_root):
        self.url = url_root

    def register_server(self, my_host, my_port):
        urlbits = urlparse.urlparse(self.url)
        if my_host is None:
            my_host = get_interface_address(urlbits.hostname)
            logger.debug("My hostname: %s", my_host)

        conn = urllib2.urlopen('%s/api/registerHandler?endpoint=image_handler&ip=%s&port=%d' % (self.url, my_host, my_port))
        resp = json.loads(conn.read())
        logger.debug("Register response: %s", resp)
        if resp.get('status') != 'success':
            raise Exception("Registration failed: %s" % resp)

    def get_device(self, devid):
        conn = urllib2.urlopen('%s/api/getDevice?device_id=%s' % (self.url, devid))
        resp = json.loads(conn.read())
        logger.debug("Device response: %s", resp)
        if resp.get('status', 'success') != 'success':
            logger.warn("Failed to get device: %s", resp)
            return None

        jdev = resp['device']
        device = ZensorDevice()
        device.device_id = jdev['device_id']
        device.activated = jdev['online'] == '1'
        device.latest_image = None
        device.password = jdev['password']

        zensors = {}
        for jzen in jdev['zensors']:
            zensor = Zensor()
            zensor.zensor_id = jzen['sensor_id']
            zensor.device_id = device.device_id
            zensor.activated = jzen['active'] == 'yes'
            zensor.question = jzen['sensor_question']
            zensor.question_type = jzen['sensor_datatype'] # match values from ZensorQuestionType
            zensor.obfuscation = jzen['sensor_obfuscation'] # match values from ZensorObfuscation

            freq = jzen['sensor_frequency']
            if isinstance(freq, (int, long, float)):
                zensor.frequency = freq
            elif freq in SENSOR_FREQUENCIES:
                zensor.frequency = SENSOR_FREQUENCIES[freq]
            elif freq.isdigit():
                zensor.frequency = int(freq)
            else:
                logger.warn("Invalid sensor frequency %s", freq)
                zensor.frequency = 0

            zensor.lasso_points = [(p['x'], p['y']) for p in json.loads(jzen['sensor_subwindowpoints'])]
            zensors[zensor.zensor_id] = zensor
        device.zensors = zensors

        return device
