<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);

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
foreach($rowData as $newKey => $newDataVal){
	foreach($newDataVal as $kay2 => $new_data_arr){
		if(isset($new_data_arr)){
			$newArr2[] = $new_data_arr;
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

<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved">
			<thead><?= $this->Html->tableHeaders($newArr) ?></thead>
			<tbody>
				<?php foreach ($newArr2 as $obj) :
				 ?>
				<tr>
				<?php foreach ($obj as $newObj) : ?>
					<td><?= $newObj ?></td>
				<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
$this->end();