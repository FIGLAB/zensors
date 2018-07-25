package com.figlab.zensors;


import android.app.Activity;
import android.content.SharedPreferences;
import android.hardware.Camera;
import android.os.Bundle;
import android.preference.ListPreference;
import android.preference.Preference;
import android.support.v4.preference.PreferenceFragment;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import com.figlab.zensors.util.Constants;


@SuppressWarnings("deprecation")
public class SettingsFragment extends PreferenceFragment {


    private boolean requireRestart = false;
    private ZensorSettingsListener mListener;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Load the preferences from an XML resource
        addPreferencesFromResource(R.xml.preferences);

        ListPreference list = (ListPreference) findPreference(Constants.PREF_CAMERA_PICKER);


        Camera.CameraInfo cameraInfo = new Camera.CameraInfo();
        int cameraCount = Camera.getNumberOfCameras();

        CharSequence[] entries = new CharSequence[cameraCount];
        CharSequence[] entryValues = new CharSequence[cameraCount];

        for (int camIdx = 0; camIdx < cameraCount; camIdx++) {
            Camera.getCameraInfo(camIdx, cameraInfo);
            entries[camIdx] = cameraInfo.facing == Camera.CameraInfo.CAMERA_FACING_FRONT ? "Front Camera" : "Back Camera";
            entryValues[camIdx] = ""+camIdx;
            }

        list.setEntries(entries);
        list.setEntryValues(entryValues);



    }

    @Override
    public View onCreateView(LayoutInflater paramLayoutInflater, ViewGroup paramViewGroup, Bundle paramBundle) {
        View v = super.onCreateView(paramLayoutInflater, paramViewGroup, paramBundle);
        v.setBackgroundResource(R.color.zensor_purple);

        SharedPreferences.OnSharedPreferenceChangeListener listener =
                new SharedPreferences.OnSharedPreferenceChangeListener() {
                    public void onSharedPreferenceChanged(SharedPreferences prefs, String key) {
                        switch(key){
                            case Constants.PREF_CAMERA_PICKER:
                            case Constants.PREF_OBFUSCATION:
                                requireRestart = true;
                                break;
                            default:
                        }
                    }
                };
        getPreferenceScreen().getSharedPreferences().registerOnSharedPreferenceChangeListener(listener);

        findPreference(Constants.PREF_DELETE_ALL).setOnPreferenceClickListener(new Preference.OnPreferenceClickListener() {
            @Override
            public boolean onPreferenceClick(Preference preference) {
                mListener.deleteAllZensors();
                return true;
            }
        });

        return v;
    }

    @Override
    public void onAttach(Activity activity) {
        super.onAttach(activity);
        try {
            mListener = (ZensorSettingsListener) activity;
        } catch (ClassCastException e) {
            throw new ClassCastException(activity.toString()
                    + " must implement OnFragmentInteractionListener");
        }
    }

    @Override
    public void onDestroyView() {
        if(requireRestart) {
            requireRestart = false;
            mListener.restartActivity();
        }
        super.onDestroyView();
    }

    @Override
    public void onDetach() {
        super.onDetach();
        mListener = null;
    }

    public interface ZensorSettingsListener {
        void deleteAllZensors();

        void restartActivity();
    }
}
