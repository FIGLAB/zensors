var REQUESTS = [];
var fs = require('fs');
var pemCert = fs.readFileSync('_ce').toString();
var privKey = fs.readFileSync('_pk').toString();

var options = {
  key: fs.readFileSync('_pk'),
  cert: fs.readFileSync('_ce')
};


function handler (req, res) {
  fs.readFile(__dirname + '/index.html',
  function (err, data) {
    if (err) {
      console.log(data)
      res.writeHead(500);
      return res.end('Error loading index.html');
    }

    res.writeHead(200);
    res.end(data);
  });
}

var app = require('https').createServer(options,handler)
  , io = require('socket.io').listen(app);
var appPort = 8008;
app.listen(appPort);
console.log("App Started at Port: "+appPort);

////////////////////////////////////////////
// Client Connection Protocol
////////////////////////////////////////////
io.sockets.on('connection', function (socket) {

  /////////////////////////////////////////
  // Send Handshake Message to the client
  /////////////////////////////////////////
  socket.emit('init', { client_id: makeid(7)});

  /////////////////////////////////////////
  // When an Acknowledgement is received from the client, grab the EXPLORE_ID and send the # of connected clients per the EXPLORE_ID
  /////////////////////////////////////////
  socket.on('realtime_label', function (data) {
    sensor_keypath = data.sensor_keypath;
    console.log('joining ' + sensor_keypath);
    socket.join(sensor_keypath);
    
    // Add the Data to our repo]
    var label = 0;
    if (!(sensor_keypath in REQUESTS)) {
        label = 0;
    } else {
        label = REQUESTS[sensor_keypath];
    }
    io.sockets.in(sensor_keypath).emit('status', {num_clients: io.sockets.clients(sensor_keypath).length, label:label});
  
  });

  socket.on('label_update', function (data) {
	  if (typeof parseInt(REQUESTS[data.sensor_keypath] == null)) {
		  REQUESTS[data.sensor_keypath] = 0;
	  }
      REQUESTS[data.sensor_keypath] = parseInt(data.label);
	  socket.broadcast.to(data.sensor_keypath).emit('label_update', {label:REQUESTS[data.sensor_keypath]});
  });
  
  socket.on('client_leaving', function(data) {
     sensor_keypath = data.sensor_keypath;
     io.sockets.in(sensor_keypath).emit('client_left', {num_clients: io.sockets.clients(sensor_keypath).length-1});
  });

  
});


function makeid(charLen)
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < charLen; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
}

