var UIZbioryForm = function () {

    var urlGetFields = '/ewidencja-zrodel-danych/get-fields'

    var startRun = function () {

        var zbiory;
        if (row.opcja != null) {
            $('#opcja').val(row.opcja);
        }
        if (row.zbiory_ids != null) {
            zbiory = JSON.parse(row.zbiory_ids);
            $('#source').val(zbiory);
        }
        if (row.cel_przetwarzania != null) {
            $('#cel_przetwarzania').text(row.cel_przetwarzania);
        }

    }
    var sourceRun = function () {
        var result;
        $('#source').change(function () {
            result = ajax(urlGetFields, {ids: $(this).val()});
            if (result !== null) {
                $("#rdos").empty();
                $.each(result, function (key, item) {
                    $.each(item.fields, function (k, row) {
                        $("#rdos").append('<label><input type="checkbox" checked name="fields[]" value="' + item.id + '.' + row + '" />' + row + '</label>');
                    })
                });
            }
        });
    };

    var buttonAddRun = function () {
        var elemValue = '';
        $('#rdo-button').click(function () {
            elemValue = $('#rdo').val();
            if (elemValue) {
                $("#rdos").append('<label><input type="checkbox" checked name="fields[]" value="0.' + elemValue + '" />' + elemValue + '</label>');
            }
        });
    }

    var ajax = function (url, data) {
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
            sourceRun();
        }
    };
}();