<?php
use Cake\Utility\Inflector;
?>

<div class="adv-search" ng-show="showAdvSearch">
	<button class="btn btn-xs close" type="button" alt="Collapse" ng-click="removeAdvSearch()">×</button>
	<div class="adv-search-label">
		<i class="fa fa-search-plus"></i>
		<label><?= __('Advanced Search')?></label>
	</div>

	<?php
		foreach ($filters as $key=>$filter) :
	?>

		<div class="select">
		  <label><?= $filter['label'] ?>:</label>
		  <div class="input-select-wrapper">	 
			  <select name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $key ?>]">
				<option value=""><?= __('-- Select --'); ?></option>
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

	<div class="search-action-btn">
		<input type="hidden" name="AdvanceSearch[<?= $model ?>][isSearch]" value="" id="isSearch" />
		<button class="btn btn-default btn-xs" href="" ng-click="submitSearch()"><?= __('Search') ?></button>
		<button id="reset" class="btn btn-outline btn-xs" name="reset" value="Reset"><?= __('Reset') ?></button>
		<?php 
			$this->Form->unlockField('reset'); 
			$this->Form->unlockField('AdvanceSearch');
		?>
	</div>

</div>

<?php if($advancedSearch):?>
<h4 ng-class="disableElement">
	<?= __('Search Results') ?>
</h4>
<?php endif;?>