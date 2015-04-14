jQuery(document).ready(function ($) {

	/**
	 * Download Configuration Metabox
	 */
	var VATMOSS_Admin_Configuration = {
		init : function() {

			$( '.tips' ).tipTip({
				'attribute': 'data-tip',
				'fadeIn': 50,
				'fadeOut': 50,
				'delay': 200
			});

			$('.generate_report').on('click', function(e) {
				e.preventDefault();
				var me = $(this);

				var submission_id	= me.attr('submission_id');

				var loadingEl = $('#license-checking-' + submission_id);
				// loadingEl.css("display","inline-block");
				loadingEl.show();
	
				// me.attr('disabled','disabled');
				me.hide();

				var data = {
					vat_moss_action:	'generate_report',
					submission_id:		submission_id,
					submission_period:	'Q1/2015',
					url:				vat_moss_vars.url
				};

				$.post(vat_moss_vars.url, data, function (response) {
					loadingEl.hide();
					// me.removeAttr('disabled');
					me.show();

					var json = {};
					try
					{
						json = jQuery.parseJSON( response );
						if (json.status && (json.status === "success" || json.status === "valid"))
						{
							alert( json.body );
							return;
						}
					}

					catch(ex)
					{
						console.log(ex);
						json.message = [vat_moss_vars.UnexpectedErrorSummary];
					}

					if (json.message && json.message.length > 0)
						alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					// me.removeAttr('disabled');
					me.show();
					alert(vat_moss_vars.ErrorValidatingCredentials);
				})
			});

			$('.view_summary').on('click', function(e) {
				e.preventDefault();
				var me = $(this);

				var submission_id	= me.attr('submission_id');
				
				var loadingEl = $('#license-checking-' + submission_id);
				// loadingEl.css("display","inline-block");
				loadingEl.show();
	
				// me.attr('disabled','disabled');
				me.hide();

				var data = {
					vat_moss_action:	'generate_summary_html',
					submission_id:		submission_id,
					url:				vat_moss_vars.url
				};

				$.post(vat_moss_vars.url, data, function (response) {
					loadingEl.hide();
					// me.removeAttr('disabled');
					me.show();

					var json = {};
					try
					{
						json = jQuery.parseJSON( response );
						if (json.status && (json.status === "success" || json.status === "valid"))
						{
							var placeholder = $('#moss_summary');
							if (!placeholder) return;
							placeholder.html(json.body);
//							alert( json.body );
							// return;
						}
					}

					catch(ex)
					{
						console.log(ex);
						json.message = [vat_moss_vars.UnexpectedErrorSummary];
					}

					if (json.message && json.message.length > 0)
						alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					// me.removeAttr('disabled');
					me.show();
					alert(vat_moss_vars.ErrorValidatingCredentials);
				})
			});

			$('#check_moss_license').on('click', function(e) {

				e.preventDefault();
				var me = $(this);

				var submission_key	= me.attr('submission_key_id');
				var submissionKeyEl = $('#' + submission_key);
				var submissionKey = submissionKeyEl.val();
				if (submissionKey.length == 0)
				{
					alert(vat_moss_vars.ReasonNoLicenseKey);
					return false;
				}

				var loadingEl = $('#license-checking');
				loadingEl.css("display","inline-block");

				me.attr('disabled','disabled');

				var data = {
					vat_moss_action:	'check_submission_license',
					submission_key:		submissionKey,
					url:				vat_moss_vars.url
				};

				$.post(vat_moss_vars.url, data, function (response) {
					loadingEl.hide();
					me.removeAttr('disabled');

					var json = {};
					try
					{
						json = jQuery.parseJSON( response );
						if (json.status && (json.status === "success" || json.status === "valid"))
						{
							var msg = vat_moss_vars.LicenseChecked.replace( '{credits}', json.credits );
							
							if (json.quarters)
								msg += "This credit can also be used to create files for these quarters: {quarters}".replace( '{quarters}', json.quarters );
							alert( msg );
							return;
						}
					}

					catch(ex)
					{
						console.log(ex);
						json.message = [vat_moss_vars.UnexpectedErrorCredentials];
					}

					if (json.message)
						alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					me.removeAttr('disabled');
					alert(vat_moss_vars.ErrorValidatingCredentials);
				})

			});

			$('.ip_address_link').on('click', function(e) {

				var ip_dialog = $('<div></div>')
							   .html('<iframe style="border: 0px; " src="' + $(this).attr('href') + '" width="100%" height="100%"></iframe>')
							   .dialog({
								   autoOpen: false,
								   modal: true,
								   height: 485,
								   width: 550,
								   title: vat_moss_vars.IPAddressInformation,
								   dialogClass: 'vat_ip_address'
							   });
				ip_dialog.dialog('open');
				return false;

			});
		}
	};

	VATMOSS_Admin_Configuration.init();

});
