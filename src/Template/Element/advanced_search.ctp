<?php

use Cake\Utility\Inflector;

?>

<div class="adv-search" ng-show="showAdvSearch" ng-init="showAdvSearch=<?= $showOnLoad ?>">
    <button class="btn btn-xs close" type="button" alt="Collapse" ng-click="removeAdvSearch()">×</button>
	<div class="adv-search-label">
		<i class="fa fa-search-plus"></i>
		<label><?= __('Advanced Search')?></label>
	</div>

    <?php
    // POCOR-8219 redone some parts

    /*
        list advanced search fields based on the order.
        order is declared on the model file $advancedSearchFieldOrder.
    */
    foreach ($order as $key => $field) {
        if (array_key_exists($field, $filters)) {
            ?>
            <?php
            $filterField = $filters[$field];
            if (isset($filterField['type']) && $filterField['type'] == 'areapicker') {
                ?>
                <?= $this->Html->script('Area.tree/sg.tree.svc', ['block' => true]); ?>
                <div class="select">
                    <label><?= $filterField['label'] ?></label>
                    <?php
                    echo $this->Form->unlockField("AdvanceSearch.$model.belongsTo.$field");
                    echo $this->Form->unlockField($field . '-tree');
                    $userId = $this->request->session()->read('Auth.User.id');
                    ?>
                    <div
                        class="tree-form"
                        id="<?= $field ?>"
                        ng-controller="SgTreeCtrl as SgTree"
                        ng-init="SgTree.model='<?= $filterField['source_model'] ?>'; <?= !empty($filterField['selected']) ? 'SgTree.outputValue=' . $filterField['selected'] : 'SgTree.outputValue=null' ?>; SgTree.userId=<?= $userId ?>; SgTree.displayCountry=<?= isset($filterField['displayCountry']) && !$filterField['displayCountry'] ? 0 : 1 ?>;">

                        <kd-tree-dropdown-ng id="<?= $field ?>-tree" expand-parent="SgTree.triggerLoad(refreshList)"
                                             output-model="outputModelText" model-type="single"
                                             text-config="textConfig"></kd-tree-dropdown-ng>
                        <?php
                        echo $this->Form->hidden("AdvanceSearch.$model.belongsTo.$field", [
                            'ng-value' => 'SgTree.outputValue'
                        ]);
                        ?>
                    </div>
                </div>
            <?php } else {
                if ($field == 'classification') {
                    ?>
                    <div class="select">
                        <label><?= $filterField['label'] ?>:</label>
                        <div class="input-select-wrapper">
                            <select ng-model="classification" ng-change="onChangeClassification()"
                                    name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $field ?>]">
                                <option value=""><?= __('-- Select --'); ?></option>
                                <?php foreach ($filterField['options'] as $optKey => $optVal): ?>
                                    <?php $selected = ($optKey == $filterField['selected']) ? 'selected' : ''; ?>
                                    <option value="<?= $optKey ?>" <?= $selected ?>><?= __($optVal) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php
                } else {

                    ?>

                    <div class="select">
                        <label><?= $filterField['label'] ?>:</label>
                        <div class="input-select-wrapper">
                            <select name="AdvanceSearch[<?= $model ?>][belongsTo][<?= $field ?>]">
                                <option value=""><?= __('-- Select --'); ?></option>
                                <?php foreach ($filterField['options'] as $optKey => $optVal): ?>
                                    <?php $selected = ($optKey == $filterField['selected']) ? 'selected' : ''; ?>
                                    <option value="<?= $optKey ?>" <?= $selected ?>><?= __($optVal) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php
                }
            }
        } else if (array_key_exists($field, $searchables) || array_key_exists($field, $includedFields)) {

            //to be used both by $searchable and $includedFields
            if (array_key_exists($field, $searchables)) {
                $fields = $searchables;
                $indexName = 'hasMany';
            } else if (array_key_exists($field, $includedFields)) {
                $fields = $includedFields;
                $indexName = 'tableField';
            }
            $searchField = $fields[$field];
            $educationals = ['education_programmes', 'education_systems', 'education_levels'];

            if (array_key_exists('type', $searchField)) {
                if ($searchField['type'] == 'select') {
                    if (in_array($field, $educationals)) {
                        if ($field === 'education_systems') {
                            $label = __($searchField['label']);
                            $fieldOptions = json_encode($searchField['options'])
                            ?>
                            <div ng-show="showEducationalSearch" class="select"
                                 >
                                <label ng-init='initEduField(<?= $fieldOptions ?>)'> <?= $label ?>:</label>
                                <div class="input-select-wrapper">
                                    <select ng-model="selectedSystem" ng-options="system.id as system.label for system in educationSystems"  ng-change="updateLevels()" name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]">
                                        <option value=""><?= __('-- Select --'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php
                        }
                        if ($field === 'education_levels') {
                            $label = __($searchField['label']);
                            $fieldOptions = json_encode($searchField['options'])
                            ?>
                            <div ng-show="showEducationalSearch" class="select" >
                                <label><?= $label ?>:</label>
                                <div class="input-select-wrapper">
                                    <select ng-model="selectedLevel" ng-options="level.id as level.label for level in filteredLevels" ng-change="updatePrograms()" name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]">
                                        <option value=""><?= __('-- Select --'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php
                        }
                        if ($field === 'education_programmes') {
                            $label = __($searchField['label']);
                            $fieldOptions = json_encode($searchField['options'])
                            ?>
                            <div ng-show="showEducationalSearch" class="select">
                                <label> <?= $label ?>:</label>
                                <div class="input-select-wrapper">
                                    <select  ng-model="selectedProgram" ng-options="program.id as program.label for program in filteredPrograms" name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]">
                                        <option value=""><?= __('-- Select --'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <?php
                        }
                    } else {
                        ?>
                        <div class="select">
                            <label><?= $searchField['label'] ?>:</label>
                            <div class="input-select-wrapper">
                                <select name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]">
                                    <option value=""><?= __('-- Select --'); ?></option>
                                    <?php foreach ($searchField['options'] as $optKey => $optVal): ?>
                                        <?php $selected = ($optKey == $searchField['selected']) ? 'selected' : ''; ?>
                                        <option value="<?= $optKey ?>" <?= $selected ?>><?= __($optVal) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php
                    }
                }
            } else {
                ?>
                <div class="text" style="margin-bottom:10px;">
                    <label for="advancesearch-directories-identity-number"><?= $searchField['label'] ?>:</label>

                    <input type="text" name="AdvanceSearch[<?= $model ?>][<?= $indexName ?>][<?= $field ?>]"
                           class="form-control focus"
                           id="advancesearch-<?= strtolower($model) ?>-<?= Inflector::dasherize($field) ?>"
                           value="<?= $searchField['value'] ?>"/>
                </div>
                <?php
            }
        }
    }
    ?>

    <div class="search-action-btn">
        <input type="hidden" name="AdvanceSearch[<?= $model ?>][isSearch]" value="" id="isSearch"/>
        <button class="btn btn-default btn-xs" href="" ng-click="submitSearch()"><?= __('Search') ?></button>
        <button id="reset" class="btn btn-outline btn-xs" name="reset" value="Reset"><?= __('Reset') ?></button>
        <?php
        $this->Form->unlockField('reset');
        $this->Form->unlockField('AdvanceSearch');
        ?>
    </div>

</div>

<?php if ($advancedSearch): ?>
    <h4 ng-class="disableElement">
        <?= __('Search Results') ?>
    </h4>
<?php endif; ?>
