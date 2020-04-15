/* Set the defaults for DataTables initialisation */
$.extend( true, $.fn.dataTable.defaults, {
    "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
    "sPaginationType": "bootstrap",
    "oLanguage": {
        "sLengthMenu": "_MENU_ rekordow na strone"
    },
    stateSave: true,
    stateSaveCallback: function(settings,data) {
		var key =  window.location.pathname.replace('/','');
        localStorage.setItem( 'DataTablesST_' + settings.sInstance + '_' + key, JSON.stringify(data) )
    },
    stateLoadCallback: function(settings) {
		var key =  window.location.pathname.replace('/','');
        return JSON.parse( localStorage.getItem( 'DataTablesST_' + settings.sInstance + '_' + key) )
    },
    bAutoWidth: false,
    responsive: true
} );


/* Default class modification */
$.extend( $.fn.dataTableExt.oStdClasses, {
    "sWrapper": "dataTables_wrapper form-inline"
} );

$.fn.dataTableExt.sErrMode = 'throw';


/* API method to get paging information */
$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings )
{
    return {
        "iStart":         oSettings._iDisplayStart,
        "iEnd":           oSettings.fnDisplayEnd(),
        "iLength":        oSettings._iDisplayLength,
        "iTotal":         oSettings.fnRecordsTotal(),
        "iFilteredTotal": oSettings.fnRecordsDisplay(),
        "iPage":          oSettings._iDisplayLength === -1 ?
            0 : Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
        "iTotalPages":    oSettings._iDisplayLength === -1 ?
            0 : Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
    };
};


/* Bootstrap style pagination control */
$.extend( $.fn.dataTableExt.oPagination, {
    "bootstrap": {
        "fnInit": function( oSettings, nPaging, fnDraw ) {
            var oLang = oSettings.oLanguage.oPaginate;
            var fnClickHandler = function ( e ) {
                e.preventDefault();
                if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
                    fnDraw( oSettings );
                }
            };

            $(nPaging).append(
                '<ul class="pagination">'+
                    '<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
                    '<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
                    '</ul>'
            );
            var els = $('a', nPaging);
            $(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
            $(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
        },

        "fnUpdate": function ( oSettings, fnDraw ) {
            var iListLength = 5;
            var oPaging = oSettings.oInstance.fnPagingInfo();
            var an = oSettings.aanFeatures.p;
            var i, ien, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

            if ( oPaging.iTotalPages < iListLength) {
                iStart = 1;
                iEnd = oPaging.iTotalPages;
            }
            else if ( oPaging.iPage <= iHalf ) {
                iStart = 1;
                iEnd = iListLength;
            } else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
                iStart = oPaging.iTotalPages - iListLength + 1;
                iEnd = oPaging.iTotalPages;
            } else {
                iStart = oPaging.iPage - iHalf + 1;
                iEnd = iStart + iListLength - 1;
            }

            for ( i=0, ien=an.length ; i<ien ; i++ ) {
                // Remove the middle elements
                $('li:gt(0)', an[i]).filter(':not(:last)').remove();

                // Add the new list items and their event handlers
                for ( j=iStart ; j<=iEnd ; j++ ) {
                    sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
                    $('<li '+sClass+'><a href="#">'+j+'</a></li>')
                        .insertBefore( $('li:last', an[i])[0] )
                        .bind('click', function (e) {
                            e.preventDefault();
                            oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
                            fnDraw( oSettings );
                        } );
                }

                // Add / remove disabled classes from the static elements
                if ( oPaging.iPage === 0 ) {
                    $('li:first', an[i]).addClass('disabled');
                } else {
                    $('li:first', an[i]).removeClass('disabled');
                }

                if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
                    $('li:last', an[i]).addClass('disabled');
                } else {
                    $('li:last', an[i]).removeClass('disabled');
                }
            }
        }
    }
} );


/*
 * TableTools Bootstrap compatibility
 * Required TableTools 2.1+
 */
if ( $.fn.DataTable.TableTools ) {
    // Set the classes that TableTools uses to something suitable for Bootstrap
    $.extend( true, $.fn.DataTable.TableTools.classes, {
        "container": "DTTT btn-group",
        "buttons": {
            "normal": "btn",
            "disabled": "disabled"
        },
        "collection": {
            "container": "DTTT_dropdown dropdown-menu",
            "buttons": {
                "normal": "",
                "disabled": "disabled"
            }
        },
        "print": {
            "info": "DTTT_print_info modal"
        },
        "select": {
            "row": "active"
        }
    } );

    // Have the collection use a bootstrap compatible dropdown
    $.extend( true, $.fn.DataTable.TableTools.DEFAULTS.oTags, {
        "collection": {
            "container": "ul",
            "button": "li",
            "liner": "a"
        }
    } );
}

defaultBootstrapDPSettings = {
    format : "yyyy-mm-dd",
    language: 'PL',
    weekStart: 1,
    onSelect: function() {
        this.focus();
    },
    onClose: function() {
        this.blur();
    }
};

/* Table initialisation */
$(document).ready(function () {
    initializeDatatables();
});
$(function(){
    $("#icon-calendar").bootstrapDP();

});

$.fn.dataTableExt.oPagination.listbox = {
    /*
     * Function: oPagination.listbox.fnInit
     * Purpose:  Initalise dom elements required for pagination with listbox input
     * Returns:  -
     * Inputs:   object:oSettings - dataTables settings object
     *             node:nPaging - the DIV which contains this pagination control
     *             function:fnCallbackDraw - draw function which must be called on update
     */
    "fnInit": function (oSettings, nPaging, fnCallbackDraw) {
        var nInput = $('<select class="form-control" data-toggle="tooltip" data-title="'+window.selectPageText+'">').wrap('<li>');
        var nPage = document.createElement('a');
        var nOf = document.createElement('a');

        nOf.className = "paginate_of";
        nPage.className = "paginate_page";
        if (oSettings.sTableId !== '') {
            nPaging.setAttribute('id', oSettings.sTableId + '_paginate');
        }
        nPage.innerHTML = "Page ";

        $(nInput).change(function (e) { // Set DataTables page property and redraw the grid on listbox change event.
            window.scroll(0,0); //scroll to top of page
            if (this.value === "" || this.value.match(/[^0-9]/)) { /* Nothing entered or non-numeric character */
                return;
            }
            var iNewStart = oSettings._iDisplayLength * (this.value - 1);
            if (iNewStart > oSettings.fnRecordsDisplay()) { /* Display overrun */
                oSettings._iDisplayStart = (Math.ceil((oSettings.fnRecordsDisplay() - 1) / oSettings._iDisplayLength) - 1) * oSettings._iDisplayLength;
                fnCallbackDraw(oSettings);
                return;
            }
            oSettings._iDisplayStart = iNewStart;
            fnCallbackDraw(oSettings);
        }); /* Take the brutal approach to cancelling text selection */
        $('span', nPaging).bind('mousedown', function () {
            return false;
        });
        $('span', nPaging).bind('selectstart', function () {
            return false;
        });
        var nFirst = $('<a class="paginate_button first" data-toggle="tooltip" data-title="'+window.firstPageText+'"><i class="icon-to-start"></i></a>').wrap('<li>');
        var nPrevious = $('<a class="paginate_button previous" data-toggle="tooltip" data-title="'+window.prevPageText+'"><i class=icon-left-open-1></i></a>').wrap('<li>');
        var nNext = $('<a class="paginate_button next" data-toggle="tooltip" data-title="'+window.nextPageText+'"><i class="icon-right-open-1"></i></a>').wrap('<li>');
        var nLast = $('<a class="paginate_button last" data-toggle="tooltip" data-title="'+window.lastPageText+'"><i class="icon-to-end"></i></a>').wrap('<li>');

        nFirst.click( function () {
            oSettings.oApi._fnPageChange( oSettings, "first" );
            fnCallbackDraw( oSettings );
        });

        nPrevious.click( function() {
            oSettings.oApi._fnPageChange( oSettings, "previous" );
            fnCallbackDraw( oSettings );
        });

        nNext.click( function() {
            oSettings.oApi._fnPageChange( oSettings, "next" );
            fnCallbackDraw( oSettings );
        });

        nLast.click( function() {
            oSettings.oApi._fnPageChange( oSettings, "last" );
            fnCallbackDraw( oSettings );
        });

        $(nPage).addClass('dt-page-no');

        $(nPaging).append('<ul class="pagination">');
        var elements = [],
            paging = $(nPaging).find('ul');



        /* Disallow text selection */
        $(nFirst.parent())
            .add(nPrevious.parent())
            .add(nInput.parent())
            .add(nNext.parent())
            .add(nLast.parent())
            .bind('selectstart', function(){return false})
            .appendTo(paging);
    },

    /*
     * Function: oPagination.listbox.fnUpdate
     * Purpose:  Update the listbox element
     * Returns:  -
     * Inputs:   object:oSettings - dataTables settings object
     *             function:fnCallbackDraw - draw function which must be called on update
     */
    "fnUpdate": function (oSettings, fnCallbackDraw) {
        if (!oSettings.aanFeatures.p) {
            return;
        }
        var iPages = Math.ceil((oSettings.fnRecordsDisplay()) / oSettings._iDisplayLength),
            iCurrentPage = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength) + 1, /* Loop over each instance of the pager */
            an = oSettings.aanFeatures.p,
            i = 0,
            iLen = an.length,
            currentPagination;

        for (; i < iLen; i++) {
            currentPagination = $(an[i]);
            var spans = an[i].getElementsByTagName('span');
            var inputs = an[i].getElementsByTagName('select');
            var elSel = inputs[0];

            if (elSel.options.length != iPages) {
                elSel.options.length = 0; //clear the listbox contents
                for (var j = 0; j < iPages; j++) { //add the pages
                    var oOption = document.createElement('option');
                    oOption.text = j + 1;
                    oOption.value = j + 1;
                    try {
                        elSel.add(oOption, null); // standards compliant; doesn't work in IE
                    } catch (ex) {
                        elSel.add(oOption); // IE only
                    }
                }
                currentPagination.find('.dt-page-no').html("&nbsp;of&nbsp;" + iPages);
            }
            elSel.value = iCurrentPage;
        }

        /* Loop over each instance of the pager */
        for (i = 0; i < iLen ; i++)
        {
            currentPagination = $(an[i]);
            var firstButton = currentPagination.find('.first'),
                previousButton = currentPagination.find('.previous'),
                nextButton = currentPagination.find('.next'),
                lastButton = currentPagination.find('.last');

            /*if ( oSettings._iDisplayStart === 0 )
            {
                buttons[0].className = "paginate_disabled_previous";
                buttons[1].className = "paginate_disabled_previous";
            }
            else
            {
                buttons[0].className = "paginate_enabled_previous";
                buttons[1].className = "paginate_enabled_previous";
            }

            if ( oSettings.fnDisplayEnd() == oSettings.fnRecordsDisplay() )
            {
                buttons[2].className = "paginate_disabled_next";
                buttons[3].className = "paginate_disabled_next";
            }
            else
            {
                buttons[2].className = "paginate_enabled_next";
                buttons[3].className = "paginate_enabled_next";
            }*/
        }
    }
};