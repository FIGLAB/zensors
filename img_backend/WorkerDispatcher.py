import log
logger = log.getLogger('WorkerDispatcher', log.DEBUG)

import Queue
import threading
import multiprocessing # for cpu_count

import cStringIO
import PIL.Image as Image


StopThreadCommand = object()

image_callback = None

def cmd_device(cmd, workqueue, device, imgdata, timestamp):
    logger.debug("Processing new image for device %s", device.device_id)
    img = Image.open(cStringIO.StringIO(imgdata))
    # XXX rotate for live demo XXX
    img = img.transpose(Image.ROTATE_270)
    device.latest_image = img

    for zensor in device.zensors.values():
        if zensor.last_timestamp and timestamp - zensor.last_timestamp < zensor.frequency * 0.9:
            logger.debug("Zensor ID %s skipping image", zensor.zensor_id)
            continue

        zensor.last_timestamp = timestamp
        workqueue.put((cmd_zensor, zensor, img, timestamp))

def cmd_zensor(cmd, zensor, img, timestamp):
    zensor.process_image(img, timestamp)
    if image_callback:
        image_callback.notify_zensor_image(zensor, img, timestamp)

def worker_thread(q):
    while True:
        item = q.get()
        cmd = item[0]
        if cmd is StopThreadCommand:
            break
        else:
            try:
                cmd(*item)
            except Exception as e:
                logger.exception("worker error while processing %s", cmd)

class WorkerDispatcher(object):
    def __init__(self, callback):
        global image_callback
        image_callback = callback

        self.threadpool = []
        self.workqueue = Queue.Queue()
        for i in xrange(multiprocessing.cpu_count()):
            t = threading.Thread(target=worker_thread, args=(self.workqueue,))
            t.daemon = True
            t.start()
            self.threadpool.append(t)

    def handle_image(self, device, imgdata, timestamp):
        self.workqueue.put((cmd_device, self.workqueue, device, imgdata, timestamp))
