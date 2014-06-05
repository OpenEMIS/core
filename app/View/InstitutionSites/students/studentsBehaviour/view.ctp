<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour Details'));

$data = $studentBehaviourObj[0]['StudentBehaviour'];

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'studentsBehaviour', $data['student_id']), array('class' => 'divider'));
if ($institutionSiteId == $data['institution_site_id']) {
	if ($_edit) {
		echo $this->Html->link(__('Edit'), array('action' => 'studentsBehaviourEdit', $data['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link(__('Delete'), array('action' => 'studentsBehaviourDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
}
$this->end();

$this->start('contentBody');
?>

<div id="studentBehaviourView" class="content_wrapper dataDisplay">
    <div class="row edit">
        <div class="label"><?php echo __('Institution Site'); ?></div>
        <div class="value">
			<?php echo $institutionSiteOptions[$data['institution_site_id']]; ?>                                           
        </div>
    </div>

    <div class="row edit">
		<div class="label"><?php echo __('Category'); ?></div>
		<div class="value"><?php echo $categoryOptions[$data['student_behaviour_category_id']]; ?></div>
	</div>

	<div class="row edit">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($data['date_of_behaviour']); ?></div>
    </div>

	<div class="row edit">
		<div class="label"><?php echo __('Title'); ?></div>
		<div class="value">
			<?php
			echo $data['title'];
			?>
		</div>
	</div>



	<div class="row edit">
		<div class="label"><?php echo __('Description'); ?></div>
		<div class="value">
			<?php
			echo $data['description'];
			?>
		</div>
	</div>

	<div class="row edit">
		<div class="label"><?php echo __('Action'); ?></div>
		<div class="value">
			<?php
			echo $data['action'];
			?>
		</div>
	</div>
</div>
<?php $this->end(); ?>
