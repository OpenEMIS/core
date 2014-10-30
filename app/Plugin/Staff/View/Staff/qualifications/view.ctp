<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'qualifications'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'qualificationsEdit', $id), array('class' => 'divider'));
}
if ($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'qualificationsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();
$this->start('contentBody');
//echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Level'); ?></div>
	<div class="col-md-6"><?php echo $data['QualificationLevel']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Institution'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['qualification_institution_name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Institution') . '/' . __('Country'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['qualification_institution_country']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Qualification') . '/' . __('Title'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['qualification_title']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Major') . '/' . __('Specialisation'); ?></div>
	<div class="col-md-6"><?php echo $data['QualificationSpecialisation']['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Graduate') . '/' . __('Year'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['graduate_year']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Document') . '/' . __('No'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['document_no']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Grade') . '/' . __('Score'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['gpa']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Attachment'); ?></div>
	<div class="col-md-6"><?php echo $this->Html->link($data[$model]['file_name'], array('action' => 'qualificationsAttachmentsDownloads', $data[$model]['id'])); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Modified on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['modified']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created by'); ?></div>
	<div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Created on'); ?></div>
	<div class="col-md-6"><?php echo $data[$model]['created']; ?></div>
</div>
<?php
$this->end();
?>
