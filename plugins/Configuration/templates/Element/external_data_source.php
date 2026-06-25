<?php if ($ControllerAction['action'] == 'index') : ?>

<?php elseif ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>

	<?php //POCOR-9590: Test Connection ribbon — shown when user clicks the Test Connection toolbar button ?>
	<div id="connection-ribbon" style="display:none; padding:10px 16px; margin-bottom:12px; border-radius:4px; font-size:13px; font-weight:600; letter-spacing:0.02em;">
		<span id="connection-ribbon-icon" style="margin-right:8px;"></span>
		<span id="connection-ribbon-text"></span>
	</div>

	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>

	<?php //POCOR-9590: JavaScript for Test Connection button ?>
	<script>
	(function () {
		//POCOR-9590: wire up the Test Connection toolbar button to show a colored ribbon
		document.addEventListener('DOMContentLoaded', function () {
			var btn = document.getElementById('btn-test-connection');
			if (!btn) return;

			//POCOR-9590: intercept the anchor click, fire AJAX, show ribbon instead of navigating
			var testUrl = btn.href || '/core/Configurations/Configurations/testExternalConnection';
			btn.href = '#';

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				showRibbon('info', '<i class="fa fa-spinner fa-spin"></i>', 'Testing connection…');

				var xhr = new XMLHttpRequest();
				xhr.open('GET', testUrl, true);
				xhr.setRequestHeader('Accept', 'application/json');
				xhr.onreadystatechange = function () {
					if (xhr.readyState !== 4) return;
					try {
						var data = JSON.parse(xhr.responseText);
						applyResult(data);
					} catch (err) {
						showRibbon('danger', '<i class="fa fa-times-circle"></i>', 'Unexpected response: ' + xhr.responseText.substring(0, 120));
					}
				};
				xhr.onerror = function () {
					showRibbon('danger', '<i class="fa fa-times-circle"></i>', 'Network error — could not reach server');
				};
				xhr.send();
			});
		});

		function applyResult(data) {
			var status  = data.status  || 'unknown';
			var message = data.message || 'Unknown result';

			switch (status) {
				case 'ok':
					showRibbon('success', '<i class="fa fa-check-circle"></i>', message);
					break;
				case 'credentials_error':
					showRibbon('warning', '<i class="fa fa-exclamation-triangle"></i>', message);
					break;
				case 'no_address':
				case 'address_error':
					showRibbon('danger', '<i class="fa fa-times-circle"></i>', message);
					break;
				case 'address_ok':
					showRibbon('info', '<i class="fa fa-info-circle"></i>', message);
					break;
				case 'no_config':
				case 'credentials_missing':
					showRibbon('warning', '<i class="fa fa-exclamation-triangle"></i>', message);
					break;
				default:
					showRibbon('info', '<i class="fa fa-question-circle"></i>', message);
			}
		}

		function showRibbon(type, iconHtml, text) {
			var ribbon  = document.getElementById('connection-ribbon');
			var icon    = document.getElementById('connection-ribbon-icon');
			var txtNode = document.getElementById('connection-ribbon-text');
			if (!ribbon) return;

			var colours = {
				success : { bg: '#d4edda', border: '#28a745', color: '#155724' },
				warning : { bg: '#fff3cd', border: '#ffc107', color: '#856404' },
				danger  : { bg: '#f8d7da', border: '#dc3545', color: '#721c24' },
				info    : { bg: '#d1ecf1', border: '#17a2b8', color: '#0c5460' },
			};
			var c = colours[type] || colours['info'];
			ribbon.style.background   = c.bg;
			ribbon.style.border       = '2px solid ' + c.border;
			ribbon.style.color        = c.color;
			ribbon.style.display      = 'block';
			icon.innerHTML  = iconHtml;
			txtNode.textContent = text;
		}
	}());
	</script>
<?php endif ?>
