<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;

class AdvanceSearchBehavior extends Behavior
{
    protected $model = '';
    protected $modelAlias = '';
    protected $data = '';
    protected $_defaultConfig = [
        'display_country' => true,
        'exclude' => ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'],
        'include' => [], //to include field that is from the database table
        'customFields' => [],
        'order' => [],
        'showOnLoad' => 0
    ];

    public function initialize(array $config): void
    {
        $this->_table->addBehavior('Area.Area');
    }


/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [];
        $newEvent['ControllerAction.Model.afterAction'] = 'afterAction';
        $newEvent['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $newEvent['ControllerAction.Model.index.beforePaginate'] = 'indexBeforePaginate';

        $events = array_merge($events, $newEvent);
        return $events;
    }


/******************************************************************************************************************
**
** CakePhp events
**
******************************************************************************************************************/

    public function afterAction(EventInterface $event, $extra)
    {
        $order = $this->getConfig('order');
        if ($this->_table->action == 'index') {
            $labels = TableRegistry::getTableLocator()->get('Labels');
            $filters = [];
            $advancedSearch = false;
            $session = $this->_table->request->getSession();
            $language = $session->read('System.language');
            $fields = $this->_table->getSchema()->columns();
            $customFields = $this->getConfig('customFields');
            $fields = array_merge($fields, $customFields);

            $requestData = $this->_table->request->getData();
            $advanceSearchData = isset($requestData['AdvanceSearch']) ? $requestData['AdvanceSearch'] : [];
            $advanceSearchModelData = isset($advanceSearchData[$this->_table->getAlias()]) ? $advanceSearchData[$this->_table->getAlias()] : [];
            $searchables = new ArrayObject();
            $includedFields = new ArrayObject(); //new type of advance search, field from the database table.

            foreach ($fields as $key) {
                $label = $labels->getLabel($this->_table->getAlias(), $key, $language);
                if (!in_array($key, $this->getConfig('exclude'))) {
                    $selected = (isset($advanceSearchModelData['belongsTo']) && isset($advanceSearchModelData['belongsTo'][$key])) ? $advanceSearchModelData['belongsTo'][$key] : '' ;

                    if ($this->isForeignKey($key)) {
                        $relatedModel = $this->getAssociatedBelongsToModel($key);
                        $list = $relatedModel->getList();
                        if ($list instanceof Query) {
                            $list = $list->toArray();
                        }
                        $translate = function (&$value, $key) {
                            $value = __($value);
                        };
                        array_walk_recursive($list, $translate);
                        $relatedModelTable = $relatedModel->getTable();
                        if ($relatedModelTable == 'areas' || $relatedModelTable == 'area_administratives') {
                            switch ($relatedModelTable) {
                                case 'areas':
                                    $filters[$key] = [
                                        'label' => ($label) ? $label : $this->_table->getHeader($relatedModel->getAlias()),
                                        'selected' => $selected,
                                        'type' => 'areapicker',
                                        'source_model' => 'Area.Areas'
                                    ];
                                    break;

                                case 'area_administratives':
                                    $filters[$key] = [
                                        'label' => ($label) ? $label : $this->_table->getHeader($relatedModel->getAlias()),
                                        'displayCountry' => $this->getConfig('display_country'),
                                        'selected' => $selected,
                                        'type' => 'areapicker',
                                        'source_model' => 'Area.AreaAdministratives'
                                    ];
                                    break;
                            }
                        } else {
                            $filters[$key] = [
                                'label' => ($label) ? $label : $this->_table->getHeader($relatedModel->getAlias()),
                                'options' => $list,
                                'selected' => $selected,
                                'type' => 'select'
                            ];
                        }

                    }

                    $customFilter  = $this->_table->dispatchEvent('AdvanceSearch.getCustomFilter'); //get custom filter set by the table

                    if ($customFilter->getResult()) {
                        $result = $customFilter->getResult();
                        foreach ($result as $customFilterKey => $item) {
                            if ($key == $customFilterKey) {
                                $filters[$customFilterKey] = [
                                    'label' => $item['label'],
                                    'options' => $item['options'],
                                    'selected' => $selected
                                ];
                            }
                        }
                    }
                }

                if (in_array($key, $this->getConfig('include'))) {
                    $includedFields[$key] = [
                        'label' => ($label) ? $label : __(Inflector::humanize($key)),
                        'value' => (isset($advanceSearchModelData['tableField']) && isset($advanceSearchModelData['tableField'][$key])) ? $advanceSearchModelData['tableField'][$key] : '',
                    ];
                }
            }

            if (isset($advanceSearchModelData['belongsTo'])) {
                foreach ($advanceSearchModelData['belongsTo'] as $field => $value) {
                    if (!empty($value) && $advancedSearch == false) {
                        $advancedSearch = true;
                    }
                }
            }

            if (isset($advanceSearchModelData['hasMany'])) {
                foreach ($advanceSearchModelData['hasMany'] as $field => $value) {
                    if (strlen($value) > 0 && $advancedSearch == false) {
                        $advancedSearch = true;
                    }
                }
            }

            if (isset($advanceSearchModelData['tableField'])) {
                foreach ($advanceSearchModelData['tableField'] as $field => $value) {
                    if (strlen($value) > 0 && $advancedSearch == false) {
                        $advancedSearch = true;
                    }
                }
            }

            if (!empty($advanceSearchModelData['isSearch'])) {
                $advancedSearch = true;
            }

            // trigger events for additional searchable fields
            $this->_table->dispatchEvent('AdvanceSearch.onSetupFormField', [$searchables, $advanceSearchModelData], $this);
            $showOnLoad = $this->getConfig('showOnLoad');


            if (empty($order)) { //if no order declared, then build the default order.
                foreach ($filters as $key => $filter) {
                    $order[] = $key;
                }
                foreach ($searchables as $key => $searchable) {
                    $order[] = $key;
                }
            }

            if ($this->isCAv4()) {
                $extra['elements']['advanced_search'] = [
                    'name' => 'advanced_search',
                    'data' => compact('filters', 'searchables', 'includedFields', 'order', 'advancedSearch', 'showOnLoad'),
                    'options' => [],
                    'order' => 0
                ];
                $this->_table->controller->set('advanced_search', true);
            } else {
                $this->_table->controller->viewVars['indexElements']['advanced_search'] = [
                    'name' => 'advanced_search',
                    'data' => compact('filters', 'searchables', 'includedFields', 'order', 'advancedSearch', 'showOnLoad'),
                    'options' => [],
                    'order' => 0
                ];
            }
        }
    }


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/

    public function indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        $this->indexBeforeQuery($event, $query, $options);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $request = $this->_table->request;
        $reset = $request->getData('reset');
        if (!empty($reset) && isset($reset)) {
            if ($reset == 'Reset') {
                $model = $this->_table;
                $alias = $model->getAlias();
                // clear session
                if ($model->Session->check($alias.'.advanceSearch.belongsTo')) {
                     $model->Session->delete($alias.'.advanceSearch.belongsTo');
                }
                if ($model->Session->check($alias.'.advanceSearch.hasMany')) {
                     $model->Session->delete($alias.'.advanceSearch.hasMany');
                }
                if ($model->Session->check($alias.'.advanceSearch.tableField')) {
                     $model->Session->delete($alias.'.advanceSearch.tableField');
                }
                
                // clear fields value
                if (array_key_exists('belongsTo', $request->getData()['AdvanceSearch'][$alias])) {
                    // foreach ($request->getData()['AdvanceSearch'][$alias]['belongsTo'] as $key => $value) {
                    //    // $request->getData()['AdvanceSearch'][$alias]['belongsTo'][$key] = '';
                    //    $request = $request->withData($this->getAlias()['AdvanceSearch'], $requestData)
                    // }
                    $advanceSearchData = $request->getData('AdvanceSearch');
                    foreach ($advanceSearchData[$alias]['belongsTo'] as $key => $value) {
                        $advanceSearchData[$alias]['belongsTo'][$key] = '';
                    }
                    $request = $request->withData('AdvanceSearch', $advanceSearchData);
                }

                if (array_key_exists('hasMany', $request->getData()['AdvanceSearch'][$alias])) {
                    // foreach ($request->getData()['AdvanceSearch'][$alias]['hasMany'] as $key => $value) {
                    //     $request->getData()['AdvanceSearch'][$alias]['hasMany'][$key] = '';
                    // }
                    $advanceSearchData = $request->getData('AdvanceSearch');
                    foreach ($advanceSearchData[$alias]['hasMany'] as $key => $value) {
                        $advanceSearchData[$alias]['hasMany'][$key] = '';
                    }
                    $request = $request->withData('AdvanceSearch', $advanceSearchData);
                }
                if (array_key_exists('tableField', $request->getData()['AdvanceSearch'][$alias])) {
                    // foreach ($request->getData()['AdvanceSearch'][$alias]['tableField'] as $key => $value) {
                    //     $request->getData()['AdvanceSearch'][$alias]['tableField'][$key] = '';
                    // }
                    $advanceSearchData = $request->getData('AdvanceSearch');
                    foreach ($advanceSearchData[$alias]['tableField'] as $key => $value) {
                        $advanceSearchData[$alias]['tableField'][$key] = '';
                    }
                    $request = $request->withData('AdvanceSearch', $advanceSearchData);
                }
                $advanceSearchData = $request->getData('AdvanceSearch');
                $advanceSearchData[$alias]['isSearch'] = false;
                // $request->getData('AdvanceSearch')[$alias]['isSearch'] = false;
                $request = $request->withData('AdvanceSearch', $advanceSearchData);
            }
        }
        return $this->advancedSearchQuery($request, $query);
    }

    public function advancedSearchQuery(ServerRequest $request, Query $query)
    {
        $conditions = [];
        $tableFieldConditions = [];

        $model = $this->_table;
        $alias = $model->getAlias();
       // $request = $this->_table->request;
        $advancedSearchBelongsTo = $model->Session->check($alias.'.advanceSearch.belongsTo') ? $model->Session->read($alias.'.advanceSearch.belongsTo') : [];
        $advancedSearchHasMany = $model->Session->check($alias.'.advanceSearch.hasMany') ? $model->Session->read($alias.'.advanceSearch.hasMany') : [];
        $advancedSearchTableField = $model->Session->check($alias.'.advanceSearch.tableField') ? $model->Session->read($alias.'.advanceSearch.tableField') : [];

        if ($request->is(['post', 'put'])) {
            if (isset($request->getData()['AdvanceSearch']) && isset($request->getData()['AdvanceSearch'][$alias])) {
                if (isset($request->getData()['AdvanceSearch'][$alias]['belongsTo'])) {
                    $advancedSearchBelongsTo = $request->getData()['AdvanceSearch'][$alias]['belongsTo'];
                }
                if (isset($request->getData()['AdvanceSearch'][$alias]['hasMany'])) {
                    $advancedSearchHasMany = $request->getData()['AdvanceSearch'][$alias]['hasMany'];
                }
                if (isset($request->getData()['AdvanceSearch'][$alias]['tableField'])) {
                    $advancedSearchTableField = $request->getData()['AdvanceSearch'][$alias]['tableField'];
                }
                $model->Session->write($alias.'.advanceSearch', $request->getData()['AdvanceSearch'][$alias]);
            }
        }

        if ($model->Session->check($alias.'.advanceSearch')) {
            $request->getData['AdvanceSearch'][$alias] = $model->Session->read($alias.'.advanceSearch');
        }

        $areaKeys[] = 'shift_type'; //POCOR-6764
        $areaKeys[] = 'alternative_name'; //POCOR-6764
        $areaKeys[] = 'area_id';
        $areaKeys[] = 'area_administrative_id';
        $areaKeys[] = 'birthplace_area_id';
        $areaKeys[] = 'address_area_id';

        foreach ($advancedSearchBelongsTo as $key => $value) {
            if (!empty($value) && $value>0) {
                if (in_array($key, $areaKeys)) {
                    switch ($key) {
                        case 'area_id':
                            $tableName = 'areas';
                            $id = $advancedSearchBelongsTo[$key];
                            $query->find('Areas', ['id' => $id, 'columnName' => $key, 'table' => $tableName]);
                            break;

                        case 'area_administrative_id':
                            //Start:POCOR-6798
                            $tableName = 'area_administratives';
                            $id = $advancedSearchBelongsTo[$key];
                            $AreaAdministrativeTable = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
                            $query->find('Areas', ['id' => $id, 'columnName' => $key, 'table' => $tableName]);
                            break;
                            //End:POCOR-6798
                        case 'birthplace_area_id':
                            break;
                        // start POCOR-6764
                        case 'shift_type':
                            $tableName = 'institution_shifts';
                            $id = $advancedSearchBelongsTo[$key];
                            $InstitutionShiftsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
                            $query->find('ShiftOptions', ['shift_option_id' => $id, 'columnName' => 'shift_option_id', 'table' => $tableName,'conditionCheck' => $advancedSearchBelongsTo]);
                            break;
                        case 'alternative_name':
                            $tableName = 'institution_shifts';
                            $id = $advancedSearchBelongsTo[$key];
                            $InstitutionShiftsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
                           $query->find('ShiftOwnership', ['shift_ownership' => $id, 'columnName' => 'shift_ownership', 'table' => $tableName,'conditionCheck' => $advancedSearchBelongsTo]);
                            break;

                           //End POCOR-6764
                        case 'address_area_id':
                            $tableName = 'area_administratives';
                            $id = $advancedSearchBelongsTo[$key];
                            $AreaAdministrativeTable = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
                            $query->find('Areas', ['id' => $id, 'columnName' => $key, 'table' => $tableName]);
                            break;
                    }
                } else {
                    $modifiedCondition = $model->dispatchEvent('AdvanceSearch.onModifyConditions', [$key, $value], $this);
                    if ($modifiedCondition->getResult()) {
                        $result = $modifiedCondition->getResult();
                        $conditions = array_merge($conditions, $result);
                    } else {
                        $conditions[$model->aliasField($key)] = $value;
                    }
                }
            }
        }
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if (!empty($advancedSearchHasMany)) {
            // trigger events for additional searchable fields
            $model->dispatchEvent('AdvanceSearch.onBuildQuery', [$query, $advancedSearchHasMany], $this);
        }
        if (!empty($advancedSearchTableField)) { //condition that comes from its own field on the database table.
            foreach ($advancedSearchTableField as $key => $value) {
                if (strlen($value) > 0) {
                    $query->where([
                        $model->aliasField("$key LIKE ") => $value . '%'
                    ]);
                }
            }
        }

        $plugin = $request->getParam('plugin');
        $ctlr = $request->getParam('controller');
        $action = $request->getParam('action');
        $furtherAction = isset($request->getParam('pass')[0]) ? $request->getParam('pass')[0] :'';
        $checkInstitution = ($plugin == 'Institution' && $ctlr == 'Institutions' && $action == 'Institutions' && $furtherAction == 'index') ? true : false;
        $resetData = $request->getData('reset');
        if ($resetData !== null && $resetData == 'Reset' && ! $checkInstitution) {
            return false;
        }
        return $query;
    }


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
    public function getAssociatedBelongsToModel($field)
    {
        $relatedModel = null;
        foreach ($this->_table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    private function isForeignKey($field)
    {
        foreach ($this->_table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedEntityArrayKey($field)
    {
        $associationKey = $this->getAssociatedBelongsToModel($field);
        $associatedEntityArrayKey = null;
        if (is_object($associationKey)) {
            $associatedEntityArrayKey = Inflector::underscore(Inflector::singularize($associationKey->alias()));
        } else {
            die($field . '\'s association not found in ' . $this->_table->getAlias());
        }
        return $associatedEntityArrayKey;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function isAdvancedSearchEnabled()
    {
        $requestData = $this->_table->request->getData();
        $advanceSearchData = isset($requestData['AdvanceSearch']) ? $requestData['AdvanceSearch'] : [];

        if ($advanceSearchData) {
            foreach ($advanceSearchData[$this->_table->getAlias()] as $key => $value) {
                if (!empty($value)) {
                    foreach ($value as $key => $searchValue) {
                        if (!empty($searchValue) || strlen($searchValue) > 0) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
