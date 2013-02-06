$(document).ready(function() {
    var width = $(".front").width();
    var margin = width / 2;
    var height = $(".front").height();
    var hide = {width: '0px', height: '' + height + 'px', marginLeft: '' + margin + 'px', opacity: '0.5'};
    var showCard = {width: '' + width + 'px', height: '' + height + 'px', marginLeft: '0px', opacity: '1'};
    $('#carts').click(function(event) {
        var id = $(event.target).parent().attr('id');
        var url = self.location.href;
        console.log(id);
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
    }, 1000);
    function update(data) {
        if (data.status) {
            if (typeof data.users !== "undefined") {
                updateUsers(data.users)
            }
            changeStatus(data)
        }
    }
    function updateUsers(users) {
        var html = '';
        for (var i in users) {
            html += '<dt>' + users[i].username + '</dt><dd>' + users[i].ip + '</dd>';
        }
        $('#users').html(html);
    }

    function changeStatus(data) {
        for (var i in data.data) {
            var el = "#carts > " + "#" + i + (data.data[i] ? ' .back' : ' .front');
            var parent = $(el).parent();
            if (data.data[i] !== parent.hasClass('showcard')) {
                if (data.data[i] && typeof data.path[i] === 'string') {
                    $("#carts > " + "#" + i + ' .front').attr('src',data.path[i]);
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

