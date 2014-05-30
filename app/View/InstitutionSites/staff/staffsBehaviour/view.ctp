<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staffBehaviourView" class="content_wrapper">
    <h1>
        <span><?php echo __('Behaviour Details'); ?></span>
		<?php
		$data = $staffBehaviourObj[0]['StaffBehaviour'];
		echo $this->Html->link(__('List'), array('action' => 'staffsBehaviour', $data['staff_id']), array('class' => 'divider'));
        if($institution_site_id == $data['institution_site_id']){
    		if($_edit) {
    			echo $this->Html->link(__('Edit'), array('action' => 'staffBehaviourEdit', $data['id']), array('class' => 'divider'));
    		}
    		if($_delete) {
    			echo $this->Html->link(__('Delete'), array('action' => 'staffBehaviourDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
    		}
        }
		?>
    </h1>
    <?php echo $this->element('alert'); ?>

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
    		<?php echo $this->Form->input('title', array('id' => 'title',
    		                                             'class' => 'default',
    		                                             'label' => false,
    		                                             'disabled' => true,
    		                                             'default' => $data['title'])); ?>
    		</div>
    	</div>

    	<div class="row edit">
    		<div class="label"><?php echo __('Description'); ?></div>
    		<div class="value">
    		<?php echo $this->Form->input('description', array('class' => 'default',
    		                                                   'label' => false,
    		                                                   'disabled' => true,
    		                                                   'type' => 'textarea',
    		                                                   'onkeyup' => 'utility.charLimit(this)',
    		'default' => $data['description'])); ?>
    		</div>
    	</div>

        <div class="row edit">
    		<div class="label"><?php echo __('Action'); ?></div>
    		<div class="value">
    		<?php echo $this->Form->input('action', array('class' => 'default',
    		                                              'label' => false,
    		                                              'disabled' => true,
    		                                              'type' => 'textarea',
    		                                              'onkeyup' => 'utility.charLimit(this)',
    		'default'=>$data['action'])); ?>
    		</div>
    	</div>
</div>
