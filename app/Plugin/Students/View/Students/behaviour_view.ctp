<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="behaviourView" class="content_wrapper">
    <h1>
        <span><?php echo __('Behaviour Details'); ?></span>
		<?php
		$data = $studentBehaviourObj[0]['StudentBehaviour'];
		echo $this->Html->link(__('List'), array('action' => 'behaviour', $data['student_id']), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>

    <div class="row edit">
        <div class="label"><?php echo __('Institution Site'); ?></div>
        <div class="value">
        <?php echo $institutionSiteOptions[$institution_site_id]; ?>
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
