<?php
use Cake\Utility\Inflector;
?>

<div class="adv-search" ng-show="showAdvSearch" ng-init="showAdvSearch=<?= $alwaysShow?>">
	<button class="btn btn-xs close" type="button" alt="Collapse" ng-click="removeAdvSearch()">×</button>
	<div class="adv-search-label">
		<i class="fa fa-search-plus"></i>
		<label><?= __('Advanced Search')?></label>
	</div>

    <?php
        /*
            list advanced search fields based on the order.
            order is declared on the model file $advancedSearchFieldOrder.
        */
        foreach ($order as $key=>$field) {
            if (array_key_exists($field, $filters)) {
    ?>
                <div class="select">
                    <label><?= $filters[$field]['label'] ?>:</label>
                    <div class="input-select-wrapper">
                        <select name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $field ?>]">
                            <option value=""><?= __('-- Select --'); ?></option>
                            <?php foreach ($filters[$field]['options'] as $optKey=>$optVal): ?>
                                <?php $selected = ($optKey==$filters[$field]['selected']) ? 'selected' : ''; ?>
                                <option value="<?= $optKey ?>" <?= $selected ?>><?= $optVal ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
    <?php
            } else if (array_key_exists($field, $searchables)) {
                if (array_key_exists('type', $searchables[$field])) {
                    if ($searchables[$field]['type'] == 'select') {
    ?>
                        <div class="select">
                            <label><?= $searchables[$field]['label'] ?>:</label>
                            <div class="input-select-wrapper">
                                <select name="AdvanceSearch[<?= $model ?>][hasMany][<?= $field ?>]">
                                    <option value=""><?= __('-- Select --'); ?></option>
                                    <?php foreach ($searchables[$field]['options'] as $optKey=>$optVal): ?>
                                        <?php $selected = ($optKey==$searchables[$field]['selected']) ? 'selected' : ''; ?>
                                    <option value="<?= $optKey ?>" <?= $selected ?>><?= $optVal ?></option>
                                 <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

    <?php
                    }
                } else {
    ?>
                    <div class="text" style="margin-bottom:10px;">
                        <label for="advancesearch-directories-identity-number"><?= $searchables[$field]['label'] ?>:</label>

                        <input type="text" name="AdvanceSearch[<?= $model ?>][hasMany][<?= $field ?>]" class="form-control focus" id="advancesearch-<?= strtolower($model) ?>-<?= Inflector::dasherize($field) ?>" value="<?= $searchables[$field]['value'] ?>" />
                    </div>
    <?php
                }
            }
        }
    ?>

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