"""
Logging support for zensors, inspired from pyftpdlib's
"""

import logging
from logging import INFO, DEBUG, WARN, ERROR
import sys
import time
try:
    import curses
except ImportError:
    curses = None

def _stderr_supports_color():
    color = False
    if curses is not None and sys.stderr.isatty():
        try:
            curses.setupterm()
            if curses.tigetnum("colors") > 0:
                color = True
        except Exception:
            pass
    return color

# configurable options
PREFIX = '[%(levelname)1.1s %(asctime)s %(module)s]'
COLOURED = _stderr_supports_color()
TIME_FORMAT = "%y-%m-%d %H:%M:%S"

# taken and adapted from Tornado
class LogFormatter(logging.Formatter):
    """Log formatter used in pyftpdlib.
    Key features of this formatter are:

    * Color support when logging to a terminal that supports it.
    * Timestamps on every log line.
    * Robust against str/bytes encoding problems.
    """
    def __init__(self, *args, **kwargs):
        logging.Formatter.__init__(self, *args, **kwargs)
        self._coloured = COLOURED and _stderr_supports_color()
        if self._coloured:
            curses.setupterm()
            # The curses module has some str/bytes confusion in
            # python3.  Until version 3.2.3, most methods return
            # bytes, but only accept strings.  In addition, we want to
            # output these strings with the logging module, which
            # works with unicode strings.  The explicit calls to
            # unicode() below are harmless in python2 but will do the
            # right conversion in python 3.
            fg_color = (curses.tigetstr("setaf") or curses.tigetstr("setf")
                        or "")
            if (3, 0) < sys.version_info < (3, 2, 3):
                fg_color = unicode(fg_color, "ascii")
            self._colors = {
                # blues
                logging.DEBUG: unicode(curses.tparm(fg_color, 4), "ascii"),
                # green
                logging.INFO: unicode(curses.tparm(fg_color, 2), "ascii"),
                # yellow
                logging.WARNING: unicode(curses.tparm(fg_color, 3), "ascii"),
                # red
                logging.ERROR: unicode(curses.tparm(fg_color, 1), "ascii")
            }
            self._normal = unicode(curses.tigetstr("sgr0"), "ascii")

    def format(self, record):
        try:
            record.message = record.getMessage()
        except Exception:
            err = sys.exc_info()[1]
            record.message = "Bad message (%r): %r" % (err, record.__dict__)

        record.asctime = time.strftime(TIME_FORMAT,
                                       self.converter(record.created))
        prefix = PREFIX % record.__dict__
        if self._coloured:
            prefix = (self._colors.get(record.levelno, self._normal) +
                      prefix + self._normal)

        try:
            message = unicode(record.message)
        except UnicodeDecodeError:
            message = repr(record.message)

        formatted = prefix + " " + message
        if record.exc_info:
            if not record.exc_text:
                record.exc_text = self.formatException(record.exc_info)
        if record.exc_text:
            formatted = formatted.rstrip() + "\n" + record.exc_text
        return formatted.replace("\n", "\n    ")

def getLogger(name, level=logging.INFO):
    logger = logging.getLogger(name)
    channel = logging.StreamHandler()
    channel.setFormatter(LogFormatter())
    logger.setLevel(level)
    logger.addHandler(channel)
    return logger
