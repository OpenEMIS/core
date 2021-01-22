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
// $headerCount = count($newArr);
$newCounter = 0;
function getValueData($obj, $newArr){
	$headerCount = count($newArr);
	if($headerCount > 10 && $headerCount <=11){
		$extraValue .= "<td>".$obj[$newArr[10]]."</td>";
	}
	if($headerCount > 11 && $headerCount <=12){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
	}
	if($headerCount > 12 && $headerCount <=13){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
	}
	if($headerCount > 13 && $headerCount <=14){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
	}
	if($headerCount > 14 && $headerCount <=15){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[14]]."</td>";
	}
	if($headerCount > 15 && $headerCount <=16){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[14]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[15]]."</td>";
	}
	if($headerCount > 16 && $headerCount <=17){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[14]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[15]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[16]]."</td>";
	}
	if($headerCount > 17 && $headerCount <=18){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[14]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[15]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[16]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[17]]."</td>";
	}
	if($headerCount > 18 && $headerCount <=19){
		$extraValue .= "<td>".$obj[$newArr[11]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[12]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[13]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[14]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[15]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[16]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[17]]."</td>";
		$extraValue .= "<td>".$obj[$newArr[18]]."</td>";
	}
	return $extraValue;
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
				<?php $newdata = getValueData($obj, $newArr); ?>
				<tr>
					<td><?= $obj[$newArr[0]] ?></td>
					<td><?= $obj[$newArr[1]] ?></td>
					<td><?= $obj[$newArr[2]] ?></td>
					<td><?= $obj[$newArr[3]] ?></td>
					<td><?= $obj[$newArr[4]] ?></td>
					<td><?= $obj[$newArr[5]] ?></td>
					<td><?= $obj[$newArr[6]] ?></td>
					<td><?= $obj[$newArr[7]] ?></td>
					<td><?= $obj[$newArr[8]] ?></td>
					<td><?= $obj[$newArr[9]] ?></td>
					<td><?= $obj[$newArr[10]] ?></td>
					<?= $newdata ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
$this->end();