$(document).ready(function() {
    var game = io.connect(config.server + '/game');

    game.on('connect', function() {
        game.emit('newUser', {user: userId, game: gameId, userName: userName, userIp: userIp});
    });
    game.on('disconnect', function() {
        location.href = '/';
    });
    game.on('card', function(data) {
        changeStatus(data);
    });
    game.on('addUser', function(data) {
        $('#users').append('<li class="alert alert-success" id="user-' + data.id + '">' + data.name + ' - ' + data.ip + '</li>');
    });
    game.on('rmUser', function(data) {
        $('#users>#user-' + data).remove();
    });
    game.on('addLog', function(data) {
        $('#log').prepend('<li class="alert alert-success">' + data.username + ' - change status card ' + data.id + '</li>');
        setTimeout(function() {
            $("#log>li").removeClass('alert-success');
        }, 500);
    });

    var width = $(".front").width();
    var margin = width / 2;
    var height = $(".front").height();
    var hide = {width: '0px', height: '' + height + 'px', marginLeft: '' + margin + 'px', opacity: '0.5'};
    var showCard = {width: '' + width + 'px', height: '' + height + 'px', marginLeft: '0px', opacity: '1'};
    $('#carts').click(function(event) {
        var id = $(event.target).parent('.card').attr('id');
        if (id) {
            var url = self.location.href;
            $.ajax({
                url: url,
                data: {'id': id.split('-')[1]},
                dataType: 'json'
            }).done(function(data) {
                game.emit('click', {game: gameId, id: data.id, path: data.path, user: userName});
            });
        }
    });
    /**
     * change status card
     * @param {Object} data{id:'',path:''}
     * @returns {undefined}
     */
    function changeStatus(data) {
        var el = $('#cart-' + data.id);
        var off = '.front', on = '.back';
        if (el.hasClass('showcard')) {
            off = '.back', on = '.front';
        } else {
            $('.front', el).attr('src', data.path);
        }
        $(on, el).stop().animate(hide, {duration: 500});
        setTimeout(function() {
            $(off, el).stop().animate(showCard, {duration: 500});
            el.toggleClass('showcard');
        }, 500);
    }
    ;
    $(".front").stop().css(hide);
    $(".showcard .front").stop().css(showCard);
});

