package com.figlab.zensors;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.pm.ResolveInfo;
import android.graphics.Color;
import android.graphics.PointF;
import android.os.Bundle;
import android.speech.RecognizerIntent;
import android.support.v4.app.Fragment;
import android.support.v7.app.ActionBarActivity;
import android.text.Editable;
import android.text.TextWatcher;
import android.util.Log;
import android.view.KeyEvent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.inputmethod.EditorInfo;
import android.view.inputmethod.InputMethodManager;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.LinearLayout;
import android.widget.RadioGroup;
import android.widget.RelativeLayout;
import android.widget.SeekBar;
import android.widget.TextView;
import android.widget.Toast;
import android.widget.ViewFlipper;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.JsonObjectRequest;
import com.figlab.zensors.util.Constants;
import com.figlab.zensors.util.Utils;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.UUID;

import butterknife.ButterKnife;
import butterknife.InjectView;
import butterknife.OnClick;


/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link com.figlab.zensors.NewSensorFragment.AddSensorFragmentListener} interface
 * to handle interaction events.
 * Use the {@link NewSensorFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class NewSensorFragment extends Fragment {

    private static final int REQUEST_CODE = 1234;


    @InjectView(R.id.speech_button)
    ImageButton speechButton;
    @InjectView(R.id.questionEditText)
    EditText questionEditText;
    @InjectView(R.id.drawingLayout)
    RelativeLayout drawingLayout;
    @InjectView(R.id.drawingView) DrawingView drawingView;
    @InjectView(R.id.viewFlipper)
    ViewFlipper viewFlipper;
    @InjectView(R.id.question_type_radio_group)
    RadioGroup qTypeRadioGroup;


    @InjectView(R.id.button_draw_cancel)
    Button cancelDrawButton;
    @InjectView(R.id.button_draw_clear) Button clearDrawButton;
    @InjectView(R.id.button_draw_next) Button nextDrawButton;


    @InjectView(R.id.button_next_question_text) Button nextQuestionTextButton;

    @InjectView(R.id.frequency_text_labels)
    LinearLayout frequencyLabels;
    @InjectView(R.id.frequency_cost_labels) LinearLayout frequencyCostLabels;
    @InjectView(R.id.frequency_seek_bar)
    SeekBar frequencySeekBar;
    @InjectView(R.id.frequency_selected_cost)
    TextView frequencySelectedCost;
    @InjectView(R.id.frequency_selected_label) TextView frequencySelectedLabel;


    private Zensor mNewZensor;

    private Gson gson;


    @OnClick(R.id.button_draw_next)
    public void viewFlipperNextDraw(View view){
        viewFlipper.setBackgroundColor(getResources().getColor(R.color.zensor_purple));
        viewFlipper.showNext();
    }
    @OnClick(R.id.question_type_next)
    public void viewFlipperNextType(View view){
        viewFlipper.showNext();
    }
    @OnClick(R.id.button_next_question_text)
    public void viewFlipperNext(View view){
        viewFlipper.showNext();
    }

    @OnClick(R.id.button_back_question_text)
    public void viewFlipperPreviousQuestion(View view) {
        viewFlipper.showPrevious();
    }
    @OnClick(R.id.button_back_question_type)
    public void viewFlipperPreviousType(View view) {
        viewFlipper.showPrevious();
        viewFlipper.setBackgroundColor(Color.TRANSPARENT);
    }
    @OnClick(R.id.button_back_frequency)
    public void viewFlipperPrevious(View view) {
        viewFlipper.showPrevious();
    }

    @OnClick(R.id.button_done_frequency)
    public void saveZensor(){
        viewFlipper.setDisplayedChild(0);

        mNewZensor.frequency = Zensor.Frequency.values()[frequencySeekBar.getProgress()];
        mNewZensor.question = questionEditText.getText().toString();
        mNewZensor.question = Character.toUpperCase(mNewZensor.question.charAt(0)) + mNewZensor.question.substring(1);
        int radioButtonID = qTypeRadioGroup.getCheckedRadioButtonId();
        View radioButton = qTypeRadioGroup.findViewById(radioButtonID);
        mNewZensor.type = Zensor.QuestionType.values()[qTypeRadioGroup.indexOfChild(radioButton)];
        mNewZensor.id = UUID.randomUUID().toString();

        mListener.saveZensor(mNewZensor, getRequestForNewZensor());
    }

    @OnClick(R.id.button_draw_cancel)
    public void cancelDraw(){
        viewFlipper.setDisplayedChild(0);
        viewFlipper.setVisibility(View.GONE);
        mListener.finishFragment();
    }

    @OnClick(R.id.button_draw_clear)
    public void clearDraw(){
        clearDrawButton.setEnabled(false);
        nextDrawButton.setEnabled(false);
        cancelDrawButton.setEnabled(true);

        drawingView.reset();
    }

    // TODO: Rename parameter arguments, choose names that match
    // the fragment initialization parameters, e.g. ARG_ITEM_NUMBER
    private static final String ARG_PARAM1 = "param1";
    private static final String ARG_PARAM2 = "param2";

    // TODO: Rename and change types of parameters
    private String mParam1;
    private String mParam2;

    private AddSensorFragmentListener mListener;

    /**
     * Use this factory method to create a new instance of
     * this fragment using the provided parameters.
     *
     * @param param1 Parameter 1.
     * @param param2 Parameter 2.
     * @return A new instance of fragment NewSensorFragment.
     */
    // TODO: Rename and change types and number of parameters
    public static NewSensorFragment newInstance(String param1, String param2) {
        NewSensorFragment fragment = new NewSensorFragment();
        Bundle args = new Bundle();
        args.putString(ARG_PARAM1, param1);
        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    public NewSensorFragment() {
        // Required empty public constructor
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);


        gson = new GsonBuilder().registerTypeAdapter(Zensor.Frequency.class, new Zensor.FrequencyTypeAdapter()).create();
        
        if (getArguments() != null) {
            mParam1 = getArguments().getString(ARG_PARAM1);
            mParam2 = getArguments().getString(ARG_PARAM2);
        }
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_new_sensor, container, false);
        ButterKnife.inject(this, view);

        viewFlipper.setBackgroundColor(Color.TRANSPARENT);

        Utils.setupQuestionType(getActivity(), qTypeRadioGroup);
        Utils.setupFrequencySlider(getActivity(), frequencyLabels, frequencyCostLabels, frequencySeekBar, frequencySelectedLabel, frequencySelectedCost, new SeekBar.OnSeekBarChangeListener() {
            @Override
            public void onProgressChanged(SeekBar seekBar, int progress, boolean fromUser) {
                Zensor.Frequency freq = Zensor.Frequency.values()[progress];
                frequencySelectedLabel.setText(freq.TextLabel());
                frequencySelectedCost.setText(freq.Cost());
            }

            @Override
            public void onStartTrackingTouch(SeekBar seekBar) {}

            @Override
            public void onStopTrackingTouch(SeekBar seekBar) {}
        });

        drawingView.registerOnFinishedListener(new DrawingView.DrawingFinished() {
            @Override
            public void onDrawingDone(ArrayList<PointF> p) {
                Toast.makeText(getActivity(), "Hit next. Or redraw by tapping clear.",
                        Toast.LENGTH_SHORT).show();
                clearDrawButton.setEnabled(true);
                nextDrawButton.setEnabled(true);
                mNewZensor.points = p;
            }
        });

        questionEditText.setOnEditorActionListener(new TextView.OnEditorActionListener() {
            @Override
            public boolean onEditorAction(TextView v, int actionId, KeyEvent event) {
                if (actionId == EditorInfo.IME_NULL) {
                    viewFlipperNext(v);
                }
                InputMethodManager inputManager = (InputMethodManager)
                        getActivity().getSystemService(Context.INPUT_METHOD_SERVICE);

                inputManager.hideSoftInputFromWindow(v.getWindowToken(),
                        InputMethodManager.HIDE_NOT_ALWAYS);

                return true;
            }
        });

        // Disable voice button if no recognition service is present
        PackageManager pm = getActivity().getPackageManager();
        List<ResolveInfo> activities = pm.queryIntentActivities(
                new Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH), 0);
        if (activities.size() == 0)
            speechButton.setVisibility(View.GONE);
        // Inflate the layout for this fragment

        frequencySeekBar.setProgress(Arrays.asList(Zensor.Frequency.values()).indexOf(Constants.DEFAULT_FREQUENCY));
        questionEditText.setText("");
        questionEditText.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {

            }

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {

                    nextQuestionTextButton.setEnabled(questionEditText.getText().length() > 5);

//               questionEditText.setText(""+ Character.toUpperCase(s.charAt(0)) + s.subSequence(1, s.length()));



            }

            @Override
            public void afterTextChanged(Editable s) {
//                if(!Character.isUpperCase(s.charAt(0)))
//                    s.replace(0,1,""+Character.toUpperCase(s.charAt(0)) );
            }
        });
        qTypeRadioGroup.clearCheck();
        drawingView.reset();

        Toast.makeText(getActivity(), "Circle the area of interest with your finger", Toast.LENGTH_SHORT).show();
        viewFlipper.setDisplayedChild(0);
        ((ActionBarActivity)getActivity()).getSupportActionBar().hide();
        mNewZensor = new Zensor();
        viewFlipper.setVisibility(View.VISIBLE);

        clearDrawButton.setEnabled(false);
        nextDrawButton.setEnabled(false);
        cancelDrawButton.setEnabled(true);

        return view;
    }

    @Override
    public void onAttach(Activity activity) {
        super.onAttach(activity);
        try {
            mListener = (AddSensorFragmentListener) activity;
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

    /**
     * This interface must be implemented by activities that contain this
     * fragment to allow an interaction in this fragment to be communicated
     * to the activity and potentially other fragments contained in that
     * activity.
     * <p/>
     * See the Android Training lesson <a href=
     * "http://developer.android.com/training/basics/fragments/communicating.html"
     * >Communicating with Other Fragments</a> for more information.
     */
    public interface AddSensorFragmentListener {

        void saveZensor(Zensor mNewZensor, JsonObjectRequest requestForNewZensor);

        void finishFragment();
    }

    /**
     * Handle the results from the voice recognition activity.
     */
    @Override
    public void onActivityResult(int requestCode, int resultCode, Intent data)
    {
        if (requestCode == REQUEST_CODE && resultCode == getActivity().RESULT_OK)
        {
            // Populate the wordsList with the String values the recognition engine thought it heard
            ArrayList<String> matches = data.getStringArrayListExtra(
                    RecognizerIntent.EXTRA_RESULTS);
            if(!matches.isEmpty()) {
                String txt = matches.get(0);
                if(txt.lastIndexOf('?') != txt.length()-1)
                    txt += '?';
                questionEditText.setText(txt);
            }
        }
        super.onActivityResult(requestCode, resultCode, data);
    }

    @OnClick(R.id.speech_button)
    public void speechInput(View v){
        Intent intent = new Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH);
        intent.putExtra(RecognizerIntent.EXTRA_LANGUAGE_MODEL,
                RecognizerIntent.LANGUAGE_MODEL_FREE_FORM);
        intent.putExtra(RecognizerIntent.EXTRA_PROMPT, "Speak your question.");
        startActivityForResult(intent, REQUEST_CODE);
    }


    private JsonObjectRequest getRequestForNewZensor(){

        String json = gson.toJson(mNewZensor);
        Utils.debug(getActivity(), json);

        JsonObjectRequest jsObjRequest = new JsonObjectRequest
                (Request.Method.POST,
                        Constants.URL_NEW_ZENSOR +"?device_id="+Utils.getAndroidID(getActivity()),
                        json,
                        new Response.Listener<JSONObject>() {

                            @Override
                            public void onResponse(JSONObject response) {
                                try {

                                    String result = response.getString("result");

                                    if(result.equals(Constants.SUCCESS)) {
//                                mNewZensor.id = response.getString("sensor_id");
                                    }
                                    else {
                                        String error = response.has("error")? response.getString("error"): "unknown error";
                                        Log.e(Constants.TAG, error);
                                    }
                                } catch (JSONException e) {
                                    String msg = e.getLocalizedMessage();
                                    if(msg == null)  msg = "";
                                    Log.e(Constants.TAG, msg);
                                }
                                mNewZensor = null;
                            }
                        }, new Response.ErrorListener() {

                    @Override
                    public void onErrorResponse(VolleyError e) {
                        String msg = e.getLocalizedMessage();
                        if(msg == null)  msg = "";
                        Log.e(Constants.TAG, msg);
                    }
                });

        return jsObjRequest;
    }

}
