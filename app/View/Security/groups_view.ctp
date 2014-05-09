<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Group Details'));
$this->start('contentActions');
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'groupsEdit', $data['SecurityGroup']['id']), array('class' => 'divider'));
}
if($_accessControl->check($this->params['controller'], 'groupsUsers')) {
	echo $this->Html->link(__('Users & Roles'), array('action' => 'groupsUsers', $data['SecurityGroup']['id']), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'groups');
$this->assign('contentClass', 'edit');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<fieldset class="section_group" style="padding-bottom: 10px;">
	<legend><?php echo __('Information'); ?></legend>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Name'); ?></label>
		<div class="col-md-4"><?php echo $data['SecurityGroup']['name']; ?></div>
	</div>
</fieldset>

<fieldset class="section_group">
	<legend><?php echo __('Access Control'); ?></legend>
	<fieldset class="section_break">
		<legend><?php echo __('Areas'); ?></legend>
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell cell_area"><?php echo __('Level'); ?></td>
					<td class="table_cell"><?php echo __('Area'); ?></td>
				</tr>
			</thead>
			
			<tbody class="table_body">
				<?php foreach($data['SecurityGroup']['areas'] as $areaObj) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $areaObj['area_level_name']; ?></td>
					<td class="table_cell"><?php echo $areaObj['area_name']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Institutions'); ?></legend>
		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell"><?php echo __('Institution'); ?></td>
				</tr>
			</thead>
			
			<tbody class="table_body">
				<?php foreach($data['SecurityGroup']['sites'] as $siteObj) { ?>
				<tr class="table_row">
					<td class="table_cell"><?php echo $siteObj['institution_site_name']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</fieldset>
</fieldset>
<?php $this->end(); ?>