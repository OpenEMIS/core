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
		<span><?php echo __('List of Custom Census Table'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'CustomTablesEditDetail'), array('class' => 'divider'));
		}
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'CustomTablesEdit', $siteType), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row input">
		<div class="label" style="width: 60px;"><?php echo __('View'); ?></div>
		<div class="value">
			<select id="customfieldchoices">
				<?php foreach($CustomFieldModelLists as $k=> $arrModelval){
					echo '<option value="'.$k.'"'.(($defaultModel == $k || $defaultModel == '')?'selected ="selected"':'').'>'.__($arrModelval['label']).'</option>';
				} ?>
			</select>
		</div>
	</div>
	
	<div class="row input" style="margin-bottom: 10px;">
		<div class="label" style="width: 60px;"><?php echo __('Filter by'); ?></div>
		<div class="value">
			 <?php
			if(count($siteTypes)>0) 
				echo $this->Form->input('institution_site_type_id',	array(
					'options'=>$siteTypes,
					'default'=>$siteType,
					'label' => FALSE,
					'div'=>FALSE,
					'onChange'=>'CustomTable.changeCustomTableFilter(this)'
				)); 
			?>
		</div>
	</div>
        
	<div class="table full_width allow_hover" action="Setup/customTablesEditDetail/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Name'); ?></div>
			<div class="table_cell"><?php echo __('Description'); ?></div>
			<div class="table_cell"><?php echo __('Site Type'); ?></div>
		</div>
		
		<div class="table_body">
			<?php
			//pr($data);
			foreach($data as $rec) {
			?>
			<div class="table_row" row-id="<?php echo $rec['CensusGrid']['id'] ?>">
				<div class="table_cell cell_visible <?php echo ($rec['CensusGrid']['visible'] == 1)? 'green' : 'red' ; ?>"><?php echo ($rec['CensusGrid']['visible'] == 1)? '&#10003;' : '&#10008;' ; ?></div>
				<div class="table_cell"><?php echo $rec['CensusGrid']['name']; ?></div>
				<div class="table_cell"><?php echo $rec['CensusGrid']['description']; ?></div>
				<div class="table_cell"><?php echo $rec['InstitutionSiteType']['name']; ?></div>
				
			</div>
			<?php } ?>
			
		</div>
	</div>
	<?php if(sizeof($data)==0) { ?>
	<div class="row center" style="color: red"><?php echo __('No Custom Census Table.'); ?></div>
	<?php } ?>
</div>
