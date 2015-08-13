<?php
	// pr($filters);
?>
<div id="advanced-search" class="advanced-search-wrapper alert search-box <?= !$advancedSearch ? 'hidden' : '' ?>">

	<button id="search-toggle" class="btn btn-xs close" type="button" alt="Collapse">×</button>
	<h4>Advanced Search</h4>

	<?php
		// pr($model);die;
		foreach ($filters as $key=>$filter) :
	?>

		<div class="input select">
		  <label class="form-label"><?= $filter['label'] ?>:</label>
		  <div class="input-select-wrapper">	 
			  <select name="AdvanceSearch[<?= $model ?>][<?= $key ?>]">
				<option value="">&nbsp;</option>
				<?php foreach ($filter['options'] as $optKey=>$optVal): ?>
					<?php $selected = ($optKey==$filter['selected']) ? 'selected' : ''; ?>
					<option value="<?= $optKey ?>" <?= $selected ?>><?= $optVal ?></option>
				<?php endforeach; ?>
			  </select>
		   </div>	  
		</div>

	<?php endforeach ?>

	<hr>
	<input type="hidden" name="AdvanceSearch[<?= $model ?>][isSearch]" value="true" id="isSearch" />
	<button class="btn btn-default btn-xs" href=""><?= __('Search') ?></button>
	<button id="reset" class="btn btn-default btn-xs" value="Reset" href=""><?= __('Reset') ?></button>
</div>

<script type="text/javascript">   
	var box = $('#advanced-search');
	var isSearch = $('#isSearch');
	$('button#search-toggle').on('click', function () {
		box.toggleClass('hidden');
	});


	//reset form 
	$("#reset").click(function(){
		box.find('input:text, select').val('');
		$(".icheckbox_minimal-grey").removeClass("checked");
	});

</script>
