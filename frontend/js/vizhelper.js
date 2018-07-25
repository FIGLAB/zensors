function freqToMs(FREQ)
{
	if (FREQ=="EVERY_SECOND") return (1);
	if (FREQ=="EVERY_10_SECONDS") return (10);
	if (FREQ=="EVERY_30_SECONDS") return (30);
	if (FREQ=="EVERY_MINUTE") return (60);
	if (FREQ=="EVERY_2_MINUTES") return (2*60);
	if (FREQ=="EVERY_5_MINUTES") return (5*60);
	if (FREQ=="EVERY_10_MINUTES") return (10*60);
	if (FREQ=="EVERY_30_MINUTES") return (30*60);
	if (FREQ=="EVERY_HOUR") return (60*60);
	if (FREQ=="EVERY_2_HOURS") return (2*60*60);
	if (FREQ=="EVERY_4_HOURS") return (4*60*60);
	if (FREQ=="EVERY_8_HOURS") return (8*60*60);
	if (FREQ=="EVERY_16_HOURS") return (16*60*60);
	if (FREQ=="EVERY_DAY") return (24*60*60);
	if (FREQ=="EVERY_3_DAYS") return (3*24*60*60);
	if (FREQ=="EVERY_WEEK") return (7*24*60*60);
}

function sortByKey(array, key) {
    return array.sort(function(a, b) {
        var x = a[key]; var y = b[key];
        return ((x < y) ? -1 : ((x > y) ? 1 : 0));
    });
}

function capitalize(str){
    return str.replace(/\w\S*/g, function(txt) {
		return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
	});
}

function monthName(m)
{
	var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
	    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
	return monthNames[parseInt(m)];
}

function generateColors(total)
{
    var i = 360 / (total - 1); // distribute the colors evenly on the hue range
    var r = []; // hold the generated colors
    for (var x=0; x<total; x++)
    {
        r.push(hsvToRgb(i * x, 100, 100)); // you can also alternate the saturation and value for even more contrast between the colors
    }
    return r;
}


function hsvToRgb(h, s, v) {
	var r, g, b;
	var i;
	var f, p, q, t;
	
	// Make sure our arguments stay in-range
	h = Math.max(0, Math.min(360, h));
	s = Math.max(0, Math.min(100, s));
	v = Math.max(0, Math.min(100, v));
	
	// We accept saturation and value arguments from 0 to 100 because that's
	// how Photoshop represents those values. Internally, however, the
	// saturation and value are calculated from a range of 0 to 1. We make
	// That conversion here.
	s /= 100;
	v /= 100;
	
	if(s == 0) {
		// Achromatic (grey)
		r = g = b = v;
		return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
	}
	
	h /= 60; // sector 0 to 5
	i = Math.floor(h);
	f = h - i; // factorial part of h
	p = v * (1 - s);
	q = v * (1 - s * f);
	t = v * (1 - s * (1 - f));

	switch(i) {
		case 0:
			r = v;
			g = t;
			b = p;
			break;
			
		case 1:
			r = q;
			g = v;
			b = p;
			break;
			
		case 2:
			r = p;
			g = v;
			b = t;
			break;
			
		case 3:
			r = p;
			g = q;
			b = v;
			break;
			
		case 4:
			r = t;
			g = p;
			b = v;
			break;
			
		default: // case 5:
			r = v;
			g = p;
			b = q;
	}
	
	return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
}