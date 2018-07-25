#!/usr/bin/python
from enumerations import *
import cPickle

class ZensorDevice:
    _devicecache = {}
    database = None # SET ME

    def __init__(self):
        self.device_id = ''  # Unique Device ID
        self.zensors = {}    # dictionary of {zensor_id:zensor}, where zensor is a Zensor object
        self.activated = False # Whether the device is on or off
        self.latest_image = None # Last image (PIL Image object)
        self.password = ''

    @classmethod
    def invalidate_cache(cls, key):
        try:
            del cls._devicecache[key]
        except KeyError:
            pass

    @classmethod
    def get_device(cls, key):
        ''' Get device with specified device id.
        Return None if device is not found. '''

        device = cls._devicecache.get(key, None)
        if device is not None:
            return device

        device = cls.database.get_device(key)
        cls._devicecache[key] = device
        return device
