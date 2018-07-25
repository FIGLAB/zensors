package com.figlab.zensors;

import android.graphics.PointF;

import com.androidplot.xy.SimpleXYSeries;
import com.androidplot.xy.XYPlot;
import com.androidplot.xy.XYSeries;
import com.figlab.zensors.util.Utils;
import com.google.gson.Gson;
import com.google.gson.TypeAdapter;
import com.google.gson.TypeAdapterFactory;
import com.google.gson.annotations.SerializedName;
import com.google.gson.reflect.TypeToken;
import com.google.gson.stream.JsonReader;
import com.google.gson.stream.JsonToken;
import com.google.gson.stream.JsonWriter;

import java.io.IOException;
import java.io.Serializable;
import java.util.ArrayList;
import java.util.HashMap;

/**
 * Created by jwwiese + glaput on 3/28/15.
 */


public class Zensor implements Serializable {
    public Zensor() {

        readings = new ArrayList();
        //TODO: This is temporary so that we can test the VIZ
//        int i = 10;
//        while(i-- >0)
//            readings.add((int)(Math.random()*10));
        //END TODO
    }

    public void updateReading(int data) {
        if (readings.size() >= 30)
            readings.remove(0);
        readings.add(data);
        for(DataListener l : mListeners)
            l.update(data);
    }

    public DataListener newDataListener(SimpleXYSeries simpleXYSeries, XYPlot plot) {
        DataListener dl = new DataListener(simpleXYSeries, plot);
        registerListener(dl);
        return dl;
    }

    public class DataListener{
        public SimpleXYSeries series;
        public XYPlot plot;

        public DataListener(SimpleXYSeries series, XYPlot plot){
            this.series = series;
            this.plot = plot;
        }

        public void update(int data){
            series.removeFirst();
            series.addLast(null, data);
            plot.redraw();
        }
    }

    public void registerListener(DataListener l){
        mListeners.add(l);
    }

    public void unRegisterListener(DataListener l){
        mListeners.remove(l);
    }


    public enum Frequency {
//        ONE_SEC(1, "1 Sec", "$10/day"),

        FIVE_SEC(5, "5 Seconds", "$5/day"),

        THIRTY_SEC(30, "30 Seconds", "$1/day"),

        ONE_MIN(60, "1 Minute", "Free"),

        TEN_MIN(600, "10 Minutes", "Free"),

        THIRTY_MIN(1800, "30 Minutes", "Free"),


        ONE_HOUR(3600, "1 Hour", "Free");

        // number of seconds
        private final int duration;
        private final String textLabel;
        private final String cost;

        Frequency(int duration, String textLabel){
            this(duration, textLabel, Utils.calculateCost(duration));
        }

        Frequency(int duration, String textLabel, String cost){
            this.duration = duration;
            this.textLabel = textLabel;
            this.cost = cost;
        }

        public int Duration() {return duration;}
        public String TextLabel() {return textLabel;}
        public String Cost(){return cost;}
    }

    public enum QuestionType {
        YES_NO("Yes / No"),
        COUNT("Count"),
        SCALE("Scale"),
        MULTIPLE_CHOICE("Multiple Choice");

        private final String label;

        QuestionType(String label){
            this.label = label;
        }

        public String Label(){return label;}
    }

    public String id;
    public String question;
    public String name;
    public QuestionType type;
    public Frequency frequency;
    public ArrayList<PointF> points;
    public boolean active=true;

    public transient ArrayList<Integer> readings;

//    public transient SimpleXYSeries series;
//    public transient XYPlot plot;

    public transient ArrayList<DataListener> mListeners = new ArrayList<>();

    public static class FrequencyTypeAdapter extends TypeAdapter<Frequency> {

        public void write(JsonWriter out, Frequency value) throws IOException {
            if (value == null) {
                out.nullValue();
                return;
            }
            Frequency freq = value;
            // Here write what you want to the JsonWriter.
            out.value(freq.duration);
        }

        public Frequency read(JsonReader in) throws IOException {
            if (in.peek() == JsonToken.NULL) {
                in.nextNull();
                return null;
            }

            int dur = in.nextInt();

            for(Frequency f: Zensor.Frequency.values())
                if(f.duration >= dur)
                    return f;

            return null;
        }
    }

}
