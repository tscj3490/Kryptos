if (typeof console === 'undefined') {
    console = {
        log: function () { }
    }
}

// defines start
enableJsCheckboxes = function () {
    var checkboxSpoofElementClick = function () {
        var tg = $(this),
            checkbox = tg.attr('data-target-id') ? $('input[name="' + tg.attr('data-target-id') + '"]') : tg.find('input');

        if (isCheckedAdv(checkbox)) {
            setCheckedAdv(checkbox, 0);
            setCheckedIndicator(tg, 0);
            tg.removeClass('checked');
        } else {
            setCheckedAdv(checkbox, 1);
            setCheckedIndicator(tg, 1);
            tg.addClass('checked');
        }

        checkbox.trigger('change');
    };
    var checkboxChange = function () {
        var tg = $('.js-checkbox-spoof-element[data-target-id="' + this.name + '"]');

        if (isCheckedAdv($(this))) {
            setCheckedIndicator(tg, 1);
            tg.addClass('checked');
        } else {
            setCheckedIndicator(tg, 0);
            tg.removeClass('checked');
        }
    };
    var inputPrepare = function () {
        var tg = $(this),
            checkbox = tg.attr('data-target-id') ? $('input[name="' + tg.attr('data-target-id') + '"]') : tg.find('input');

        var setSpoofValue = function () {
            if (isCheckedAdv($(this))) {
                setCheckedIndicator(tg, 1);
                tg.addClass('checked');
            } else {
                setCheckedIndicator(tg, 0);
                tg.removeClass('checked');
            }
        };

        var fn = $.proxy(setSpoofValue, checkbox);

        checkbox
            .on('change', fn);

        fn();
    };
    $('.js-checkbox')
        .not('.processed-js-checkbox')
        .on('click', checkboxSpoofElementClick)
        .each(inputPrepare)
        .addClass('.js-checkbox-spoof-element')
        .addClass('processed-js-checkbox');

    $('.js-checkbox-container')
        .not('.processed-js-checkbox-container')
        .on('click', '.js-checkbox-from-container', checkboxSpoofElementClick)
        .addClass('processed-js-checkbox-container')
        .find('.js-checkbox-from-container')
        .addClass('js-checkbox-spoof-element');

    $('.hiddenFormElements')
        .on('change', '.js-checkbox-target', checkboxChange)
        .find('.js-checkbox-target')
        .each(checkboxChange);

    $('.action-select-all')
        .not('.processed-action-select-all')
        .on('click', function () {
            var tg = $(this),
                tr = tg.closest('tr');

            if (tg.hasClass('checked')) {
                tg
                    .removeClass('checked')
                    .closest('tr')
                    .find('.js-checkbox.checked')
                    .trigger('click');

                tr.find('.js-checkbox-from-container.checked')
                    .each(function () {
                        var event = $.Event('click', {
                            target: this
                        });
                        tr.trigger(event);
                    });
            } else {
                tg
                    .addClass('checked')
                    .closest('tr')
                    .find('.js-checkbox:not(.checked)')
                    .trigger('click');

                tr.find('.js-checkbox-from-container:not(.checked)')
                    .each(function () {
                        var event = $.Event('click', {
                            target: this
                        });
                        tr.trigger(event);
                    });
            }
        })
        .each(function () {
            var tg = $(this),
                targetSize = tg.closest('tr').find('.js-checkbox, .js-checkbox-from-container').size();

            if (tg.closest('tr').find('.js-checkbox.checked, .js-checkbox-from-container.checked').size() === targetSize) {
                tg.addClass('checked');
            }
        })
        .addClass('processed-action-select-all');
};
isCheckedAdv = function (tg) {
    return tg.is(':checkbox') ? tg.is(':checked') : parseInt(tg.val());
};
setCheckedAdv = function (tg, checked) {
    if (tg.is(':checkbox')) {
        if (checked) {
            tg.prop('checked', true);
        } else {
            tg.removeAttr('checked');
        }
    } else {
        if (checked) {
            tg.val(1);
        } else {
            tg.val(0);
        }
    }
};
setCheckedIndicator = function (tg, checked) {
    var indicator = tg.find('.text-indicator');
    if (indicator.size()) {
        if (checked) {
            indicator.text('checked');
        } else {
            indicator.text('not checked');
        }
    }
};

uncommentBigData = function () {
    if ($('.uncommentBigData').size()) {
        $(".uncommentBigData").contents().filter(function () {
            return this.nodeType == 8;
        }).each(function (i, e) {
            $(e).closest('.uncommentBigData').html(e.nodeValue);
        });
    }
};

generateFormRow = function () {
    var tg = $(this),
        newIndex = parseInt(tg.attr('data-index')) + 1,
        row = tg.closest('.row'),
        col = tg.closest('div[class^=col-]'),
        label = row.find('label'),
        inputId = label.attr('for').replace(/[0-9]+$/, newIndex);

    row
        .clone(true)
        .insertAfter(row)
        .find('label')
        .attr('for', inputId)
        .end()
        .find('input, select, textarea')
        .attr('id', inputId)
        .val('');

    col.hide();
};
enableFormGenerator = function () {
    $('.generateFormRow')
        .not('.processed-generateFormRow')
        .on('click', generateFormRow)
        .addClass('processed-generateFormRow');
};
enableDropzone = function (injectParams) {
    var dropzoneElement = $("#dropzone");
    if (dropzoneElement.size() < 1 || dropzoneElement.hasClass('processed-dropzone')) {
        return;
    }
    var params = {};
    if (injectParams) {
        params = $.extend(params, injectParams);
    }

    dropzoneElement
        .dropzone(params)
        .addClass('processed-dropzone');

    return Dropzone.forElement('#dropzone');
};
enableDropzoneWidget = function (tg, params) {
    params = $.extend({
        hiddenInputContainer: '#' + $(tg).closest('form').attr('id'),
        addRemoveLinks: true,
        dictCancelUpload: "Cancel upload",
        dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
        dictRemoveFile: "Usuń plik"
    }, params);
    var dropzoneWidget = new Dropzone(tg, params);

    return dropzoneWidget;
};
enableUploadList = function () {
    $('.courses-slide-list')
        .not('.processed-courses-slide-list')
        .each(function () {
            var tg = $(this);

            tg.sortable();
            tg.disableSelection();
        })
        .addClass('processed-courses-slide-list');

    $('.courses-slide-list .slide')
        .not('.processed-courses-slide-list-slide')
        .each(function () {
            var tg = $(this);

            tg
                .append('<span class="image-delete-button glyphicon glyphicon-remove"></span>')
                .on('click', '.image-delete-button', function () {
                    $(this).closest('.image').remove();
                });
        })
        .addClass('processed-courses-slide-list-slide');
};

var dial = {
    callers: {},
    lastDialTarget: null,
    lastDialProcessFn: null,
    lastDialReadyFn: null,
    lastDialModel: null,

    variations: {
        default: {
            select: function () {
                toggleElement({
                    id: $(this).attr('id'),
                    name: $(this).html()
                }, 1);
                runOptsSearch();
            },
            remove: function () {
                toggleElement({
                    id: $(this).attr('id'),
                    name: $(this).html()
                }, 1);

            },
            checkall: function () {
                $.each(getDialogElements(), function () {
                    if (!$(this).hasClass('active') && $(this).css('display') != 'none') {
                        addElement({
                            id: $(this).attr('id'),
                            name: $(this).html()
                        }, 1);
                    }
                });
            },
            uncheckall: function () {
                $.each(getDialogElements(), function () {
                    if ($(this).hasClass('active') && $(this).css('display') != 'none') {
                        removeElement({
                            id: $(this).attr('id'),
                            name: $(this).html()
                        }, 1);
                    }
                });
            }
        },
        dialogChooseMultiple: {
            setDialogSelectedItems: function () {
                var dialogContainer = $('#personscont');

                dialogContainer.find('.selopt2bl').each(function () {
                    var id = this.getAttribute('data-id');

                    var html = $(this).html();
                    var a = t_opts['t_persons'].indexOf(html);
                    if (a == -1) { $(this).removeClass('active'); }
                    else { $(this).addClass('active'); }
                });
            },
            setSummaryView: function () {
                var viewContainer = $('#personscont'),
                    valuesStorage = t_opts;

                // reset view
                viewContainer.html('');

                // empty message, when no selection
                if (valuesStorage['t_persons'].length == 0) {
                    viewContainer.append('<div class="alert alert-info"><i class="fa fa-info fa-fw"></i> &nbsp;<i>Nie wybrano osób. Szablon będzie aktywny dla wszystkich osób.</i></div>');
                }

                // add selected rows
                $.each(valuesStorage['t_persons'], function (i2, v2) {
                    viewContainer.append(
                        '<div class="seloptmin">' +
                        '<span>' + v2 + '</span>' +
                        '<i title="Usuń" class="glyphicon glyphicon-trash" ' +
                        'onclick="activeFields = 0; deletePerson(\'' + valuesStorage['t_personsdata'][v2] + '\',\'' + v2 + '\');"' +
                        '>' +
                        '</i>' +
                        '</div>');
                });

                // save ? save hidden fields ?
                $('#persons').val(JSON.stringify(t_opts));
            }
        }
    },

    getCaller: function (id) {
        var caller = {
            persisted: false
        };

        if (typeof id !== 'undefined') {
            if (typeof dial.callers[id] !== 'undefined') {
                dial.callers[id] = caller;
                caller.persisted = true;
                caller.id = uniqueId.get();
            }
            caller = dial.callers[id];
        }

        return caller;
    },

    initializer: function () {
        $('.choose-from-dial')
            .not('.processed-choose-from-dial')
            .each(function () {
                var starterElement = $(this),
                    dialogClass = this.getAttribute('data-dialog-class');

                switch (dialogClass) {
                    case "multiple":
                        dialogClass = dial.variations.dialogChooseMultiple;
                        break;
                    default:
                        dialogClass = $.noop;
                }

                dial.prepareStarter(starterElement, dialogClass);
            })
            .addClass('processed-choose-from-dial');
    },

    getQueue: function () {
        var x = {};

        var addElement = function (element, noview) {
            if (element.id in data.elements) {
                return;
            }

            data.ids.push(element.id);
            data.elements[element.id] = element;

            if (!noview) {
                setPersonsSel();
                setView();
            }
        };

        var removeElement = function (element, noview) {
            if ((!element.id in data.elements)) {
                return;
            }

            var idsIndex = data.ids.indexOf(element.id);
            data.ids.splice(idsIndex, 1);
            delete data.elements[element.id];

            if (!noview) {
                setPersonsSel();
                setView();
            }
        };

        var toggleElement = function (element, noview) {
            if (element.id in data.elements) {
                removeElement(element, noview);
            } else {
                addElement(element, noview);
            }
        };


        return x;
    },

    prepareStarter: function (callerButton, dialogClass) {
        var url = callerButton.attr('data-dial-url'),
            id = callerButton.attr('data-dial-id'),
            urlFn = callerButton.attr('data-dial-url-fn'),
            dialClass = callerButton.attr('data-dial-class'),
            processFn = callerButton.attr('data-dial-process-fn') ? $.proxy(window[callerButton.attr('data-dial-process-fn')], callerButton) : $.proxy(dial.defaultDialogProcess, callerButton),
            readyFn = callerButton.attr('data-dial-ready-fn') ? $.proxy(window[callerButton.attr('data-dial-ready-fn')], callerButton) : $.noop,
            dialModel = callerButton.attr('data-dial-model') ? $.proxy(window[callerButton.attr('data-dial-model')], callerButton) : $.noop,
            dataProcessFn = callerButton.attr('data-dial-data-process-fn') ? $.proxy(window[callerButton.attr('data-dial-data-process-fn')], callerButton) : $.noop,
            data = {
                ids: [],
                elements: {}
            },
            newDialog = callerButton.attr('data-new-dialog');

        if (urlFn) {
            urlFn = $.proxy(window[urlFn], callerButton);
        } else {
            urlFn = function () {
                return url;
            }
        }

        var call = dial.getCaller(id ? id : null);

        var triggerAction = function (action, triggerElement) {
            if (action in dialogClass) {
                dialogClass[action](triggerElement);
            } else if (action in dial.variations.default) {
                dial.variations.default[action](triggerElement);
            } else {
                console.log('Invalid action');
            }
        };
        var openDialog = function () {
            dial.lastDialTarget = callerButton;
            dial.lastDialProcessFn = processFn;
            dial.lastDialReadyFn = readyFn;
            dial.lastDialModel = dialModel;

            if (newDialog) {
                newOpenDialog();
            } else {
                var choosedUrl = urlFn();
                if (choosedUrl) {
                    showDial(urlAddHintValue(choosedUrl), dialClass ? dialClass : 'modal-lg', '');
                }
            }
        };

        var urlAddHintValue = function (url) {
            var hintValue = callerButton.closest('.input-group').find('.typeaheadElement').val();
            if (!url.match(/\?/)) {
                url += '?';
            } else {
                url += '&';
            }
            url += 'hintValue=' + hintValue;

            return url;
        };

        var newOpenDialog = function () {
            var choosedUrl = urlFn();
            if (!choosedUrl) {
                return;
            }

            dataProcessFn(data);

            $.ajax({
                dataType: 'html',
                url: urlAddHintValue(choosedUrl),
                data: data,
                method: 'POST',
                success: newDialogSuccess,
                error: function () {
                    newDialogError('Nie udało się otworzyć okna');
                }
            });
        };

        var getDialogElements = function () {
            return $('.selopt2bl');
        };

        callerButton.click(openDialog);
    },

    defaultDialogProcess: function (result) {
        var targetElement = $(this.attr('data-target-element')),
            sourceVariableName = targetElement.attr('data-source-variable');

        if (typeof result === 'object') {
            if (sourceVariableName) {
                window[sourceVariableName].push(result);
            }
            targetElement.val(result.id).change();
        } else {
            targetElement.val(result).change();
        }

        $('#ajaxDial').modal('hide');
        selectNextFormElement(dial.lastDialTarget);
    }
};

uiActiveDialog = null;
newDialogSuccess = function (result) {
    if ('' === result) {
        return newDialogError('Nie udało się otworzyć okna');
    } else if ('<!doctype' === result.substr(0, 9)) {
        return newDialogError('Niepoprawny format odpowiedzi AJAX');
    }
    var dialog = $(result),
        dialogBody = dialog.find('.modal-body');

    if (dialog.find('.modal-header h3').text().trim() === '') {
        if (dialogBody.find('h1').size()) {
            dialog.find('.modal-header h3').text(dialogBody.find('h1').text());
            dialogBody.find('h1').remove();
        }
    }

    if (dialog.find('.modal-footer').text().trim() === '') {
        if (dialogBody.find('.footer-actions').size()) {
            dialog.find('.modal-footer').html(dialogBody.find('.footer-actions').html());
            dialogBody.find('.footer-actions').remove();
        }
    }

    dialog.appendTo('body');
    dialog.modal();
    dialog.on('hidden.bs.modal', function () {
        $(this).remove();
        uiActiveDialog = null;
    });

    uiActiveDialog = dialog;

    systemAssignHandlers();
};
newDialogError = function (message) {
    displayFlashMessage({
        type: 'error',
        title: 'Błąd',
        text: message
    });
};

objectsStorage = {
    init: function (data) {
        var x = {};
        x.data = data;

        x.has = function (find, key) {
            var found = false;
            key = key ? key : 'id';
            find = find.toString();

            $(x.data).each(function () {
                if (typeof this[key] === 'undefined') {
                    return false;
                }

                if (this[key].toString() === find) {
                    found = true;
                    return false;
                }
            });

            return found;
        };

        x.push = $.proxy([].push, x.data);

        return x;
    }
};

dialActionInit = function () {
    $('.js-dial-action')
        .not('.processed-js-dial-action')
        .each(function () {
            var tg = $(this),
                action = this.getAttribute('data-action');

            tg.on('click', function () {
                dial.lastDialModel.triggerAction(action, this);
            });
        })
        .addClass('processed-js-dial-action');
};

timeouter = {
    storageTimeout: {},
    storageFn: {},
    runOnExit: [],
    exitCompleted: [],
    registerStorage: function (storageName, runOnExit, config) {
        if (typeof timeouter.storageTimeout[storageName] === 'undefined') {
            timeouter.storageTimeout[storageName] = {};
            timeouter.storageFn[storageName] = {};
            if (runOnExit) {
                if (null === config) {
                    config = {};
                }
                timeouter.runOnExit.push($.extend({
                    unloadBefore: []
                }, config, {
                        storageName: storageName
                    }));
            }
        }
    },
    add: function (storageName, id, fn, timeoutMs) {
        if (typeof timeouter.storageTimeout[storageName][id] !== 'undefined') {
            clearTimeout(timeouter.storageTimeout[storageName][id]);
        }

        timeouter.storageFn[storageName][id] = fn;

        timeouter.storageTimeout[storageName][id] = setTimeout(function () {
            timeouter.trigger(storageName, id);
        }, timeoutMs);
    },
    unloadHandler: function () {
        var l = 0;
        do {
            $.each(timeouter.runOnExit, function (roeIndex, storageConfig) {
                if (-1 !== timeouter.exitCompleted.indexOf(storageConfig.storageName)) {
                    return;
                }

                var skip = false;
                $.each(timeouter.runOnExit, function (idx, cfg) {
                    var dependentIdx = cfg.unloadBefore.indexOf(storageConfig.storageName);
                    if (-1 !== dependentIdx) {
                        if (-1 === timeouter.exitCompleted.indexOf(cfg.storageName)) {
                            skip = true;
                        }
                    }
                });
                if (skip) {
                    return;
                }

                $.each(timeouter.storageTimeout[storageConfig.storageName], function (storageId, timeout) {
                    if (timeout) {
                        timeouter.trigger(storageConfig.storageName, storageId);
                    }
                });

                timeouter.exitCompleted.push(storageConfig.storageName);
            });
        } while (timeouter.runOnExit.length > timeouter.exitCompleted.length || ++l > 1000);

        if (++l > 1000) {
            console.log('Timeouter loop guard');
        }
    },
    trigger: function (storageName, id) {
        clearTimeout(timeouter.storageTimeout[storageName][id]);
        timeouter.storageFn[storageName][id]();
    }
};
$(window).on('beforeunload', timeouter.unloadHandler);

typeaheadInit = function () {
    var dependentTypeaheads = [],
        getSourceFromStorage = function () {
            return this.data;
        };
    $('.typeaheadElement')
        .not('.processed-typeaheadElement')
        .each(function () {
            var tg = $(this),
                sourceFn = tg.attr('data-source-function') ? window[tg.attr('data-source-function')] : $.proxy(getSourceFromStorage, window[tg.attr('data-source-variable')]),
                targetElement = $(tg.attr('data-target-element')),
                relativeElement = tg.attr('data-relative-element') ? $(tg.attr('data-relative-element')) : null,
                afterSelectFn = tg.attr('data-after-select-fn') ? window[tg.attr('data-after-select-fn')] : $.noop;

            if (relativeElement) {
                dependentTypeaheads.push(tg);
            }

            var setNameById = function () {
                var ths = $(this),
                    items = sourceFn(true),
                    elementId = ths.val(),
                    found = false;

                $(items).each(function () {
                    if (this.id === elementId) {
                        tg.val(this.name);

                        found = true;
                        return false;
                    }
                });

                if (!found) {
                    tg.val('');
                }
            };

            tg
                .typeahead({
                    source: function (query, process) {
                        var items = sourceFn();

                        if (relativeElement) {

                            var filteredElements = [],
                                relativeId = relativeElement.val();

                            if (relativeId) {
                                $(items).each(function () {
                                    if (typeof this.relation === 'object') {
                                        if ($.inArray(relativeId, this.relation) !== -1) {
                                            filteredElements.push(this);
                                        }
                                    } else {
                                        if (this.relation === relativeId) {
                                            filteredElements.push(this);
                                        }
                                    }
                                });
                                return process(filteredElements);
                            } else {
                                process(items);
                            }
                        } else {
                            process(items)
                        }
                    },
                    autoSelect: true,
                    minLength: 0,
                    getItemHtml: function (item, highlightedHtml) {
                        var result = '';
                        if (typeof item.icon !== 'undefined' && item.icon) {
                            result = '<span class="' + item.icon + '"></span> ';
                        }
                        result += highlightedHtml;

                        return result;
                    }
                })
                .change(function () {
                    var current = tg.typeahead("getActive");
                    if (current) {
                        $(dependentTypeaheads).each(function () {
                            var dependentTypeahead = $(this);
                            if (targetElement.is(dependentTypeahead.attr('data-relative-element'))) {
                                dependentTypeahead.val('');
                                $(dependentTypeahead.attr('data-target-element')).val('');
                            }
                        });

                        // Some item from your model is active!
                        if (current.name.replace(/[\r\n]+/, '') == tg.val()) {
                            var previousValue = targetElement.val();

                            // set value for hidden input
                            targetElement.val(current.id);

                            if (relativeElement && !relativeElement.val()) {
                                relativeElement
                                    .val(current.relation)
                                    .change();
                            }

                            afterSelectFn({
                                typeaheadElement: tg,
                                targetElement: targetElement,
                                previousValue: previousValue
                            });
                            selectNextFormElement(tg);
                            // This means the exact match is found. Use toLowerCase() if you want case insensitive match.
                        } else {
                            // This means it is only a partial match, you can either add a new item
                            // or take the active if you don't want new items
                        }
                    } else {
                        // Nothing is active so it is a new value (or maybe empty value)
                    }
                })
                .blur(function (e) {
                    var tg = $(e.target),
                        allowNew = tg.attr('data-allow-new') === 'true' ? true : false;

                    if (tg.val() === '') {
                        $(tg.attr('data-target-element')).val('').change();
                    } else if (!allowNew) {
                        setTimeout(function () {
                            if (targetElement) {
                                var proxied = $.proxy(setNameById, targetElement);
                                proxied();
                            }
                        }, 250);
                    }
                })
                .focus(function (e) {
                    var tg = $(e.target);
                    if (tg.val() === '') {
                        tg.typeahead('lookup');
                    }
                });

            if (targetElement) {
                targetElement
                    .on('change', setNameById)
                    .change();
            }
        })
        .addClass('processed-typeaheadElement');
};

dialogFormSubmitInit = function () {
    $('.dialog-form-submit')
        .not('.processed-dialog-form-submit')
        .each(function () {
            $(this).on('click', function () {
                $(this).closest('.modal').find('form.ajax-form').submit();
            });
        })
        .addClass('processed-dialog-form-submit');
        
};

locationReload = function () {
    document.location.reload();
};

wizardInit = function () {
    $('.wizardElement')
        .not('.processed-wizardElement')
        .each(function () {
            var navigation = this.hasAttribute('data-navigation') && this.getAttribute('data-navigation') === 'false' ? false : true;
            $(this).easyWizard({
                'stepsText': '{n} {t}',
                buttonsClass: 'btn btn-default',
                submitButtonClass: 'btn btn-info',
                prevButton: '&lt; Poprzednia',
                nextButton: 'Następna &gt;',
                submitButtonText: 'Zapisz',
                showSteps: navigation
            });
        })
        .addClass('processed-wizardElement');
};

transferValuesInit = function () {
    $('.switch-values')
        .not('.processed-switch-values')
        .each(function () {
            var tg = $(this),
                firstInput = tg.parent().prev().find('input'),
                secondInput = tg.parent().next().find('input');

            tg.on('click', function () {
                var firstVal = firstInput.val(),
                    secondVal = secondInput.val();

                firstInput.val(secondVal);
                secondInput.val(firstVal);
            });
        })
        .addClass('processed-switch-values');
};

examInit = function () {
    $('.examElement')
        .not('.processed-examElement')
        .each(function () {
            var wizardElement = $(this),
                course = wizardElement.closest('.widget'),
                navigation = this.hasAttribute('data-navigation') && this.getAttribute('data-navigation') === 'false' ? false : true,
                progress = course.find('.progress .progress-bar'),
                steps = course.find('.examElement section').size() - 1;

            function updateProgress(step) {
                progress.css('width', step / steps * 100 + '%');
            }

            wizardElement.easyWizard({
                'stepsText': '{n} {t}',
                buttonsClass: 'btn btn-default',
                submitButtonClass: 'btn btn-info',
                prevButton: 'Poprzednie pytanie',
                nextButton: 'Akceptuj odpowiedź',
                submitButtonText: 'Zapisz',
                showSteps: navigation,
                before: function (wizardObj, currentStepObj, nextStepObj) {
                    var selectedAnswer = currentStepObj.find('input[type=checkbox]:checked').size() ? true : false,
                        stepBackward = currentStepObj.prevAll().size() > nextStepObj.prevAll().size(),
                        step = nextStepObj.prevAll().size();

                    if (stepBackward || selectedAnswer) {
                        updateProgress(step);

                        return true;
                    }

                    return false;
                },
                after: function (wizardObj, prevStepObj, currentStepObj) {
                    if (currentStepObj.attr('data-pagination') === 'false') {
                        course.find('.easyWizardButtons').hide();
                    } else {
                        course.find('.easyWizardButtons').show();
                    }
                }
            });

            course.find('.wizard-back-button').on('click', function () {
                wizardElement.easyWizard('prevStep');
            });

            updateProgress(0);
        })
        .addClass('processed-examElement');
};

formProcessCloseModal = function (result) {
    if (result.status) {
        this.closest('.modal').find('button[data-dismiss=modal]').click();
    }
};

formProcessLocationReload = function (result) {
    if (result.status) {
        locationReload();
    }
};

formProcessDialModal = function (result) {
    if (result.status) {
        this.closest('.modal').find('button[data-dismiss=modal]').click();
        dial.lastDialProcessFn(result);
    }
};

ajaxFormInit = function () {
    $('.ajax-form')
        .not('.processed-ajax-form')
        .each(function () {
            var form = $(this),
                processFn = this.hasAttribute('data-process-fn') ? $.proxy(window[this.getAttribute('data-process-fn')], form) : $.noop,
                submitFn = this.hasAttribute('data-submit-fn') ? $.proxy(window[this.getAttribute('data-submit-fn')], form) : null;

            if (submitFn) {
                form.on('submit', submitFn);
            } else {
                form.on('submit', function (e) {
                    e.preventDefault();

                    if (typeof CKEDITOR !== 'undefined') {
                        form.find('[class*="processed-ckeditor"]').each(function () {
                            var textarea = $(this),
                                textareaId = textarea.attr('id'),
                                textareaName = textarea.attr('name');

                            $.each(CKEDITOR.instances, function (index) {
                                if (textareaId.indexOf(index) !== -1
                                    || textareaName.indexOf(index) !== -1) {
                                    //this.updateElement();
                                    textarea.val(CKEDITOR.instances[index].getData());
                                }
                            });
                        });
                    }

                    var submitData = form.serializeObject();

                    $.ajax({
                        method: "POST",
                        url: form.attr('action'),
                        data: submitData,
                        success: $.proxy(defaultAjaxResponseHandler, { processFn: processFn })
                    });
                });
            }
        })
        .addClass('processed-ajax-form');
};

defaultAjaxResponseHandler = function (result) {
    var processFn = typeof this.processFn !== 'undefined' ? this.processFn : $.noop;

    if (typeof result.app !== 'undefined') {
        if (typeof result.app.notification !== 'undefined') {
            var notifyConfig = $.extend({ disappear: 5 }, result.app.notification);
            displayFlashMessage(notifyConfig);
        }

        if (typeof result.app.redirect !== 'undefined') {
            document.location.href = result.app.redirect;
        }

        if (typeof result.app.reload !== 'undefined' && result.app.reload == true) {
            document.location.reload();
        }
    }

    processFn(result);
};

refreshSection = function (result) {
    var tg = $(this),
        elementSelector = tg.attr('data-refresh-element');

    refreshElement(elementSelector);
};

replaceElementFromResult = function (result, elementSelector) {
    $(elementSelector).html($(result).find(elementSelector).html());
    systemAssignHandlers();
};

refreshElement = function (elementSelector, url) {
    url = url | document.location.href;

    $.ajax({
        url: url,
        success: function (result) {
            replaceElementFromResult(result, elementSelector);
            systemAssignHandlers();
        }
    });
};

selectNextFormElement = function (tg) {
    var check = false,
        found = null;

    if (tg.closest('form').size()) {
        $(tg.closest('form').get(0).elements).each(function () {
            if (check && $(this).is('input[type=text], input[type=radio], input[type=checkbox], input[type=file], textarea, select')) {
                found = $(this);
                check = false;
            }
            if (tg.get(0) === this) {
                check = true;
            }
        });

        if (found) {
            setTimeout(function () {
                found.focus();
            }, 20);
        }
    }
};

transferValueInit = function () {
    $('.transfer-value')
        .not('.processed-transfer-value')
        .each(function () {
            var tg = $(this),
                target = $(tg.attr('data-target'));

            if (target.val() === tg.attr('data-value')) {
                if (tg.is('[type=checkbox]')) {
                    tg.prop('checked', true).change();
                } else {
                    tg.prop('selected', true).change();

                    if (tg.closest('.iradio_square-aero').size()) {
                        tg.closest('.iradio_square-aero').addClass('checked');
                    }
                }
            }

            tg.on('click change ifChanged ifChecked', function () {
                var value = tg.attr('data-value');

                if (tg.is(':checkbox') && tg.attr('data-value-on') && tg.attr('data-value-off')) {
                    if (tg.is(':checked')) {
                        value = tg.attr('data-value-on');
                    } else {
                        value = tg.attr('data-value-off');
                    }
                }

                target
                    .val(value)
                    .change();
            });
        })
        .addClass('processed-transfer-value');
};

universalRemoveRowInit = function () {
    $('.remove-row')
        .not('.processed-remove-row')
        .each(function () {
            $(this).on('click', function () {
                var tg = $(this),
                    beforeRemove = tg.attr('data-before-remove-fn') ? $.proxy(window[tg.attr('data-before-remove-fn')], tg) : $.noop,
                    target = tg.closest('.row');

                beforeRemove();
                target.remove();
            });
        })
        .addClass('processed-remove-row');
};

datepickerRelativeInit = function () {
    var assignDateChange = function (target, source, type) {
        target = $(target);
        source = $(source);
        type = 'set' + type + 'Date';

        target
            .on('show', function (e) {
                var date = false;
                if (source.is(':visible') && source.val()) {
                    date = source.bootstrapDP('getDate');
                }
                target.bootstrapDP(type, date);
            })
            .on('change', function () {
                $(this).bootstrapDP('hide');
            });
    };
    $('.datepicker-relative')
        .not('.processed-datepicker-relative')
        .each(function () {
            if (this.hasAttribute('data-date-from')) {
                assignDateChange(this, this.getAttribute('data-date-from'), 'Start');
            }
            if (this.hasAttribute('data-date-to')) {
                assignDateChange(this, this.getAttribute('data-date-to'), 'End');
            }
        })
        .addClass('processed-datepicker-relative');
};


tableMaxStore = {};
tableMaxInit = function () {
    var tableEnableFilters = function (table) {
        if (table.hasClass('filters-enabled')) {
            return;
        }
        table.addClass('filters-enabled');

        var thead = table.find('thead'),
            ths = thead.find('tr:first').children(),
            filtersRow = $('<tr>'),
            filtersCols = new Array(ths.size() + 1).join('<td class="filter-row"></td>');

        filtersRow
            .addClass('filters-row')
            .append(filtersCols);
        thead.prepend(filtersRow);
    };
    var tableSetFilter = function (th, filter) {
        var table = th.closest('table'),
            columnNo = th.prevAll().size(),
            filterContainer = $('<div class="filter-container">'),
            filterCol = table.find('.filters-row').children().eq(columnNo);

        filter
            .addClass('form-control')
            .addClass('filter-item');

        filterContainer.append(filter);

        filterCol.append(filterContainer);

        $(th.get(0).attributes).each(function () {
            if (this.name.match(/^data-/)) {
                filterCol.attr(this.name, this.value);
            }
        });
    };
    var retreiveColumnValues = function (th, itemMode) {
        var table = th.closest('table'),
            columnNo = th.prevAll().size(),
            values = {},
            realValues = {},
            result = '<option value="">wybierz</option><option value="">wszystkie</option>',
            uniqueNames = [],
            key;

        table.find('tbody tr').each(function () {
            var column = $(this).children().eq(columnNo);
            if (itemMode) {
                column.find('.select-item').each(function () {
                    var key = this.hasAttribute('data-select-value') ? this.getAttribute('data-select-value') : $(this).text().trim();
                    key = key.toUpperCase();
                    values[key] = true;
                    realValues[key] = this.hasAttribute('data-value') ? this.getAttribute('data-value') : key;
                });
            } else {
                values[column.text().trim()] = true;
            }
        });
        if ('' in values) {
            delete values[''];
        }

        for (key in values) {
            if (values.hasOwnProperty(key)) {
                uniqueNames.push(key);
            }
        }
        uniqueNames.sort();

        for (key in uniqueNames) {
            if (uniqueNames.hasOwnProperty(key)) {
                result += '<option data-value="' + realValues[uniqueNames[key]] + '">' + uniqueNames[key] + '</option>';
            }
        }

        return result;
    };
    var addDataTableSearchFilter = function (fn) {
        $.fn.DataTable.ext.afnFiltering.push(fn);
    };
    var preInitDatatableEvent = function (e, settings) {
        this.settings = settings;
        this.table = settings.nTable;
        this.wrapper = $(settings.nTableWrapper);

        this.fnUpdateChangeLimit = $.proxy(updateChangeLimit, this);
    };
    var initDatatableEvent = function (e, settings) {
        var table = $(e.target),
            tableWrapper = getTableWrapper(table),
            paginationSection = tableWrapper.find('.dt-pagination'),
            hiddenSection = tableWrapper.find('.dt-hidden'),
            operationsHeader = tableWrapper.prev(),
            operationsFooter = tableWrapper.next(),
            filters = table.find('.filters-row').children();

        table.find('tbody').addClass('ui-widget-content');

        if (operationsHeader.is('.table-operations-header')) {
            operationsHeader.prependTo(tableWrapper.find('.dt-header-operations'));
        }

        if (operationsFooter.is('.table-operations-footer')) {
            operationsFooter
                .prependTo(tableWrapper.find('.dt-footer-operations'))
                .find('.table-row-action')
                .on('click', function (e) {
                    var tg = $(this),
                        triggerFn = this.hasAttribute('data-trigger-fn') ? window[this.getAttribute('data-trigger-fn')] : null,
                        actionUrl = this.hasAttribute('data-action-url') ? this.getAttribute('data-action-url') : null;

                    if (table.find(':checkbox:checked').size() === 0 && $('.js-checkbox-target[value=1]').size() === 0) {
                        return false;
                    }

                    if (triggerFn) {
                        triggerFn();
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                    console.log(actionUrl);
                    if (actionUrl) {
                        var form = tg.closest('form'),
                            definedUrl = form.attr('action');

                        form
                            .attr('action', actionUrl)
                            .submit()
                            .attr('action', definedUrl);

                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                });
        }

        var resetButton = $('<button class="btn btn-warning btn-sm dt-reset-filter-button" data-title="Wyczyszczenie kryteriów wyszukiwania" data-toggle="tooltip"><i class="fa fa-eraser"></i></button>');
        resetButton
            .appendTo(tableWrapper.find('.dt-header-operations'))
            .on('click', $.proxy(actionResetFilters, this));

        if (operationsHeader.find('.dt-toogle-search-button').size()) {
            operationsHeader.find('.dt-toogle-search-button').on('click', function () {
                tableWrapper.closest('.widget').find('.table-operations-search').toggleClass('enabled');
            });
        }

        if (operationsHeader.find('.dt-report').size()) {
            operationsHeader.find('.dt-report').on('click', function () {
                var searchData = getSearchParams(table, 'url'),
                    baseUrl = $(this).attr('data-href');

                if (baseUrl.match(/\?/)) {
                    baseUrl += '&';
                } else {
                    baseUrl += '?';
                }

                document.location.href = baseUrl + searchData;
            });
        }

        if (operationsFooter.find('.dt-select-all-button').size()) {
            operationsFooter.find('.dt-select-all-button').on('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var checkboxes = table.find('tbody td:first-child').find('.icheckbox_square-aero');

                if (checkboxes.filter('.checked').size() === checkboxes.size()) {
                    checkboxes.find('.iCheck-helper').trigger('click');
                } else {
                    checkboxes.filter(':not(.checked)').find('.iCheck-helper').click();
                }
            });
        }

        if (hasEnabledFilters(settings)) {
            $(settings.aoPreSearchCols).each(function (index) {
                if (this.sSearch !== '') {
                    filters.eq(index).find(':input').val(this.sSearch);
                }
            });
        }

        var changeLimitButton = $('<div class="dt-change-limit-section"><div class="input-group"><select class="form-control"></select><span class="input-group-btn show-notify processed" data-notify-text="#notify_trigger_mode_4_day"><button type="button" class="btn btn-default" data-toggle="tooltip" data-placement="left" data-title="'+window.numberOfResultText+'"><i class="fa fa-th-list"></i></button></span></div></div>');
        changeLimitButton
            .appendTo(paginationSection)
            .find('select')
            .on('change', function () {
                hiddenSection
                    .find('.dataTables_length select')
                    .val($(this).val())
                    .change();
            });
        paginationSection.find('.buttons-colvis').tooltip({
            title: 'Wybierz kolumny',
            container: 'body'
        });
        this.fnUpdateChangeLimit();
    };
    var getSearchParams = function (table, mode) {
        var filters = table.find('.filters-row :input'),
            helperForm = $('<form>');

        mode = mode ? mode : 'object';

        filters.each(function () {
            var tg = $(this),
                input = $('<input>').get(0),
                value = null;

            input.name = this.name;

            if (tg.is('select')) {
                var selectedOption = this.options[this.selectedIndex];
                if (selectedOption.hasAttribute('data-value')) {
                    value = selectedOption.getAttribute('data-value');
                }
            }
            if (value === null) {
                value = tg.val();
            }

            input.value = value;

            $(helperForm).append(input);
        });

        return mode === 'url' ? helperForm.serialize() : helperForm.serializeObject();
    };
    var searchDatatableEvent = function (e, settings) {
        var a;
    };
    var drawDatatableEvent = function (e, settings) {
        var tableWrapper = getTableWrapper(e.target);

        tableWrapper.addClass('table-max');

        if (settings.fnRecordsDisplay() === 0) {
            tableWrapper.addClass('no-results');
        } else {
            tableWrapper.removeClass('no-results');
        }

        if (hasEnabledFilters(settings)) {
            tableWrapper.addClass('dt-has-filters');
        } else {
            tableWrapper.removeClass('dt-has-filters');
        }

        this.fnUpdateChangeLimit();
    };
    var hasEnabledFilters = function (settings) {
        return settings.fnRecordsDisplay() !== settings.fnRecordsTotal();
    };
    var getTableWrapper = function (table) {
        return $(table).closest('.dataTables_wrapper');
    };
    var getTableObject = function (table) {
        return tableMaxStore[$(table).attr('data-table-max-id')];
    };
    var actionResetFilters = function (e) {
        e.stopPropagation();
        e.preventDefault();

        $.each(this.settings.aoPreSearchCols, function () {
            this.sSearch = '';
        });
        this.settings.oApi._fnDraw(this.settings);

        this.wrapper.find('.filters-row').find('input, select').val('');

        getTableObject(this.table).fnDraw();
    };
    var updateChangeLimit = function () {
        var select = this.wrapper.find('.dt-change-limit-section select'),
            original = this.wrapper.find('.dt-hidden .dataTables_length select');

        select
            .html(original.html())
            .val(original.val());
    };
    var uniqueId = 0;
    var setFilterName = function (el, filterName) {
        el.attr('name', 'dt-filters[' + filterName + ']');
    };

    /**
     * @TODO
     * przycisk do usuwania filtrów
     * zmiana ilości wyników w paginacji
     * custom buttony na górze
     * custom buttony na dole
     */

    $('table.example')
        .not('.processed-table-max')
        .each(function () {
            var table = $(this),
                tableStore = {};

            ++uniqueId;
            var tableMaxId = 'table-max-instance-' + uniqueId;
            table
                .addClass(tableMaxId)
                .attr('data-table-max-id', tableMaxId);

            table.find('thead th').each(function () {
                var th = $(this),
                    filterType = this.getAttribute('data-filter-type'),
                    filterName = this.getAttribute('data-filter-name'),
                    filter = null,
                    columnNo = th.prevAll().size();

                if (filterType) {
                    tableEnableFilters(table);
                    switch (filterType) {
                        case "string":
                            filter = $('<input type="text" placeholder="' + uiTranslate('wyszukaj') + '">')
                                .on('input', function () {
                                    var dataTable = table.DataTable(),
                                        searchString = escapeRegExp(this.value);

                                    dataTable
                                        .column(columnNo)
                                        .search(searchString, true, false, true)
                                        .draw();
                                });

                            if (filterName) {
                                setFilterName(filter, filterName);
                            }
                            break;
                        case "select-items":
                        case "select":
                            filter = $('<select>')
                                .append(retreiveColumnValues(th, filterType === 'select-items'))
                                .on('change', function () {
                                    var dataTable = table.DataTable();

                                    dataTable
                                        .column(columnNo)
                                        .search($(this).val(), true, false, true)
                                        .draw();
                                });

                            if (filterName) {
                                setFilterName(filter, filterName);
                            }
                            break;
                        case "date-range":
                            filter = $('<div class="filter-data-range input-group input-daterange">');
                            var filterFromId = 'filer-from-id-' + uniqueId,
                                filterToId = 'filer-to-id-' + uniqueId;

                            var filterFrom = $('<input type="text" class="filter-data-range-from datepicker-input datepicker-relative" placeholder="' + uiTranslate('od') + '">')
                                .attr({
                                    id: filterFromId,
                                    'data-date-to': '#' + filterToId
                                })
                                .on('change', function () {
                                    dateStorageCache[this.id] = $(this).bootstrapDP('getDate');

                                    table.DataTable().draw();
                                }).
                                on('input', function () {
                                    $(this).val('');
                                    dateStorageCache[this.id] = null;

                                    table.DataTable().draw();
                                });

                            if (filterName) {
                                setFilterName(filterFrom, filterName + '_from');
                            }

                            var filterTo = $('<input type="text" class="filter-data-range-to datepicker-input datepicker-relative" placeholder="' + uiTranslate('do') + '">')
                                .attr({
                                    id: filterToId,
                                    'data-date-from': '#' + filterFromId
                                })
                                .on('change', function () {
                                    dateStorageCache[this.id] = $(this).bootstrapDP('getDate');

                                    table.DataTable().draw();
                                }).
                                on('input', function () {
                                    $(this).val('');
                                    dateStorageCache[this.id] = null;

                                    table.DataTable().draw();
                                });

                            if (filterName) {
                                setFilterName(filterTo, filterName + '_to');
                            }

                            filter
                                .append(filterFrom)
                                .append(filterTo);

                            if (typeof dateStorageCache === 'undefined') {
                                dateStorageCache = {};
                            }
                            dateStorageCache[filterFromId] = null;
                            dateStorageCache[filterToId] = null;

                            addDataTableSearchFilter(function (oSettings, aData, iDataIndex) {
                                // "date-range" is the id for my input
                                var dateFrom = dateStorageCache[filterFromId],
                                    dateTo = dateStorageCache[filterToId];

                                if (!dateFrom && !dateTo) {
                                    return true;
                                }

                                var year = parseInt(aData[columnNo].substring(0, 4)),
                                    month = parseInt(aData[columnNo].substring(5, 7)) - 1,
                                    day = parseInt(aData[columnNo].substring(8, 10)),
                                    currentDate = new Date(year, month, day);

                                if ((dateFrom && currentDate < dateFrom)
                                    || (dateTo && currentDate > dateTo)) {
                                    return false;
                                }

                                return true;
                            });
                            break;
                    }
                }

                if (filter) {
                    tableSetFilter(th, filter);
                }

            });

            table
                .on('preInit.dt', $.proxy(preInitDatatableEvent, tableStore))
                .on('init.dt', $.proxy(initDatatableEvent, tableStore))
                .on('draw.dt', $.proxy(drawDatatableEvent, tableStore))
                .on('search.dt', $.proxy(searchDatatableEvent, tableStore));

        })
        .addClass('processed-table-max');
};

toggleRelativeInit = function () {
    $('.toggle-relative')
        .not('.processed-toggle-relative')
        .each(function () {
            $(this).on('change', function () {
                var relativeElements = $('.form-group, .relative-element').filter('[data-relation-base="' + this.id + '"]');
                relativeElements
                    .addClass('relativeHidden')
                    .find(':input')
                    .prop('disabled', true)
                    .end()
                    .filter('[data-relation-id="' + this.value + '"], [data-relation-id="all"], [data-relation-ids^="' + this.value + '"], [data-relation-ids$=",' + this.value + '"], [data-relation-ids*=",' + this.value + ',"]')
                    .not('[data-relation-exclude-id="' + this.value + '"]')
                    .removeClass('relativeHidden')
                    .find(':input')
                    .prop('disabled', false);

                relativeElements.filter('option.relativeHidden').each(function () {
                    var opt = $(this);
                    if (opt.closest('select').val() === opt.val()) {
                        opt.closest('select')
                            .val(0);
                    }
                });
            })
                .change();
        })
        .addClass('processed-toggle-relative');
};

clearRelativeInit = function () {
    $('.clear-relative')
        .not('.processed-clear-relative')
        .each(function () {
            $(this).on('change', function () {
                var relativeElements = $('.form-group, .clear-relative-element').filter('[data-clear-relative-base="' + this.id + '"]');
                relativeElements.val('').change();
            });
        })
        .addClass('processed-clear-relative');
};

nestableInit = function () {
    $('.nestable')
        .not('.processed-nestable')
        .each(function () {
            var tg = $(this),
                collapseMode = tg.attr('data-collapse');

            tg
                .nestable({
                    maxDepth: 10,
                    group: false
                })
                .disableSelection();

            var tgNestable = tg.data('nestable');

            if (!collapseMode) {
                if (tg.find('ol:first ol:first').children().size() > 1) {
                    tgNestable.collapseAll();
                    tgNestable.expandItem(tg.find('li:first'));
                }
            } else if (collapseMode === 'expandFirst') {
                tgNestable.collapseAll();
                tgNestable.expandItem(tg.find('li:first'));
            } else if (collapseMode === 'collapseAll') {
                tgNestable.collapseAll();
            }

            var expandItem = function () {
                var item = $(this).closest('.dd-item');
                tgNestable.expandItem(item);
            };

            tg
                .find('.dd-handle')
                .on('click', expandItem);

        })
        .addClass('processed-nestable');
};

selectAllInit = function () {
    $('.select-all')
        .not('.processed-select-all')
        .each(function () {
            var tg = $(this),
                target = $(tg.attr('data-target')),
                selectButton = tg.find('.action-select'),
                deselectButton = tg.find('.action-deselect'),
                selectedSelector = 'input[value=1]',
                deselectedSelector = 'input[value=0]';

            selectButton.on('click', function () {
                target.find(deselectedSelector)
                    .val(1)
                    .change();
                selectButton.hide();
                deselectButton.show();
            });
            deselectButton.on('click', function () {
                target.find(selectedSelector)
                    .val(0)
                    .change();
                selectButton.show();
                deselectButton.hide();
            });
        })
        .addClass('processed-select-all');
};

selectAllCheckboxInit = function () {
    $('.select-all-checkbox')
        .not('.processed-selectAllCheckbox')
        .each(function () {
            var tg = $(this),
                target = $(tg.attr('data-target')),
                selectedSelector = 'input[value=1]',
                deselectedSelector = 'input[value=0]';

            tg.on('click', function () {
                var tgSelector = deselectedSelector,
                    tgVal = 1;

                if (!$(this).is('.checked')) {
                    tgSelector = selectedSelector;
                    tgVal = 0;
                }

                target.find(tgSelector)
                    .val(tgVal)
                    .change();
            });
        })
        .addClass('processed-selectAllCheckbox');
};

showNotifyInit = function () {
    $('.show-notify')
        .not('.processed-show-notify')
        .each(function () {
            var tg = $(this),
                notifyText = $(tg.attr('data-notify-text')),
                notifyPosition = 'top right';

            tg.on('click', function (e) {
                e.stopPropagation();
                $('.notifyjs-wrapper').trigger('notify-hide');

                $(tg).notify({
                    text: notifyText
                }, {
                        style: 'metro',
                        className: 'nonspaced',
                        elementPosition: notifyPosition,
                        showAnimation: "fadeIn",
                        showDuration: 200,
                        hideAnimation: "fadeOut",
                        hideDuration: 100,
                        autoHide: false,
                        clickToHide: true
                    });

                $('body').one('click', function () {
                    $('.notifyjs-wrapper').trigger('notify-hide');
                });
            });
        })
        .addClass('processed-show-notify');
};

confirmDeleteInit = function () {
    $('.confirm-delete')
        .not('.processed-confirm-delete')
        .each(function (e) {
            var tg = $(this),
                confirmationTitle = this.getAttribute('data-confirmation-title');

            tg
                .attr('id', 'confirm-delete-' + uniqueId.get())
                .confirmation({
                    title: confirmationTitle,
                    placement: 'top',
                    container: 'body',
                    btnOkLabel: 'POTWIERDŹ',
                    btnCancelLabel: 'ANULUJ'
                });
        })
        .addClass('processed-confirm-delete');
};

modalConfirmDelete = function () {
    var baseText = '<h3>Potwierdź operację</h3><div>Czy na pewno chcesz usunąć wybrane elementy?</div>';

    $('.modal-confirm-delete')
        .not('.processed-modal-confirm-delete')
        .each(function (e) {
            var tg = $(this),
                cssBase = '.modal-confirm-',
                modalName = this.getAttribute('data-modal-name'),
                textSelector = cssBase + modalName + '-text',
                submitSelector = cssBase + modalName + '-submit';

            tg.on('click', function (e) {
                e.stopImmediatePropagation();
                e.preventDefault();

                var modalText = $(textSelector).size() ? $(textSelector).html() : baseText;

                bootbox.confirm(modalText, function (result) {
                    if (result == true) {
                        $(submitSelector).click();
                    }
                })
            });
        })
        .addClass('processed-modal-confirm-delete');
};

modalConfirmInit = function () {
    var baseTexts = {
        singleDelete: '<h3>Potwierdź operację</h3><div>Czy na pewno chcesz usunąć wybrany element?</div>'
    };

    $('.modal-confirm')
        .not('.processed-modal-confirm')
        .each(function (e) {
            var tg = $(this),
                confirmClass = tg.attr('data-confirmation-class'),
                confirmText = this.hasAttribute('data-confirmation-text') ? this.getAttribute('data-confirmation-text') : baseTexts[confirmClass],
                processMoreFn = this.hasAttribute('data-process-more') ? $.proxy(window[this.getAttribute('data-process-more')], tg) : $.noop;

            tg.on('click api.click', function (e) {
                if (true === getData(e, 'modal-confirm.confirmed')) {
                    return;
                }
                e.stopImmediatePropagation();
                e.preventDefault();

                var href = tg.attr('data-href'),
                    isAjax = tg.attr('data-ajax'),
                    isChain = tg.attr('data-chain');

                bootbox.confirm({
                    message: confirmText,
                    callback: function (result) {
                        if (result == true) {
                            if (isAjax) {
                                $.ajax({
                                    method: 'post',
                                    url: href,
                                    success: function (result) {
                                        defaultAjaxResponseHandler(result);
                                        processMoreFn(result);
                                    }
                                });
                            } else if (isChain) {
                                tg.trigger('click.api', { 'modal-confirm': { confirmed: true } });
                            } else {
                                document.location.href = href;
                            }
                        }
                    }
                })
            });
        })
        .addClass('processed-modal-confirm');
};

var uniqueId = {
    current: 0,
    get: function () {
        return 'uniqueId_' + ++uniqueId.current;
    }
};

timeagoInit = function () {
    $('.timeago')
        .not('.processed-timeago')
        .each(function (e) {
            var tg = $(this);

            tg.timeago();
        })
        .addClass('processed-timeago');
};

showSectionInit = function () {
    $('.show-section')
        .not('.processed-show-section')
        .each(function (e) {
            var tg = $(this),
                target = $(this.getAttribute('data-target'));

            tg.on('click', function () {
                var autoHide = getTargetElement(tg.attr('data-auto-hide'));
                if (autoHide.size()) {
                    tg.closest(autoHide).addClass('hidden');
                } else {
                    tg.addClass('hidden');
                }

                target.removeClass('hidden');

                $('.modal.in').stop().animate({ scrollTop: $('.modal.in').get(0).scrollTop += target.height() });
            });
        })
        .addClass('processed-show-section');
};

inputMaskInit = function () {
    $('.input-mask')
        .not('.processed-input-mask')
        .each(function (e) {
            var tg = $(this),
                mask = this.getAttribute('data-mask');

            tg.inputmask({
                mask: mask
            });
        })
        .addClass('processed-input-mask');
};

datepickerInit = function () {
    $('.datepicker-input')
        .not('.processed-datepicker-input')
        .each(function (e) {
            var tg = $(this);

            tg.bootstrapDP(defaultBootstrapDPSettings);
        })
        .addClass('processed-datepicker-input');
};

datetimepickerInit = function () {
    $('.datetimepicker-input')
        .not('.processed-datetimepicker-input')
        .each(function (e) {
            var tg = $(this);

            tg.datetimepicker({
                format: "YYYY-MM-DD HH:mm:ss",
                locale: 'pl'
            });
        })
        .addClass('processed-datetimepicker-input');
};

handleInit = function () {
    $('.handle')
        .not('.processed-handle')
        .each(function () {
            $(this).on('click', function (e) {
                e.preventDefault();
                $(this).blur();
            })
        })
        .addClass('processed-handle');
};

autoResizeInit = function () {
    $('.auto-resize')
        .not('.processed-auto-resize')
        .each(function () {
            var tg = $(this);

            function resize() {
                var tg = $(this),
                    lines = (tg.val().match(/\n/g) || []).length + 2;

                if (lines < 5) {
                    lines = 5;
                }

                tg.attr('rows', lines);
            }

            tg.on('change input paste', resize);
            ($.proxy(resize, tg))();
        })
        .addClass('processed-auto-resize');
};

widgetCopyValueAsTextInit = function () {
    $('.widget-copy-value-as-text')
        .not('.processed-widget-copy-value-as-text')
        .each(function (e) {
            var tg = $(this),
                target = tg.closest('.widget').find(tg.attr('data-target')),
                filterFn = tg.attr('data-copy-filter-fn') ? window[tg.attr('data-copy-filter-fn')] : function (a) { return a },
                filterFnProxied = $.proxy(filterFn, tg);

            tg.on('input change', function () {
                setTextOrValue(target, filterFnProxied(tg.val().toUpperCase()));
            });
        })
        .addClass('processed-widget-copy-value-as-text');
};

icheckDumbInit = function () {
    //ICHECK

    var elem = $('input:not(.ios-switch, .angular, .noprocess, .processed-icheckDumb)');

    if (elem.parents('.surveyjs').length == 0) {
        elem.each(function () {
            if ($(this).parent().attr('class') != 'checknew') {
                $(this)
                    .iCheck({ checkboxClass: 'icheckbox_square-aero', radioClass: 'iradio_square-aero', increaseArea: '20%' })
                    .addClass('processed-icheckDumb');
            }
        });
    }
};

displayFlashMessage = function (options) {
    var defaults = {
        type: 'info',
        title: 'Notyfikacja',
        text: 'Notyfikacja',
        disappear: false,
        autohide: false,
        icon: 'fa fa-circle-o',
        position: 'top right'
    },
        config = $.extend({}, defaults, options);

    if (config.disappear) {
        config.disappear *= 1000;
        config.autohide = true;
    }

    if (config.type == "error") {
        config.icon = "fa fa-exclamation";
    } else if (config.type == "danger") {
        config.type = 'warning';
        config.icon = "fa fa-warning";
    } else if (config.type == "success") {
        config.icon = "fa fa-check";
    } else if (config.type == "info") {
        config.icon = "fa fa-question";
    }

    $.notify({
        title: config.title,
        text: config.text,
        image: "<i class='" + config.icon + "'></i>"
    }, {
            style: 'metro',
            className: config.type,
            globalPosition: config.position,
            showAnimation: "fadeIn",
            showDuration: 400,
            hideDuration: 400,
            autoHideDelay: config.disappear,
            autoHide: config.autohide,
            clickToHide: true
        });
};

flashMessagesInit = function () {
    $('.flashMessages > div')
        .not('.processed-flashMessages')
        .each(function (e) {
            displayFlashMessage({
                type: this.getAttribute('data-type'),
                title: this.getAttribute('data-title'),
                disappear: parseInt(this.getAttribute('data-disappear')),
                position: this.getAttribute('data-position'),
                text: this.innerHTML,
            });
        })
        .addClass('processed-flashMessages');
};

messagesTableInit = function () {
    var messageList = $('.table-message');
    if (messageList.size() === 0) {
        return;
    }

    function updateCounters(result) {
        $.each(result.tags, function () {
            updateTagCounter(this);
        });

        $.each(result.folders, function () {
            updateFolderCounter(this);
        });
    }

    function updateTagCounter(tag) {
        var tagListItem = $('.messages-tags').find('.list-group-item[data-id=' + tag.id + ']'),
            tagCounter = tagListItem.find('.badge');

        tagCounter.text(tag.unread_counter + ' / ' + tag.messages_counter);

        if (tag.messages_counter > 0) {
            tagListItem.removeClass('hidden');
        } else {
            tagListItem.addClass('hidden');
        }
    }

    function updateFolderCounter(folder) {
        var folderListItem = $('.menu-message').find('.list-group-item[data-id=' + folder.id + ']'),
            folderCounter = folderListItem.find('.badge');

        folderCounter.text(folder.unread_counter);
    }

    function messageDisplayTag(messageId, tagId, mode) {
        var messageRow = messageList.find('.message-row[data-id=' + messageId + ']');
        if (tagId == 1) {
            if (mode === 'add') {
                messageRow
                    .find('.icon-star-empty-1')
                    .removeClass('icon-star-empty-1')
                    .addClass('icon-star-1');
            } else {
                messageRow
                    .find('.icon-star-1')
                    .removeClass('icon-star-1')
                    .addClass('icon-star-empty-1');
            }
        } else {
            var label = messageRow.find('.label[data-id=' + tagId + ']');
            if (mode === 'add') {
                var tagListItem = $('.messages-tags').find('.list-group-item[data-id=' + tagId + ']');
                if (label.size() === 0) {
                    var tagLabel = $('<span>')
                        .addClass('label')
                        .addClass('label-xs')
                        .css('background-color', tagListItem.find('.fa-circle').css('color'))
                        .attr('data-id', tagListItem.attr('data-id'))
                        .text(tagListItem.find('.tag-name').text());

                    messageRow.find('.tags-container').prepend(tagLabel);
                }
            } else {
                if (label.size()) {
                    label.remove();
                }
            }
        }
    }

    function getRowsById(ids) {
        var rows = $();

        $(ids).each(function () {
            var message = messageList.find('.message-row[data-id=' + this + ']');
            if (message.size()) {
                rows = rows.add(message);
            }
        });

        return rows;
    }

    function messagesUpdateTags(ids, tag, mode) {
        $.post('/messages/ajax-message-tag', {
            ids: ids,
            tag: tag,
            mode: mode
        }, function (result) {
            $.each(ids, function () {
                messageDisplayTag(this, tag, mode);
            });
            updateCounters(result);
        });
    }

    function messagesUpdateStatus(ids, status) {
        $.post('/messages/ajax-message-status', {
            ids: ids,
            status: status
        }, function (result) {
            var rows = getRowsById(ids);

            switch (status) {
                case 'read':
                    rows.removeClass('unread');
                    break;
                case 'unread':
                    rows.addClass('unread');
                    break;
                case 'trash':
                case 'untrash':
                    rows.remove();
                    break;
            }

            updateCounters(result);
        });
    }

    function getSelectedIds() {
        var ids = [];

        messageList.find('.message-row input[type=hidden]').filter('[value=1]').each(function () {
            ids.push(this.name.match(/[0-9]+/)[0]);
        });

        return ids;
    }

    function getAllIds() {
        var ids = [];

        messageList.find('.message-row').each(function () {
            ids.push(this.getAttribute('data-id'));
        });

        return ids;
    }

    function deselectAll() {
        messageList.find('.message-row input[type=hidden]').filter('[value=1]').each(function () {
            $(this).next().click();
        });
    }

    $('.message-mark-favourite')
        .not('.processed-message-mark-favourite')
        .each(function (e) {
            var tg = $(this),
                messageId = tg.closest('.message-row').attr('data-id');

            tg
                .on('click', function () {
                    var mode = tg.find('.icon-star-empty-1').size() ? 'add' : 'remove';

                    messagesUpdateTags([messageId], 1, mode);
                });
        })
        .addClass('processed-message-mark-favourite');

    $('.messages-add-tag')
        .not('.processed-messages-add-tag')
        .each(function (e) {
            var tg = $(this),
                tagId = tg.attr('data-id');

            tg
                .on('click', function () {
                    var mode = 'add';
                    var ids = [];

                    messageList.find('.message-row input[type=hidden]').filter('[value=1]').each(function () {
                        ids.push(this.name.match(/[0-9]+/)[0]);
                    });

                    messagesUpdateTags(ids, tagId, mode);
                });
        })
        .addClass('processed-messages-add-tag');

    $('.message-selection-status')
        .not('.processed-message-selection-status')
        .each(function (e) {
            var tg = $(this),
                status = tg.attr('data-status');

            tg
                .on('click', function () {
                    var ids = getSelectedIds();

                    messagesUpdateStatus(ids, status);
                    deselectAll();
                });
        })
        .addClass('processed-message-selection-status');

    $('.message-all-status')
        .not('.processed-message-all-status')
        .each(function (e) {
            var tg = $(this),
                status = tg.attr('data-status');

            tg
                .on('click', function () {
                    var ids = getAllIds();

                    messagesUpdateStatus(ids, status);
                });
        })
        .addClass('processed-message-all-status');
};

desktopNotifications = (function () {

    if (typeof Notification === 'undefined') {
        return;

    }

    var exp = {};
        config = {
            refreshTime: 30 * 1000
        };
        storage = {
            hasUnreadKomunikat: false
        };
        notifications = {
            unreadKomunikat: ['Nowy komunikat', 'Nowy komunikat'],
            lastMessageDate: ['Nowa wiadomość', 'Nowa wiadomość', '/messages'],
            lastTaskId: ['Nowe zadanie', 'Nowe zadanie', '/tasks-my'],
        };

        Notification = window.Notification || window.mozNotification || window.webkitNotification;

    ajaxHeartbeat = function () {

        $.ajax({
            method: 'get',
            url: '/service/heartbeat',
            success: function (response) {
                if (response.lastMessageDate !== storage.lastMessageDate && response.lastMessageDate > 0) {
                    exp.show(notifications.lastMessageDate[0], notifications.lastMessageDate[1], notifications.lastMessageDate[2]);
                }

                if (response.hasUnreadKomunikat && !storage.hasUnreadKomunikat) {
                    exp.show(notifications.unreadKomunikat[0], notifications.unreadKomunikat[1]);
                    storage.hasUnreadKomunikat = true;
                    getKomunikatWidget();
                }

                if (response.sessionExpiredAt) {
                    logoutDate = new Date(response.sessionExpiredAt * 1000);
                } else {
                    logoutDate = new Date(0);
                }

                if (logoutDate > new Date()) {
                    closeReloginWidget();
                }

                storage.lastMessageDate = response.lastMessageDate;
            }
        });
    };

    exp.show = function (title, message, url) {
        var instance = new Notification(
            title, {
                body: message
            }
        );

        instance.onclick = function () {
            window.focus();
            if (typeof this.cancel !== 'undefined') {
                this.cancel();
            }
            if (typeof this.close !== 'undefined') {
                this.close();
            }

            if (url) {
                // wyłączone, bo może usunąć niezapisane dane w formularzu
                // document.location.href = url;
            }
        };
        instance.onerror = function () {
            // Something to do
        };
        instance.onshow = function () {
            // Something to do
        };
        instance.onclose = function () {
            // Something to do
        };

        return false;
    };

    $(function () {
        storage = $.extend({}, storage, {
            lastMessageDate: lastMessageDate
        });

        // initialization
        setInterval(ajaxHeartbeat, config.refreshTime);
        $(window).on('focus', ajaxHeartbeat);
        ajaxHeartbeat();
        Notification.requestPermission(function (permission) {
            // console.log(permission);
        });
    });

    return exp;
})();

forceFullScreenInit = function () {
    $('.flashMessages > div')
        .not('.processed')
        .each(function () {
            $('.force-fullscreen');
        })
        .addClass('.processed');
};

getIdsFromCheckboxesHelper = function (data) {
    var result = [];

    $.each(data, function (index, value) {
        if (value) {
            result.push(index);
        }
    });

    return result;
};

// not working, cant assign handlers
getModal = function (params) {
    params = $.extend({
        id: null,
        class: null
    }, params);

    var modalElement = $('<div>'),
        id = params.id ? params.id : 'modal-generated-' + uniqueId.get();

    modalElement
        .attr('id', id)
        .addClass(params.class)
        .append('<div class="md-content">');

    $('body').append(modalElement);

    return $('#' + id);
};

// defines end
// ------
// ------

// DataTable initializer
initializeDatatables = function () {

    var languagePack = {
        pl: {
            "processing": "Proszę czekać...",
            "lengthMenu": "Pokaż _MENU_ pozycji",
            "zeroRecords": "Nie znaleziono rekordów spełniających podane kryteria wyszukiwania",
            "emptyTable": "Brak rekordów",
            "infoEmpty": "Brak rekordów",
            "info": "Rekordy <b>_START_</b><span class=\"three-dot\">…</span><b>_END_</b> z _TOTAL_",
            "infoFiltered": "(filtrowanie z _MAX_ dostępnych)",
            "infoPostFix": "",
            "search": "Szukaj:",
            "url": "",
            "paginate": {
                "first": "Pierwsza",
                "previous": "Poprzednia",
                "next": "Następna",
                "last": "Ostatnia"
            }
        },
        en: {
            "processing": "Please wait...",
            "lengthMenu": "Show _MENU_",
            "zeroRecords": "No records found matching the search criteria",
            "emptyTable": "No records",
            "infoEmpty": "No records",
            "info": "Display <b>_START_</b><span class=\"three-dot\">…</span><b>_END_</b> from _TOTAL_",
            "infoFiltered": "(filtered from _MAX_)",
            "infoPostFix": "",
            "search": "Search:",
            "url": "",
            "paginate": {
                "first": "First",
                "previous": "Last",
                "next": "Next",
                "last": "Last"
            }
        }
    };

    $('.example, #example')
        .not('.bootstrap-table-initialized')
        .each(function () {
            var tg = $(this),
                sortByColumn = 0,
                ths = tg.find('thead th'),
                thSize = ths.size(),
                sortingOption = [],
                dataFirstColumn = tg.find('tbody td:first-child'),
                dataSortColumn,
                colvisInclude = [],
                ajaxSourceUrl = tg.attr('data-source-url') ? tg.attr('data-source-url') : null;

            if (tg.find('th.defaultSort').size()) {
                sortByColumn = tg.find('th.defaultSort').prevAll().size();
            }

            if (thSize) {
                // preserve default dt order
                for (var i = 0; i < thSize; i++) {
                    sortingOption.push({});
                }
                ths.each(function () {
                    var operationsTdIndex = $(this).prevAll().size();
                    if ($(this).attr('data-visible') === 'false') {
                        sortingOption[operationsTdIndex]['bVisible'] = false;
                    }
                    if ($(this).attr('data-visible') !== 'always') {
                        colvisInclude.push(operationsTdIndex);
                    }
                    if ($(this).attr('data-column-name')) {
                        sortingOption[operationsTdIndex]['data'] = $(this).attr('data-column-name');
                    }
                    if ($(this).attr('data-column-class')) {
                        sortingOption[operationsTdIndex]['className'] = $(this).attr('data-column-class');
                    }
                });
                ths.filter('[data-disable-sort]').each(function () {
                    var operationsTdIndex = $(this).prevAll().size();
                    sortingOption[operationsTdIndex]['bSortable'] = false;
                });
                if (tg.find('.operations').size()) {
                    var operationsTdIndex = tg.find('.operations').eq(0).prevAll().size();
                    sortingOption[operationsTdIndex]['bSortable'] = false;
                    colvisInclude.splice(colvisInclude.indexOf(operationsTdIndex), 1);
                }
                if (dataFirstColumn.find('input[type=checkbox], .iCheck-helper').size()) {
                    sortingOption[0]['bSortable'] = false;
                    sortByColumn = 1;
                    colvisInclude.splice(colvisInclude.indexOf(0), 1);
                    ths.eq(0).addClass('min-width');
                }
                if (sortByColumn === 0) {
                    dataSortColumn = dataFirstColumn;
                } else {
                    dataSortColumn = tg.find('tbody td:nth-child(' + (sortByColumn + 1) + ')');
                }
                if (dataSortColumn.text().match(/^[0-9 ]+$/)) {
                    sortByColumn++;
                }
            } else {
                sortingOption = null;
            }

            var sortBy = [[sortByColumn, 'asc']];

            var processing = false,
                serverSide = false;
            if (ajaxSourceUrl) {
                processing = true;
                serverSide = true;
            }

            var dtObject = tg.dataTable({
                fnDrawCallback: function () {
                    tg.find('input:not(.ios-switch, .angular)').each(function () {
                        if ($(this).parent().attr('class') != 'checknew' && !$(this).parent().hasClass('icheckbox_square-aero')) {
                            $(this).iCheck({ checkboxClass: 'icheckbox_square-aero', radioClass: 'iradio_square-aero', increaseArea: '20%' });
                        }
                    });
                    //systemAssignHandlers();
                },
                ajax: ajaxSourceUrl,
                processing: processing,
                serverSide: serverSide,
                buttons: [{
                    extend: 'colvis',
                    text: '<i class="fa fa-eye"></i>',
                    columns: colvisInclude
                }, {
                    extend: 'pdf',
                    title: 'PDF',
                    exportOptions: {
                        columns: "thead th:not(:last-child)"
                    }
                }, {
                    extend: 'excel',
                    title: 'XLS',
                    exportOptions: {
                        columns: "thead th:not(:last-child)"
                    }
                }],
                order: sortBy,
                aoColumns: sortingOption,
                lengthMenu: [10, 25, 50, 100, 1000],
                sDom: "<'dt-hidden hiddenElement'l><'row dt-header'<'col-md-6 dt-header-operations'i><'col-md-6 dt-pagination'pB>>rt<'row dt-footer'<'col-md-6 dt-footer-operations'><'col-md-6 dt-pagination'p>>",
                //"sDom": "<'row'<'col-md-8'l><'col-md-4'f>r>t<'row'<'col-md-6'i><'col-md-6'p>>",
                sPaginationType: "listbox",
                language: languagePack[uiLanguage],
                responsive: true
            });

            if (tg.attr('data-table-max-id')) {
                tableMaxStore[tg.attr('data-table-max-id')] = dtObject;
            }
        })
        .addClass('bootstrap-table-initialized');
};

dtClearAllStateCache = function () {
    var storages = ['localStorage', 'sessionStorage'];
    for (var storageType in storages) {
        if (storages.hasOwnProperty(storageType)) {
            var storage = storages[storageType];
            if (storage in window) {
                var storageObject = window[storage];
                for (var storageKey in storageObject) {
                    if (storageObject.hasOwnProperty(storageKey)) {
                        if (storageKey.match(/^DataTables_/)) {
                            try {
                                storageObject.removeItem(storageKey);
                            } catch (ex) {
                            }
                        }
                    }
                }
            }
        }
    }
};

extraOrderInit = function () {
    $('select.extra-order')
        .not('.processed-extra-order')
        .each(function (e) {
            var tg = $(this),
                originalOptions = tg.children().not('.extra-order-skip'),
                orderedOptions = originalOptions.get();

            orderedOptions.sort(function (a, b) {
                return a.textContent.localeCompare(b.textContent);
            });

            originalOptions.remove();
            tg.append(orderedOptions);
        })
        .addClass('processed-extra-order');
};

getPlainText = function (string) {
    var helper = $('<div>');
    helper.html(string);
    return helper.text();
};

setTextOrValue = function (tg, value) {
    if (tg.is(':input')) {
        tg.val(value);
    } else {
        tg.html(value);
    }
};

var widget = (function () {
    var widget = {},
        definitions = {},
        loadedScripts = [],
        blockInit = [];

    var defaultDefinition = {
        class: undefined,
        scripts: [],
        initElement: function () { },
        init: function () { },
        afterInit: function () { },
        scriptsAreLoaded: false,
        scriptsAreLoading: false,
        initialized: false,
        autoinit: true,
        blockInit: []
    };

    widget.push = function (def) {
        var definition = $.extend({}, defaultDefinition, def);

        if (definition.scripts.length == 0) {
            definition.scriptsAreLoaded = true;
        }

        definition.classSelector = '.' + definition.class;
        definition.classProcessed = 'processed-' + definition.class;
        definition.classSelectorProcessed = '.' + definition.classProcessed;

        definition.proxyInit = $.proxy(definition.init, definition);
        definition.proxyAfterInit = $.proxy(definition.afterInit, definition);
        definition.proxyInitElement = $.proxy(definition.initElement, definition);

        definitions[definition.class] = definition;

        $.each(definition.blockInit, function (idx, blockClass) {
            if (typeof blockInit[blockClass] === 'undefined') {
                blockInit[blockClass] = [];
            }
            blockInit[blockClass].push(definition.class);
        });
    };

    widget.initializer = function () {
        initializer(definitions, true, $(document), {});
    };

    widget.initialize = function (_definitions, context, parameters) {
        var filteredDefinitions = [];
        context = context || $(document);
        parameters = parameters || {};

        $.each(_definitions, function () {
            if ('string' === $.type(this)) {
                filteredDefinitions.push(definitions[this]);
            } else {
                filteredDefinitions.push(this);
            }
        });
        initializer(filteredDefinitions, false, context, parameters)
    };

    var initializer = function (_definitions, checkAutoinit, context, parameters, loopGuard) {
        var repeat = false;
        if (undefined === loopGuard) {
            loopGuard = 0;
        }

        $.each(_definitions, function () {
            if (checkAutoinit && !this.autoinit) {
                return;
            }
            var go = true;

            if (typeof blockInit[this.class] !== 'undefined' && !testBlockInit(this, context)) {
                repeat = true;
                go = false;
            }

            go && initializeDefinition(this, context, parameters);
        });

        if (repeat && loopGuard < 500) {
            setTimeout(function () {
                console.log('repeating');
                initializer(_definitions, checkAutoinit, context, parameters, loopGuard);
            }, 100);
        }
    };

    var testBlockInit = function (definition, context) {
        var go = true,
            testContext = context.find(definition.classSelector).not(definition.classProcessed);

        if (typeof blockInit[definition.class] !== 'undefined') {
            $.each(blockInit[definition.class], function (__idx, blockerClass) {
                var blockerDefinition = definitions[blockerClass],
                    contextElements = testContext.find(blockerDefinition.classSelector),
                    notProcessedContextElements = contextElements.not(blockerDefinition.classSelectorProcessed);

                if (contextElements.size() && notProcessedContextElements.size()) {
                    go = false;
                    return false;
                }
            });
        }

        return go;
    };

    var loadScript = widget.loadScript = function (url, callback) {
        var deferred = $.Deferred(function () {
            var tagName = url.match(/.css$/) ? 'link' : 'script',
                element = document.createElement(tagName);
            callback = callback || $.noop;

            if ('link' === tagName) {
                element.type = 'text/css';
                element.rel = 'stylesheet';
                element.href = url;
            } else {
                element.type = "text/javascript";
                element.src = url;
            }

            if (element.readyState) { // IE
                element.onreadystatechange = function () {
                    if (element.readyState == "complete") {
                        element.onreadystatechange = null;
                        callback();
                        deferred.resolve();
                    }
                };
            } else { // Others
                element.onload = function () {
                    callback();
                    deferred.resolve();
                };
            }

            element.onerror = function () {
                deferred.reject();
            };

            if (typeof asdasd === 'undefined') {
                asdasd = [];
            }
            if (-1 !== $.inArray(element.src, asdasd)) {
                var i;
                console.log('duplicate');
            }
            asdasd.push(element.src);


            console.log('Load script', element.src);
            document.getElementsByTagName("head")[0].appendChild(element);
        });

        return deferred;
    };

    var initializeDefinition = function (definition, context, parameters) {
        var elements = context.find(definition.classSelector)
            .not(definition.classSelectorProcessed);

        if (elements.size()) {
            if (!definition.scriptsAreLoaded) {
                if (!definition.scriptsAreLoading) {
                    definition.scriptsAreLoading = true;
                    loadScripts(definition.scripts, afterLoadScripts(definition.class));
                }
            } else {
                if (!definition.initialized) {
                    definition.proxyInit();
                    definition.initialized = true;
                    definition.proxyAfterInit();
                }

                $.each(elements, function () {
                    initElement(definition, $(this), context, parameters);
                });
            }
        }
    };

    var initElement = function (definition, element, context, parameters) {
        definition.proxyInitElement(element, context, parameters);

        element.addClass(definition.classProcessed);
    };

    var loadScriptsSync = function (list, doneFn) {
        $.each(list, function () {
            loadedScripts.push(this)
        });
        loadScript(list[0],
            function () {
                list.shift();
                if (list.length > 1) {
                    loadScriptsSync(list, doneFn);
                } else if (list.length === 1) {
                    if (list[0] instanceof Array) {
                        loadScriptsAsync(list[0], doneFn);
                    } else {
                        doneFn();
                    }
                }
            }
        );
    };

    var loadScriptsAsync = function (asyncList, doneFn) {
        $.each(asyncList, function () {
            loadedScripts.push(this)
        });
        var doLoadAsync = [];

        $.each(asyncList, function () {
            if (-1 === loadedScripts.indexOf(this)) {
                doLoadAsync.push(this);
            }
        });

        if (0 === doLoadAsync.length) {
            return doneFn();
        }

        var _promises = $.map(doLoadAsync, function (src) {
            loadScript(src);
        });

        _promises.push($.Deferred(function (deferred) {
            $(deferred.resolve);
        }));

        $.when.apply($, _promises).done(doneFn);
    };

    var loadScripts = function (scripts, doneFn) {
        var syncList = [],
            asyncList = [];

        if (scripts instanceof Array) {
            asyncList = scripts.slice(0);
        } else {
            if ('undefined' !== typeof scripts.async) {
                asyncList = scripts.async.slice(0);
            }
            if ('undefined' !== typeof scripts.sync) {
                syncList = scripts.sync.slice(0);
            }
        }

        if (syncList.length) {
            syncList.push(asyncList);
            return loadScriptsSync(syncList, doneFn);
        } else if (asyncList.length) {
            return loadScriptsAsync(asyncList, doneFn);
        } else {
            return doneFn();
        }
    };

    var afterLoadScripts = function (_class, context, parameters) {
        return function () {
            definitions[_class].scriptsAreLoaded = true;
            widget.initialize([_class], context, parameters);
        }
    };

    return widget;
})();

ckeditorLiveSaveIntegration = function (editor, tg) {
    if (tg.closest('.live-save').size()) {
        timeouter.registerStorage('live-save-ckeditor', true, { unloadBefore: ['live-save'] });

        editor.on('key', function () {
            timeouter.add('live-save-ckeditor', tg.prop('id'), function () {
                editor.updateElement();
                tg.change();
            }, 2000);
        });
    }
};
ckeditorAutohide = function (editor) {
    var queueName = 'ckeditor.autohide';

    editor.on('focus', function (e) {
        var tg = $(e.editor.container.$),
            top = tg.find('.cke_top'),
            bottom = tg.find('.cke_bottom-wrapper'),
            contents = tg.find('.cke_contents'),
            topHeight = top.height(),
            bottomHeight = bottom.height(),
            contentsHeight = contents.height();

        if (!top.queue().length) {
            top.css({ height: 0, display: 'block', bottom: contentsHeight });
        } else {
            top.css({ height: 'auto' });
            topHeight = top.height();
        }
        top.stop().animate({ height: topHeight, bottom: contentsHeight });

        bottom
            .css({ height: 0, display: 'block', bottom: 0 })
            .animate({ height: bottomHeight, bottom: -bottomHeight });
    });
    editor.on('blur', function (e) {
        var tg = $(e.editor.container.$),
            top = tg.find('.cke_top'),
            bottom = tg.find('.cke_bottom-wrapper'),
            contents = tg.find('.cke_contents'),
            contentsHeight = contents.height();

        top
            .stop()
            .animate({ height: 0, bottom: contentsHeight }, 400, 'swing', function () {
                top.css({ height: 'auto', display: 'none' });
            });

        bottom.animate({ height: 0, bottom: 0 }, 400, 'swing', function () {
            bottom.css({ height: 'auto', display: 'none' });
        });
    });
    editor.on('loaded', function (e) {
        var tg = $(e.editor.container.$);
        tg.find('.cke_top').hide();
        tg.find('.cke_bottom').wrap($('<div class="cke_bottom-wrapper">').hide());

        tg.find('.cke_top, .cke_bottom')
            .on('click', function (e) {
                if (e.target == this) {
                    editor.focus();
                }
            });
    });

    editorResizeHandler = function (e) {
        var tg = $(e.editor.container.$),
            top = tg.find('.cke_top'),
            bottom = tg.find('.cke_bottom-wrapper'),
            contents = tg.find('.cke_contents'),
            contentsHeight = contents.height();

        top.css('bottom', contentsHeight);
    };
    editor.on('resize', editorResizeHandler);
    editor.on('autoGrow', editorResizeHandler);
};

ckeditorAssets = {
    sync: [
        '/assets/plugins/ckeditor.4.4/ckeditor.js',
        '/_gfx/js/plugins/ckeditor.js'
    ]
};
ckeditorDefaults = {
    filebrowserBrowseUrl: '/elfinder/browser',
    fullPage: false,
    entities_additional: '',
    filebrowserImageBrowseUrl: '/elfinder/browser?type=image',
    removeDialogTabs: 'link:upload;image:upload',
    toolbarGroups: [
        { name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
        { name: 'colors' },
        { name: 'clipboard', groups: ['clipboard', 'undo'] },
        { name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align'] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'styles' },
        { name: 'tools' },
        { name: 'others' },
        { name: 'document', groups: ['mode'] },
    ],
    extraPlugins: 'autogrow',
    removeButtons: 'Flash,Iframe,Fullscren',
    height: 80,
    autoGrow_minHeight: 80,
    autoGrow_maxHeight: 300,
    autoGrow_onStartup: true,

    skin: 'office2013',
    language: uiLanguage,
    toolbarCanCollapse: false,
    sharedSpaces: { top: 'tahighlightsTBdiv' }
};

widget.push({
    class: 'ckeditor-extended',
    scripts: ckeditorAssets,
    blockInit: ['live-save'],
    initElement: function (tg) {
        var editor = CKEDITOR.replace(tg.get(0), ckeditorDefaults);

        ckeditorLiveSaveIntegration(editor, tg);
        ckeditorAutohide(editor);
    }
});

widget.push({
    class: 'ckeditor-default',
    scripts: ckeditorAssets,
    blockInit: ['live-save'],
    initElement: function (tg) {
        if($(tg.get(0)).val() == ''){
            $(tg.get(0)).val($("<div/>").html($(tg.get(0)).attr('value')).text());
        }
        var editor = CKEDITOR.replace(tg.get(0), ckeditorDefaults);

        ckeditorLiveSaveIntegration(editor, tg);
        ckeditorAutohide(editor);
    }
});


widget.push({
    class: 'live-save',
    init: function () {
        timeouter.registerStorage('live-save', true);
    },
    initElement: function (tg) {
        var elements = tg.find('input, select, textarea'),
            formId = uniqueId.get(),
            saveFunction = window[tg.attr('data-save-fn')];

        elements.addClass('live-save-field');
        tg.attr('data-unique-id', formId);

        widget.initialize(['live-save-field'], tg, {
            formId: formId,
            saveFunction: saveFunction
        });
    }
});
widget.push({
    class: 'live-save-field',
    autoinit: false,
    initElement: function (tg, form, parameters) {
        tg.on('live change input select', function () {
            timeouter.add('live-save', parameters.formId, function () {
                parameters.saveFunction(form);
            }, 2000);
        });
    }
});

widget.push({
    class: 'remove-element',
    initElement: function (tg) {
        var closestElement = tg.closest(tg.attr('data-closest-target') ? tg.attr('data-closest-target') : document),
            targetElement = tg.attr('data-target'),
            target = targetElement ? closestElement.find(targetElement) : closestElement;

        tg.on('click', function () {
            target.remove();
        });
    }
});

widget.push({
    class: 'toggle-collapse',
    initElement: function (tg) {
        var targetElement = $(tg.attr('data-target'));

        tg.on('click', function (e) {
            if (e.target == this) {
                targetElement.toggleClass('in');
            }
        });
    }
});

widget.push({
    class: 'relative-select',
    initElement: function (tg) {
        var sourceElement = $(tg.attr('data-source-element'));

        sourceElement.on('change', function () {
            var selectedId = sourceElement.val();

            if (selectedId === '') {
                tg.children().show();
            } else {
                tg.children().each(function () {
                    if (this.getAttribute('data-relative-id') == selectedId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        sourceElement.change();
    }
});


widget.push({
    class: 'ajax-operation',
    initElement: function (tg) {
        var processMoreFn = tg.attr('data-process-more') ? $.proxy(window[tg.attr('data-process-more')], tg) : $.noop;

        tg.on('click click.api', function (e) {
            e.stopImmediatePropagation();
            e.preventDefault();

            var href = tg.attr('data-href'),
                isAjax = tg.attr('data-ajax'),
                isChain = tg.attr('data-chain');

            if (isAjax) {
                $.ajax({
                    method: 'post',
                    url: href,
                    success: function (result) {
                        defaultAjaxResponseHandler(result);
                        processMoreFn(result);
                    }
                });
            } else if (isChain) {
                tg.trigger('click.api', { 'modal-confirm': { confirmed: true } });
            } else {
                document.location.href = href;
            }
        });
    }
});

bootstrapCalendarScripts = {
    sync: [
        uiLanguage === 'pl' ? '/assets/plugins/bootstrap-calendar-0.2.5/js/language/pl-PL.js' : '/assets/plugins/bootstrap-calendar-0.2.5/js/language/pl-PL.js',
        '/assets/plugins/bootstrap-calendar-0.2.5/js/calendar.js'
    ],
    async: ['/assets/plugins/bootstrap-calendar-0.2.5/css/calendar.css']
};

widget.push({
    class: 'calendar-max',
    scripts: bootstrapCalendarScripts,
    initElement: function (tg) {
        var widget = tg.closest('.widget'),
            buttons = widget.find('.btn-group button');

        var options = {
            language: uiLanguage === 'pl' ? 'pl-PL' : 'en-EN',
            tmpl_path: "/_gfx/plugins/bootstrap-calendar-0.2.5/tmpls/",

            events_source: '/ajax/get-calendar-home-events',

            view: 'month',
            events_cache: true,
            tmpl_cache: false,
            // day: '2015-03-12',
            onAfterEventsLoad: function (events) {
                console.log('onAfterEventsLoad');
                if (!events) {
                    return;
                }
                var list = $('#eventlist');
                list.html('');

                $.each(events, function (key, val) {
                    $(document.createElement('li'))
                        .html('<a href="' + val.url + '">' + val.title + '</a>')
                        .appendTo(list);

                    if (typeof val.data !== 'undefined') {
                        var innerHtml = '';
                        $.each(val.data, function (dataKey, dataVal) {
                            innerHtml += ' data-' + dataKey + '="' + dataVal + '"';
                        });
                        val['tagInnerHtml'] = innerHtml;
                    }
                });
            },
            onAfterViewLoad: function (view) {
                console.log('onAfterViewLoad');
                tg.closest('.widget').find('h3 span:first').text(this.getTitle());
                buttons.removeClass('active');
                buttons.filter('[data-calendar-view="' + view + '"]').addClass('active');

                systemAssignHandlers();
            },
            classes: {
                months: {
                    general: 'label'
                }
            }
        };

        setTimeout(function () {
            var calendar = tg.calendar(options);

            buttons.filter('[data-calendar-nav]').each(function () {
                var $this = $(this);
                $this.click(function () {
                    calendar.navigate($this.data('calendar-nav'));
                });
            });

            buttons.filter('[data-calendar-view]').each(function () {
                var $this = $(this);
                $this.click(function () {
                    calendar.view($this.data('calendar-view'));
                });
            });
        }, 1);

        console.log('end');

    }
});

widget.push({
    class: 'multiple-row',
    initElement: function (tg) {
        var wrapper = tg.parent(),
            sourceInputSelector = tg.attr('data-source-input'),
            addRowButton = tg.find('.add-row'),
            removeRowButton = tg.find('.row-remove');

        var addRow = function (objectData) {
            var lastSibling = wrapper.children().eq(-1);

            if (lastSibling.size() && lastSibling.find(sourceInputSelector).val().length) {
                var cloned = tg.clone(false),
                    newElementId = $.type(objectData) === 'object' ? objectData.id : 'new-' + getNextId(),
                    sourceInput = cloned.find(sourceInputSelector);

                //systemReplaceIdentifiers(cloned, 'new-' + sourceRowId, 'new-' + objectData.id);
                systemRemoveProcessedState(cloned);

                cloned.insertAfter(tg);
                sourceInput
                    .val('')
                    .attr('name', sourceInput.attr('name').replace(/\[([^\]]*)]([^\[]*)$/, '[' + newElementId + ']$2'))
                    .change();

                systemAssignHandlers();
                cloned.find('.add-row').on('click', addRowButtonHandler);
                cloned.find('.row-remove').on('click', removeRow);
            }
        };

        var addRowButtonHandler = function () {
            addRow();
        };

        var getNextId = function () {
            var top = 0;
            wrapper.find('[name*="new-"]').each(function () {
                var current = this.name.match(/new-([0-9]+)/)[1];
                if (current > top) {
                    top = current;
                }
            });

            return parseInt(top) + 1;
        };

        var removeRow = function () {
            var rtg = $(this);
            rtg.closest('.multiple-row').remove();
        };

        tg.on('live change input select', addRowButtonHandler);
        addRowButton.on('click', addRowButtonHandler);
        removeRowButton.on('click', removeRow);
    }
});

widget.push({
    // remove tabs linking to undefined tab content
    class: 'nav-tabs',
    initElement: function (tg) {
        var elements = tg.find('a[href]'),
            lis = elements.closest('li');

        if (!lis.filter('.active').size()) {
            lis.eq(0).addClass('active');
        }

        elements.each(function () {
            var anhor = $(this),
                hashStart = this.href.indexOf('#'),
                target = $(this.href.slice(hashStart));

            if (!target.size()) {
                anhor.closest('li').remove();
            } else {
                if (anhor.closest('li').hasClass('active')) {
                    target.addClass('active');
                }
            }
        });
    }
});

widget.push({
    class: 'mirror-value',
    initElement: function (tg) {
        var target = $(tg.attr('data-target'));

        tg.val(target.val());

        tg.on('live change input', function () {
            target.val(tg.val());
        });
    }
});

widget.push({
    class: 'multichoose-element',
    initElement: function (tg) {
        tg.on('live change input select', function () {
            timeouter.add('live-save', parameters.formId, function () {
                parameters.saveFunction(form);
            }, 2000);
        });
    }
});


liveSaveInit = function () {
    timeouter.registerStorage('live-save', true);

    $('.live-save')
        .not('.processed-live-save')
        .each(function () {
            var tg = $(this),
                elements = tg.find('input, select, textarea');

            elements.addClass('live-save-field');
            tg.attr('data-unique-id', uniqueId.get());
        })
        .addClass('processed-live-save');

    $('.live-save-field')
        .not('.processed-live-save-field')
        .each(function () {
            var tg = $(this),
                form = tg.closest('.live-save'),
                saveFunction = window[form.attr('data-save-fn')];

            tg.on('live change input select', function () {
                timeouter.add('live-save', form.attr('data-unique-id'), function () {
                    saveFunction(form);
                }, 2000);
            });
        })
        .addClass('processed-live-save-field');
};

// jQuery plugins

$.fn.bindFirst = function (name, fn) {
    // bind as you normally would
    // don't want to miss out on any jQuery magic
    this.on(name, fn);

    // Thanks to a comment by @Martin, adding support for
    // namespaced events too.
    this.each(function () {
        var handlers = $._data(this, 'events')[name.split('.')[0]];
        // take out the handler we just inserted from the end
        var handler = handlers.pop();
        // move it at the beginning
        handlers.splice(0, 0, handler);
    });
};

CKEDITOR_BASEPATH = '/assets/plugins/ckeditor.4.4/';

var bootstrapDP = $.fn.datepicker.noConflict();
$.fn.bootstrapDP = bootstrapDP;

bootbox.setDefaults({
    locale: 'pl'
});

function escapeRegExp(str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

// Configuration

Dropzone.autoDiscover = false;

// ------
// System start begin

function systemAssignHandlers() {
    try {
        baseClearScreen();

        // has to be on top
        uncommentBigData();
        tableMaxInit();
        timeagoInit();
        flashMessagesInit();
        extraOrderInit();

        icheckDumbInit();

        enableJsCheckboxes();
        enableFormGenerator();
        enableDropzone();
        enableUploadList();
        dial.initializer();
        typeaheadInit();
        wizardInit();
        transferValueInit();
        transferValuesInit();
        universalRemoveRowInit();
        datepickerInit();
        datetimepickerInit();
        datepickerRelativeInit();
        toggleRelativeInit();
        clearRelativeInit();
        selectAllInit();
        selectAllCheckboxInit();
        showNotifyInit();
        confirmDeleteInit();
        nestableInit();
        messagesTableInit();
        inputMaskInit();
        showSectionInit();
        modalConfirmDelete();
        modalConfirmInit();
        dialogFormSubmitInit();
        examInit();
        forceFullScreenInit();
        widgetCopyValueAsTextInit();
        handleInit();
        autoResizeInit();
        widget.initializer();
        ajaxFormInit();

    } catch (e) {
        alert('Błąd aplikacji, zgłoś problem do administratora');
        console.log('Application error');
        console.log(e.stack);
    }
}

function systemRemoveProcessedState(el) {
    el.find('[class*="processed-"]').add(el).each(function () {
        this.className = this.className.replace(/processed-[^ ]+/g, '');
    });
}

function systemReplaceIdentifiers(el, oldName, newName) {
    el.find('[name], [for], [id]').each(function () {
        replaceInAttribute(this, 'name', oldName, newName);
        replaceInAttribute(this, 'id', oldName, newName);
        replaceInAttribute(this, 'for', oldName, newName);
    });
}

function replaceInAttribute(el, attributeName, oldValue, newValue) {
    if (el.hasAttribute(attributeName)) {
        el.setAttribute(attributeName, el.getAttribute(attributeName).replace(oldValue, newValue));
    }
}

function removeWithEffect(el) {
    if (el.is('tr')) {
        el.children()
            .animate({ paddingTop: 0, paddingBottom: 0 }, 400)
            .wrapInner('<div />')
            .children()
            .slideUp(function () {
                el.remove();
            });
    } else {
        el.slideUp(function () {
            el.remove();
        });
    }
}

function getTargetElement(selector, context) {
    if (!selector) {
        return $();
    }
    var selectorArray = selector.split('>>');

    if (!context) {
        context = $('document');
    }

    if ('self' === selector) {
        // context = context;
    } else if (selectorArray.length === 2) {
        context = context.closest(selectorArray[0]);
        context = context.find(selectorArray[1]);
    } else if (selectorArray.length === 1) {
        context = context.find(selectorArray[0]);
    } else {
        alert('invalid selector');
        context = $();
    }

    return context;
}

function getData(data, key) {
    var akey = key.split('.'),
        cdata = $.extend({}, data);

    $.each(akey, function () {
        if (typeof cdata[this] !== 'undefined') {
            cdata = cdata[this];
        } else {
            cdata = null;
            return false;
        }
    });

    return cdata;
}

function baseClearScreen() {
    $('.tooltip').remove();
}

// has to be on top
uncommentBigData();

enableJsCheckboxes();
enableFormGenerator();


// System start end
// ------
// ------
// ------
$(function () {

    systemAssignHandlers();

    // activate page main form
    if ($('.content-page .content form').size()) {
        setTimeout(function () {
            $('.content-page .content form').eq(0).find(':input').not(':hidden').eq(0).focus();
        }, 100);
    }

});