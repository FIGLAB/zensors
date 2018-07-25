package com.figlab.zensors.util;

import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.hardware.Camera;
import android.provider.Settings;
import android.util.Log;
import android.view.Gravity;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.RelativeLayout;
import android.widget.SeekBar;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.JsonObjectRequest;
import com.androidplot.ui.LayoutManager;
import com.androidplot.xy.BoundaryMode;
import com.androidplot.xy.LineAndPointFormatter;
import com.androidplot.xy.SimpleXYSeries;
import com.androidplot.xy.XYGraphWidget;
import com.androidplot.xy.XYPlot;
import com.figlab.zensors.R;
import com.figlab.zensors.Zensor;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.List;
import java.util.UUID;

public class Utils {
    public static double round(double value, int places) {
        if (places < 0) throw new IllegalArgumentException();

        BigDecimal bd = new BigDecimal(value);
        bd = bd.setScale(places, RoundingMode.HALF_UP);
        return bd.doubleValue();
    }

    public static String calculateCost(long duration) {
        return("$" + Double.toString(
                round(Constants.MILLISECONDS_PER_DAY/duration*Constants.COST_PER_LABEL, 2))
                + "/day");
    }

    public static ArrayList<Zensor> readZensorsFromFile(Context c){
        ArrayList<Zensor> retval = new ArrayList<>();
        Gson gson = new Gson();

        FileInputStream fIn;
        try {
            fIn = c.openFileInput(Constants.ZENSORS_FILENAME);
            BufferedReader myReader = new BufferedReader(new InputStreamReader(fIn));
            retval =
                    gson.fromJson(myReader, new TypeToken<List<Zensor>>(){}.getType());
            myReader.close();
        } catch (FileNotFoundException e) {

        } catch (IOException e) {

        }
        return retval;
    }

    public static void writeZensorsToFile(Context c, Collection<Zensor> zensors){
        Gson gson = new Gson();
        FileOutputStream fOut = null;
        try {
            fOut = c.openFileOutput(Constants.ZENSORS_FILENAME, Context.MODE_PRIVATE);
            BufferedWriter myWriter = new BufferedWriter(new OutputStreamWriter(fOut));
            String json = gson.toJson(zensors);
            myWriter.write(json);
            Log.d(Constants.TAG, json);
            myWriter.close();
        } catch (FileNotFoundException e) {

        } catch (IOException e) {

        }
    }

    public static boolean hasAndroidID(Context c){
        SharedPreferences prefs =
                c.getSharedPreferences(Constants.SHARED_PREFS_NAME, Context.MODE_PRIVATE);

        String ret = prefs.getString(Constants.INSTALLATION_ID_KEY, null);

        return ret != null;
    }

    public static String getAndroidID(Context c){
        SharedPreferences prefs =
                c.getSharedPreferences(Constants.SHARED_PREFS_NAME, Context.MODE_PRIVATE);

        String ret = prefs.getString(Constants.INSTALLATION_ID_KEY, null);

        if(ret == null){
            ret = UUID.randomUUID().toString();
            prefs.edit().putString(Constants.INSTALLATION_ID_KEY, ret).commit();
        }

        return ret;
    }

    public static void debug(Context c, String s){
        if(Constants.DEBUG && c != null && s != null)
            Toast.makeText(c, s, Toast.LENGTH_SHORT).show();
    }


    public static void setupFrequencySlider(Context c, LinearLayout labels, LinearLayout costs, SeekBar seekbar,
                                      final TextView selectionLabel, final TextView selectionCost,
                                      SeekBar.OnSeekBarChangeListener listener) {
        TextView labelTv=null, priceTv=null;
        boolean isFirst = true;
        String lastLabel = "", lastPrice = "";
        for(Zensor.Frequency f : Zensor.Frequency.values()){
            labelTv = new TextView(c);
            labelTv.setLayoutParams(new LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.WRAP_CONTENT,
                    1
            ));
            labelTv.setGravity(Gravity.LEFT);
            if(isFirst) {
                labelTv.setText(f.TextLabel());
                labels.addView(labelTv);
            }
            else
                lastLabel = f.TextLabel();



            priceTv = new TextView(c);
            priceTv.setLayoutParams(new LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.WRAP_CONTENT,
                    1
            ));
            priceTv.setGravity(Gravity.LEFT);
            if(isFirst) {
                priceTv.setText(f.Cost());
                isFirst = false;
                costs.addView(priceTv);
            }
            else
                lastPrice = f.Cost();

        }

        labelTv.setText(lastLabel);
        labelTv.setGravity(Gravity.RIGHT);
        labels.addView(labelTv);

        priceTv.setText(lastPrice);
        priceTv.setGravity(Gravity.RIGHT);
        costs.addView(priceTv);

        int length = Zensor.Frequency.values().length;
        seekbar.setMax(length - 1);
        seekbar.setLayoutParams(new LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.WRAP_CONTENT,
                ViewGroup.LayoutParams.WRAP_CONTENT,
                length
        ));

        seekbar.setOnSeekBarChangeListener(listener);

        seekbar
                .setProgress(
                        Arrays.asList(Zensor.Frequency.values())
                                .indexOf(Constants.DEFAULT_FREQUENCY));



        labels.requestLayout();
        costs.requestLayout();
        seekbar.requestLayout();
    }

    public static void setupQuestionType(Context c, RadioGroup rg){
        for(Zensor.QuestionType qt : Zensor.QuestionType.values()){
            RadioButton rb = new RadioButton(c);
//            rb.setButtonTintMode();setButtonTintList();
//            rb.setButtonDrawable();
//            rb.setHighlightColor(Color.WHITE);
//            rb.setDra
            rb.setText("  " + qt.Label());
            rg.addView(rb);
        }
    }

    public static SimpleXYSeries setupPlot(Context c, XYPlot plot, Zensor z){
        plot.clear();
        SimpleXYSeries series = new SimpleXYSeries("readingsHistory");
        series.useImplicitXVals();
        XYGraphWidget gw = plot.getGraphWidget();
        plot.setDomainBoundaries(0, Constants.HISTORY_SIZE, BoundaryMode.FIXED);
        LineAndPointFormatter fmt = new LineAndPointFormatter(

                R.color.zensor_green,                   // line color
                c.getResources().getColor(R.color.zensor_pink),                   // point color
                        R.color.zensor_green,                                   // fill color (none)
                null);                           // text color
//        fmt.setVertexPaint(null);
//        fmt.setFillPaint(null);
        plot.addSeries(series, fmt);
        plot.setBackgroundColor(Color.TRANSPARENT);
        gw.setDomainGridLinePaint(null);
        gw.setDomainLabelPaint(null);
        plot.setBackgroundPaint(null);
        gw.setRangeGridLinePaint(null);
        gw.setRangeLabelPaint(null);
        gw.setBackgroundPaint(null);
        gw.setGridBackgroundPaint(null);
        gw.setDomainOriginLinePaint(null);
        gw.setRangeOriginLinePaint(null);
        gw.setPadding(0,15,0,0);
        plot.setRangeBottomMin(0);
        plot.setRangeTopMin(1);
//        gw.setMarginTop(4);
        plot.setBorderPaint(null);
        plot.getLegendWidget().setBackgroundPaint(null);
        plot.getLegendWidget().setTextPaint(null);

        LayoutManager lm = plot.getLayoutManager();
        lm.remove(plot.getDomainLabelWidget());
        lm.remove(plot.getLegendWidget());
        lm.remove(plot.getRangeLabelWidget());

        drawGraphFromHistory(z,series,plot);

        return series;
    }

    public static void drawGraphFromHistory(Zensor z, Zensor.DataListener dl){
        drawGraphFromHistory(z, dl.series, dl.plot);
    }

    public static void drawGraphFromHistory(Zensor z, SimpleXYSeries series, XYPlot plot){
        int n = series.size();
        for(int i = 0; i<n; i++)
            series.removeFirst();

//        int minReading = Integer.MAX_VALUE;
        for(int i : z.readings){
//            minReading = Math.min(i, minReading);
            series.addLast(null, i);
        }

        for(int i = z.readings.size(); i < Constants.HISTORY_SIZE; i++)
            series.addFirst(null, 0);


        plot.redraw();
    }


    public static Camera.Size getOptimalPreviewSize(List<Camera.Size> sizes, int w, int h) {
        final double ASPECT_TOLERANCE = 0.1;
        double targetRatio=(double)h / w;

        if (sizes == null) return null;

        Camera.Size optimalSize = null;
        double minDiff = Double.MAX_VALUE;

        int targetHeight = h;

        for (Camera.Size size : sizes) {
            double ratio = (double) size.width / size.height;
            if (Math.abs(ratio - targetRatio) > ASPECT_TOLERANCE) continue;
            if (Math.abs(size.height - targetHeight) < minDiff) {
                optimalSize = size;
                minDiff = Math.abs(size.height - targetHeight);
            }
        }

        if (optimalSize == null) {
            minDiff = Double.MAX_VALUE;
            for (Camera.Size size : sizes) {
                if (Math.abs(size.height - targetHeight) < minDiff) {
                    optimalSize = size;
                    minDiff = Math.abs(size.height - targetHeight);
                }
            }
        }
        return optimalSize;
    }

}
