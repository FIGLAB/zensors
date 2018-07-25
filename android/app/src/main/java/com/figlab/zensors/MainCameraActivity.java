package com.figlab.zensors;

import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Point;
import android.graphics.Rect;
import android.graphics.YuvImage;
import android.hardware.Camera;
import android.os.AsyncTask;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.v4.app.Fragment;
import android.support.v4.app.FragmentManager;
import android.support.v4.app.FragmentTransaction;
import android.support.v7.app.ActionBarActivity;
import android.text.TextUtils;
import android.util.Log;
import android.view.Display;
import android.view.WindowManager;
import android.widget.FrameLayout;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.JsonObjectRequest;
import com.android.volley.toolbox.Volley;
import com.figlab.zensors.util.Constants;
import com.figlab.zensors.util.Utils;
import com.squareup.okhttp.Headers;
import com.squareup.okhttp.MediaType;
import com.squareup.okhttp.MultipartBuilder;
import com.squareup.okhttp.OkHttpClient;
import com.squareup.okhttp.RequestBody;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.net.MalformedURLException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Timer;
import java.util.TimerTask;

import butterknife.ButterKnife;
import butterknife.InjectView;
import io.socket.IOAcknowledge;
import io.socket.IOCallback;
import io.socket.SocketIO;
import io.socket.SocketIOException;

public class MainCameraActivity extends ActionBarActivity implements
        ZensorsListFragment.ZensorListFragmentListener,
        NewSensorFragment.AddSensorFragmentListener,
        ZensorsDetailsFragment.ZensorDetailsListener,
        SettingsFragment.ZensorSettingsListener{
    /**
     * The camera that is being used
     */
    private Camera mCamera;
    private CameraPreview mPreview;

    @InjectView(R.id.camera_preview) FrameLayout preview;
    @InjectView(R.id.fragment_holder) FrameLayout fragmentHolder;

    // For triggering screen grabs to upload
    private Timer mTimer;

    private RequestQueue mRequestQueue;

    private HashMap<String, Zensor> mZensorMap = new HashMap();

    private static final MediaType MEDIA_TYPE_JPG = MediaType.parse("image/jpg");

    private final OkHttpClient client = new OkHttpClient();
    private SocketIO mSocketIO;
    private Fragment mCurrentFragment;
    private String mBackendAddress;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        setContentView(R.layout.activity_main_camera);

        ButterKnife.inject(this);


        mRequestQueue = Volley.newRequestQueue(this);

        if(!Utils.hasAndroidID(this)){
            mRequestQueue.add(registerDeviceIDRequest());
        }

        mRequestQueue.add(getBackendAddress());


        try
        {
            getSupportActionBar().getClass().getDeclaredMethod("setShowHideAnimationEnabled", boolean.class).invoke(getActionBar(), false);
        }
        catch (Exception exception)
        {
            // Too bad, the animation will be run ;(
        }

        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);


        setupSocketIO();

        ArrayList<Zensor> tmp = Utils.readZensorsFromFile(this);
        if(tmp != null) {
            for (Zensor z : tmp)
                mZensorMap.put(z.id, z);
        }


            FragmentManager fragmentManager = getSupportFragmentManager();
            FragmentTransaction fragmentTransaction = fragmentManager.beginTransaction();
            mCurrentFragment = new ZensorsListFragment();
            fragmentTransaction.add(R.id.fragment_holder, mCurrentFragment);
            fragmentTransaction.commit();
    }

    private void setupSocketIO() {
        Log.d(Constants.TAG, Utils.getAndroidID(this));

        try {
            mSocketIO = new SocketIO(Constants.SOCKET_IO_URI);
        } catch (MalformedURLException e) {
            e.printStackTrace();
        }
        mSocketIO.connect(mSocketIOCallbacks);
        mSocketIO.emit("register",Utils.getAndroidID(MainCameraActivity.this));
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        Utils.writeZensorsToFile(this, mZensorMap.values());

        if(mSocketIO != null)
            mSocketIO.disconnect();
    }

    @Override
    protected void onResume() {
        SharedPreferences prefs = PreferenceManager.getDefaultSharedPreferences(this);
        new OpenCameraTask().execute(
                Integer.parseInt(prefs.getString(Constants.PREF_CAMERA_PICKER, "0")),
                prefs.getBoolean(Constants.PREF_OBFUSCATION, false)?1:0);
        super.onResume();

        // Timer for capturing snapshots of the preview
        mTimer = new Timer();
        mTimer.schedule(new PreviewCaptureTimerTask(), 1000, 5000);
    }

    @Override
    protected void onPause() {
        mTimer.cancel();
        stopPreviewAndFreeCamera();
        super.onPause();
    }

    @Override
    public void addZensor() {
        FragmentManager fragmentManager = getSupportFragmentManager();
        NewSensorFragment fragment = new NewSensorFragment();
        fragmentManager.beginTransaction()
                .addToBackStack(null)
                .replace(R.id.fragment_holder, fragment)
                .commit();
    }

    @Override
    public void showZensor(Zensor z) {
        FragmentManager fragmentManager = getSupportFragmentManager();
        ZensorsDetailsFragment fragment = ZensorsDetailsFragment.newInstance(z);
        fragmentManager.beginTransaction()
                .addToBackStack(null)
                .replace(R.id.fragment_holder, fragment)
                .commit();
    }

    @Override
    public java.util.Collection<Zensor> getActiveZensorList() {
        return mZensorMap.values();
    }

    @Override
    public void openSettings() {
        FragmentManager fragmentManager = getSupportFragmentManager();
        Fragment fragment = new SettingsFragment();
        fragmentManager.beginTransaction()
                .addToBackStack(null)
                .replace(R.id.fragment_holder, fragment)
                .commit();
    }

    @Override
    public void saveZensor(Zensor mNewZensor, JsonObjectRequest requestForNewZensor) {
        mZensorMap.put(mNewZensor.id, mNewZensor);
        Utils.writeZensorsToFile(this, mZensorMap.values());
        mRequestQueue.add(requestForNewZensor);
        getSupportFragmentManager().popBackStack();
    }

    @Override
    public void finishFragment() {
        getSupportFragmentManager().popBackStack();
    }

    @Override
    public void deleteZensor(String id) {
        mRequestQueue.add(deleteZensor(this, id));
    }

    @Override
    public void updateZensor(String id, Zensor.Frequency freq, JsonObjectRequest jsonObjectRequest) {
        mZensorMap.get(id).frequency = freq;
        mRequestQueue.add(jsonObjectRequest);
    }

    @Override
    public void deleteAllZensors() {
        for(String s: new ArrayList<>(mZensorMap.keySet()))
            deleteZensor(s);
    }

    @Override
    public void restartActivity() {
        getSupportFragmentManager().beginTransaction().remove(mCurrentFragment).commit();
        getSupportFragmentManager().popBackStack(null, FragmentManager.POP_BACK_STACK_INCLUSIVE);
        recreate();
    }

    public JsonObjectRequest deleteZensor(final Context c, final String id){

        JsonObjectRequest jsObjRequest = new JsonObjectRequest
                (Request.Method.GET,
                        Constants.URL_DELETE_ZENSOR +"?device_id="+Utils.getAndroidID(c)+"&sensor_id="+id,
                        "",
                        new Response.Listener<JSONObject>() {

                            @Override
                            public void onResponse(JSONObject response) {
                                try {

                                    String result = response.getString("result");

                                    if(result.equals(Constants.SUCCESS)) {
                                        mZensorMap.remove(id);
                                        Utils.debug(c, "Zensor has been deleted on Server");
                                    }
                                    else {
                                        String error = response.has("error")? response.getString("error"): "unknown error";
                                        Log.e(Constants.TAG, error);
                                        Utils.debug(c, "Error: " + error);
                                    }
                                } catch (JSONException e) {
                                    String msg = e.getLocalizedMessage();
                                    if(msg == null)  msg = "";
                                    Log.e(Constants.TAG, msg);
                                    Utils.debug(c, "Error: " + msg);
                                }
                            }
                        }, new Response.ErrorListener() {

                    @Override
                    public void onErrorResponse(VolleyError e) {
                        String msg = e.getLocalizedMessage();
                        if(msg == null)  msg = "";
                        Log.e(Constants.TAG, msg);
                        Utils.debug(c, msg);
                    }
                });

        return jsObjRequest;
    }


    private class PreviewCaptureTimerTask extends TimerTask {
        @Override
        public void run() {

            if(mPreview != null)
                mPreview.triggerImageCapture(mPreviewCallback);

            //TODO: this was for testing, remove these lines
//            if(mPlot != null) {
//                mPlot.removeFirst();
//                mPlot.addLast(null, (int) (Math.random() * 10));
//                plotToRedraw.redraw();
//            }

        }
    }

    private void stopPreviewAndFreeCamera() {
        if(mPreview != null)
            mPreview.setCamera(null);

        if (mCamera != null) {
            // Call stopPreview() to stop updating the preview surface.
            mCamera.stopPreview();
            mCamera.release();
            mCamera = null;
        }
    }

    class OpenCameraTask extends AsyncTask<Integer, Void, Boolean>{
        private Camera cam;

        @Override
        protected Boolean doInBackground(Integer... params) {
            return safeCameraOpen(params[0], params[1]);
        }

        @Override
        protected void onPostExecute(Boolean aBoolean) {
            super.onPostExecute(aBoolean);

            mCamera = cam;

            // Create our Preview view and set it as the content of our activity.
            if(mPreview == null) {
                mPreview = new CameraPreview(MainCameraActivity.this, mCamera);
                preview.addView(mPreview);
                preview.invalidate();
            }
            else
                mPreview.setCamera(mCamera);
        }

        /**
         * Get Camera Instance. Don't forget to close it!
         * @return
         */
        private boolean safeCameraOpen(int id, int obfuscation) {
            boolean qOpened = false;

            try {
//                stopPreviewAndFreeCamera();
                cam = Camera.open(id);
                qOpened = (cam != null);
            } catch (Exception e) {
                Log.e(getString(R.string.app_name), "failed to open Camera");
                e.printStackTrace();
            }

            if (qOpened){
                // get Camera parameters
                Camera.Parameters params = cam.getParameters();

                cam.setDisplayOrientation(90);

                List<String> whiteBalanceModes = params.getSupportedWhiteBalance();
                if (whiteBalanceModes.contains(Camera.Parameters.WHITE_BALANCE_AUTO)) {
                    params.setWhiteBalance(Camera.Parameters.WHITE_BALANCE_AUTO);
                }

                List<String> focusModes = params.getSupportedFocusModes();
                if (focusModes.contains(Camera.Parameters.FOCUS_MODE_CONTINUOUS_PICTURE)) {
                    params.setFocusMode(Camera.Parameters.FOCUS_MODE_CONTINUOUS_PICTURE);
                }

                if(obfuscation != 0){
                    Camera.Size size = params.getPreviewSize();
                    for(Camera.Size s : params.getSupportedPreviewSizes()){
                        if((s.width < size.width) /*&& (((double)s.width))/s.height == (((double)size.width))/size.height*/)
                            size = s;
                    }
                    params.setPreviewSize(size.width, size.height);
                }
                else{
                    Display display = getWindowManager().getDefaultDisplay();
                    Point size = new Point();
                    display.getSize(size);
                    Camera.Size sizes = Utils.getOptimalPreviewSize(params.getSupportedPreviewSizes(), size.x, size.y);
                    params.setPreviewSize(sizes.width, sizes.height);
                }
                cam.setParameters(params);
            }
            return qOpened;
        }
    }

    private int bWidth = -1, bHeight;
    private int prevFormat;
    Camera.PreviewCallback mPreviewCallback = new Camera.PreviewCallback() {
        private int callbackCount = 0;
        @Override
        public void onPreviewFrame(final byte[] data, Camera camera) {



            if(bWidth == -1) {
                Camera.Parameters params = camera.getParameters();
                bWidth = params.getPreviewSize().width;
                bHeight = params.getPreviewSize().height;
                prevFormat = params.getPreviewFormat();
            }


            new Thread(new Runnable() {
                @Override
                public void run() {
                    ByteArrayOutputStream out = new ByteArrayOutputStream();



                    if(data != null) {
                        // Alter the second parameter of this to the actual format you are receiving
                        YuvImage yuv = new YuvImage(data, prevFormat, bWidth, bHeight, null);


                        // bWidth and bHeight define the size of the bitmap you wish the fill with the preview image
                        yuv.compressToJpeg(new Rect(0, 0, bWidth, bHeight), 50, out);

                        // Use the imgur image upload API as documented at https://api.imgur.com/endpoints/image
                        RequestBody requestBody = new MultipartBuilder()
                                .type(MultipartBuilder.FORM)
                                .addPart(
                                        Headers.of("Content-Disposition", "form-data; name=\"image\""),
                                        RequestBody.create(MEDIA_TYPE_JPG, out.toByteArray()))
                                .build();

                        if (mBackendAddress == null) {
                            Log.d(Constants.TAG, "Backend address is null");
                            return;
                        }

                        com.squareup.okhttp.Request request = new com.squareup.okhttp.Request.Builder()
                                .url(mBackendAddress + Constants.URL_UPLOAD_IMAGE + Utils.getAndroidID(MainCameraActivity.this) + Constants.URL_UPLOAD)
                                .post(requestBody)
                                .build();

                        com.squareup.okhttp.Response response = null;
                        try {
                            response = client.newCall(request).execute();
                        } catch (IOException e) {
                            Log.d(Constants.TAG, e.getLocalizedMessage());
                        }
                        if (response != null && !response.isSuccessful())
                            Log.d(Constants.TAG, "Unexpected code " + response);

                    }
                }
            }).start();

            }


//            MultipartJSONObjectRequest req =
//                    new MultipartJSONObjectRequest(Constants.URL_UPLOAD_IMAGE + "?device_id=" +
//                            Utils.getAndroidID(MainCameraActivity.this),
//                    new Response.ErrorListener() {
//                        @Override
//                        public void onErrorResponse(VolleyError error) {
//                            Log.d(Constants.TAG, "Upload Failed");
//                            Utils.debug(MainCameraActivity.this, "Upload Failed");
//                        }
//                    },
//                    new Response.Listener<JSONObject>() {
//                        @Override
//                        public void onResponse(JSONObject response) {
//                            Utils.debug(MainCameraActivity.this, "Upload Success");
//                        }
//                    }, out.toByteArray());
//
//            mRequestQueue.add(req);


//            Toast.makeText(MainCameraActivity.this, "" + callbackCount, Toast.LENGTH_SHORT).show();


    };

    IOCallback mSocketIOCallbacks = new IOCallback() {
        @Override
        public void onDisconnect() {}

        @Override
        public void onConnect() {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {
                    Toast.makeText(MainCameraActivity.this, "Connected", Toast.LENGTH_SHORT).show();
                }
            });
        }

        @Override
        public void onMessage(final String payload, IOAcknowledge ioAcknowledge) {
            Log.d(Constants.TAG, "Emitter was called!");
        }

        @Override
        public void onMessage(JSONObject jsonObject, IOAcknowledge ioAcknowledge) {

        }

        @Override
        public void on(String s, IOAcknowledge ioAcknowledge, final Object... objects) {
            if(s.equals("new_data")) {
                final String payload = (String)objects[0];
                Log.d(Constants.TAG, payload);

                runOnUiThread(new Runnable() {
                    @Override
                    public void run() {
//                        Utils.debug(MainCameraActivity.this, payload);
                        TextUtils.SimpleStringSplitter splitter = new TextUtils.SimpleStringSplitter(',');
                        splitter.setString(payload);
                        String id = splitter.next();
                        splitter.next();
                        int data;
                        try {
                            data = Integer.parseInt(splitter.next());
                        } catch(NumberFormatException e) {
                            e.printStackTrace();
                            return;
                        }

                        Zensor z = mZensorMap.get(id);
                        z.updateReading(data);

                    }
                });
            }
        }

        @Override
        public void onError(final SocketIOException e) {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {
                    Toast.makeText(MainCameraActivity.this, "SENSOR IO ERROR: " +
                            e.getLocalizedMessage(), Toast.LENGTH_SHORT).show();
                }
            });
        }
    };

    private JsonObjectRequest registerDeviceIDRequest() {
        return new JsonObjectRequest
                (Request.Method.POST,
                        Constants.URL_REGISTER_DEVICE + "?device_id=" + Utils.getAndroidID(this),
                        "",
                        new Response.Listener<JSONObject>() {

                            @Override
                            public void onResponse(JSONObject response) {
                                try {

                                    String result = response.getString("result");

                                    if (result.equals(Constants.SUCCESS)) {
//                                mNewZensor.id = response.getString("sensor_id");
                                        Utils.debug(MainCameraActivity.this, "Device ID added to Server");
                                    } else {
                                        String error = response.has("error") ? response.getString("error") : "unknown error";
                                        Log.e(Constants.TAG, error);
                                        Utils.debug(MainCameraActivity.this, "Error: " + error);
                                    }
                                } catch (JSONException e) {
                                    String msg = e.getLocalizedMessage();
                                    if (msg == null) msg = "";
                                    Log.e(Constants.TAG, msg);
                                    Utils.debug(MainCameraActivity.this, "Error: " + msg);
                                }
                            }
                        }, new Response.ErrorListener() {

                    @Override
                    public void onErrorResponse(VolleyError e) {
                        String msg = e.getLocalizedMessage();
                        if (msg == null) msg = "";
                        Log.e(Constants.TAG, msg);
                        Utils.debug(MainCameraActivity.this, msg);
                    }
                });

    }

    private JsonObjectRequest getBackendAddress() {
        return new JsonObjectRequest
                (Request.Method.POST,
                        Constants.URL_GET_BACKEND,
                        "",
                        new Response.Listener<JSONObject>() {

                            @Override
                            public void onResponse(JSONObject response) {
                                try {
                                        mBackendAddress = "http://" + response.getString("ip");
                                        mBackendAddress += ":" + response.getInt("port");
                                        Utils.debug(MainCameraActivity.this, "Retrieved server address");
                                } catch (JSONException e) {
                                    String msg = e.getLocalizedMessage();
                                    if (msg == null) msg = "";
                                    Log.e(Constants.TAG, msg);
                                    Utils.debug(MainCameraActivity.this, "Error: " + msg);
                                }
                            }
                        }, new Response.ErrorListener() {

                    @Override
                    public void onErrorResponse(VolleyError e) {
                        String msg = e.getLocalizedMessage();
                        if (msg == null) msg = "";
                        Log.e(Constants.TAG, msg);
                        Utils.debug(MainCameraActivity.this, msg);
                    }
                });

    }

}
