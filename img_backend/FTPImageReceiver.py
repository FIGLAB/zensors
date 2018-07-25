''' Class to receive images over FTP and send them to the WorkerDispatcher. '''

import log
logger = log.getLogger('FTPImageReceiver')

# import pyftpdlib.log
# pyftpdlib.log.LEVEL = logging.DEBUG

from pyftpdlib.authorizers import DummyAuthorizer, AuthenticationFailed
from pyftpdlib.handlers import FTPHandler
from pyftpdlib.servers import FTPServer
from pyftpdlib.filesystems import AbstractedFS, FilesystemError
from StringIO import StringIO
from ZensorDevice import ZensorDevice
import time

worker_dispatcher = None

class MyStringIO(StringIO):
    ''' Simple StringIO subclass that calls a specified notification function when closed. '''
    def __init__(self, filename, closefunc):
        StringIO.__init__(self)
        self.name = filename
        self.closefunc = closefunc

    def close(self):
        self.closefunc(self.getvalue())
        StringIO.close(self)

class VirtualUploadFS(AbstractedFS):
    ''' A virtual filesystem that allows for uploads anywhere.
    
    To prevent this from interacting with your real filesystem,
    ensure the user only has 'w' (write) and 'e' (chdir) permissions. '''

    def __init__(self, root, cmd_channel):
        self.devid = root
        AbstractedFS.__init__(self, u'/', cmd_channel)

    def ftp2fs(self, ftppath):
        return self.ftpnorm(ftppath)

    def fs2ftp(self, fspath):
        return fspath

    def validpath(self, path):
        return True


    def chdir(self, path):
        self._cwd = path

    def get_list_dir(self, path):
        return []


    def open(self, filename, mode):
        logger.debug("opening file %s", filename)
        t = time.time()
        if 'w' in mode:
            return MyStringIO(filename, lambda data: self.notify_closed(data, t))
        else:
            raise FilesystemError("File does not exist")

    def mkstemp(self, *args):
        raise FilesystemError("not supported")

    def notify_closed(self, imgdata, t):
        device = ZensorDevice.get_device(self.devid)
        if device is None:
            logger.warn("device %s not found", self.devid)
        elif not worker_dispatcher:
            logger.warn("no active dispatcher")
        else:
            worker_dispatcher.handle_image(device, imgdata, t)

class ZensorAuthorizer(DummyAuthorizer):
    def __init__(self):
        # Explicitly override the DummyAuthorizer initializer to do nothing
        pass

    def validate_authentication(self, username, password, handler):
        msg = "Authentication failed."
        device = ZensorDevice.get_device(username)
        if device is None:
            raise AuthenticationFailed(msg)
        elif device.password != password:
            raise AuthenticationFailed(msg)

    def get_home_dir(self, username):
        return username

    def has_user(self, username):
        return ZensorDevice.get_device(username) != None

    def has_perm(self, username, perm, path=None):
        return perm in self.get_perms(username)

    def get_perms(self, username):
        # Permit cwd, write file
        return 'ew'

    def get_msg_login(self, username):
        return "Welcome to the ZensorFTP service."

    def get_msg_quit(self, username):
        return "Bye"

def run_ftp_server(dispatcher, port=2121):
    global worker_dispatcher
    worker_dispatcher = dispatcher

    authorizer = ZensorAuthorizer()
    handler = FTPHandler
    handler.authorizer = authorizer
    handler.abstracted_fs = VirtualUploadFS
    server = FTPServer(("0.0.0.0", port), handler)
    server.serve_forever()

if __name__ == '__main__':
    # Run just the FTP server for testing purposes.
    run_ftp_server(None)
