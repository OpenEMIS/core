<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => $_action.'Edit'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteClass/controls');
if(!empty($data)) :
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('EducationGrade.name'); ?></th>
				<th><?php echo $this->Label->get('EducationSubject.name'); ?></th>
				<th><?php echo $this->Label->get('EducationSubject.code'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($data as $obj) { ?>
			<tr>
				<td><?php echo $obj['EducationGrade']['name']; ?></td>
				<td><?php echo $obj['EducationSubject']['name']; ?></td>
				<td><?php echo $obj['EducationSubject']['code']; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>

<?php 
endif;
$this->end();
?>
