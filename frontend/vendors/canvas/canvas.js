// Setup
var isCurrentlyDrawing = false;
var strokeColor = '#ff0000';
var fillColor = strokeColor;
var strokeIndex = 0;
var canvasLoaded = false;
var strokeData = new Array();
var viewport = new Image();
var areaPercentageThreshold = 0.33;

var ROWS = {};
var DEBUG;
function setupCanvas(viewPortImgSrc, lassoPoints) 
{
	
	// Load the Image
	viewport.src = viewPortImgSrc;

	viewport.onload = function() {
		while(!viewport.complete);

		// Setup the Canvass once the image is loaded
		var ctxt = $("#lassoPointsEditor")[0].getContext('2d');
		var ASPECT_RATIO = viewport.width / viewport.height;
		var FIXED_WIDTH = viewport.width;
		var FIXED_HEIGHT = FIXED_WIDTH / ASPECT_RATIO;
		var PADDING = 0;
		ctxt.canvas.width = FIXED_WIDTH + PADDING; // padding = 0 for now
		ctxt.canvas.height = FIXED_WIDTH / ASPECT_RATIO;
		
		// Draw Image unto the canvass
		ctxt.drawImage(viewport, 0.0, 0.0, FIXED_WIDTH, FIXED_HEIGHT);

		$("#lassoPointsEditor").mousedown(event,function(){
			isCurrentlyDrawing = true;
			clearCanvasAndRender();
			strokeData = new Array();

			ctxt.lineJoin = 'round';
	  		ctxt.lineCap = 'round';

	  		ctxt.strokeStyle = strokeColor;
			ctxt.fillStyle = fillColor;
	  		ctxt.lineWidth = 3;

	  		ctxt.beginPath();

	  		var pos = getMousePos($('#lassoPointsEditor')[0],event);
	  		ctxt.moveTo(pos.X, pos.Y);

	  		var dataset = [pos.X, pos.Y];
	  		strokeData = Array();
	  		strokeData.push(dataset);

		});

		$("#lassoPointsEditor").mousemove(event,function() {
			if (isCurrentlyDrawing) {

				var pos = getMousePos($('#lassoPointsEditor')[0],event);
				ctxt.lineTo(pos.X, pos.Y);
				ctxt.stroke();	
				strokeData.push([pos.X, pos.Y]);
			}
			$(this).css('cursor', 'url(images/pencil.png) 4 27, move');
		});


		$("#lassoPointsEditor").mouseup(event,function() {
			ctxt.fill();
			ctxt.closePath();
			
			var xVals = [];
			var yVals = [];
			
			for (var i=0; i<strokeData.length; i++)
			{
			 	var point = [parseInt(strokeData[i][0]), parseInt(strokeData[i][1])];
			 	xVals.push(point[0]);
				yVals.push(point[1]);
			}
			
			xVals = xVals.sort(sortNumber);
			yVals = yVals.sort(sortNumber);
			DEBUG = {xVals: xVals, yVals: yVals};
			var xMinMax = [xVals[0], xVals.splice(-1)[0]]; // [min,max]
			var yMinMax = [yVals[0], yVals.splice(-1)[0]];
			var rect = {x: xMinMax[0], y:yMinMax[0], width: Math.abs(xMinMax[1]-xMinMax[0]), height: Math.abs(yMinMax[1]-yMinMax[0])};
			
			var imgd = ctxt.getImageData(rect.x, rect.y, rect.width, rect.height);
			var num_pixels = findPixelsWithRGBColor(imgd,255,0,0);
			
			// Reset and Re-draw Lasso Points
			clearCanvasAndRender();
	  		ctxt.beginPath();
	  		ctxt.moveTo(strokeData[0][0], strokeData[0][1]);
			for (var i=0; i<strokeData.length; i++)
			{
			 	var point = [parseInt(strokeData[i][0]), parseInt(strokeData[i][1])];
				ctxt.lineTo(point[0], point[1]);
				ctxt.stroke();
			}
			
			if (num_pixels/(FIXED_WIDTH*FIXED_HEIGHT)>areaPercentageThreshold) {
				alert("This area is too large. Please select a smaller region.");
				clearCanvasAndRender();
			}
			
			isCurrentlyDrawing = false;
		});

		canvasLoaded = true;
		$('#canvas_preloader').remove();

		renderResult(lassoPoints);

	};

	strokeData = lassoPoints;
	
}

function sortNumber(a,b) {
    return a - b;
}

function findPixelsWithRGBColor(canvasImageData, rColor, gColor, bColor)
{
	// Get the CanvasPixelArray from the given coordinates and dimensions.
	var imgd = canvasImageData;
	var pix = imgd.data;

	// Loop over each pixel and invert the color.
	var num_pixels = 0;
	for (var i = 0, n = pix.length; i < n; i += 4) {
		// i+3 is alpha (the fourth element)
	    var R =  pix[i  ]; // red
	    var G =  pix[i+1]; // green
	    var B =  pix[i+2]; // blue
	 	if (R==rColor && G==gColor && B==bColor) {
	 		num_pixels += 1;
	 	}
	}
	return num_pixels;
}


function clearCanvasAndRender()
{
	// Setup the Canvass
	var ctxt = $("#lassoPointsEditor")[0].getContext('2d');
	var ASPECT_RATIO = viewport.width / viewport.height;
	var FIXED_WIDTH = viewport.width;
	var FIXED_HEIGHT = FIXED_WIDTH / ASPECT_RATIO;
	var PADDING = 0;

	ctxt.clearRect(0, 0, ctxt.canvas.width, ctxt.canvas.height);
	ctxt.canvas.width = FIXED_WIDTH + PADDING;
	ctxt.canvas.height = FIXED_WIDTH / ASPECT_RATIO;

	// Draw Image unto the canvass
	ctxt.drawImage(viewport, 0.0, 0.0, FIXED_WIDTH, FIXED_HEIGHT);

	ctxt.lineWidth = 3;
	ctxt.lineJoin = 'round';
  	ctxt.lineCap = 'round';

	ctxt.beginPath();
	ctxt.strokeStyle = strokeColor;
}


function renderResult(lassoPoints)
{
	// Clear all annotation data and text input
	var ctxt = $("#lassoPointsEditor")[0].getContext('2d');

	ctxt.lineWidth = 3;
	ctxt.lineJoin = 'round';
  	ctxt.lineCap = 'round';
	ctxt.beginPath();
	ctxt.strokeStyle = strokeColor;
	
	if (lassoPoints.length<=0) return;

	for (var j=0; j < lassoPoints.length; j++)
	{
		
		if (j==0) {ctxt.moveTo(lassoPoints[j][0], lassoPoints[j][1]);}
		if (j!=0) {ctxt.lineTo(lassoPoints[j][0], lassoPoints[j][1]); ctxt.stroke();}
	}
	ctxt.lineTo(lassoPoints[0][0], lassoPoints[0][1]); ctxt.stroke();
	ctxt.closePath();
}


function getMousePos(canvas, evt) {
	var rect = canvas.getBoundingClientRect(), root = document.documentElement;

	// return relative mouse position
	var mouseX = evt.clientX - rect.left - root.scrollLeft;
	var mouseY = evt.clientY - rect.top - root.scrollTop;
	return {
	  X: mouseX,
	  Y: mouseY
	};
}

function capitalize(str)
{
	return str.charAt(0).toUpperCase() + str.slice(1)
}
