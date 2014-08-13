$(document).ready(function() {
    $('.js-attend-toggle').on('click', function(e) {
        // prevents the browser from "following" the link
        e.preventDefault();

        var $anchor = $(this);
        var url = $(this).attr('href')+'.json';

        $.post(url, null, function(data) {
            if (data.attending) {
                var message = 'See you there!';
            } else {
                var message = 'We\'ll miss you!';
            }

            $anchor.after('<span class="label label-default">&#10004; '+message+'</span>');
            $anchor.hide();
        });
    });
});
