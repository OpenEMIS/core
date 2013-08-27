<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
echo $this->Html->css('finance', 'stylesheet', array('inline' => false));

echo $this->Html->script('financePerEducation', false);


$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear))? $selectedYear : $currentYear;
$currency = "({$this->Session->read('configItem.currency')})";
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="finance" class="content_wrapper edit">
	
	<h1>
		<span><?php echo __('Total Public Expenditure Per Education Level'); ?></span>
		<!-- <?php echo $this->Html->link('View', array('action' => 'financePerEducationLevel'), array('class' => 'divider')); ?> -->
		<?php echo $this->Html->link(__('View'), array('action' => 'financePerEducationLevel'), array('id' => 'viewLink', '	class' => 'divider'));
		?>
	</h1>

	<?php
	echo $this->Form->create('Finance', array(
			'url' => array(
				'controller' => 'Finance',
				'action' => 'financePerEducationLevel'
			),
			'model' => 'PublicExpenditureEducationLevel',
            'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);
	?>
	
	<input type="hidden" name="data[previousAction]" value="edit"/>
	<div class="row per_education_level_year year">
		<div class="label"><?php echo __('Year'); ?></div>
		<?php 
			echo $this->Utility->getYearList($this->Form,'data[year]',array(
				'name' => "data[year]",
				'id' => "year_id",
				'maxlength' => 30,
				'desc' => true,
				'label' => false,
				'div' => false), true);
		?>
	</div>

	<fieldset id="area_section_group" class="section_group">
        <legend id="area"><?php echo __('Area'); ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_id',$initAreaSelection['area_id'], array()); ?>
    </fieldset>

	<fieldset id="data_section_group" class="section_group">
		<legend><?php echo __('Total Public Expenditure Per Education Level'); ?></legend>

		<?php foreach($eduLevels as $eduLevel): ?>
			<fieldset class="section_break">
				<legend><?php echo $eduLevel['name']; ?></legend>
				
				<div class="mainlist" id="edu_level_<?php echo $eduLevel['id']; ?>" name="edu_level_<?php echo $eduLevel['id']; ?>">
					<div class="table">
						<div class="table_head">
							<div class="table_cell cell_arealevel"><?php echo __('Area Level'); ?></div>
							<div class="table_cell"><?php echo __('Area'); ?></div>
							<div class="table_cell"><?php echo __('Amount'); ?> <?php echo $currency; ?></div>
						</div>

						<div class="table_body"></div>
					</div>
				</div>
			</fieldset>
			<?php endforeach; ?>

	</fieldset>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return Finance.save();" />
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" onClick="Finance.TotalPublicExpenditurePerEducationLevelBacktoList();" />
		</div>

	</fieldset>

	<?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">

$(document).ready(function(){
	var selectedYear = <?php echo $selectedYear?>
	
	if($('#year_id').children().first().val().length < 1){
		$('#year_id').children().first().remove();
	}

	if(selectedYear !== null){
		// $("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').trigger('change');
		Finance.year = $("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').val();
	}else{
		// $("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected').trigger('change');
		Finance.year = $("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected').val();
	}

	$('#viewLink').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() + 'Finance/financePerEducationLevel');
		$('form').submit();
	});

	Finance.isEditable = true;
	<?php if(isset($initAreaSelection) && count($initAreaSelection)>0){ ?>
	Finance.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	var currentSelect;
	for(var key in Finance.initAreaSelection){
		currentSelect = $('select[name*="['+key+']"]');
		Finance.parentAreaIds.push(parseInt(Finance.initAreaSelection[key]));
		currentSelect.find($('option[value="'+Finance.initAreaSelection[key]+'"]')).attr('selected','selected');
	}
	<?php } ?>

    Finance.fetchData();

});

</script>