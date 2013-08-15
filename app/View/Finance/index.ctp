<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
echo $this->Html->css('finance', 'stylesheet', array('inline' => false));

echo $this->Html->script('finance', false);

$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear))? $selectedYear : $currentYear;
$currency = "({$this->Session->read('configItem.currency')})";
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="finance" class="content_wrapper">
	
	<h1>
		<span><?php echo __('Total Public Expenditure'); ?></span>
		<?php 
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('id' => 'edit', 'class' => 'divider'));
		} 
		?>
	</h1>

	<?php
	echo $this->Form->create('Finance', array(
			'url' => array('controller' => 'Finance','action' => 'index'),
            'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);
	?>
	
	<div class="row total_public_expenditure">
		<div class="label"><?php echo __('View'); ?></div>
		<div type="select" name="view" value="1" class="value">
			<select name="data[view]" id="view">
				<option value="Total Public Expenditure"><?php echo __('Total Public Expenditure'); ?></option>
				<option value="Total Public Expenditure Per Education Level"><?php echo __('Total Public Expenditure Per Education Level'); ?></option>
			</select>
		</div>
	</div>

	<div class="row total_public_expenditure year">
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

	<div class="row total_public_expenditure">
		<div class="label"><?php echo __('GNP'); ?> <?php echo $currency; ?></div>
		<div id="gnp" class="value"><?php echo (!empty($data['PublicExpenditure']['gross_national_product'])) ? $data['PublicExpenditure']['gross_national_product'] : __("No Data"); ?></div>
	</div>

	<fieldset id="area_section_group" class="section_group">
        <legend id="area"><?php echo __('Area'); ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_id',$initAreaSelection['area_id'], array()); ?>
    </fieldset>

	<?php echo $this->Form->end(); ?>
	
	<fieldset id="data_section_group" class="section_group">
		<legend><?php echo __('Total Public Expenditure'); ?></legend>
	
		<fieldset class="section_break" id="parent_section">
		<legend id="parent_level"><?php echo __('Country'); ?></legend>
		<div id="parentlist">
			<div class="table">
				<div class="table_head">
					<!-- <div class="table_cell cell cell_arealevel">Area Level</div> -->
					<div class="table_cell cell_area"><?php echo __('Area'); ?></div>
					<div class="table_cell"><?php echo __('Total Public Expenditure'); ?> <?php echo $currency; ?></div>
					<div class="table_cell"><?php echo __('Total Public Expenditure For Education'); ?> <?php echo $currency; ?></div>
				</div>

				<div class="table_body"></div>
			</div>
		</div>
		</fieldset>

		<fieldset class="section_break" id="children_section">
		<legend id="children_level"><?php echo __('Province'); ?></legend>
		<div id="mainlist">
			<div class="table">
				<div class="table_head">
					<!-- <div class="table_cell cell cell_arealevel">Area Level</div> -->
					<div class="table_cell cell_area"><?php echo __('Area'); ?></div>
					<div class="table_cell"><?php echo __('Total Public Expenditure'); ?> <?php echo $currency; ?></div>
					<div class="table_cell"><?php echo __('Total Public Expenditure For Education'); ?> <?php echo $currency; ?></div>
				</div>

				<div class="table_body">
				</div>
			</div>
		</div>
		</fieldset>
	</fieldset>
</div>

<script type="text/javascript">

$(document).ready(function(){
	var selectedYear = <?php echo $selectedYear?>;
	
	if($('#year_id').children().first().val().length < 1){
		$('#year_id').children().first().remove();
	}

	if(selectedYear !== '' || selectedYear !== undefined || selectedYear !== 'null'){
		Finance.year = $("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').val();
		
	}else{
		Finance.year = $("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected');
	}
	
	$('#edit').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() + 'Finance/edit');
		$('form').submit();
	});
	
	<?php if(isset($initAreaSelection) && count($initAreaSelection) > 0){ ?>
	Finance.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	var currentSelect;
	for(var key in Finance.initAreaSelection){
		currentSelect = $('select[name*="['+key+']"]');
		Finance.parentAreaIds.push(parseInt(Finance.initAreaSelection[key]));
		currentSelect.find($('option[value="'+Finance.initAreaSelection[key]+'"]')).attr('selected','selected');
		//$('select[name*="['+key+']"]').find($('option[value="'+Finance.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');
	}
	<?php } ?>

    Finance.fetchData();
    Finance.fetchGNP();
});
</script>