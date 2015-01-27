<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	$params = array('action' => $_action.'Edit');
	if(isset($selectedSection)) {
		$params[] = $selectedSection;
	}
	echo $this->Html->link($this->Label->get('general.edit'), $params, array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/classes/controls');
if(!empty($data)) :
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.openemisId'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) : ?>
			<tr>
				<td><?php echo $obj['Student']['identification_no']; ?></td>
				<td><?php echo $this->Model->getName($obj['Student']); ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php 
endif;
$this->end();
?>
