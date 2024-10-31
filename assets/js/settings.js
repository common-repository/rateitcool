jQuery(document).ready(function () {
	var hide_tabname = function(duration) {
		if(jQuery('#rateitcool_settings_form .rateitcool-widget-location').val() == 'tab') {
			jQuery('#rateitcool_settings_form .rateitcool-widget-tab-name').show(duration);
		}
		else {
			jQuery('#rateitcool_settings_form .rateitcool-widget-tab-name').hide(duration);
		}
	};

	var hide_other_explanation = function(duration) {
		if(jQuery('#rateitcool_settings_form .rateitcool-widget-location').val() == 'other') {
			jQuery('#rateitcool_settings_form .rateitcool-widget-location-other-explain').show(duration);
		}
		else {
			jQuery('#rateitcool_settings_form .rateitcool-widget-location-other-explain').hide(duration);
		}
	};

	hide_tabname(0);
	hide_other_explanation(0);
	jQuery('#rateitcool_settings_form .rateitcool-widget-location').change(function() {
		hide_tabname(1000);
		hide_other_explanation(1000);
	});

	jQuery('#rateitcool-export-reviews').click(function() {
		document.getElementById('export_reviews_submit').click();
	});
});
