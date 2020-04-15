var UISearch = function () {

    var startRun = function () {
        var result;
        var url;

        $('button.js-search').click(function () {
            $('#messages').children().remove();
            url = $(this).data('url');
            result = ajax(url);

            if (result !== null) {
                ustway(result[0],'#us');
                ustway(result[1],'#ro');
                ustway(result[2],'#in');
                fieldDeleted();
                typeAhead();
            }
        });
    };

    var ustway = function(data,element){

        var tpl = '<ul class="list-unstyled">{{#data}}' +
            '<li><label>{{value}}</label><br />' +
            '<button class="btn btn-danger btn-xs js-field" data-url="/ustawy/delete/id/{{idustawy}}">' +
            '<i class="glyphicon glyphicon-trash"> Usuń</i></button>' +
            '</li>' +
            '{{/data}}</ul>';

        var html = Mustache.to_html(tpl,data);
        $(element).html(html);
    }

    var fieldDeleted = function () {

        $('#searchModal').on('shown.bs.modal',function(e) {
            $('button.js-field').click(function () {
                url = $(this).data('url');
                result = ajax(url);
                if (result !== null) {
                    $(this).parent('li').remove();
                }
            });
        });
    }

    var typeAhead = function(){
        var data = ajax('/ustawy/get-content');
        var substringMatcher = function (strs) {
            return function findMatches(q, cb) {
                var matches, substrRegex;
                matches = [];
                substrRegex = new RegExp(q, 'i');
                $.each(strs, function (i, str) {
                    if (substrRegex.test(str)) {
                        matches.push({value: str});
                    }
                });
                cb(matches);
            };
        };

        $('#the-basics #typeahead').typeahead({
                hint: true,
                highlight: true,
                minLength: 1
            },
            {
                name: 'data',
                displayKey: 'value',
                source: substringMatcher(data)
            });
        $('#the-basics #typeahead').css('background-color', '#FFF');
    };

    var buttonAddRun = function () {
        var typeValue = '';
        var signatureValue = '';
        var contentValue = '';
        var result ='';

        $('#rdo-button').click(function () {
            typeValue = $('#type :selected').val();
            signatureValue = $('#signature').val();
            contentValue = $('#content').val();
            var data = {
                type:typeValue,
                signature:signatureValue,
                content:contentValue
            };

           result = ajaxData('/ustawy/add',data);
           console.log(result);
            if(result.message == 'success') {
                $('#messages').append('<div class="alert alert-success" role="alert">Dodano do bazy</div>');
                if (typeValue == 1) {
                    $("#us ul").append('<li><label>' + signatureValue + ':' + contentValue + '</label><br /><button class="btn btn-danger btn-xs js-field" disabled><i class="glyphicon glyphicon-trash"> Usuń</i></button></li>');
                }
                if (typeValue == 2) {
                    $("#ro ul").append('<li><label>' + signatureValue + ':' + contentValue + '</label><br /><button class="btn btn-danger btn-xs js-field" disabled><i class="glyphicon glyphicon-trash"> Usuń</i></button></li>');
                }
                if (typeValue == 3) {
                    $("#in ul").append('<li><label>' + signatureValue + ':' + contentValue + '</label><br /><button class="btn btn-danger btn-xs js-field" disabled><i class="glyphicon glyphicon-trash"> Usuń</i></button></li>');
                }
            }else{
                $('#messages').append('<div class="alert alert-danger" role="alert">'+result.message+'</div>');
            }
        });
    }

    var ajax = function (url) {
        var result;

        $.ajax({
            method: "POST",
            url: url,
            async: false
        }).done(function (data) {
            result = data;
        }).fail(function () {
            result = null;
        });

        return result;
    };

    var ajaxData = function (url, data) {
        var result;

        $.ajax({
            method: "POST",
            url: url,
            data: data,
            async: false
        }).done(function (data) {
            result = data;
        }).fail(function () {
            result = null;
        });
        return result;
    };

    return {
        init: function () {
            startRun();
            buttonAddRun();
        }
    };
}();