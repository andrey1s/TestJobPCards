$(document).ready(function() {
    var width = $(".front").width();
    var margin = width / 2;
    var height = $(".front").height();
    var hide = {width: '0px', height: '' + height + 'px', marginLeft: '' + margin + 'px', opacity: '0.5'};
    var showCard = {width: '' + width + 'px', height: '' + height + 'px', marginLeft: '0px', opacity: '1'};
    $('#carts').click(function(event) {
        var id = $(event.target).parent().attr('id');
        var url = self.location.href;
        $.ajax({
            url: url,
            data: {'id': id},
            dataType: 'json'
        }).done(function(data) {
            update(data);
        });
    });
    setInterval(function() {
        var url = $('#carts').data('status');
        $.ajax({
            url: url,
            dataType: 'json'
        }).done(function(data) {
            update(data);
        });
    }, 5000);
    function update(data) {
        updateUsers(data.users);
        if (data.status) {
            updateLog(data.log);
            changeStatus(data)
        }
    }
    function updateLog(log) {
        $('#log').prepend('<li class="alert alert-success">' + log.username + ' - ' + log.action + '</li>');
        setTimeout(function() {
            $("#log>li").removeClass('alert-success');
        }, 500);
    }
    function updateUsers(users) {
        if(typeof users === 'undefined'){
            return;
        }
        $('#users li').each(function(index, value) {
            var id = $(value).data('id');
            if(typeof users[id] === 'undefined'){
                $(value).remove();
            }else{
                delete users[id];
            }
        });
        var html = '';
        for (var i in users) {

            html += '<li class="alert alert-success" data-id="'+i+'">' + users[i].username + ' - ' + users[i].ip + '</li>';
        }
        $('#users').append(html);
    }

    function changeStatus(data) {
        for (var i in data.data) {
            var el = "#carts > " + "#" + i + (data.data[i] ? ' .back' : ' .front');
            var parent = $(el).parent();
            if (data.data[i] !== parent.hasClass('showcard')) {
                if (data.data[i] && typeof data.path[i] === 'string') {
                    $("#carts > " + "#" + i + ' .front').attr('src', data.path[i]);
                }
                var off = 'back';
                if (data.data[i]) {
                    off = 'front';
                }
                $(el).stop().animate(hide, {duration: 500});
                animateShow(off, parent);
            }
        }
    }
    ;
    function animateShow(off, parent) {
        setTimeout(function() {
            $("." + off, parent).stop().animate(showCard, {duration: 500});
            parent.toggleClass('showcard');
        }, 500);
    }
    $(".front").stop().css(hide);
    $(".showcard .front").stop().css(showCard);
});

