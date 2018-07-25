''' Main entry point for the backend '''

from WorkerDispatcher import WorkerDispatcher
from WebFrontEnd import WebFrontEnd, ImageNotifier

from FTPImageReceiver import run_ftp_server
from HTTPImageReceiver import run_http_server

import threading
import time

from ZensorDevice import ZensorDevice

import log
logger = log.getLogger('main')

def parse_args(argv):
    import argparse
    parser = argparse.ArgumentParser(description="Backend image processor")
    parser.add_argument('--ftp-port', help="Port for the FTP image receiver",
        type=int, default=2121)
    parser.add_argument('--http-port', help="Port for the HTTP image receiver and frontend callback server",
        type=int, default=8080)
    parser.add_argument('--ip', help="Public-facing IP address for this computer (default: autodetect)",
        default=None)
    parser.add_argument('--url', help="URL for the web front end API",
        default='http://zensors.gierad.com')
    parser.add_argument('--notify-url', help="URL to notify about new images",
        default='http://zensors.gierad.com:8008')

    args = parser.parse_args(argv)
    return args

def main(argv):
    args = parse_args(argv)

    frontend = WebFrontEnd(args.url)
    ZensorDevice.database = frontend

    notifier = ImageNotifier(args.notify_url, args.ip, args.http_port)
    logger.info("Starting dispatcher")
    dispatcher = WorkerDispatcher(notifier)

    logger.info("Starting FTP server")
    ftpthread = threading.Thread(target=run_ftp_server, args=(dispatcher, args.ftp_port))
    ftpthread.daemon = True
    ftpthread.start()

    logger.info("Starting HTTP server")
    httpthread = threading.Thread(target=run_http_server, args=(dispatcher, args.http_port))
    httpthread.daemon = True
    httpthread.start()

    logger.info("Registering with frontend")
    frontend.register_server(args.ip, args.http_port)

    # Wait for Ctrl+C
    logger.info("Ready")
    while True:
        time.sleep(1)

if __name__ == '__main__':
    import sys
    exit(main(sys.argv[1:]))
