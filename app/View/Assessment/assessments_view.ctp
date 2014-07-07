<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Assessment Details'));
$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'index'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'assessmentsEdit', $data['id']), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'assessment');
$this->start('contentBody');
?>
<fieldset class="section_group info">
	<legend><?php echo __('Assessment Details'); ?></legend>
	<div class="row">
		<div class="col-md-3"><?php echo __('Code'); ?></div>
		<div class="col-md-4"><?php echo $data['code']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Name'); ?></div>
		<div class="col-md-4"><?php echo $data['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Description'); ?></div>
		<div class="col-md-4 description"><?php echo $data['description']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Education Level'); ?></div>
		<div class="col-md-4"><?php echo $data['education_level_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Education Programme'); ?></div>
		<div class="col-md-4"><?php echo $data['education_programme_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Education Grade'); ?></div>
		<div class="col-md-4"><?php echo $data['education_grade_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Status'); ?></div>
		<div class="col-md-4"><?php echo $this->Utility->getStatus($data['visible']); ?></div>
	</div>
</fieldset>

<fieldset class="section_group items">
	<legend><?php echo __('Assessment Items'); ?></legend>
	
	<div class="table-responsive">
	 <table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<th class="table_cell cell_subject_code"><?php echo __('Code'); ?></th>
				<th class="table_cell"><?php echo __('Subject'); ?></th>
				<th class="table_cell cell_number_input"><?php echo __('Minimum'); ?></th>
				<th class="table_cell cell_number_input"><?php echo __('Maximum'); ?></th>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php 
			foreach($data['AssessmentItem'] as $item) {
				if($item['visible'] == 1) {
			?>
			<tr class="table_row">
				<td class="table_cell"><?php echo $item['code']; ?></td>
				<td class="table_cell"><?php echo $item['name']; ?></td>
				<td class="table_cell cell_number"><?php echo $item['min']; ?></td>
				<td class="table_cell cell_number"><?php echo $item['max']; ?></td>
			</tr>
			<?php }
			} ?>
		</tbody>
	</table>
	</div>
</fieldset>

<?php $this->end(); ?>