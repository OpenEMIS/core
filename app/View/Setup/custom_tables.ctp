<?php
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);
echo $this->Html->script('custom_table', false);
echo $this->Html->script('setup_variables', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="census_table" class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'setupVariablesEdit', $selectedCategory, $siteType), array('class' => 'divider'));
		}
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'CustomTablesEditDetail', $selectedCategory, $siteType), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'class' => 'default',
			'label' => false,
			'div'=> false,
			'options' => $categoryList,
			'default' => $selectedCategory,
			'url' => 'Setup/setupVariables/',
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	<div class="row" style="margin-bottom: 20px;">
		<?php
		echo $this->Form->input('institution_site_type_id',	array(
			'id' => 'siteTypeId',
			'class' => 'default',
			'label' => false,
			'div'=> false,
			'options' => $siteTypes,
			'default' => $siteType,
			'url' => sprintf('Setup/setupVariables/%s/', $selectedCategory),
			'onchange' => 'custom.changeSiteType(this)'
		));
		?>
	</div>
        
	<div class="table full_width allow_hover" action="Setup/customTablesEditDetail/<?php echo $selectedCategory . '/' . $siteType . '/'; ?>">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell cell_name"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Description'); ?></div>
			<div class="table_cell cell_site_type"><?php echo __('Site Type'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row<?php echo $obj['CensusGrid']['visible']!=1 ? ' inactive' : ''; ?>" row-id="<?php echo $obj['CensusGrid']['id'] ?>">
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['CensusGrid']['visible']==1); ?></div>
				<div class="table_cell"><?php echo $obj['CensusGrid']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['CensusGrid']['description']; ?></div>
				<div class="table_cell"><?php echo $obj['InstitutionSiteType']['name']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
