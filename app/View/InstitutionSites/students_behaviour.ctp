<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="studentBehaviour" class="content_wrapper">
    <h1>
        <span><?php echo __('List of Behaviour'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'studentsView', $id), array('class' => 'divider'));
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'studentsBehaviourAdd', $id), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviour'),
		'inputDefaults' => array('label' => false, 'div' => false)
	)); 
	?>
	
	<div class="table full_width allow_hover" action="InstitutionSites/studentsBehaviourView/">
		<div class="table_head">
			<div class="table_cell cell_behaviour_date"><?php echo __('Date'); ?></div>
            <div class="table_cell cell_behaviour_category"><?php echo __('Category'); ?></div>
            <div class="table_cell cell_behaviour_title"><?php echo __('Title'); ?></div>
            <div class="table_cell"><?php echo __('Insitution Site'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $id => $obj) { $i=0; ?>
			<div class="table_row" row-id="<?php echo $obj['StudentBehaviour']['id']; ?>">
				<div class="table_cell center"><?php echo $this->Utility->formatDate($obj['StudentBehaviour']['date_of_behaviour']); ?></div>
                <div class="table_cell"><?php echo $obj['StudentBehaviourCategory']['name']; ?></div>
                <div class="table_cell"><?php echo $obj['StudentBehaviour']['title']; ?></div>
                <div class="table_cell"><?php echo $obj['InstitutionSite']['name']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
