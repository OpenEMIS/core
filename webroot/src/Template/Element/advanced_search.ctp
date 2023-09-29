<?php
use Cake\Utility\Inflector;
?>

<div class="adv-search" ng-show="showAdvSearch" ng-init="showAdvSearch=<?= $showOnLoad?>">
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
            <?php
                if (isset($filters[$field]['type']) && $filters[$field]['type'] == 'areapicker') {
            ?>
                    <?= $this->Html->script('Area.tree/sg.tree.svc', ['block' => true]); ?>
                    <div class="select">
                        <label><?= $filters[$field]['label'] ?></label>
                        <?php
                        echo $this->Form->unlockField("AdvanceSearch.$model.belongsTo.$field");
                        echo $this->Form->unlockField($field.'-tree');
                        $userId = $this->request->session()->read('Auth.User.id');
                        ?>
                        <div
                            class="tree-form"
                            id="<?= $field ?>"
                            ng-controller="SgTreeCtrl as SgTree"
                            ng-init="SgTree.model='<?= $filters[$field]['source_model'] ?>'; <?= !empty($filters[$field]['selected']) ? 'SgTree.outputValue='.$filters[$field]['selected'] : 'SgTree.outputValue=null'?>; SgTree.userId=<?= $userId ?>; SgTree.displayCountry=<?= isset($filters[$field]['displayCountry']) && !$filters[$field]['displayCountry'] ? 0 : 1 ?>;">

                            <kd-tree-dropdown-ng id="<?=$field ?>-tree" expand-parent="SgTree.triggerLoad(refreshList)" output-model="outputModelText" model-type="single" text-config="textConfig"></kd-tree-dropdown-ng>
                            <?php
                                echo $this->Form->hidden("AdvanceSearch.$model.belongsTo.$field", [
                                    'ng-value' => 'SgTree.outputValue'
                                ]);
                             ?>
                        </div>
                    </div>
        <?php   } else { ?>
                    <div class="select">
                        <label><?= $filters[$field]['label'] ?>:</label>
                        <div class="input-select-wrapper">
                            <select name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $field ?>]">
                                <option value=""><?= __('-- Select --'); ?></option>
                                <?php foreach ($filters[$field]['options'] as $optKey=>$optVal): ?>
                                    <?php $selected = ($optKey==$filters[$field]['selected']) ? 'selected' : ''; ?>
                                    <option value="<?= $optKey ?>" <?= $selected ?>><?= __($optVal) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
    <?php
                }
            } else if (array_key_exists($field, $searchables) || array_key_exists($field, $includedFields)) {

                //to be used both by $searchable and $includedFields
                if (array_key_exists($field, $searchables)) {
                    $varName = $searchables;
                    $indexName = 'hasMany';
                } else if (array_key_exists($field, $includedFields)) {
                    $varName = $includedFields;
                    $indexName = 'tableField';
                }
                if (array_key_exists('type', $varName[$field])) {
                    if ($varName[$field]['type'] == 'select') {
    ?>
                        <div class="select">
                            <label><?= $varName[$field]['label'] ?>:</label>
                            <div class="input-select-wrapper">
                                <select name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]">
                                    <option value=""><?= __('-- Select --'); ?></option>
                                    <?php foreach ($varName[$field]['options'] as $optKey=>$optVal): ?>
                                        <?php $selected = ($optKey==$varName[$field]['selected']) ? 'selected' : ''; ?>
                                    <option value="<?= $optKey ?>" <?= $selected ?>><?= __($optVal) ?></option>
                                 <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
    <?php
                    }
                } else {
    ?>
                    <div class="text" style="margin-bottom:10px;">
                        <label for="advancesearch-directories-identity-number"><?= $varName[$field]['label'] ?>:</label>

                        <input type="text" name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]" class="form-control focus" id="advancesearch-<?= strtolower($model) ?>-<?= Inflector::dasherize($field) ?>" value="<?= $varName[$field]['value'] ?>" />
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