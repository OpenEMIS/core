<?php
use Cake\Utility\Inflector;
?>
<!-- <div id="advanced-search" class="advanced-search-wrapper alert search-box <?= !$advancedSearch ? 'hidden' : '' ?>"> -->
<div class="adv-search">
	<button class="btn btn-xs close" type="button" alt="Collapse">×</button>
	<div class="adv-search-label">
		<i class="fa fa-search-plus"></i>
		<label><?= __('Advanced Search')?></label>
	</div>
<!-- 	<button class="btn btn-xs close" type="button" alt="Collapse">×</button>
	<h4><?= __('Advanced Search')?></h4> -->

	<?php
		foreach ($filters as $key=>$filter) :
	?>

		<div class="select">
		  <label><?= $filter['label'] ?>:</label>
		  <div class="input-select-wrapper">	 
			  <select name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $key ?>]">
				<option value=""><?= __('-- SELECT --'); ?></option>
				<?php foreach ($filter['options'] as $optKey=>$optVal): ?>
					<?php $selected = ($optKey==$filter['selected']) ? 'selected' : ''; ?>
					<option value="<?= $optKey ?>" <?= $selected ?>><?= $optVal ?></option>
				<?php endforeach; ?>
			  </select>
		   </div>	  
		</div>

	<?php endforeach ?>

	<?php
		foreach ($searchables as $key=>$searchable) :
	?>

		<div class="text" style="margin-bottom:10px;">
			<label for="advancesearch-directories-identity-number"><?= $searchable['label'] ?>:</label>

			<input type="text" name="AdvanceSearch[<?= $model ?>][hasMany][<?= $key ?>]" class="form-control focus" id="advancesearch-<?= strtolower($model) ?>-<?= Inflector::dasherize($key) ?>" value="<?= $searchable['value'] ?>" />
		</div>

	<?php endforeach ?>

	<hr>
	<input type="hidden" name="AdvanceSearch[<?= $model ?>][isSearch]" value="" id="isSearch" />
	<button class="btn btn-default btn-xs" href=""><?= __('Search') ?></button>
	<button id="reset" class="btn btn-default btn-xs" name="reset" value="Reset"><?= __('Reset') ?></button>
</div>

<script type="text/javascript">   
	// var box = $('#advanced-search');
	// var isSearch = $('#isSearch');

	// $('button#search-toggle').on('click', function () {
	// 	box.toggleClass('hidden');
	// 	if (!isSearch.val()) {
	// 		isSearch.val('true');
	// 	}else {
	// 		isSearch.val('');
	// 	}
	// });

	// $(box.selector+' button.close').on('click', function (e) {
	// 	e.preventDefault();
	// 	$('button#search-toggle').trigger('click');
	// });

	// if (!box.hasClass('hidden')) {
	// 	isSearch.val('true');
	// }
</script>
