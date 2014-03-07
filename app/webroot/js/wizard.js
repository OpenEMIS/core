$(document).ready(function() {
	wizard.blockLinks();
});

var wizard = {
	blockLinks: function(exclude) {
		$('a[href]').each(function() {
			if($(this).attr('href').indexOf('/') != -1) {
				if($(this).attr('wizard') == undefined && !$(this).hasClass('logout')) {
					$(this).click(function() {
						var dlgOpt = {
							id: 'wizard-dialog',
							title: i18n.Wizard.title,
							content: i18n.Wizard.uncomplete
						};
						$.dialog(dlgOpt);
						return false;
					});
				}
			}
		});
	}
}