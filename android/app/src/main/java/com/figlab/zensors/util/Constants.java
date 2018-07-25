package com.figlab.zensors.util;

import com.figlab.zensors.Zensor;

public class Constants {

    public static final boolean DEBUG = false;


    public static final String TAG = "ZENSORS";
    public static final long MILLISECONDS_PER_DAY = 60000*60*24;


    public static final int HISTORY_SIZE = 30;

    public static final double COST_PER_LABEL = 0.01;
    public static final Zensor.Frequency DEFAULT_FREQUENCY = Zensor.Frequency.ONE_MIN;
    public static final String ZENSORS_FILENAME = "zensors_cache.json";
    public static final String SHARED_PREFS_NAME = "zensorsSharedPrefs";
    public static final String INSTALLATION_ID_KEY = "uniqueInstallationID";
    public static final String URL_BASE = "http://localhost:8081/api/";
    public static final String URL_NEW_ZENSOR = URL_BASE + "newSensor";
    public static final String SUCCESS = "SUCCESS";
    public static final String URL_UPLOAD_IMAGE = "/device/";
    public static final String URL_UPLOAD = "/upload";//URL_BASE + "uploadImage";
    public static final String SOCKET_IO_URI = "http://localhost:8008/zensors";
    public static final String URL_UPDATE_ZENSOR = URL_BASE + "updateSensor";
    public static final String URL_DELETE_ZENSOR = URL_BASE+ "deleteSensor" ;

    public static final String PREF_CAMERA_PICKER = "pref_camera_picker";
    public static final String PREF_OBFUSCATION = "pref_obfuscation";
    public static final CharSequence PREF_DELETE_ALL = "pref_delete_all" ;
    public static final String URL_REGISTER_DEVICE = URL_BASE+"newDevice";

    public static final String URL_GET_BACKEND = URL_BASE+"getBackendAddress";
}
