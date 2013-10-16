<?php
echo $this->Html->script('custom_table', false);
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
$ctr = 1; 
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="custom" class="content_wrapper">
	<h1>
		<span ><?php echo __('Institution Site Totals - Tables'); ?></span>
		<?php echo $this->Html->link(__('List'), array('action' => 'setupVariables', $selectedCategory, $siteType), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php
	echo $this->Form->create('CensusGrid', array(
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'Setup', 'action' => 'customTablesEditDetail', $selectedCategory, $siteType, $id)
	));
	?>
	
	<?php echo $this->Form->input('id',array('type'=>'hidden','value'=>$id));?>
	<?php echo $this->Form->input('order',array('type'=>'hidden','value'=>0));?>
	<div class="row edit" style="margin-bottom: 20px;">
		<div class="label" style="width: 90px;"><?php echo __('Site Type'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('institution_site_type_id',	array(
				'id' => 'siteTypeId',
				'class' => 'default',
				'options' => array_merge(array('0'=>'All'),$siteTypes),
				'default' => $siteType
			));
			?>
		</div>
	</div>

	<div class="custom_grid">
		<fieldset class="custom_section_break">
			<?php echo $this->Form->hidden('dataId', array('value'=>(array_key_exists('id', $data['CensusGrid'])?$data['CensusGrid']['id']:'') ) ); ?>
			<legend><div class="input_wrapper"><?php echo $this->Form->input('name',array('value'=>@$data['CensusGrid']['name']));  ?></div></legend>
		</fieldset>
		
		<div class="desc"><?php echo $this->Form->input('description',array('value'=>@$data['CensusGrid']['description'],'rows' => '5', 'cols' => '8', 'style' => 'width:98%')); ?></div>
		<div class="x_title"><div class="input_wrapper" style="margin: 0px auto; width: 250px;"><?php echo $this->Form->input('x_title',array('value'=>@$data['CensusGrid']['x_title'],'style'=>'text-align:center')); ?></div></div>
		
		<div class="table_wrapper">
			<div class="table">
				<div class="table_head">
					<div class="table_cell y_col">&nbsp;</div>
					<?php $inCtr= 1; foreach($data['CensusGridXCategory'] as $statVal) { ?>
					<div class="table_cell">
						<span id="Xlabel_<?php echo $inCtr;?>">
						<?php echo $statVal['name']; ?>
						</span>
					</div>
					<?php $inCtr++; } ?>
				</div>
				
				<div class="table_body" id="section">
				<?php
				$inCtr= 1;
				foreach($data['CensusGridYCategory']  as $yCatId => $yCatName) {
				?>
					<div id="data-grid-row-<?php echo$inCtr;?>" class="table_row<?php echo $ctr++%2==0? ' even' : ''; ?>">
						<div class="table_cell">
							<span id="Ylabel_<?php echo $inCtr;?>">
								<?php echo $yCatName['name'];  ?>
							</span>
						</div>
						<?php foreach($data['CensusGridXCategory'] as $xCatId => $xCatName) { ?>
						<div class="table_cell">
							
						</div>
						<?php } ?>
					</div>
					
				<?php $inCtr++;  } ?>
					
				</div>
			</div>
		</div>
	</div>
	
	<div id="YCatList" class="custom_field" style="margin-top: 20px;">
		<div class="field_label"><?php echo __('Y Category'); ?></div>
		<div class="field_value">
			<ul class="quicksand options" data-category="y">
				<?php
				$ctr = 1;
				$fieldOption =  sprintf('data[%s][%%s][%%s]', 'CensusGridYCategory');
				foreach($data['CensusGridYCategory']  as $yCatId => $yCatName) {
				?>
				
				<?php 	$isOptionVisible = $yCatName['visible']==1; ?>
					<li data-id="<?php echo $ctr;?>" class="<?php echo !$isOptionVisible ? 'inactive' : ''; ?>">
						<input type="hidden" id="order" name="<?php echo sprintf($fieldOption, $ctr, 'order'); ?>" value="<?php echo $ctr; ?>" />
						<input type="hidden" id="visible" name="<?php echo sprintf($fieldOption, $ctr, 'visible'); ?>" value="<?php echo $yCatName['visible'] ?>" />
													<?php if(isset($yCatName['id'])){ ?>
						<input type="hidden" id="id" name="<?php echo sprintf($fieldOption, $ctr, 'id'); ?>" value="<?php echo $yCatName['id']; ?>" />
													<?php } ?>
						<input type="hidden" id="ref_id" name="<?php echo sprintf($fieldOption, $ctr, 'census_grid_id'); ?>" value="<?php echo $id; ?>" />
						<input type="text" class="default" onKeyUp="$('#Ylabel_<?php echo $ctr; ?>').html(this.value);" name="<?php echo sprintf($fieldOption, $ctr, 'name'); ?>" value="<?php echo $yCatName['name']; ?>" />
						<span class="icon_visible"></span>
						<span class="icon_up" onClick="CustomTable.reorder(this);CustomTable.moveY(this);"></span>
						<span class="icon_down" onClick="CustomTable.reorder(this);CustomTable.moveY(this);"></span>
					</li>
				<?php $ctr++;  ?>
				<?php } ?>
			</ul>
			<div class="row" style="margin: 5px 0"><a id="addRow" class="void icon_plus"><?php echo __('Add'); ?></a></div>
		</div>
	</div>
	
	<div id="XCatList" class="custom_field" style="margin-top: 20px;">
		<div class="field_label"><?php echo __('X Category'); ?></div>
		<div class="field_value">
			<ul class="quicksand options" data-category="x">
				<?php
				$ctr = 1;
				$fieldOption =  sprintf('data[%s][%%s][%%s]', 'CensusGridXCategory');
				foreach($data['CensusGridXCategory']  as $xCatId => $xCatName) {
				?>
					
				<?php $isOptionVisible = $xCatName['visible']==1; ?>
					<li data-id="<?php echo $ctr;?>" class="<?php echo !$isOptionVisible ? 'inactive' : ''; ?>">
						<input type="hidden" id="order" name="<?php echo sprintf($fieldOption, $ctr, 'order'); ?>" value="<?php echo $ctr; ?>" />
						<input type="hidden" id="visible" name="<?php echo sprintf($fieldOption, $ctr, 'visible'); ?>" value="<?php echo $xCatName['visible'] ?>" />
						<?php if(isset($xCatName['id'])){ ?>
						<input type="hidden" id="id" name="<?php echo sprintf($fieldOption, $ctr, 'id'); ?>" value="<?php echo $xCatName['id']; ?>" />
						<?php } ?>
						<input type="hidden" id="ref_id" name="<?php echo sprintf($fieldOption, $ctr, 'census_grid_id'); ?>" value="<?php echo $id; ?>" />
						<input type="text" class="default" onKeyUp="$('#Xlabel_<?php echo $ctr;?>').html(this.value);" name="<?php echo sprintf($fieldOption, $ctr, 'name'); ?>" value="<?php echo $xCatName['name']; ?>" />
						<span class="icon_visible"></span>
						<span class="icon_up" onClick="CustomTable.reorder(this);CustomTable.moveX(this);"></span>
						<span class="icon_down" onClick="CustomTable.reorder(this);CustomTable.moveX(this);"></span>
					</li>
					
				<?php $ctr++;  ?>
				<?php } ?>
			</ul>
			<div class="row" style="margin: 5px 0"><a id="addCol" class="void icon_plus"><?php echo __('Add'); ?></a></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setupVariables', $selectedCategory, $siteType), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>