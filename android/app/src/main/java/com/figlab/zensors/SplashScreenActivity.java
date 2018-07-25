package com.figlab.zensors;

import android.content.Intent;
import android.os.Bundle;
import android.support.v7.app.ActionBarActivity;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.animation.Animation;
import android.view.animation.AnimationUtils;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import butterknife.ButterKnife;
import butterknife.InjectView;
import butterknife.OnClick;


public class SplashScreenActivity extends ActionBarActivity {

    @InjectView(R.id.editTextEmailAddress) EditText emailAddress;
    @InjectView(R.id.editTextPassword) EditText password;
    @InjectView(R.id.appName) ImageView appName;

    @OnClick(R.id.button_sign_in)
    public void onClick(View v) {
        if(authenticate(emailAddress.getText().toString(), password.getText().toString())) {

            SplashScreenActivity.this.finish();
            SplashScreenActivity.this.startActivity(
                    new Intent(SplashScreenActivity.this, MainCameraActivity.class));
        }
        else
            Toast.makeText(SplashScreenActivity.this,
                    "Authentication failed, please try again", Toast.LENGTH_SHORT).show();
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_splash_screen);

        ButterKnife.inject(this);

        Animation animation = AnimationUtils.loadAnimation(this, R.anim.splash_logo_animator);
        appName.startAnimation(animation);
    }

    private boolean authenticate(String text, String text1) {
        //TODO: Implement
        return true;
    }


    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        // Inflate the menu; this adds items to the action bar if it is present.
        getMenuInflater().inflate(R.menu.menu_camera_activity, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle action bar item clicks here. The action bar will
        // automatically handle clicks on the Home/Up button, so long
        // as you specify a parent activity in AndroidManifest.xml.
        int id = item.getItemId();

        //noinspection SimplifiableIfStatement
        if (id == R.id.action_settings) {
            return true;
        }

        return super.onOptionsItemSelected(item);
    }
}
