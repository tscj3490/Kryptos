var UIZbiory = function () {

    var fieldsSoureRun = function () {
        var result;
        var url;
        var tpl = '<div class="modal fade" id="fieldsModal">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '<h4 class="modal-title">Pola</h4>' +
            '</div>' +
            '<div class="modal-body">' +
            '<ul class="list-unstyled">{{#fields}}' +
            '<li><label>{{zbiory_pole}}</label>' +
            '<button class="btn btn-danger btn-xs js-field" data-url="/ewidencja-zrodel-danych/delete-field/id/{{id_rdo}}">' +
            '<i class="glyphicon glyphicon-trash"> Usuń</i></button>' +
            '</li>{{/fields}}</ul></div>' +
            '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>'+
            '</div></div></div>';

        $('button.js-fields').click(function () {
            url = $(this).data('url');
            result = ajax(url);
            if (result !== null) {
               var html = Mustache.to_html(tpl,result);
               $('#modalresult').html(html);
                fieldDeleted();
            }
        });
    };

    var personSoureRun = function () {
        var result;
        var url;
        var tpl = '<div class="modal fade" id="personModal">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '<h4 class="modal-title">Szczegółowe dane firmy</h4>' +
            '</div>' +
            '<div class="modal-body">' +
            '<div class="portlet">' +
            '<div class="portlet-body">{{#person}}' +
            '<div class="row static-info"><div class="col-md-5 name">Nazwa firmy:</div><div class="col-md-7 value">{{company_name}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Imie:</div><div class="col-md-7 value">{{name}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Nazwisko:</div><div class="col-md-7 value">{{surname}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Email:</div><div class="col-md-7 value">{{email}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Telefon:</div><div class="col-md-7 value">{{phone}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Ulica:</div><div class="col-md-7 value">{{street}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Miasto:</div><div class="col-md-7 value">{{city}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Kod pocztowy:</div><div class="col-md-7 value">{{post_code}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Nip:</div><div class="col-md-7 value">{{nip}}</div></div>' +
            '<div class="row static-info"><div class="col-md-5 name">Regon:</div><div class="col-md-7 value">{{regon}}</div></div>' +
            '{{/person}}</div></div>' +
            '</div>' +
            '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>'+
            '</div></div></div>';

        $('button.js-person').click(function () {
            url = $(this).data('url');
            result = ajax(url);
            if (result !== null) {
                var html = Mustache.to_html(tpl,result);
                $('#modalresult').html(html);
            }
        });
    };

    var fieldDeleted = function () {

        $('#fieldsModal').on('shown.bs.modal',function(e) {
            $('button.js-field').click(function () {
                url = $(this).data('url');
                result = ajax(url);
                if (result !== null) {
                    $(this).parent('li').remove();
                }
            });
        });
    }

    var ajax = function (url, data) {
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

    return {
        init: function () {
            personSoureRun();
            fieldsSoureRun();
        }
    };
}();