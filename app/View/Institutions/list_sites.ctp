<?php echo $this->Html->css('table', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->css('institution', 'stylesheet', array('inline' => false)); ?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site-list" class="content_wrapper">
	<h1><?php echo __('List of Institution Sites'); ?></h1>
	
	<?php echo $this->element('alert'); ?>
	<?php
	$tableAttr = $_view_sites ? '"table full_width allow_hover" action="InstitutionSites/index/"' : '"table full_width"';
	?>
	<div class=<?php echo $tableAttr; ?>>
		<div class="table_head">
			<div class="table_cell cell_code"><?php echo __('Code'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Area'); ?></div>
			<div class="table_cell"><?php echo __('Site Type'); ?></div>
		</div>
		
		<div class="table_body">
			<?php
			foreach($sites as $site) {
			?>
			<div class="table_row" row-id="<?php echo $site['InstitutionSite']['id'] ?>">
				<div class="table_cell"><?php echo $site['InstitutionSite']['code']; ?></div>
				<div class="table_cell"><?php echo $site['InstitutionSite']['name']; ?></div>
				<div class="table_cell"><?php echo $site['Area']['name']; ?></div>
				<div class="table_cell"><?php echo $site['InstitutionSiteType']['name']; ?></div>
			</div>
			<?php } ?>
			
		</div>
	</div>
	<?php if(sizeof($sites)==0) { ?>
	<div class="row center" style="color: red"><?php echo __('No Institution Sites.'); ?></div>
	<?php } ?>
</div>