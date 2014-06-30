<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour Details'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'behaviourStaff', $data[$model]['staff_id']), array('class' => 'divider'));
     //   if($institutionSiteId == $data['institution_site_id']){
    		if($_edit) {
    			echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'behaviourStaffEdit', $staffId, $data[$model]['id']), array('class' => 'divider'));
    		}
    		if($_delete) {
    			echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'behaviourStaffDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
    		}
    //    }
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
/*
?>

<div id="staffBehaviourView" class="content_wrapper dataDisplay">

    <div class="row edit">
        <div class="label"><?php echo __('Institution Site'); ?></div>
        <div class="value">
        <?php echo $institutionSiteOptions[$data['institution_site_id']]; ?>  
        </div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Category'); ?></div>
		<div class="value"><?php echo $categoryOptions[$data['staff_behaviour_category_id']]; ?></div>
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
    		<?php echo $data['action']; ?>
    		</div>
    	</div>
</div>
<?php */ $this->end(); ?>