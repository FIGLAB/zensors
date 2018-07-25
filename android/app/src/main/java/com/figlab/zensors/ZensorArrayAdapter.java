package com.figlab.zensors;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.TextView;

import com.androidplot.util.PlotStatistics;
import com.androidplot.xy.BezierLineAndPointFormatter;
import com.androidplot.xy.BoundaryMode;
import com.androidplot.xy.LineAndPointFormatter;
import com.androidplot.xy.SimpleXYSeries;
import com.androidplot.xy.XYPlot;
import com.androidplot.xy.XYSeries;

import java.util.List;

/**
 * Created by jwwiese + glaput on 4/2/15.
 */
public class ZensorArrayAdapter extends ArrayAdapter<Zensor>{

    public static final int HISTORY_SIZE = 30;

    public ZensorArrayAdapter(Context context, Zensor[] objects) {
        super(context, R.layout.zensor_list_item, objects);
    }

    @Override
    public View getView(int position, View convertView, ViewGroup parent) {
        View retView;
        if(convertView != null)
            retView = convertView;
        else{
            LayoutInflater inflater =
                    (LayoutInflater) getContext().getSystemService(Context.LAYOUT_INFLATER_SERVICE);

            retView = inflater.inflate(R.layout.zensor_list_item, parent, false);
        }

        Zensor currZensor = getItem(position);

        TextView question = (TextView)retView.findViewById(R.id.text_view_zensor_question);
        TextView type = (TextView)retView.findViewById(R.id.text_view_zensor_question);
        XYPlot plot = (XYPlot)retView.findViewById(R.id.xyplot_zensor_history);
        SimpleXYSeries series = new SimpleXYSeries("readingsHistory");
        series.useImplicitXVals();
        plot.setDomainBoundaries(0, HISTORY_SIZE, BoundaryMode.FIXED);
        plot.addSeries(series, new LineAndPointFormatter());

        //TODO customize plot

        question.setText(currZensor.question);
        type.setText(currZensor.type.Label());

        for(int i : currZensor.readings){
            series.addLast(null, i);
        }

        plot.redraw();

        return retView;
    }


}
