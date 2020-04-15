var w;
var h;
var dw;
var dh;

function executeFunctionByName(functionName, context /*, args */) {
  var args = [].slice.call(arguments).splice(2);
  var namespaces = functionName.split(".");
  var func = namespaces.pop();
  for(var i = 0; i < namespaces.length; i++) {
    context = context[namespaces[i]];
  }
  return context[func].apply(this, args);
}

var changeptype = function(){
    w = $(window).width();
    h = $(window).height();
    dw = $(document).width();
    dh = $(document).height();

    if(jQuery.browser.mobile === true){
      	$("body").addClass("mobile").removeClass("fixed-left");
    }

    if(!$("#wrapper").hasClass("forced")){
        if(w > 990){
            $("body").removeClass("smallscreen").addClass("widescreen");
            $("#wrapper").removeClass("enlarged");
        }else{
            $("body").removeClass("widescreen").addClass("smallscreen");
            $("#wrapper").addClass("enlarged");
            $(".left ul").removeAttr("style");
        }
        if($("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left")){
            $("body").removeClass("fixed-left").addClass("fixed-left-void");
        }else if(!$("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left-void")){
            $("body").removeClass("fixed-left-void").addClass("fixed-left");
        }
    }
	toggle_slimscroll(".slimscrollleft");
}

$(document).ready(function(){
	FastClick.attach(document.body);
	resizefunc.push("initscrolls");
	resizefunc.push("changeptype");
	$('.sparkline').sparkline('html', { enableTagOptions: true });

	$('.animate-number').each(function(){
		$(this).animateNumbers($(this).attr("data-value"), true, parseInt($(this).attr("data-duration"))); 
	})

//TOOLTIP
    $('body').tooltip({
        selector: "[data-toggle=tooltip]",
        container: "body",
        html: true,
        delay: {
            hide: 100
        },
        placement: function(tooltipElement, target) {
            var placement    = 'top',
                clonedTooltip = $(tooltipElement).clone().appendTo('body'),
                pos          = this.getPosition(),
                $parent      = this.$element.parent(),
                actualWidth  = clonedTooltip[0].offsetWidth,
                actualHeight = clonedTooltip[0].offsetHeight,
                docScroll    = document.documentElement.scrollTop || document.body.scrollTop,
                parentHeight = this.options.container == 'body' ? window.innerHeight : $parent.outerHeight(),
                calculatedOffset = { top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2  };

            clonedTooltip.remove();

            //this.applyPlacement(calculatedOffset, placement);

            return 'top';
        }

    });

//RESPONSIVE SIDEBAR


$(".open-right").click(function(e){
  $("#wrapper").toggleClass("open-right-sidebar");
  e.stopPropagation();
  $("body").trigger("resize");
});


$(".open-left").click(function(e){
	e.stopPropagation();
    $("#wrapper").toggleClass("enlarged");
    $("#wrapper").addClass("forced");
    $(".left ul").removeAttr("style");
    toggle_slimscroll(".slimscrollleft");
    $("body").trigger("resize");
});

// LEFT SIDE MAIN NAVIGATION
sidebarMenuClick = function (e) {
    e.stopPropagation();
    if (!$("#wrapper").hasClass("enlarged")) {
        var parentAnhor = $(this).parents('a:first');
        if (!parentAnhor.hasClass("subdrop")) {
            // hide any open menus and remove all other classes
            $("ul", parentAnhor.parents("ul:first")).slideUp(350);
            $("a", parentAnhor.parents("ul:first")).removeClass("subdrop");
            $("#sidebar-menu .pull-right i").removeClass("fa-angle-up").addClass("fa-angle-down");

            // open our new menu and add the open class
            parentAnhor.next("ul").slideDown(350);
            parentAnhor.addClass("subdrop");
            $(".pull-right i", parentAnhor.parents(".has_sub:last")).removeClass("fa-angle-down").addClass("fa-angle-up");
            $(".pull-right i", parentAnhor.siblings("ul")).removeClass("fa-angle-up").addClass("fa-angle-down");
        } else if (parentAnhor.hasClass("subdrop")) {
            parentAnhor.removeClass("subdrop");
            parentAnhor.next("ul").slideUp(350);
            $(".pull-right i", parentAnhor.parent()).removeClass("fa-angle-up").addClass("fa-angle-down");
            //$(".pull-right i",parentAnhor.parents("ul:eq(1)")).removeClass("fa-chevron-down").addClass("fa-chevron-left");
        }
    }
};
$("#sidebar-menu a span.pull-right").on('click', sidebarMenuClick);
$('#sidebar-menu a[href="javascript:;"]').on('click', function() {
    $(this).find('span.pull-right:first').click();
});

//WIDGET ACTIONS
$(".widget-header .widget-close").on("click",function(){
  $item = $(this).parents(".widget:first");
  bootbox.confirm("Are you sure to remove this widget?", function(result) {
    if(result === true){
      $item.addClass("animated bounceOutUp");
        window.setTimeout(function () {
           $item.remove();
        }, 300);
    }
  }); 
});

$(document).on("click", ".widget-header .widget-toggle, .widget-header h2", function(event){
    event.preventDefault();

    if (this.tagName === 'H2') {
        var nextTag = $(this).next();
        if (event.target.tagName !== 'H2' || !nextTag.hasClass('additional-btn') || !nextTag.find('.widget-toggle').size()) {
            return;
        }
    }

    $(this).toggleClass("closed").parents(".widget:first").find(".widget-content").slideToggle();
});

$(document).on("click", ".widget-header .widget-popout", function(event){
  event.preventDefault();
  var widget = $(this).parents(".widget:first");
  if(widget.hasClass("modal-widget")){
    $("i",this).removeClass("icon-window").addClass("icon-publish");
    widget.removeAttr("style").removeClass("modal-widget");
    widget.find(".widget-maximize,.widget-toggle").removeClass("nevershow");
    widget.draggable("destroy").resizable("destroy");
  }else{
    widget.removeClass("maximized");
    widget.find(".widget-maximize,.widget-toggle").addClass("nevershow");
    $("i",this).removeClass("icon-publish").addClass("icon-window");
    var w = widget.width();
    var h = widget.height();
    widget.addClass("modal-widget").removeAttr("style").width(w).height(h);
    $(widget).draggable({ handle: ".widget-header",containment: ".content-page" }).css({"left":widget.position().left-2,"top":widget.position().top-2}).resizable({minHeight: 150,minWidth: 200});
  }
  $("body").trigger("resize");
});

$(document).on("click", ".widget", function(){
    if($(this).hasClass("modal-widget")){
      $(".modal-widget").css("z-index",5);
      $(this).css("z-index",6);
    }
});

$(document).on("click", '.widget .reload', function (event) { 
  event.preventDefault();
  var el = $(this).parents(".widget:first");
  blockUI(el);
    window.setTimeout(function () {
       unblockUI(el);
    }, 1000);
});

$(document).on("click", ".widget-header .widget-maximize", function(event){
    event.preventDefault();
    $(this).parents(".widget:first").removeAttr("style").toggleClass("maximized");
    $("i",this).toggleClass("icon-resize-full-1").toggleClass("icon-resize-small-1");
    $(this).parents(".widget:first").find(".widget-toggle").toggleClass("nevershow");
    $("body").trigger("resize");
    return false;
});

$( ".portlets" ).sortable({
    connectWith: ".portlets",
    handle: ".widget-header",
    cancel: ".modal-widget",
    opacity: 0.5,
    dropOnEmpty: true,
    forcePlaceholderSize: true,
    receive: function(event, ui) {$("body").trigger("resize")}
});

// Init Code Highlighter
prettyPrint();

//RUN RESIZE ITEMS
$(window).resize(debounce(resizeitems,100));
$("body").trigger("resize");

//SELECT
$('.selectpicker').selectpicker();


//DATE PICKER
//$.noConflict();
/*
$('.datepicker-input').datepicker({
	format : "yyyy-mm-dd",
	language: 'PL',
	onSelect: function() {
		this.focus();
	},
	onClose: function() {
		this.blur();
	}
});
*/

// IOS7 SWITCH
$(".ios-switch").each(function(){
    mySwitch = new Switch(this);
});

//GALLERY
$('.gallery-wrap').each(function() { // the containers for all your galleries
    $(this).magnificPopup({
        delegate: 'a.zooming', // the selector for gallery item
        type: 'image',
    		removalDelay: 300,
    		mainClass: 'mfp-fade',
        gallery: {
          enabled:true
        }
    });
}); 



});

var debounce = function(func, wait, immediate) {
  var timeout, result;
  return function() {
    var context = this, args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) result = func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) result = func.apply(context, args);
    return result;
  };
}

function resizeitems(){
  if($.isArray(resizefunc)){  
    for (i = 0; i < resizefunc.length; i++) {
        window[resizefunc[i]]();
    }
  }
}

function initscrolls(){
    if(jQuery.browser.mobile !== true){
	    //SLIM SCROLL
	    $('.slimscroller').slimscroll({
	      height: 'auto',
	      size: "5px"
	    });

	    $('.slimscrollleft').slimScroll({
	        height: 'auto',
	        position: 'left',
	        size: "5px",
	        color: '#7A868F',
            wheelStep: 5,
            opacity:.8
	    });

        $(document).one('mousemove', function(e) {
            var slimscroll = $(e.target).parents('.slimscrollleft');
            if (!slimscroll.size()) {
                $('.slimscrollleft').trigger('mouseleave');
            }
        });
	}
}
function toggle_slimscroll(item){
    if($("#wrapper").hasClass("enlarged")){
      $(item).css("overflow","inherit").parent().css("overflow","inherit");
      $(item).siblings(".slimScrollBar").css("visibility","hidden");
    }else{
      $(item).css("overflow","hidden").parent().css("overflow","hidden");
      $(item).siblings(".slimScrollBar").css("visibility","visible");
    }
}

function nifty_modal_alert(effect,header,text){
    
    var randLetter = String.fromCharCode(65 + Math.floor(Math.random() * 26));
    var uniqid = randLetter + Date.now();

    $modal =  '<div class="md-modal md-effect-'+effect+'" id="'+uniqid+'">';
    $modal +=    '<div class="md-content">';
    $modal +=      '<h3>'+header+'</h3>';
    $modal +=      '<div class="md-modal-body">'+text;
    $modal +=      '</div>';
    $modal +=    '</div>';
    $modal +=  '</div>';

    $("body").prepend($modal);

    window.setTimeout(function () {
        $("#"+uniqid).addClass("md-show");
        $(".md-overlay,.md-close").click(function(){
          $("#"+uniqid).removeClass("md-show");
          window.setTimeout(function () {$("#"+uniqid).remove();},500);
        });
    },100);

    return false;
}

function blockUI(item) {    
    $(item).block({
      message: '<div class="loading"></div>',
      css: {
          border: 'none',
          width: '14px',
          backgroundColor: 'none'
      },
      overlayCSS: {
          backgroundColor: '#fff',
          opacity: 0.4,
          cursor: 'wait'
      }
    });
}

function unblockUI(item) {
    $(item).unblock();
}

function toggle_fullscreen(){
    var fullscreenEnabled = document.fullscreenEnabled || document.mozFullScreenEnabled || document.webkitFullscreenEnabled;
    if(fullscreenEnabled){
      if(!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
          launchIntoFullscreen(document.documentElement);
      }else{
          exitFullscreen();
      }
    }
}


// Thanks to http://davidwalsh.name/fullscreen

function launchIntoFullscreen(element) {
  if(element.requestFullscreen) {
    element.requestFullscreen();
  } else if(element.mozRequestFullScreen) {
    element.mozRequestFullScreen();
  } else if(element.webkitRequestFullscreen) {
    element.webkitRequestFullscreen();
  } else if(element.msRequestFullscreen) {
    element.msRequestFullscreen();
  }
}

function exitFullscreen() {
  if(document.exitFullscreen) {
    document.exitFullscreen();
  } else if(document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if(document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }
}

freezeTimeFromLogout = false;
function getTimeFromLogoutDate() {
    var currentTime = new Date(),
        diffMiliseconds = logoutDate.getTime() - currentTime.getTime(),
        diffDate = new Date(diffMiliseconds);

    var hours = diffDate.getHours() - 1,
        minutes = diffDate.getMinutes(),
        seconds = diffDate.getSeconds(),
        days = diffDate.getDate() - 1,
        timeArray = [];

    if (days > 0) {
        hours += days * 24;
    }
    if (hours > 0) {
        timeArray.push(hours);
    }
    timeArray.push(("0" + minutes).slice(-2));
    timeArray.push(("0" + seconds).slice(-2));

    if (diffMiliseconds <= 0) {
        //window.location = '/user/logout/r/session';
        freezeTimeFromLogout = true;
        getReloginWidget();
    }

    return timeArray.join(':');
}

setTimeout(function() {
    setInterval(function () {
        if (freezeTimeFromLogout) {
            return;
        }

        var timeStr = getTimeFromLogoutDate();

        $("#time_session").text(timeStr);

    }, 1000);
}, 1000);

getReloginWidget = function() {
    $.post('/index/relogin-widget', {login: userLogin}, function(widgetHtml) {
        if (widgetHtml === 'force_logout') {
            window.location.href = '/user/logout/r/session';
            return;
        }

        var reloginModal = $('#relogin-modal');

        reloginModal.find('.md-content').html(widgetHtml);
        $('#relogin-button').click();

        modalScreenBlock.enable();
        var loginForm = $('#loginForm');

        loginForm.on('submit', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();

            $.post('/index/ajax-authorize', loginForm.serialize(), function(result) {
                if (result.status === 'authorized') {
                    logoutDate = new Date(result.sessionExpiredAt * 1000);
                    closeReloginWidget();
                } else {
                    var firstChild = reloginModal.find('.section > .errorMessage:first-child');
                    if (firstChild.size()) {
                        firstChild.remove();
                    }
                    reloginModal.find('.section').prepend('<div class="errorMessage"><span>Niepoprawny login lub hasło.</span></div>');
                }
            });

            return false;
        });
    });
};

closeReloginWidget = function() {
    if ($('#relogin-modal').hasClass('md-show')) {
        modalScreenBlock.disable();
        $('#relogin-modal .md-close').click();
        freezeTimeFromLogout = false;
    }
};

disableEvent = function(e) {
    e.stopImmediatePropagation();
    e.preventDefault();
};

modalScreenBlock = {
    enable: function() {
        $('.md-overlay').bindFirst('click', disableEvent);
    },
    disable: function() {
        $('.md-overlay').unbind('click', disableEvent);
    }
};


getKomunikatWidget = function() {
    $.post('/ajax/komunikat-widget', function(widgetHtml) {
        if (widgetHtml === 'NO_KOMUNIKAT') {
            return;
        }
        var modal = $('#prototype-modal');
        modal.addClass('modal-signature');

        modal.find('.md-content').html(widgetHtml);
        $('#prototype-modal-button').click();
        modalScreenBlock.enable();

        var form = modal.find('form');
        form.on('submit', function(e) {
            if (e.isDefaultPrevented()) {
                return;
            }
            e.stopImmediatePropagation();
            e.preventDefault();

            $.post('/ajax/komunikat-accept', form.serialize(), function(result) {
                modalScreenBlock.disable();
                modal.removeClass('modal-signature');
                modal.find('.md-close').click();
            });

            return false;
        });
    });
};




popstateTabHandler = function(e) {
    if (location.hash) {
        var activeTab = $('[href=' + location.hash + ']');
        if (activeTab.length) {
            activeTab.tab('show');
        }
    }
};

// navigate to a tab when the history changes
window.addEventListener("popstate", popstateTabHandler);

$(document).ready(function() {
    popstateTabHandler();
    // add a hash to the URL when the user clicks on a tab
    $('a[data-toggle="tab"]').on('click', function(e) {
        history.pushState(null, null, $(this).attr('href'));
    });
});

