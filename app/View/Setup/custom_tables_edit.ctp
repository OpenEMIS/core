<?php
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);
echo $this->Html->script('custom_table', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="census_table" class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('View'), array('action' => 'setupVariables', $selectedCategory, $siteType), array('class' => 'divider'));
		}
		?>
	</h1>
	
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

	<?php
	echo $this->Form->create('CensusGrid', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'Setup', 'action' => 'customTablesEdit', $selectedCategory, $siteType)
	));
	?>

	<div class="table">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell cell_site_type"><?php echo __('Site Type'); ?></div>
			<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
		</div>
	</div>
	
	<?php
	$model = 'CensusGrid';
	echo $this->Utility->getListStart();
	foreach($data as $i => $obj) {
		$isVisible = $obj[$model]['visible']==1;
		$fieldName = sprintf('data[%s][%s][%%s]', $model, $i);
		
		echo $this->Utility->getListRowStart($i, $isVisible);
		echo $this->Utility->getIdInput($this->Form, $fieldName, $obj[$model]['id']);
		echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
		echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
		echo $this->Utility->getNameInput($this->Form, $fieldName, $obj[$model]['name'], false);
		echo '<div class="cell cell_site_type">' . $obj['InstitutionSiteType']['name'] . '</div>';
		echo $this->Utility->getOrderControls();
		echo $this->Utility->getListRowEnd();
	}
	echo $this->Utility->getListEnd();
	?>
		
	<div class="controls">
        <input type="submit" value="Save" class="btn_save btn_right">
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setupVariables', $selectedCategory, $siteType), array('class' => 'btn_cancel btn_left')); ?>
    </div>

	<?php echo $this->Form->end(); ?>
</div>
