''' Server to provide both HTTP image reception and web front end callbacks '''
import log
logger = log.getLogger('HTTPImageReceiver', log.DEBUG)
log.getLogger('tornado.access', log.DEBUG)
log.getLogger('tornado.application', log.DEBUG)
log.getLogger('tornado.general', log.DEBUG)

import tornado.web
import os

from PIL import Image
from cStringIO import StringIO

from ZensorDevice import ZensorDevice

worker_dispatcher = None

HANDLERS = []
def route(url):
    def wrapper(cls):
        HANDLERS.append((url, cls))
        return cls
    return wrapper

@route(r"/")
class MainHandler(tornado.web.RequestHandler):
    def get(self):
        self.write("Hello, World!")

### WEB FRONT END CALLBACKS ###
@route(r"/device/([^/]+)/(created|updated|deleted)")
class DeviceCallbackHandler(tornado.web.RequestHandler):
    def get(self, devid, method):
        ZensorDevice.invalidate_cache(devid)

@route(r"/device/([^/]+)/latest_image")
class DeviceImageHandler(tornado.web.RequestHandler):
    def get(self, devid):
        device = ZensorDevice.get_device(devid)
        if device is None:
            self.send_error(404)
            return

        if device.latest_image is None:
            self.send_error(204) # no content
            return

        # XXX SECURITY HAZARD
        self.set_header("Access-Control-Allow-Origin", "*")
        self.set_header("Content-Type", 'image/jpeg')
        io = StringIO()
        device.latest_image.save(io, 'jpeg')
        self.write(io.getvalue())

@route(r"/device/([^/]+)/zensor/([^/]+)/(created|updated|deleted)")
class ZensorCallbackHandler(tornado.web.RequestHandler):
    def get(self, devid, zensorid, method):
        ZensorDevice.invalidate_cache(devid)

@route(r"/device/([^/]+)/zensor/([^/]+)/latest_image")
class ZensorImageHandler(tornado.web.RequestHandler):
    def get(self, devid, zensorid):
        device = ZensorDevice.get_device(devid)
        if device is None:
            self.send_error(404)
            return

        zensor = device.zensors.get(zensorid)
        if zensor is None:
            self.send_error(404)
            return

        if zensor.last_image is None:
            self.send_error(204) # no content
            return

        self.set_header("Content-Type", 'image/png')
        io = StringIO()
        Image.fromarray(zensor.last_image.astype('uint8')).save(io, 'png')
        self.write(io.getvalue())

### HTTP IMAGE RECEPTION ###
CHECK_PASSWORD = False

@route(r"/device/([^/]+)/upload")
class ZensorUploadHandler(tornado.web.RequestHandler):
    def post(self, devid):
        import time
        import json

        device = ZensorDevice.get_device(devid)
        if device is None:
            self.send_error(404)
            return
        if CHECK_PASSWORD:
            password = self.get_body_argument("password")
            if password != device.password:
                self.send_error(401)
                return

#       XXX client does not currently use the file API :P
#         file = self.request.files['image'][0]
#         filedata = file['body']
        filedata = self.request.arguments['image'][0]

        worker_dispatcher.handle_image(device, filedata, time.time())
        self.set_header("Content-Type", "application/json")
        self.write(json.dumps({'status': 'success'}))

def run_http_server(dispatcher, port=8080):
    global worker_dispatcher
    worker_dispatcher = dispatcher

    app = tornado.web.Application(HANDLERS)
    app.listen(port)
    tornado.ioloop.IOLoop.instance().start()

if __name__ == "__main__":
    run_http_server(None)
