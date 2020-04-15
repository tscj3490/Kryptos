function BackendUtilities() {
	/***************************************************************************
	 * Properties
	 */

	/***************************************************************************
	 * Methods
	 * 
	 * 
	 * /** Constructor
	 */
	this.constructor = function() {
		$(document).ready(function() {
			$(document).keydown(function(e) {
				if ((e.which == 49) && (!e.ctrlKey && e.altKey)) // alt + 1
				{
					window.location = global_www_path + '/admin/warehouse/pz';
					e.preventDefault();
				} else if ((e.which == 50) && (!e.ctrlKey && e.altKey)) // alt +
																		// 2
				{
					window.location = global_www_path + '/admin/warehouse/wz';
					e.preventDefault();
				} else if ((e.which == 51) && (!e.ctrlKey && e.altKey)) // alt +
																		// 3
				{
					window.location = global_www_path + '/admin/warehouse/lw';
					e.preventDefault();
				}

			});
		});
	}();

	var validationEngineDefaultConfig = {
		binded: false
	};

	/**
	 * Add validation engine
	 * 
	 * @param string
	 *            formId
	 */
	this.addVatidationEngine = function(formId) {
		$(document).ready(function() {
			$('#' + formId).validationEngine('attach', validationEngineDefaultConfig);
		});
	};

	/**
	 * Rebuild validation engine
	 * 
	 * @param string
	 *            formId
	 */
	this.rebuildVatidationEngine = function(formId) {
		$(document).ready(function() {
			
			// rebuild validation engine
			$('#' + formId).validationEngine('detach');
			$('#' + formId).validationEngine('attach', validationEngineDefaultConfig);
		});
	};

	/**
	 * Add DatePicker to HTML element
	 * 
	 * @param string
	 *            element element to attach
	 */
	this.addDatePickerToDateFields = function(element) {
				
		$(document).ready(function() {
			
			/* Polish initialisation for the jQuery UI date picker plugin. */
			/* Written by Jacek Wysocki (jacek.wysocki@gmail.com). */
			jQuery(function($){
			        $.datepicker.regional['pl'] = {
			                closeText: 'Zamknij',
			                prevText: '&#x3c;Poprzedni',
			                nextText: 'Następny&#x3e;',
			                currentText: 'Dziś',
			                monthNames: ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
			                'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'],
			                monthNamesShort: ['Sty','Lu','Mar','Kw','Maj','Cze',
			                'Lip','Sie','Wrz','Pa','Lis','Gru'],
			                dayNames: ['Niedziela','Poniedzialek','Wtorek','Środa','Czwartek','Piątek','Sobota'],
			                dayNamesShort: ['Nie','Pn','Wt','Śr','Czw','Pt','So'],
			                dayNamesMin: ['N','Pn','Wt','Śr','Cz','Pt','So'],
			                weekHeader: 'Tydz',
			                dateFormat: 'yy-mm-dd',
			                firstDay: 1,
			                isRTL: false,
			                showMonthAfterYear: false,
			                yearSuffix: ''};
			        $.datepicker.setDefaults($.datepicker.regional['pl']);
			});

			$(element).datepicker({
				dateFormat : "yy-mm-dd"
			});
		});
	};

	/**
	 * Create tabs
	 * 
	 * @param string
	 *            id id_html to create tabs
	 */
	this.createTabs = function(id) {
		$(document).ready(function() {
			$("#" + id).tabs();
		});
	};

	/**
	 * Prevent events
	 * 
	 * @param event
	 *            e event from browser
	 */
	this.preventEvents = function(e) {
		e.preventDefault();

		// For IE
		if ($.browser.msie) {
			this.onselectstart = function() {
				return false;
			};
			var me = this; // capture in a closure
			window.setTimeout(function() {
				me.onselectstart = null;
			}, 0);
		}
		;
	};

	/**
	 * Load time session in div
	 */
	this.loadTimeSession = function() {
		$(document).ready(function() {
			$('#time').load(global_www_path + '/admin/index/status');
		});
	};

	/**
	 * Refresh timer
	 */
	this.refreshTimeSession = function() {
		
	};
	
	this.val = function(val) {
		var tmp = backendUtilities.str_replace(',', '.', val);
		tmp = parseFloat(tmp);
		
		return tmp;
	};

	this.str_replace = function(search, replace, subject, count) 
	{
		// http://kevin.vanzonneveld.net
		// + original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// + improved by: Gabriel Paderni
		// + improved by: Philip Peterson
		// + improved by: Simon Willison (http://simonwillison.net)
		// + revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// + bugfixed by: Anton Ongson
		// + input by: Onno Marsman
		// + improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// + tweaked by: Onno Marsman
		// + input by: Brett Zamir (http://brett-zamir.me)
		// + bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// + input by: Oleg Eremeev
		// + improved by: Brett Zamir (http://brett-zamir.me)
		// + bugfixed by: Oleg Eremeev
		// % note 1: The count parameter must be passed as a string in order
		// % note 1: to find a global variable in which the result will be given
		// * example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
		// * returns 1: 'Kevin.van.Zonneveld'
		// * example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name},
		// lars');
		// * returns 2: 'hemmo, mars'
		var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0, f = []
				.concat(search), r = [].concat(replace), s = subject, ra = Object.prototype.toString
				.call(r) === '[object Array]', sa = Object.prototype.toString
				.call(s) === '[object Array]';
		s = [].concat(s);
		if (count) {
			this.window[count] = 0;
		}

		for (i = 0, sl = s.length; i < sl; i++) {
			if (s[i] === '') {
				continue;
			}
			for (j = 0, fl = f.length; j < fl; j++) {
				temp = s[i] + '';
				repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
				s[i] = (temp).split(f[j]).join(repl);
				if (count && s[i] !== temp) {
					this.window[count] += (temp.length - s[i].length)
							/ f[j].length;
				}
			}
		}
		return sa ? s : s[0];
	};

	this.explode = function(delimiter, string, limit) 
	{

		if (arguments.length < 2 || typeof delimiter == 'undefined'
				|| typeof string == 'undefined')
			return null;
		if (delimiter === '' || delimiter === false || delimiter === null)
			return false;
		if (typeof delimiter == 'function' || typeof delimiter == 'object'
				|| typeof string == 'function' || typeof string == 'object') {
			return {
				0 : ''
			};
		}
		if (delimiter === true)
			delimiter = '1';

		// Here we go...
		delimiter += '';
		string += '';

		var s = string.split(delimiter);

		if (typeof limit === 'undefined')
			return s;

		// Support for limit
		if (limit === 0)
			limit = 1;

		// Positive limit
		if (limit > 0) {
			if (limit >= s.length)
				return s;
			return s.slice(0, limit - 1).concat(
					[ s.slice(limit - 1).join(delimiter) ]);
		}

		// Negative limit
		if (-limit >= s.length)
			return [];

		s.splice(s.length + limit);
		return s;
	};
	
	/**
	 * Radio changer
	 */
	this.radioSpanChecker = function()
	{
		$('.params_pointer').click(function()
		{			
			//$('#' + $(this).attr('key')).prop('checked', true);
			$('#' + $(this).attr('key')).click();
		});
	};
	
	/**
	 * Check that hurtcode is incorrect
	 * 
	 * @param string
	 *            hurtcode hurtcode
	 */
	this.isIncorrectHurtcode = function(hurtcode) {
		
		if(
			hurtcode.length < 10 ||	
			hurtcode == '5414368073241' ||
			hurtcode == '5414368073272' ||
			hurtcode == '5414368073296' 
		)
		{
			return true;
		}
		else
		{
			return false;
		}
		
	};

	/**
	 * Check that number is int
	 * 
	 * @param mixes
	 *            n 
	 */
	this.isInt = function(n) 
	{		
		return n % 1 == 0;
	};
	
	/**
	 * Check number
	 *
	 * @param double number
	 */
	this.isFloat = function(number)
	{
		number = backendUtilities.str_replace(',', '.', number);
		
		if(!isNaN(number))
		{
			return true;
		}
		else
		{
			return false;
		}
	};
	
	/**
	 * Go to previous page
	 */
	this.previousPage = function()
	{
		window.history.back();
	};
	
};
var backendUtilities = new BackendUtilities();