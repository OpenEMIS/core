<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(empty($selectedClass)){
	if ($_edit) {
		$params = array('action' => $model, 'edit');
		if(isset($selectedGrade)) {
			$params[] = $selectedGrade;
		}
		echo $this->Html->link($this->Label->get('general.edit'), $params, array('class' => 'divider'));
	}
}

$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/controls');
if(!empty($data)) :
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.category'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['SecurityUser']['openemis_no']; ?></td>
				<td><?php echo $this->Model->getName($obj['Student']); ?></td>
				<td><?php echo $obj['StudentCategory']['name']; ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php 
endif;
$this->end();
?>
