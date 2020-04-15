var UIMessages = function () {

    var startRun = function () {

        //disabled
        return;

        $.get("/messages/get", function (data) {
            var arFeeds = new Array();

            data.forEach(function (item) {
                var tmp = {
                    id: item.id,
                    title: item.description,
                    body: item.content
                };
                arFeeds.push(tmp);
            });

            var feedCounter = 0;
            var $currFeed = $('footer .feedrss span a')
                .css('margin-left', '2000px')
                .bind('click', function () {
                    var $divContainer = $('<div></div>')
                        .attr('id', 'RssReaderContainer')
                        .addClass('rss-reader-container')
                        .css('opacity', 0.0);
                });

            startFeedReader();

            function startFeedReader() {
                $currFeed
                    .queue(function () {
                        $(this).text(arFeeds[feedCounter]['title'])
                            .dequeue();
                    })
                    .animate({
                        'opacity': 1.0,
                        'margin-left': '0px'
                    }, 4000, 'easeOutSine')
                    .delay(2000)
                    .animate({
                        'opacity': 0.0,
                        'margin-left': '2000px'
                    }, 4000, 'easeInOutSine', function () {
                        (++feedCounter == arFeeds.length) ? feedCounter = 0 : "";
                        startFeedReader();
                    });
            }
        });

        /*
         * FOOTER
         */
        $("#footer .links ul li a")
            .css("opacity", 0.7)
            .hover(function () {
                $(this).fadeTo("fast", 0.5);
            }, function () {
                $(this).fadeOut("slow", 0.7);
            });
    };

    return {
        init: function () {
            startRun();
        }
    };
}();