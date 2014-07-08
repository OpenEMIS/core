<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('population', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.area', false);
echo $this->Html->script('population', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Population'));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('id' => 'edit', 'class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'population');
$this->start('contentBody');


$formOptions = $this->FormUtility->getFormOptions();
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Population', $formOptions);

echo $this->element('../Population/controls');
?>
<!--
<div class="row year">
	<div class="label"><?php echo __('Year'); ?></div>
<?php
//		echo $this->Utility->getYearList($this->Form,'data[year]',array(
//			'name' => "data[year]",
//			'id' => "year_id",
//			'class'=>'form-control',
//			'div' => 'col-md-4',
//			'maxlength' => 30,
//			'desc' => true,
//			'label' => false,
//			'default' => $selectedYear,
//			'div' => false), true);
?>
</div>-->

<fieldset id="area_section_group" class="section_group">
    <legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>

<?php echo $this->Form->end(); ?>

<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Population'); ?></legend>
	<div id="mainlist">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<!--div class="table_cell">Area Level</div-->
				<tr>
					<th class="cell_source"><?php echo __('Source'); ?></span></th>
					<th class=""><?php echo __('Age'); ?></th>
					<th class=""><?php echo __('Male'); ?></th>
					<th class=""><?php echo __('Female'); ?></th>
					<th class=""><?php echo __('Total'); ?></th>
				</tr>

			</thead>

			<tbody class="table_body" style="display:none;">&nbsp;</tbody>

			<tfoot>
				<tr>
					<td class="cell-number" colspan="4"><?php echo __('Total'); ?></td>
					<td class="cell_value cell_number">0</td>
				</tr>
			</tfoot>
		</table>
	</div>
</fieldset>

<script type="text/javascript">

//	$(document).ready(function() {
//		var selectedYear = <?php echo $selectedYear ?>;
//
//		if ($('#year_id').children().first().val().length < 1) {
//			$('#year_id').children().first().remove();
//		}
//
//		if (selectedYear !== '' || selectedYear !== undefined || selectedYear !== 'null') {
//			//$("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').trigger('change');
//			population.year = $("#year_id option[value='" + selectedYear + "']").attr('selected', 'selected').val();
//
//		} else {
//			//$("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected').trigger('change');
//			population.year = $("#year_id option[value='" + new Date().getFullYear() + "']").attr('selected', 'selected').val();
//
//		}
//		$('#edit').click(function(event) {
//			event.preventDefault();
//			var form = $('form').attr('action', getRootURL() + 'Population/edit');
//			$('form').submit();
//		});
//
//<?php if (isset($initAreaSelection) && count($initAreaSelection) > 0) { ?>
	//			population.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	//			var currentSelect;
	//
	//			for (var key in population.initAreaSelection) {
	//				currentSelect = $('select[name*="[' + key + ']"]');
	//				population.parentAreaIds.push(parseInt(population.initAreaSelection[key]));
	//				currentSelect.find($('option[value="' + population.initAreaSelection[key] + '"]')).attr('selected', 'selected');
	//				// $('select[name*="['+key+']"]').find($('option[value="'+population.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');
	//
	//			}
	//			currentSelect.find($('option[value="' + population.initAreaSelection[key] + '"]')).trigger('change');
	//
	//<?php } ?>
//	});


</script>
<?php $this->end(); ?>
