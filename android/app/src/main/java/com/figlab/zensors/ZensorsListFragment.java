package com.figlab.zensors;


import android.app.Activity;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.support.v7.app.ActionBarActivity;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import com.androidplot.xy.XYPlot;
import com.figlab.zensors.util.Utils;

import java.util.HashMap;
import java.util.Map;

import butterknife.ButterKnife;
import butterknife.InjectView;


/**
 * A simple {@link Fragment} subclass.
 * Use the {@link ZensorsListFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class ZensorsListFragment extends Fragment {

    @InjectView(R.id.linearlayout_active_zensors) LinearLayout activeZensorsLinearLayout;
    private ZensorListFragmentListener mListener;
    private HashMap<Zensor, Zensor.DataListener> mDataListeners = new HashMap<>();

    public static ZensorsListFragment newInstance() {
        return new ZensorsListFragment();
    }

    public ZensorsListFragment() {
        // Required empty public constructor
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        setHasOptionsMenu(true);
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_zensors_list, container, false);
        ButterKnife.inject(this, view);


        return view;
    }


    @Override
    public void onResume() {
        activeZensorsLinearLayout.removeAllViewsInLayout();

        ((ActionBarActivity)getActivity()).getSupportActionBar().show();

        setupActiveZensorsList(getActivity().getLayoutInflater());
        super.onResume();
    }

    @Override
    public void onPause() {
        for(Map.Entry<Zensor, Zensor.DataListener> dl : mDataListeners.entrySet()){
            dl.getKey().unRegisterListener(dl.getValue());
        }
        mDataListeners.clear();
        System.gc();
        super.onPause();
    }

    private void setupActiveZensorsList(LayoutInflater li){
        int i = 0;
        for(Zensor z : mListener.getActiveZensorList())
            addZensorToLayout(z, li, i++);
    }

    private View addZensorToLayout(final Zensor z, LayoutInflater inflater, int num){
        View retView = inflater.inflate(R.layout.zensor_list_item, activeZensorsLinearLayout, false);
        if(num%2==0)
            retView.setBackgroundColor(getResources().getColor(R.color.zensor_light_gray));
        else
            retView.setBackgroundColor(getResources().getColor(R.color.zensor_dark_gray));
        retView.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                mListener.showZensor(z);
            }
        });
        TextView question = (TextView)retView.findViewById(R.id.text_view_zensor_question);
        TextView type = (TextView)retView.findViewById(R.id.text_view_zensor_type);
        XYPlot plot = (XYPlot)retView.findViewById(R.id.xyplot_zensor_history);

        Zensor.DataListener dl = z.newDataListener(Utils.setupPlot(getActivity(), plot,z), plot);
        mDataListeners.put(z,dl);

        question.setText(z.question);
        type.setText(z.type.Label());

        activeZensorsLinearLayout.addView(retView);
        return retView;
    }


    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle presses on the action bar items
        switch (item.getItemId()) {
            case R.id.action_add_sensor:
                mListener.addZensor();
                return true;
            case R.id.action_settings:
                mListener.openSettings();
                return true;
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    @Override
    public void onCreateOptionsMenu(Menu menu, MenuInflater inflater) {
        // Inflate the menu items for use in the action bar
        inflater.inflate(R.menu.menu_camera_activity, menu);
//        mAddSensorButton = menu.findItem(R.id.action_add_sensor);
//        mSettingsButton = menu.findItem(R.id.action_settings);
        super.onCreateOptionsMenu(menu, inflater);
    }

    @Override
    public void onAttach(Activity activity) {
        super.onAttach(activity);


        try {
            mListener = (ZensorListFragmentListener) activity;
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
    public interface ZensorListFragmentListener {

        void addZensor();

        void showZensor(Zensor z);

        java.util.Collection<Zensor> getActiveZensorList();

        void openSettings();
    }
}
