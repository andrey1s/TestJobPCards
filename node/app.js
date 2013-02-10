var io = require('socket.io').listen(8080);
var nMemcached = require('memcached'), memcached;
memcached = new nMemcached('127.0.0.1:11211');


var games = {};
var game = io
        .of('/game')
        .on('connection', function(socket) {
    socket.on('newUser', function(data) {
        socket.join(data.game);
        var gameId = data.game;
        var userId = data.user;
        var userData = {id: userId, name: data.userName, ip: data.userIp};
        if (!games[gameId]) {
            games[data.game] = {};
        } else if (games[gameId][userId]) {
            socket.broadcast.to(data.game).emit('disconnect');
        }
        games[gameId][userId] = userData;
        socket.broadcast.to(data.game).emit('addUser', userData);
        socket.id = gameId + '_' + userId;
        memcached.set('user:' + userId, 1, 10000, function(err, result) {
            if (err) {
                console.error(err);
            }
        });
    });

    socket.on('click', function(data) {
        socket.broadcast.to(data.game).emit('card', {id: data.id, path: data.path});
        socket.broadcast.to(data.game).emit('addLog', {id: data.id, username: data.user});
    });

    socket.on('disconnect', function() {
        var data = this.id.split('_');
        delete games[data[0]][data[1]];
        socket.broadcast.to(data[0]).emit('rmUser', data[1]);
        memcached.del('user:' + data[1], function(err, result) {
            if (err) {
                console.error(err);
            }
        });
    });

});
