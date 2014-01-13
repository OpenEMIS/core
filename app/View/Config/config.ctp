var ajaxType = {
	success: <?php echo $ajaxReturnCodes['success']; ?>,
	error: <?php echo $ajaxReturnCodes['error']; ?>,
	alert: <?php echo $ajaxReturnCodes['alert']; ?>
};

$(document).ready(function() {
	$.ajaxSetup({error: ajaxErrorHandler});
});

function getRootURL() {
	return '<?php echo $rootURL ?>';
}

function ajaxErrorHandler(jqXHR, textStatus, errorThrown) {
	var dlgOpt = { id: 'ajax-error-dialog' };
	$('.mask').each(function() {
		if($(this).attr('id') != 'ajax_dialog_mask') {
			$(this).remove();
		}
	});
	
	if (jqXHR.status === 0) {
		<?php 
			$handler = $ajaxErrorHandler[0];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else if (jqXHR.status == 403 || jqXHR.status == 503) { // Forbidden, session timed out
		<?php 
			$handler = $ajaxErrorHandler[403];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
		var maskId;
		var loginBtn = {
			id: 'ajax-login-btn',
			value: '<?php echo __('Login'); ?>',
			callback: function() {
				var form = $('#ajax_login');
				
				$.ajax({
					type: "post",
					dataType: "text",
					url: form.attr('action'),
					data: form.serialize(),
					beforeSend: function (XMLHttpRequest) {
						maskId = $.mask({parent: '#' + dlgOpt.id + ' .dialog-box', text: i18n.General.textReconnecting});
					},
					complete: function (XMLHttpRequest, textStatus) {
						$.unmask({id: maskId});
					},
					success: function (data, textStatus) {
						if(data) {
							$.closeDialog({id: dlgOpt.id});
                                                        window.location.href = getRootURL() + 'Home';
						} else {
							$.alert({
								parent: '#' + dlgOpt.id + ' .dialog-box',
								text: i18n.Config.InvalidUser ,
								type: alertType.error
							});
						}
					}
				});
			}
		}
		
		dlgOpt.buttons = [loginBtn];
		dlgOpt.ajaxUrl = getRootURL() + 'Security/login';
		dlgOpt.onOpen = function() {
			$('#ajax-login form input[type="password"]').keypress(function(e) {
				var key = utility.getKeyPressed(e);
				if(key==13) { // enter key
					$('#ajax-login-btn').click();
				}
			});
		};
		dlgOpt.onClose = function() {
			window.location.href = getRootURL() + 'Security/logout';
		}
	} else if (jqXHR.status == 404) {
		<?php 
			$handler = $ajaxErrorHandler[404];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else if (jqXHR.status == 500) {
		<?php 
			$handler = $ajaxErrorHandler[500];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else if (textStatus === 'parsererror') {
		<?php 
			$handler = $ajaxErrorHandler['parsererror'];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else if (textStatus === 'timeout') {
		<?php 
			$handler = $ajaxErrorHandler['timeout'];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else if (textStatus === 'abort') {
		<?php 
			$handler = $ajaxErrorHandler['abort'];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	} else {
		<?php 
			$handler = $ajaxErrorHandler['unknown'];
			foreach($handler as $name => $val) {
				echo "dlgOpt." . $name . " = '" . addslashes($val) . "';";
			}
		?>
	}
	
	if($('#' + dlgOpt.id).length==0) {
		$.dialog(dlgOpt);
	}
}
