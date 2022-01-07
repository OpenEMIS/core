<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);
echo $this->Html->css('https://cdn.datatables.net/v/dt/dt-1.10.23/datatables.min.css', ['block' => true]);
echo $this->Html->script('https://cdn.datatables.net/v/dt/dt-1.10.23/datatables.min.js', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if (!array_key_exists('type', $btn) || $btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();
$this->start('panelBody');
foreach ($rowHeader as $key => $val) {
	foreach($val AS $kay1 => $val1){
		if(isset($val1)){
			$rowHeaderData[] = $val1;
		}
	}
}
$params = $this->request->params;
$url = ['plugin' => $params['plugin'], 'controller' => $params['controller'], 'action' => 'ajaxGetReportProgress'];
$url = $this->Url->build($url);
$table = $ControllerAction['table'];
$downloadText = __('Downloading...');
?>
<style type="text/css">
.none { display: none !important; }
</style>
<script>
$(document).ready( function () {
    $('#myTable').DataTable();
	$('.dataTables_length').hide();
	$('#myTable_filter').hide();
} );
</script>

<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved" id="myTable">
		<thead>
			<?php foreach ($rowHeaderData as $newArrdata) : ?>
				<th><?= $newArrdata ?> </th>
			<?php endforeach; ?>
			</thead>
			<tbody>
				<?php foreach ($newArr2 as $key => $val) :?>
				<tr>
					<?php foreach ($val as $key1 => $val1) :?>
					<td><?=$val1 ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
$this->end();