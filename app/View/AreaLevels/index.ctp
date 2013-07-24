<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('area', 'stylesheet', array('inline' => false));

echo $this->Html->script('area', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper" style="min-height: 650px;">
	<h1>
		<span><?php echo __('Administrative Boundaries'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'levelsEdit'), array('class' => 'divider'));
		}
		//echo $this->Html->link(__('Areas'), array('action' => 'index'), array('class' => 'divider', 'id' => 'viewAreas'));
		?>
	</h1>
	<?php echo $this->element('area_categories'); ?>
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell" style="width:569px;"><span class="left"><?php echo __('Name'); ?></span></div>
		</div>
		<div class="table_body">
		<?php for ($i =0; $i < count($levels);  $i++ ) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $levels[$i]['name']; ?></div>
			</div>
		<?php } ?>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function () {
	$('#edit').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() + 'Areas/edit');
		$('form').submit();
	});

	<?php if(isset($initAreaSelection) && count($initAreaSelection) > 0){ ?>
	areas.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	var currentSelect;
	for(var key in areas.initAreaSelection){
		currentSelect = $('select[name*="['+key+']"]');
		areas.parentAreaIds.push(parseInt(areas.initAreaSelection[key]));
		currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected');
		//$('select[name*="['+key+']"]').find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');
	}

	currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).trigger('change');	
	<?php } ?>

});

</script>
	
