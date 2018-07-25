class ZensorQuestionType(object):
    YesNo = 'YESNO'
    Number = 'NUMBER'
    Scale = 'SCALE'
    MultipleChoice = 'MULTIPLECHOICE'
    FreeText = 'FREETEXT'
    
class ZensorObfuscation(object):
    NoFilter = 'NONE'
    LightBlur = 'LIGHT_BLUR'
    HeavyBlur = 'HEAVY_BLUR'
    MedianFilter = 'MEDIAN'
    EdgeFilter = 'EDGE'

class ZensorFrequency(object):
    # All Values in Seconds
	EVERY_SECOND = 1
	EVERY_10_SECONDS = 10
	EVERY_30_SECONDS = 30
	EVERY_MINUTE = 60
	EVERY_2_MINUTES = 2*60
	EVERY_5_MINUTES = 5*60
	EVERY_10_MINUTES = 10*60
	EVERY_30_MINUTES = 30*60
	EVERY_HOUR = 60*60
	EVERY_2_HOURS = 2*60*60
	EVERY_4_HOURS = 4*60*60
	EVERY_8_HOURS = 8*60*60
	EVERY_16_HOURS = 16*60*60
	EVERY_DAY = 24*60*60
	EVERY_3_DAYS = 3*24*60*60
	EVERY_WEEK = 7*24*60*60
