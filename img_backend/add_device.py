#!/usr/bin/env python
''' Simple script to add a device to the database. '''

from ZensorDevice import ZensorDevice
from enumerations import *
import sys
import random

def main(argv):
    if len(argv) != 2:
        print "Usage:", argv[0], "<device_id>"
        return -1

    devid = sys.argv[1]
    device = ZensorDevice.get_device(devid)
    if device is not None:
        print "Device", devid, "already exists: not adding!"
        return -1

    device = ZensorDevice()
    device.device_id = devid
    device.zensors = {}
    device.activated = True
    device.latest_image = None
    device.password = '%016x' % random.getrandbits(64)
    if device.save():
        print "Device successfully saved!"
        print "Device ID:", device.device_id
        print "Device Password:", device.password
    else:
        print "Error: Device failed to save."

if __name__ == '__main__':
    exit(main(sys.argv))
