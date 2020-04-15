// Dodawanie i usuwanie osób i przedmiotów
if(typeof t_opts === 'undefined'){
    var t_opts = {};
    t_opts['t_items'] = [];
}
function selOptGetItemTitle(el) {
    return $(el).find('.title').html();
}
function runitemsSel() {
    setitemsSel();
    $('.selopt2bl').click(function () {
        var ide = $(this).attr('id');
        if (t_opts['t_itemsdata'][selOptGetItemTitle(this)]) {
            deleteItem(selOptGetItemTitle(this));
        }
        else {
            additem($(this).attr('id'));
        }
        setitemsSel();
    });
}
function setitemsSel() {
    $('.selopt2bl').each(function () {
        var html = selOptGetItemTitle(this);
        var have = 0;
        $.each(t_opts['t_itemsdata'], function (k, v) {
            if (html == k) {
                have = 1;
            }
        });
        if (have == 1) {
            $(this).addClass('active');
        }
        else {
            $(this).removeClass('active');
        }
    });
}
function additem(id) {
    $.ajax({
        dataType: 'html',
        url: '/fielditems/addminito/?id=' + id,
        data: {},
        method: 'POST',
        success: function (data) {
            $('body').append(data);
            $('#' + id).addClass('active');
        }
    });
}
function deleteItem(ide) {
    t_opts['t_items'].splice($.inArray(ide, t_opts['t_items']), 1);
    delete t_opts['t_itemsdata'][ide];
    setOptsView();
}

// Dodawanie i usuwanie osób i przedmiotów

function runPersontypesSel() {
    setPersonTypesSel();
    $('.selopt2bl').click(function () {
        var html = selOptGetItemTitle(this);
        var ide = $(this).attr('id');
        if (t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypesdata'][html]) {
            deletePersonType($(this).attr('id'), html);
        }
        else {
            addPersonType($(this).attr('id'), html);
        }
        setPersonTypesSel();
    });
}
function setPersonTypesSel() {
    $('.selopt2bl').each(function () {
        var ide = $(this).attr('id');
        var have = 0;
        $.each(t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypesdata'], function (k, v) {
            if (ide == v) {
                have = 1;
            }
        });
        if (have == 1) {
            $(this).addClass('active');
        }
        else {
            $(this).removeClass('active');
        }
    });
}
function addPersonType(id, html) {
    t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypes'].push(html);
    t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypesdata'][html] = id;
    setOptsView();
}
function deletePersonType(id, html) {
    t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypes'].splice($.inArray(html, t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypes']), 1);
    delete t_opts['t_itemsdata'][activeitem]['t_personsdata'][activepersons[activeitem]]['t_persontypesdata'][html];
    setOptsView();
}

function showSens() {
    var sensitiveData = $('*[data-sens="1"]:checked'),
        sensitiveDataExists = sensitiveData.size() * 1;
    if (sensitiveDataExists > 0) {
        $('#dane_wrazliwe').val('1');
        $('#show_dane_wrazliwe_podstawa').css('display', '');
    } else {
        $('#dane_wrazliwe').val('0');
        $('#dane_wrazliwe_podstawa').val([]);
        $('#show_dane_wrazliwe_podstawa').css('display', 'none');
    }
    var dane_wrazliwe_podstawa = $('#dane_wrazliwe_podstawa').val();
    var has1 = 0;
    var has3 = 0;
    if (dane_wrazliwe_podstawa) {
        $.each(dane_wrazliwe_podstawa, function (k, v) {
            if (v == 1) {
                has1 = 1;
            }
            if (v == 3) {
                has3 = 1;
            }
        });
    }
    if (has1 == 1) {
        setlegalacts1();
        $('#show_dane_wrazliwe_podstawa_ustawa').css('display', '');
    } else {
        $('#show_dane_wrazliwe_podstawa_ustawa').css('display', 'none');
        $('#dane_wrazliwe_podstawa_ustawa').val([]);
    }
    if (has3 == 1) {
        $('#show_dane_wrazliwe_opis').css('display', '');
    } else {
        $('#show_dane_wrazliwe_opis').css('display', 'none');
        $('#dane_wrazliwe_opis').val('');
    }

    var sensitiveDataNames = [],
        sensitiveDataIds = [];

    sensitiveData.each(function() {
        var id = this.name.match(/^field([0-9]+)/)[1];

        if ($.inArray(id, sensitiveDataIds)) {
            return;
        }

        sensitiveDataIds.push(id);

        $.each(t_opts.t_itemsdata, function(idx) {
            if (this.id == id) {
                sensitiveDataNames.push(idx);
            }
        });
    });

    $('#elementy-wrazliwe-list').text(sensitiveDataNames.join(', '));
}

// Widok

function setOptsView() {
    $('#itemsList').html('');
    if (t_opts['t_items'].length == 0) {
        $('#itemsList').html('<option value="">nie dodano przedmiotów</option>');
    }

    var activeExists = 0;
    $.each(t_opts['t_items'], function (k, v) {
        $('#itemsList').append('<option value="' + v + '">' + v + '</option>');
        if (v == activeitem) {
            activeExists = 1;
        }
    });
    if (activeExists == 0) {
        activeitem = $('#itemsList option:first').attr('value');
    }
    $('#itemsList').val(activeitem);

    $('.optsitems').html('');

    $.each(t_opts['t_items'], function (k, v) {
        $('.optsitems').append('<div class="itemtab" rel="' + v + '" style="display:none;"><br /><input type="button" name="" class="btn btn-default deleteitem" rel="' + v + '" value="Usuń aktywny przedmiot" /><br />');
        $('.deleteitem[rel="' + v + '"]').click(function () {
            deleteItem(v);
            setOptsView();
        });
    });

    $.each(t_opts['t_items'], function (k, v) {
        var cfg = t_opts['t_itemsdata'][v],
            versions = [[1, 'Kopia'], [2, 'Oryginał'], [4, 'Egzemplarz']],
            itemtab = $('.itemtab[rel="' + v + '"]'),
            versionsContainer = $('<div class="mod-versions"></div>');

        itemtab.append('<br /><strong>Wersje elementu zbioru</strong>');

        $(versions).each(function () {
            var uniqId = 'version_' + this[0] + uniqueId.get(),
                isChecked = cfg.versions & this[0],
                element = $('<div class="seloptmin2">' +
                    '<div class="checknew">' +
                        '<input type="checkbox" rel="' + uniqId + '" ' +
                            'value="' + this[0] + '" ' +
                            'name="' + uniqId + '" ' +
                            (isChecked ? 'checked ' : '') +
                            'id="' + uniqId + '">' +
                        '<label for="' + uniqId + '"></label>' +
                   '</div>' +
                    '<span> ' + this[1] + '</span>' +
                '</div>');

            versionsContainer.append(element);
        });

        itemtab.append(versionsContainer);
        var versionsElements = itemtab.find('.mod-versions .seloptmin2 input');

        versionsElements.on('click', function() {
            var version = 0;
            versionsElements.each(function() {
                version |= this.checked ? this.value : 0;
            });

            cfg.versions = version;
        });

        if (t_opts['t_itemsdata'][v]['t_fields0'].length > 0) {
            itemtab.append('<br /><strong>Pola w sekcji "DANE NIEOSOBOWE"</strong>');
            itemtab.append(' &nbsp;<input type="button" class="btn btn-default btn-xs field0checkall" rel="' + v + '" value="Zaznacz wszystkie" /> &nbsp;<input type="button" class="btn btn-default btn-xs field0uncheckall" rel="' + v + '" value="Odznacz wszystkie" /><br />');
            itemtab.find('.field0checkall[rel="' + v + '"]').click(function () {
                $.each(t_opts['t_itemsdata'][v]['t_fields0'], function (i2, v2) {
                    t_opts['t_itemsdata'][v]['t_fields0checked'][v2] = 1
                });
                setOptsView();
            });
            itemtab.find('.field0uncheckall[rel="' + v + '"]').click(function () {
                $.each(t_opts['t_itemsdata'][v]['t_fields0'], function (i2, v2) {
                    t_opts['t_itemsdata'][v]['t_fields0checked'][v2] = 0
                });
                setOptsView();
            });

            itemtab.append('<div class="fields0" rel="' + v + '"><br /></div>');
            $.each(t_opts['t_itemsdata'][v]['t_fields0'], function (i2, v2) {
                itemtab.find('.fields0[rel="' + v + '"]').append('<div class="seloptmin2"><div class="checknew"><input type="checkbox" name="field0' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '" class="field0' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '" id="field0' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '" rel="' + v + '" value="1"><label for="field0' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '"></labe`l></div><span>' + v2 + '</span></div>');
                itemtab.find('.field0' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '[rel="' + v + '"]').click(function () {
                    if ($(this).is(':checked')) {
                        t_opts['t_itemsdata'][v]['t_fields0checked'][v2] = 1;
                    } else {
                        t_opts['t_itemsdata'][v]['t_fields0checked'][v2] = 0;
                    }
                });
                if (t_opts['t_itemsdata'][v]['t_fields0checked'][v2] == 1) {
                    itemtab.find('.field0' + t_opts['t_itemsdata'][v]['t_fields0data'][v2] + '[rel="' + v + '"]').prop('checked', true);
                }
            });
        }

        itemtab.append('<br /><select name="persons" class="form-control persons" rel="' + v + '"></select>');
        var activeExists = 0;
        $.each(t_opts['t_itemsdata'][v]['t_persons'], function (k2, v2) {
            itemtab.find('.persons[rel="' + v + '"]').append('<option value="' + v2 + '">' + v2 + '</option>');
            if (v2 == activepersons[v]) {
                activeExists = 1;
            }
        });
        if (activeExists == 0) {
            activepersons[v] = itemtab.find('.persons[rel="' + v + '"] option:first').attr('value');
        }
        itemtab.find('.persons[rel="' + v + '"]').val(activepersons[v]);
        itemtab.find('.persons[rel="' + v + '"]').change(function () {
            activepersons[v] = $(this).val();
            setOptsView();
        });

        itemtab.append('<div class="personstabs" rel="' + v + '"></div>');

        $.each(t_opts['t_itemsdata'][v]['t_persons'], function (kx, vx) {
            var itemtabPerson = itemtab.find('.personstabs[rel="' + v + '"]');
            itemtabPerson.append('<div style="display:none;" class="personTab" rel="' + vx + '"></div>');
            var itemtabPersonTab = itemtabPerson.find(' .personTab[rel="' + vx + '"]');

            itemtabPersonTab.append('<br /><strong>Osoby</strong>');
            if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['addPerson'] == 1) {
                itemtabPersonTab.append(' &nbsp;<input type="button" value="wybierz" class="btn btn-info btn-xs edi" />');
                itemtabPersonTab.find(' .edi:last').click(function () {
                    showDial('/persontypes/addmini/', '', '');
                });
            }
            itemtabPersonTab.append('<div class="personTypes" rel="' + vx + '"><br /></div>');
            $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_persontypes'], function (i2, v2) {
                if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['addPerson'] == 1) {
                    itemtabPerson.find(' .personTypes[rel="' + vx + '"]').append('<div class="seloptmin"><span>' + v2 + '</span><i title="Usuń" class="glyphicon glyphicon-trash" onclick="deletePersonType(\'' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_persontypesdata'][v2] + '\',\'' + v2 + '\');"></i></div>');
                } else {
                    itemtabPerson.find(' .personTypes[rel="' + vx + '"]').append('<div class="selopt2"><span>' + v2 + '</span></div>');
                }
            });

            if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1'].length > 0) {
                itemtabPersonTab.append('<hr />');

                itemtabPersonTab.append('<strong>Pola w sekcji "DANE DODATKOWE"</strong>');
                itemtabPersonTab.append(' &nbsp;<input type="button" class="btn btn-default btn-xs field1checkall" rel="' + vx + '" value="Zaznacz wszystkie" /> &nbsp;<input type="button" class="btn btn-default btn-xs field1uncheckall" rel="' + vx + '" value="Odznacz wszystkie" /><br />');
                itemtabPerson.find(' .field1checkall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1checked'][v2] = 1
                    });
                    setOptsView();
                });
                itemtabPerson.find(' .field1uncheckall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1checked'][v2] = 0
                    });
                    setOptsView();
                });

                itemtabPersonTab.append('<div class="fields1" rel="' + vx + '"><br /></div>');
                $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1'], function (i2, v2) {
                    itemtabPerson.find(' .fields1[rel="' + vx + '"]').append('<div class="seloptmin2"><div class="checknew"><input type="checkbox" name="field1' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '" class="field1' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '" id="field1' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '" rel="' + vx + '" value="1"><label for="field1' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '"></label></div><span>' + v2 + '</span></div>');
                    itemtabPerson.find(' .field1' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '[rel="' + vx + '"]').click(function () {
                        if ($(this).is(':checked')) {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1checked'][v2] = 1;
                        } else {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1checked'][v2] = 0;
                        }
                        setOptsView();
                    });
                    if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1checked'][v2] == 1) {
                        itemtabPerson.find(' .field1' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields1data'][v2] + '[rel="' + vx + '"]').prop('checked', true);
                    }
                });
            }

            if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2'].length > 0) {
                itemtabPersonTab.append('<hr />');

                itemtabPersonTab.append('<strong>Pola w sekcji "DANE PODSTAWOWE"</strong>');
                itemtabPersonTab.append(' &nbsp;<input type="button" class="btn btn-default btn-xs field2checkall" rel="' + vx + '" value="Zaznacz wszystkie" /> &nbsp;<input type="button" class="btn btn-default btn-xs field2uncheckall" rel="' + vx + '" value="Odznacz wszystkie" /><br />');
                itemtabPerson.find(' .field2checkall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2checked'][v2] = 1
                    });
                    setOptsView();
                });
                itemtabPerson.find(' .field2uncheckall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2checked'][v2] = 0
                    });
                    setOptsView();
                });

                itemtabPersonTab.append('<div class="fields2" rel="' + vx + '"><br /></div>');
                $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2'], function (i2, v2) {
                    itemtabPerson.find(' .fields2[rel="' + vx + '"]').append('<div class="seloptmin2"><div class="checknew"><input type="checkbox" name="field2' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '" class="field2' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '" id="field2' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '" rel="' + vx + '" value="1"><label for="field2' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '"></label></div><span>' + v2 + '</span></div>');
                    itemtabPerson.find(' .field2' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '[rel="' + vx + '"]').click(function () {
                        if ($(this).is(':checked')) {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2checked'][v2] = 1;
                        } else {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2checked'][v2] = 0;
                            setOptsView();
                        }
                    });
                    if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2checked'][v2] == 1) {
                        itemtabPerson.find(' .field2' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields2data'][v2] + '[rel="' + vx + '"]').prop('checked', true);
                    }
                });
            }

            if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3'].length > 0) {
                itemtabPersonTab.append('<hr />');

                itemtabPersonTab.append('<strong>Pola w sekcji "DANE WRAŻLIWE"</strong>');
                itemtabPersonTab.append(' &nbsp;<input type="button" class="btn btn-default btn-xs field3checkall" rel="' + vx + '" value="Zaznacz wszystkie" /> &nbsp;<input type="button" class="btn btn-default btn-xs field3uncheckall" rel="' + vx + '" value="Odznacz wszystkie" /><br />');
                itemtabPerson.find(' .field3checkall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3checked'][v2] = 1
                    });
                    setOptsView();
                });
                itemtabPerson.find(' .field3uncheckall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3checked'][v2] = 0
                    });
                    setOptsView();
                });

                itemtabPersonTab.append('<div class="fields3" rel="' + vx + '"><br /></div>');
                $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3'], function (i2, v2) {
                    itemtabPerson.find(' .fields3[rel="' + vx + '"]').append('<div class="seloptmin2"><div class="checknew"><input type="checkbox" data-sens="1" name="field3' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '" class="field3' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '" id="field3' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '" rel="' + vx + '" value="1"><label for="field3' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '"></label></div><span>' + v2 + '</span></div>');
                    itemtabPerson.find(' .field3' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '[rel="' + vx + '"]').click(function () {
                        if ($(this).is(':checked')) {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3checked'][v2] = 1;
                        } else {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3checked'][v2] = 0;
                        }
                        showSens();
                        setOptsView();
                    });
                    if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3checked'][v2] == 1) {
                        itemtabPerson.find(' .field3' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields3data'][v2] + '[rel="' + vx + '"]').prop('checked', true);
                    }
                });
            }

            if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4'].length > 0) {
                itemtabPersonTab.append('<hr />');

                itemtabPersonTab.append('<strong>Pola w sekcji "INNE"</strong>');
                itemtabPersonTab.append(' &nbsp;<input type="button" class="btn btn-default btn-xs field4checkall" rel="' + vx + '" value="Zaznacz wszystkie" /> &nbsp;<input type="button" class="btn btn-default btn-xs field4uncheckall" rel="' + vx + '" value="Odznacz wszystkie" /><br />');
                itemtabPerson.find(' .field4checkall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4checked'][v2] = 1
                    });
                    setOptsView();
                });
                itemtabPerson.find(' .field4uncheckall[rel="' + vx + '"]').click(function () {
                    $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4'], function (i2, v2) {
                        t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4checked'][v2] = 0
                    });
                    setOptsView();
                });

                itemtabPersonTab.append('<div class="fields4" rel="' + vx + '"><br /></div>');
                $.each(t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4'], function (i2, v2) {
                    itemtabPerson.find(' .fields4[rel="' + vx + '"]').append('<div class="seloptmin2"><div class="checknew"><input type="checkbox" name="field4' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '" class="field4' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '" id="field4' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '" rel="' + vx + '" value="1"><label for="field4' + t_opts['t_itemsdata'][v]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['id'] + '' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '"></label></div><span>' + v2 + '</span></div>');
                    itemtabPerson.find(' .field4' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '[rel="' + vx + '"]').click(function () {
                        if ($(this).is(':checked')) {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4checked'][v2] = 1;
                        } else {
                            t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4checked'][v2] = 0;
                        }
                        showSens();
                        setOptsView();
                    });
                    if (t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4checked'][v2] == 1) {
                        itemtabPerson.find(' .field4' + t_opts['t_itemsdata'][v]['t_personsdata'][vx]['t_fields4data'][v2] + '[rel="' + vx + '"]').prop('checked', true);
                    }
                });
            }
        });

        itemtab.find(' .personTab[rel="' + activepersons[v] + '"]').css('display', 'block');
    });

    $('.itemtab[rel="' + activeitem + '"]').css('display', '');

    $('#options').val(JSON.stringify(t_opts));
    showSens();
}

// Start systemu

$(document).ready(function () {
    $('#addItem').click(function () {
        showDial('/fielditems/addmini/', '', '');
    });
    setOptsView();
});