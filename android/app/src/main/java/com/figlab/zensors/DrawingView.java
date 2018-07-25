package com.figlab.zensors;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BlurMaskFilter;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Path;
import android.graphics.PointF;
import android.graphics.PorterDuff;
import android.graphics.PorterDuffXfermode;
import android.util.AttributeSet;
import android.view.MotionEvent;
import android.view.View;

import java.util.ArrayList;

/**
 * Created by jwwiese + glaput on 3/17/15.
 */
public class DrawingView extends View {

    private static final float MINP = 0.25f;
    private static final float MAXP = 0.75f;
    private Bitmap  mBitmap, mask;
    private Canvas  mCanvas;
    private Path    mPath;
    private Paint   mBitmapPaint;
    private Paint   mPaint, maskPaint;
    private ArrayList<PointF> mPathPoints;
    Context context;
    private DrawingFinished mFinishListener;
    private boolean lockCanvas;

    public DrawingView(Context c) {
        super(c);
        context=c;

        initView();

    }



    public DrawingView(Context context, AttributeSet attrs)
    {
        super(context, attrs);
        this.context = context;
        initView();
    }

    public DrawingView(Context context, AttributeSet attrs, int defStyle)
    {
        super(context, attrs, defStyle);
        this.context = context;
        initView();
    }

    @Override
    protected void onSizeChanged(int w, int h, int oldw, int oldh) {
        super.onSizeChanged(w, h, oldw, oldh);
//        mBitmap = null;
//        mCanvas = null;
//        System.gc();
        mBitmap = Bitmap.createBitmap(w, h, Bitmap.Config.ARGB_8888);
        mCanvas = new Canvas(mBitmap);

    }

    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);


        canvas.drawBitmap(mBitmap, 0, 0, mBitmapPaint);

        canvas.drawPath(mPath, mPaint);

        canvas.drawLine(startX, startY, mX, mY, mPaint);

        if (mask != null) {
            //may need to replace mPaint
            Paint p = new Paint();
            p.setMaskFilter(new BlurMaskFilter(500, BlurMaskFilter.Blur.NORMAL));
            canvas.drawBitmap(mask, 0f, 0f, p);
        }
    }

    public void registerOnFinishedListener(DrawingFinished fl){
        mFinishListener = fl;
    }

    private float mX, mY, startX, startY;
    private static final float TOUCH_TOLERANCE = 4;

    private void touch_start(float x, float y) {
        mPathPoints = new ArrayList<PointF>();
        mPath.reset();
        mPath.moveTo(x, y);
        mX = x;
        mY = y;

        startX = x;
        startY = y;



    }
    private void touch_move(float x, float y) {
        float dx = Math.abs(x - mX);
        float dy = Math.abs(y - mY);
        if (dx >= TOUCH_TOLERANCE || dy >= TOUCH_TOLERANCE) {
            mPath.quadTo(mX, mY, (x + mX)/2, (y + mY)/2);
            mX = x;
            mY = y;
        }
    }
    private void touch_up() {
        lockCanvas = true;
        mPath.lineTo(mX, mY);
// commit the path to our offscreen
//        mCanvas.drawPath(mPath, mPaint);
// kill this so we don't double draw
//        mPath.reset();
        mPaint.setXfermode(new PorterDuffXfermode(PorterDuff.Mode.SCREEN));
//mPaint.setMaskFilter(null);
        createMask();

        if(mFinishListener != null)
            mFinishListener.onDrawingDone(mPathPoints);

    }

    @Override
    public boolean onTouchEvent(MotionEvent event) {
        if(lockCanvas)
            return false;

        float x = event.getX();
        float y = event.getY();

        switch (event.getAction()) {
            case MotionEvent.ACTION_DOWN:
                touch_start(x, y);
                invalidate();
                break;
            case MotionEvent.ACTION_MOVE:

                touch_move(x, y);
                invalidate();
                break;
            case MotionEvent.ACTION_UP:
                touch_up();
                invalidate();
                break;
        }
        mPathPoints.add(new PointF(x/getWidth(),y/getHeight()));
        return true;
    }

    private void initView() {
        mPath = new Path();
        mBitmapPaint = new Paint(Paint.DITHER_FLAG);

        mPaint = new Paint();
        mPaint.setAntiAlias(true);
        mPaint.setDither(true);
        mPaint.setColor(Color.MAGENTA);
        mPaint.setStyle(Paint.Style.STROKE);
        mPaint.setStrokeJoin(Paint.Join.ROUND);
        mPaint.setStrokeCap(Paint.Cap.ROUND);
        mPaint.setStrokeWidth(20);

        maskPaint = new Paint();
        maskPaint.setColor(Color.TRANSPARENT);
        maskPaint.setXfermode(new PorterDuffXfermode(PorterDuff.Mode.CLEAR));
        maskPaint.setStyle(Paint.Style.FILL);

        if(mask != null) {
            mask.recycle();
            mask = null;
            invalidate();
        }

        startX = startY = mX = mY = -1;

        lockCanvas = false;
    }

    private void createMask(){

        if (mask!=null){mask.recycle();}

        mask = Bitmap.createBitmap(mBitmap.getWidth(), mBitmap.getHeight(), Bitmap.Config.ARGB_8888);
        Canvas maskCanvas = new Canvas(mask);
        maskCanvas.drawColor(R.color.sensor_mask);
        maskCanvas.drawPath(mPath, maskPaint);


    }

    public void reset(){
        initView();
    }

    public interface DrawingFinished{
        public abstract void onDrawingDone(ArrayList<PointF> p);
    }
}