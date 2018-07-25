//package com.figlab.zensors.util;
//
//import com.android.volley.AuthFailureError;
//import com.android.volley.NetworkResponse;
//import com.android.volley.Response;
//import com.android.volley.VolleyLog;
//import com.android.volley.toolbox.JsonObjectRequest;
//
//import org.apache.http.entity.mime.MultipartEntity;
//import org.apache.http.entity.mime.content.ByteArrayBody;
//import org.apache.http.entity.mime.content.FileBody;
//import org.json.JSONObject;
//
//import java.io.ByteArrayOutputStream;
//import java.io.File;
//import java.io.IOException;
//import java.io.UnsupportedEncodingException;
//
///**
// * Created by jwwiese on 4/12/15.
// */
//public class MultipartJSONObjectRequest  extends JsonObjectRequest {
//
//        private MultipartEntity entity = new MultipartEntity();
//
//        private static final String FILE_PART_NAME = "file";
//        private static final String STRING_PART_NAME = "text";
//
//        private final Response.Listener<JSONObject> mListener;
//        private final byte[] mFilePart;
//
//        public MultipartJSONObjectRequest(String url, Response.ErrorListener errorListener, Response.Listener<JSONObject> listener, byte[] file)
//        {
//            super(Method.POST, url, listener, errorListener);
//
//            mListener = listener;
//            mFilePart = file;
//            buildMultipartEntity();
//        }
//
//        private void buildMultipartEntity()
//        {
//            entity.addPart(FILE_PART_NAME, new ByteArrayBody(mFilePart, FILE_PART_NAME));
//        }
//
//        @Override
//        public String getBodyContentType()
//        {
//            return entity.getContentType().getValue();
//        }
//
//        @Override
//        public byte[] getBody()
//        {
//            ByteArrayOutputStream bos = new ByteArrayOutputStream();
//            try
//            {
//                entity.writeTo(bos);
//            }
//            catch (IOException e)
//            {
//                VolleyLog.e("IOException writing to ByteArrayOutputStream");
//            }
//            return bos.toByteArray();
//        }
//
//        @Override
//        protected Response<JSONObject> parseNetworkResponse(NetworkResponse response)
//        {
//            return super.parseNetworkResponse(response);
//        }
//
//        @Override
//        protected void deliverResponse(JSONObject response)
//        {
//            mListener.onResponse(response);
//        }
//    }
