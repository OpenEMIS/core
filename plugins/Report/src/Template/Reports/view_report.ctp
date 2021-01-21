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
$counter = 0;
$emptyCounter = 0;
foreach ($rowHeader as $key => $value) {
	foreach($value AS $kay1 => $val){
		if(isset($val)){
			$newArr[] = $val;
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
} );
</script>

<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved" id="myTable">
			<thead>
			<?php foreach ($newArr as $newArrdata) : ?>
				<th><?= $newArrdata ?> </th>
			<?php endforeach; ?>
			</thead>
			<tbody>
				<?php foreach ($newArr2 as $obj) :?>
				<tr>
					<td><?= $obj['Institution'] ?></td>
					<td><?= $obj['Region Code'] ?></td>
					<td><?= $obj['Region Name'] ?></td>
					<td><?= $obj['District Code'] ?></td>
					<td><?= $obj['District Name'] ?></td>
					<td><?= $obj['Institution Class'] ?></td>
					<td><?= $obj['Subject Name'] ?></td>
					<td><?= $obj['Subject Teacher'] ?></td>
					<td><?= $obj['Number of seats'] ?></td>
					<td><?= $obj['Male students'] ?></td>
					<td><?= $obj['Female students'] ?></td>
					<td><?= $obj['Total students'] ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
$this->end();