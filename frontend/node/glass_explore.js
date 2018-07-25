var REQUESTS = [];
var fs = require('fs');
var pemCert = fs.readFileSync('_ce').toString();
var privKey = fs.readFileSync('_pk').toString();

var options = {
  key: fs.readFileSync('_pk'),
  cert: fs.readFileSync('_ce')
};

var app = require('https').createServer(options,handler)
  , io = require('socket.io').listen(app);

app.listen(8001);
console.log("App Started at Port 8001");


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

io.sockets.on('connection', function (socket) {

  // Send Handshake Message to the client
  socket.emit('init', { client_id: makeid(7)});

  // When an Acknowledgement is received from the client, grab the EXPLORE_ID and send the # of connected clients per the EXPLORE_ID
  socket.on('explore', function (data) {
    explore_id = data.explore_id;
    console.log('joining ' +explore_id);
    socket.join(explore_id);
    
    // Add the Data to our repo]
    var defaultText = "";
    if (!(explore_id in REQUESTS)) {
        defaultText = "";
    } else {
        defaultText = REQUESTS[explore_id];
    }

    io.sockets.in(explore_id).emit('status', {num_clients: io.sockets.clients(explore_id).length, text:defaultText});

  });

  socket.on('text_update', function (data) {
      REQUESTS[data.explore_id] = data.text;
      socket.broadcast.to(data.explore_id).emit('text_update', {text:data.text});
  });
  
  socket.on('client_leaving', function(data) {
     explore_id = data.explore_id;
     io.sockets.in(explore_id).emit('client_left', {num_clients: io.sockets.clients(explore_id).length-1});
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

