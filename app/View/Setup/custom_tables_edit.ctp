<?php
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('custom_field', false);
echo $this->Html->script('custom_table', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
?>

<script type="text/javascript">
function goToSite(id) {
	window.location = "<?php echo $this->Html->url(array("controller" => "Setup","action" => "customTablesEditDetail")); ?>/"+id;
}
</script>

<?php echo $this->element('breadcrumb'); ?>

<div id="census_table" class="content_wrapper">
	<h1>
		<span><?php echo __('Edit Custom Census Table'); ?></span>
		<?php echo $this->Html->link(__('Add'), array('action' => 'CustomTablesEditDetail'), array('class' => 'divider')); ?>
		<?php echo $this->Html->link(__('List'), array('action' => 'CustomTables', $siteType), array('class' => 'divider')); ?>
	</h1>
	
	<div class="row input" style="display:none;">
		<div class="label" style="width: 60px;"><?php echo __('View'); ?></div>
		<div class="value">
			<select id="customfieldchoices" disabled="disabled">
				<?php foreach($CustomFieldModelLists as $k=> $arrModelval){
					echo '<option value="'.$k.'"'.(($defaultModel == $k || $defaultModel == '')?'selected ="selected"':'').'>'.__($arrModelval['label']).'</option>';
				} ?>
			</select>
		</div>
	</div>
	
	<div class="row input" style="margin-bottom: 10px;display:none;">
		<div class="label" style="width: 60px;"><?php echo __('Filter by'); ?></div>
		<div class="value">
			 <?php
			if(count($siteTypes)>0) 
				echo $this->Form->input('institution_site_type_id',	array(
					'options'=>$siteTypes,
					'default'=>$siteType,
					'label' => FALSE,
					'div'=>FALSE,
					'onChange'=>'CustomTable.changeCustomTableFilter(this)',
					'disabled' => 'disabled'
				)); 
			?>
		</div>
	</div>

	<?php
	echo $this->Form->create('CensusGrid', array(
			'id' => 'submitForm',
			'inputDefaults' => array('label' => false, 'div' => false),
			'url' => array('controller' => 'Setup', 'action' => 'customTablesEdit')
		)
	);
	?>

	<div class="table">
		<div class="table_head">
			<div class="table_cell" style="min-width:38px;width:38px;"><?php echo __('Visible'); ?></div>
			<div class="table_cell" style="min-width:165px;"><?php echo __('Name'); ?></div>
			<div class="table_cell" style="min-width:165px;"><?php echo __('Description'); ?></div>
			<div class="table_cell" style="min-width:165px;"><?php echo __('Site Type'); ?></div>
			<div class="table_cell" style="width:59px;"><?php echo __('Order'); ?></div>
		</div>

		<div class="table_body" style="display:none;">

		</div>
	</div>
		<ul class="quicksand table_view" style="margin-bottom:12px;">
			<?php
			//pr(sizeof($data));
			foreach($data as $key => $rec) {
			    //pr($key);
			    $isVisible = $rec['CensusGrid']['visible']==1;
                $fieldName = sprintf('data[%s][%s][%%s]', 'CensusGrid', $key);
			?>
		    <li data-id="<?php echo $key+1; ?>" class="<?php echo (!$isVisible)? 'inactive':''; ?>">
		    <?php echo $this->Utility->getOrderInput($this->Form, $fieldName, ($key));?>
	            <input type="hidden" id="id" name="data[CensusGrid][<?php echo $key; ?>][id]" value="<?php echo $rec['CensusGrid']['id']; ?>" />
				<?php echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible); ?>
				<div class="cell cell_name"><?php echo $rec['CensusGrid']['name']; ?></div>
				<div class="cell cell_description"><?php echo $rec['CensusGrid']['description']; ?></div>
				<div class="cell cell_site_type"><?php echo $rec['InstitutionSiteType']['name']; ?></div>
				<div class="cell cell_order">
					<span class="icon_up" onClick="CustomTable.reorder(this);"></span>
					<span class="icon_down" onClick="CustomTable.reorder(this);"></span>
				</div>
		    </li>
			<?php } ?>
		</ul>
	<div class="controls">
        <input type="submit" value="Save" class="btn_save btn_right">
        <input type="button" value="Cancel" class="btn_cancel btn_left" onclick="window.location=getRootURL()+'Setup/customTables/6';">
    </div>

	<?php echo $this->Form->end(); ?>

	<?php if(sizeof($data)==0) { ?>
	<div class="row center" style="color: red"><?php echo __('No Custom Census Table.'); ?></div>
	<?php } ?>
</div>
