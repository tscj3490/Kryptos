

(function () {
    var inspectionId = $('#inspection-id').val(),
        activitiesWrapper = $('#inspection-activities');

    activitiesWrapper
        .on('click', '.activity-remove', function () {
            var tg = $(this);

            bootbox.confirm("Czy na pewno chcesz usunąc czynność i zgłoszone incydenty?", function (result) {
                if (result === true) {
                    $.ajax({
                        dataType: 'json',
                        url: '/inspections/ajax-remove-activity',
                        data: {activity_id: tg.closest('.widget').attr('data-id')},
                        method: 'POST',
                        success: function (result) {
                            if (tg.closest('.widget').parent().children().size() > 1) {
                                tg.closest('.widget').remove();
                            } else {
                                addActivity(function (result) {
                                    if (result.status) {
                                        cloneActivityAsEmpty(result);
                                        tg.closest('.widget').remove();
                                    }
                                });
                            }
                            updateActivityGrid();
                        }
                    });
                }
            });
        })
        .on('click', '.handle-indent', function () {
            var tg = $(this),
                widget = tg.closest('.widget'),
                currentIndent = parseInt(widget.attr('data-indent'));

            if (widget.prev().size()) {
                var prevIndent = parseInt(widget.prev().attr('data-indent'));

                if (prevIndent >= currentIndent) {
                    widget.attr('data-indent', currentIndent + 1);
                }

                updateActivityGrid();
            }
        })
        .on('click', '.handle-unindent', function () {
            var tg = $(this),
                widget = tg.closest('.widget'),
                currentIndent = parseInt(widget.attr('data-indent'));

            if (currentIndent > 0) {
                widget.attr('data-indent', currentIndent - 1);

                updateActivityGrid();
            }
        });

    $('#add-activity').on('click', function () {
        addActivity(function (result) {
            if (result.status) {
                cloneActivityAsEmpty(result);
            }
        });
    });

    function cloneActivityAsEmpty(result) {
        var objectData = result.objects[0],
            sourceRow = activitiesWrapper.children().eq(0),
            cloned = sourceRow.clone(),
            sourceRowId = cloned.attr('data-id');

        cloned.attr('data-id', objectData.id);

        systemReplaceIdentifiers(cloned, '_' + sourceRowId + '_', '_' + objectData.id + '_');
        systemRemoveProcessedState(cloned);
        cloned
            .find('textarea[name=comment]')
            .removeAttr('style')
            .next()
            .remove();

        cloned.appendTo(activitiesWrapper);

        systemAssignHandlers();

        cloned.find('[name=title], [name=comment]').val('').change();
        if (cloned.find('.non-compilances-list').size()) {
            cloned.find('.non-compilances-list').remove();
        }

        updateActivityGrid();
    }

    function addActivity(successFn) {
        $.ajax({
            dataType: 'json',
            url: '/inspections/ajax-save-activities',
            data: {activities: [{inspection_id: inspectionId}]},
            method: 'POST',
            success: successFn
        });
    }

    $('#inspection-activities').sortable({
        handle: '.handle-sort',
        helper: function(event, ui){
            var $clone =  $(ui).clone();
            $clone .css('position','absolute');
            return $clone.get(0);
        },
        update: function () {
            updateActivityGrid();
        }
    });

    $('.handle-fold-all').on('click', function () {
        activitiesWrapper.find('.widget-toggle').not('.closed').click();
    });

    $('.handle-unfold-all').on('click', function () {
        activitiesWrapper.find('.widget-toggle.closed').click();
    });

    updateActivityGrid();
})();

function saveActivities(elements) {
    var data = [];

    elements.each(function() {
        var tg = $(this);

        data.push({
            id: tg.closest('.widget').attr('data-id'),
            title: tg.find('[name=title]').val(),
            comment: tg.find('[name=comment]').val(),
            ordinal: tg.find('h2 .order').text()
        });
    });

    $.ajax({
        dataType: 'json',
        url: '/inspections/ajax-save-activities',
        data: {activities: data},
        method: 'POST'
    });
}

function getAddNonCompilanceMiniUrl() {
    return '/inspections/mini-update-non-compilance?activityId=' + $(this).closest('.widget').attr('data-id');
}

function processAddNonCompilance(result) {
    var widget = this.closest('.widget');

    if (!widget.find('.non-compilances-list').size()) {
        var compilancesList = $('#non-compilances-template').html();
        widget.find('.non-compilances-add-button-wrapper').before(compilancesList);
    }

    var compilancesListBody = widget.find('.non-compilances-list tbody'),
        newRow = $('<tr>'),
        dateCol = $('<td><span data-toggle="tooltip" data-title="' + result.object.notification_date + '" data-original-title="" title="">' + result.object.notification_date.substr(0, 10) + '</span></td>'),
        nameCol = $('<td></td>').text(result.object.title.toUpperCase()),
        operationsCol = $('<td class="operations"><a class="glyphicon glyphicon-pencil choose-from-dial" data-dial-url="/inspections/mini-update-non-compilance/id/' + result.object.id + '" data-toggle="tooltip" title="EDYTUJ" data-new-dialog="1"></a></td>');

    newRow.attr('data-id', result.object.id);

    newRow.append(dateCol).append(nameCol).append(operationsCol);

    compilancesListBody.append(newRow);
    systemAssignHandlers();
}

function processEditNonCompilance(result) {
    var widget = this.closest('.widget'),
        compilancesListBody = widget.find('.non-compilances-list tbody'),
        editedRow = compilancesListBody.find('tr[data-id='+result.object.id+']'),
        tds = editedRow.children();

    tds.eq(0).html('<span data-toggle="tooltip" data-title="' + result.object.notification_date + '" data-original-title="" title="">' + result.object.notification_date.substr(0, 10) + '</span>');
    tds.eq(1).text(result.object.title.toUpperCase());

    systemAssignHandlers();
}

function updateActivityGrid() {
    var indentSize = 25,
        i = [0],
        lastIndent = -1;

    $('#inspection-activities > div').each(function () {
        var tg = $(this),
            orderIndicator = $(this).find('h2 .order'),
            indent = parseInt(tg.attr('data-indent'));

        if (indent > lastIndent + 1) {
            indent = lastIndent + 1;
        }
        if (indent < lastIndent) {
            i = i.slice(0, indent + 1);
        }

        var ordinal = getNextOrdinal(i, indent);

        orderIndicator.text(ordinal);
        tg
            .css({marginLeft: (ordinal.match(/\./g) || []).length * indentSize})
            .attr('data-indent', indent);

        lastIndent = indent;
    });

    saveActivities($('#inspection-activities > div'));
}

function getNextOrdinal(data, index) {
    if (typeof data[index] !== 'undefined') {
        data[index]++;
    } else {
        data[index] = 1;
    }

    data = data.slice(0, index + 1);

    return data.join('.');
}

widget.push({
    class: 'ckeditor-inspections',
    scripts: ckeditorAssets,
    blockInit: ['live-save'],
    initElement: function(tg) {
        var roxyFileman = '/assets/plugins/roxyFileman/fileman/index.html';

        var editor = CKEDITOR.replace(tg.get(0), {
            filebrowserBrowseUrl: roxyFileman,
            fullPage: false,
            entities_additional: '',
            filebrowserImageBrowseUrl: roxyFileman + '?type=image',
            removeDialogTabs: 'link:upload;image:upload',
            toolbarGroups: [
                { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                { name: 'colors' },
                { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ] },
                { name: 'links' },
                { name: 'insert' },
                { name: 'styles' },
                { name: 'tools' },
                { name: 'others' },
                { name: 'document', groups: [ 'mode' ] },
                { name: 'kryptoscustomtags', groups: [ 'editkryptoscustomtag' ] }
            ],
            extraPlugins: 'kryptoscustomtags',
            removeButtons: 'Flash,Iframe,Print,Preview,Save,NewPage',
            extraAllowedContent: 'div[data-*]',
            contentsCss: '/inspections/editor-get-css',
            height:600,

            kryptoscustomtags: {
                elements: {
                    blocks: {
                        metadane: {
                            name: 'metadane',
                            label: 'Metadane',
                            url: '/inspections/editor-get-metadata/id/' + $('#inspection-id').val()
                        },
                        activities: {
                            name: 'activities',
                            label: 'Czynności',
                            url: '/inspections/editor-get-activities/id/' + $('#inspection-id').val()
                        },
                        noncompilances: {
                            name: 'noncompilances',
                            label: 'Niezgodności',
                            url: '/inspections/editor-get-non-compilances/id/' + $('#inspection-id').val()
                        }
                    }
                }
            }
        });

        ckeditorLiveSaveIntegration(editor, tg);
        ckeditorAutohide(editor);
    }
});
