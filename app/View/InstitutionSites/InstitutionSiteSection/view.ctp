<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $data[$model]['school_year_id']), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $data[$model]['id']), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => $model, 'remove', $data[$model]['id']), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/controls');
?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('SchoolYear.name'); ?></div>
	<div class="col-md-6"><?php echo $data['SchoolYear']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('InstitutionSiteClass.shift'); ?></div>
	<div class="col-md-6"><?php echo $data['InstitutionSiteShift']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('EducationGrade.name'); ?></div>
	<div class="col-md-6">
		<?php
		foreach($grades as $g) {
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
