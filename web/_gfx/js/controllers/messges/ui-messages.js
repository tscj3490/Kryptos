var UIInfo = function () {

    var startRun = function () {
        $("[name='active']").bootstrapSwitch();
        $("[name='active']").on('switchChange.bootstrapSwitch', function (event, state) {
            var result = state ? 1 : 0;
            var url = '/messages/on-off/id/'+$(this).data('id')+'/status/'+ result;
           ajax(url);
        });
    };

     var ajax = function (url) {

        $.ajax({
            method: "POST",
            url: url,
            async: true
        }).done(function (data) {

        }).fail(function () {

        })
    };

    return {
        init: function () {
            startRun();
        }
    };
}();