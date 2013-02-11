var io = require('socket.io').listen(8080);
var nMemcached = require('memcached'), memcached;
memcached = new nMemcached('127.0.0.1:11211');

var check = require('validator').check,
        sanitize = require('validator').sanitize;


var games = {};
var game = io
        .of('/game')
        .on('connection', function(socket) {
    socket.on('newUser', function(data) {
        var gameId = data.game;
        var userId = data.user;
        try {
            check(userId).isInt();
            check(gameId).isInt();
            check(data.userIp).isIP();
        } catch (e) {
            disconnect(e);
            return;
        }
        if (!games[gameId]) {
            games[data.game] = {};
        } else if (games[gameId][userId]) {
            disconnect('user online');
        }
        socket.join(data.game);
        var userData = {id: userId, name: sanitize(data.userName).escape(), ip: data.userIp};
        games[gameId][userId] = userData;
        socket.broadcast.to(data.game).emit('addUser', userData);
        socket.id = gameId + '_' + userId;
        memcached.set('user:' + userId, 1, 10000, function(err, result) {
            if (err) {
                disconnect(err);
            }
        });
    });

    socket.on('click', function(data) {
        var id = sanitize(data.id).toInt();
        try {
            check(data.game).isInt();
            check(data.user).isInt();
            if (typeof games[data.game][data.user] !== 'undefined') {
                socket.broadcast.to(data.game).emit('card', {id: id, path: sanitize(data.path).escape()});
                socket.broadcast.to(data.game).emit('addLog', {id: id, username: games[data.game][data.user].name});
            }
        } catch (e) {
            disconnect(e);
        }
    });

    socket.on('disconnect', function() {
        var data = this.id.split('_');
        if (games[data[0]] && games[data[0]][data[1]]) {
            delete games[data[0]][data[1]];
            socket.broadcast.to(data[0]).emit('rmUser', data[1]);
            memcached.del('user:' + data[1], function(err, result) {
                if (err) {
                    disconnect(err);
                }
            });
        }
    });
    var disconnect = function(err) {
        console.error(err);
        socket.emit('disconnect');
    }

});
