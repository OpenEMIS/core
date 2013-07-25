<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo __('Behaviour'); ?></span>
		<?php
		if($_add_behaviour) {
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
	
	<!--<div class="row year">
		<div class="label"><?php //echo __('Year'); ?></div>
		<div class="value">
			<?php
			/*echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'default' => $selectedYear,
				'onchange' => 'InstitutionSiteClasses.navigate()'
			));*/
			?>
		</div>
	</div>-->
	
	<div class="table full_width allow_hover" action="InstitutionSites/studentsBehaviourView/">
		<div class="table_head">
			<div class="table_cell cell_behaviour_date"><?php echo __('Date'); ?></div>
            <div class="table_cell cell_behaviour_category" style="text-align:left"><?php echo __('Category'); ?></div>
            <div class="table_cell cell_behaviour_title" style="text-align:left"><?php echo __('Title'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $id => $obj) { $i=0; ?>
			<div class="table_row" row-id="<?php echo $obj['StudentBehaviour']['id']; ?>">
				<div class="table_cell center"><?php echo $this->Utility->formatDate($obj['StudentBehaviour']['date_of_behaviour']); ?></div>
                <div class="table_cell cell_behaviour_category"><?php echo $obj['StudentBehaviourCategory']['name']; ?></div>
                <div class="table_cell cell_behaviour_title"><?php echo $obj['StudentBehaviour']['title']; ?></div>
			</div>
			<?php } // end for (multigrade) ?>
		</div>
	</div>
</div>
