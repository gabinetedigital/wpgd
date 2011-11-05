$(function () {
    $(document.getElementsByTagName('gd:video')).each(function (idx, el) {
        var $el = $(el);
        var vid = $el.attr('id');
        var uid = 'wpgd-video-' + vid + '-' + (new Date()).getTime();
        $el.replaceWith($('<div>').attr('id', uid));

        var url = SOURCES_URL + '&vid=' + vid + '&callback=?';
        $.getJSON(url, function (sources) {
            avl.player('#' + uid, { sources: sources });
        });
    });
});
