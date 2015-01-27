<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $_action, $data[$model]['academic_period_id']), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $_action.'Edit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $_action.'Delete', $data[$model]['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/classes/controls');
?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('AcademicPeriod.name'); ?></div>
	<div class="col-md-6"><?php echo $data['AcademicPeriod']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteClass.no_of_seats'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['no_of_seats']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.sections'); ?></div>
	<div class="col-md-6">
		<?php
		foreach($sections as $g) {
			echo $g . '<br />';
		}
		?>
	</div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.modified_by'); ?></div>
	<div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.modified'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.created_by'); ?></div>
	<div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.created'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['created']; ?></div>
</div>
<?php
$this->end();
?>
