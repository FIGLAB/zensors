package com.figlab.zensors;


import android.app.Activity;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.RelativeLayout;
import android.widget.SeekBar;
import android.widget.TextView;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.JsonObjectRequest;
import com.androidplot.xy.SimpleXYSeries;
import com.androidplot.xy.XYPlot;
import com.figlab.zensors.util.Constants;
import com.figlab.zensors.util.Utils;
import com.google.gson.JsonObject;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.Arrays;

import butterknife.ButterKnife;
import butterknife.InjectView;
import butterknife.OnClick;


/**
 * A simple {@link Fragment} subclass.
 * Use the {@link ZensorsDetailsFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class ZensorsDetailsFragment extends Fragment {
    private static final String ZENSOR_PARAM = "paramZENSOR";

    @InjectView(R.id.details_frequency_text_labels)
    LinearLayout detailsFrequencyLabels;
    @InjectView(R.id.details_frequency_cost_labels) LinearLayout detailsFrequencyCostLabels;
    @InjectView(R.id.details_frequency_seek_bar)
    SeekBar detailsFrequencySeekBar;
    @InjectView(R.id.details_frequency_selected_cost)
    TextView detailsFrequencySelectedCost;
    @InjectView(R.id.details_frequency_selected_label) TextView detailsFrequencySelectedLabel;

    @InjectView(R.id.zensor_details_relativeLayout)
    RelativeLayout detailsRelativeLayout;
    @InjectView(R.id.details_question_text) TextView detailsQuestionText;
    @InjectView(R.id.details_question_type) TextView detailsQuestionType;
    @InjectView(R.id.details_xyplot_zensor_history)
    XYPlot detailsPlot;
    private Zensor mZensor;
    private SimpleXYSeries detailsSeries;
    private ZensorDetailsListener mListener;
//    private Zensor.DataListener dl;
    private Zensor.DataListener mDataListener;

    @OnClick(R.id.button_details_back)
    public void detailsBack(View v){
        mListener.finishFragment();
    }

    @OnClick(R.id.button_details_delete)
    public void detailsDelete(View v){
        mListener.deleteZensor(mZensor.id);
        mListener.finishFragment();
    }

    /**
     * Use this factory method to create a new instance of
     * this fragment using the provided parameters.
     *
     * @return A new instance of fragment ZensorsDetailsFragment.
     */
    // TODO: Rename and change types and number of parameters
    public static ZensorsDetailsFragment newInstance(Zensor z) {
        ZensorsDetailsFragment fragment = new ZensorsDetailsFragment();
        Bundle args = new Bundle();

        args.putSerializable(ZENSOR_PARAM, z);

        fragment.setArguments(args);
        return fragment;
    }

    public ZensorsDetailsFragment() {
        // Required empty public constructor
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        if (getArguments() != null) {
            mZensor = (Zensor)getArguments().getSerializable(ZENSOR_PARAM);
        }
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_zensors_details, container, false);
        ButterKnife.inject(this, view);

        prepareDetailsView();



        return view;
    }

    public void prepareDetailsView(){
        detailsQuestionText.setText(mZensor.question);
        detailsQuestionType.setText(mZensor.type.Label());

        Utils.setupFrequencySlider(getActivity(), detailsFrequencyLabels, detailsFrequencyCostLabels, detailsFrequencySeekBar, detailsFrequencySelectedLabel, detailsFrequencySelectedCost,
                new SeekBar.OnSeekBarChangeListener() {
                    @Override
                    public void onProgressChanged(SeekBar seekBar, int progress, boolean fromUser) {
                        Zensor.Frequency freq = Zensor.Frequency.values()[progress];
                        detailsFrequencySelectedLabel.setText(freq.TextLabel());
                        detailsFrequencySelectedCost.setText("("+freq.Cost()+")");

                        if (fromUser) {
                            mZensor.frequency = freq;
                            mListener.updateZensor(mZensor.id, freq, updateFrequency(mZensor.id, freq));
                        }
                    }

                    @Override
                    public void onStartTrackingTouch(SeekBar seekBar) {}

                    @Override
                    public void onStopTrackingTouch(SeekBar seekBar) {}
                });
        detailsFrequencySeekBar.setProgress(Arrays.asList(Zensor.Frequency.values()).indexOf(mZensor.frequency));

        detailsSeries = Utils.setupPlot(getActivity(), detailsPlot, mZensor);

        mDataListener = mZensor.newDataListener(detailsSeries, detailsPlot);
    }

    @Override
    public void onResume() {
        Utils.drawGraphFromHistory(mZensor, mDataListener);
        super.onResume();
    }

    private JsonObjectRequest updateFrequency(String id, Zensor.Frequency freq){
        JsonObject req = new JsonObject();
        req.addProperty("frequency", freq.Duration());
        String json = req.toString();

        JsonObjectRequest jsObjRequest = new JsonObjectRequest
                (Request.Method.POST,
                        Constants.URL_UPDATE_ZENSOR +"?device_id="+Utils.getAndroidID(getActivity())+"&sensor_id="+id,
                        json,
                        new Response.Listener<JSONObject>() {

                            @Override
                            public void onResponse(JSONObject response) {
                                try {

                                    String result = response.getString("result");

                                    if(result.equals(Constants.SUCCESS)) {
//                                mNewZensor.id = response.getString("sensor_id");
                                        Utils.debug(getActivity(), "Zensor has been updated on Server");
                                    }
                                    else {
                                        String error = response.has("error")? response.getString("error"): "unknown error";
                                        Log.e(Constants.TAG, error);
                                        Utils.debug(getActivity(), "Error: " + error);
                                    }
                                } catch (JSONException e) {
                                    String msg = e.getLocalizedMessage();
                                    if(msg == null)  msg = "";
                                    Log.e(Constants.TAG, msg);
                                    Utils.debug(getActivity(), "Error: " + msg);
                                }
                            }
                        }, new Response.ErrorListener() {

                    @Override
                    public void onErrorResponse(VolleyError e) {
                        String msg = e.getLocalizedMessage();
                        if(msg == null)  msg = "";
                        Log.e(Constants.TAG, msg);
                        Utils.debug(getActivity(), msg);
                    }
                });

        return jsObjRequest;
    }

    public void updatePlot(String id, int data){
        if(mZensor.id.equals(id)){
            detailsSeries.removeFirst();
            detailsSeries.addLast(null, data);
            detailsPlot.redraw();
        }
    }

    @Override
    public void onAttach(Activity activity) {
        super.onAttach(activity);

        try {
            mListener = (ZensorDetailsListener) activity;
        } catch (ClassCastException e) {
            throw new ClassCastException(activity.toString()
                    + " must implement OnFragmentInteractionListener");
        }
    }

    @Override
    public void onDetach() {
        super.onDetach();


        mListener = null;
    }

    @Override
    public void onDestroyView() {

        mZensor.unRegisterListener(mDataListener);
        super.onDestroyView();
    }

    public interface ZensorDetailsListener {

        void updateZensor(String id, Zensor.Frequency freq, JsonObjectRequest jsonObjectRequest);

        void finishFragment();

        void deleteZensor(String id);
    }
}
