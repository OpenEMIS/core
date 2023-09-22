<?php

namespace Directory\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

class MergeBehavior extends Behavior
{
    public function initialize(array $config)
    {
        $this->_table->addBehavior('User.AdvancedNameSearch');
        $this->_table->addBehavior('User.AdvancedNameSearch');
        $this->_table->addBehavior('OpenEmis.Autocomplete');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.merge'] = 'merge';
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        $events['ControllerAction.Model.merge.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        $events['ControllerAction.Model.onGetFieldLabel'] = ['callable' => 'onGetFieldLabel', 'priority' => 100];
        return $events;
    }

    public function merge(Event $event, ArrayObject $extra)
    {
//        $this->Alert->error($this->aliasField('unableToTransfer'));
//    use Cake\Datasource\ConnectionManager;


////        ConnectionManager::get('default')->schemaCollection()->listTables();
        $model = $this->_table;
////        $model->log($results, 'debug');
//        die();
//        $model->log('merge--', 'debug');
        $first_entity = false;
        $merge_entity = false;

        $first_entity = $this->getUserEntity($model, 'first_id');
        $model->log('$first_entity', 'debug');
        $model->log($first_entity, 'debug');

        $merge_entity = $this->getUserEntity($model, 'merge_id');
        $model->log('$merge_entity', 'debug');
        $model->log($merge_entity, 'debug');


        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $model->fields = []; // reset all the fields

        $model->field('first_id', [
//            'type' => 'readonly',
            'entity' => $first_entity
        ]);

        $model->field('merge_id');

        $extra = $this->addBackButton($extra, $model);
        // end back button
        $associations = [];
        if ($merge_entity) {
            $associations = $this->getRelatedRecords($merge_entity->id);
        }
//        $model->log($associations, 'debug');
        $cells = [];
        $totalCount = 0;
        Log::write('debug', '$associations');
        Log::write('debug', $associations);
        foreach ($associations as $key=>$row) {
            Log::write('debug', '$row');
            Log::write('debug', $row);
            $modelName = $row['model'];
            $cells[] = [0 => __($modelName), 1 => $row['count']];
            $totalCount += $row['count'];
        }

        $model->field('associated_fields', [
            'type' => 'table',
            'headers' => [__('Field'), __('New Value'), __('Old Value')],
            'cells' => [[1 => 2, 3 => 4, 5 => 6]],
        ]);

        if ($totalCount > 0) { //POCOR-6964
            $model->Alert->error(__('There are related records. They will be overwritten. This operation can not be undone'), ['type' => 'string', 'reset' => true]);
            $extra['cells'] = $cells;
//        $model->log($cells,'debug');
            $model->field('associated_records', [
                'type' => 'table',
                'headers' => [__('External Table'), __('No of Records')],
                'cells' => $cells,
            ]);
            Log::write('debug', '$cells');
            Log::write('debug', $cells);
        }
        $model->controller->set('data', $first_entity);
        return $first_entity;
    }

    public function onUpdateFieldFirstId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'merge') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->id;
            $attr['attr']['value'] = $entity->name_with_id;
        }
        return $attr;
    }

    public function onUpdateFieldMergeId(Event $event, array $attr, $action, Request $request)
    {
        $model = $this->_table;
        if ($action == 'merge') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'merge_id', 'name' => $model->aliasField('merge_id')];
            $attr['noResults'] = __('No Merge User found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $urlAction = $model->alias();
            $attr['url'] = ['controller' => $model->controller->name, 'action' => $urlAction, 'ajaxUserAutocomplete'];
            $Users = TableRegistry::get('User.Users');
            $requestData = $model->request->data;
            if (isset($requestData) && !empty($requestData[$model->alias()]['merge_id'])) {
                $mergeId = $requestData[$model->alias()]['merge_id'];
                $mergeName = $Users->get($mergeId)->name_with_id;
                $attr['attr']['value'] = $mergeName;
//                $requestData[$model->alias()]['merge_id'] = $mergeId;
            }
//
//            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
//            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
//            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
//            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
            $attr['onSelect'] = "$('#reload').click();";
        }
        return $attr;
    }

    public function ajaxUserAutocomplete()
    {
        $this->_table->controller->autoRender = false;
        $this->_table->ControllerAction->autoRender = false;

        if ($this->_table->request->is(['ajax'])) {
            $term = $this->_table->request->query['term'];

            $Users = TableRegistry::get('User.Users');
            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $Users
                ->find()
                ->select([
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                    $Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $Users->aliasField('id')
                    ]
                )
                ->group([
                    $Users->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);
            if (!empty($term)) {
                $query = $this->_table->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
//        $this->_table->log('onGetFieldLabel', 'debug');
//        $this->_table->log($field, 'debug');
        switch ($field) {
            case 'first_id':
                return __('Base Account');
            case 'merge_id':
                return __('Account to be merged');
            default:
                return $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * @param ArrayObject $extra
     * @param \Cake\ORM\Table $model
     */
    private function addBackButton(ArrayObject $extra, \Cake\ORM\Table $model)
    {
        $action = 'view';
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $model->url($action);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        return $extra;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (isset($toolbarButtonsArray['edit'])) {
            $toolbarButtonsArray['merge'] = $toolbarButtonsArray['edit'];
            $toolbarButtonsArray['merge']['url'][0] = 'merge';
            $toolbarButtonsArray['merge']['label'] = '<i class="fa kd-reassign"></i>';
            $toolbarButtonsArray['merge']['attr']['title'] = __('Merge');
//                unset($toolbarButtonsArray['edit']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    /**
     * @param $model
     * @param $user_field
     * @return Entity|null
     */

    private function getUserEntity($model, $user_field)
    {
        $requestData = $model->request->data;
        Log::write('debug', $requestData);
        if ($user_field == 'first_id') {
            $encodedParam = $model->request->params['pass'][1];
            $user_id = $model->ControllerAction->paramsDecode($encodedParam)['id'];
        } else {
            $user_id = $requestData[$model->alias()][$user_field];
        }
        $user_entity = null;
        $user_ids = empty($user_id) ? ['id' => -1] : ['id' => $user_id];
        $user_id_keys = $model->getIdKeys($model, $user_ids);
        $contain = [];
        if ($model->exists([$user_id_keys])) {
            $query = $model->find()->where($user_id_keys)->contain($contain);
            $user_entity = $query->first();
        }
        return $user_entity;
    }

    /**
     * @param $merge_id
     * @return array
     */
    private function getRelatedRecords($merge_id)
    {
//         Get a database connection
        $relatedRecords = [];
        $connection = ConnectionManager::get('default');
        $connectionConfig = $connection->config();
        $database = $connectionConfig['database'];
        $query = $connection->newQuery();
        $query->select(['COLUMN_NAME', 'TABLE_NAME'])
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where([
                'COLUMN_NAME IN' => [
                    'security_user_id', 'student_id', 'user_id', 'core_user_id',
                    'staff_id', 'secondary_staff_id', 'assignee_id', 'guardian_id'
                ],
                'COLUMN_NAME NOT IN' => ['modified_user_id', 'created_user_id'],
                'TABLE_NAME NOT LIKE' => 'z%',
                'TABLE_SCHEMA' => $database
            ]);
        $results = $query->execute();
        $i = 0;
        foreach ($results as $result) {

//            Log::write('debug', $result);
            $column_name = $result['COLUMN_NAME'];
            $table_name = $result['TABLE_NAME'];
            $table = TableRegistry::get($table_name);
            $count = 0;
            try {
                $count = $table->find()
                    ->where([$table->aliasField($column_name) => $merge_id])
                    ->count();
            } catch (\Exception $exception) {
                Log::write('error', $exception->getMessage());
            }
            $title = Inflector::humanize(Inflector::underscore($table_name));
            if ($count > 0) {
                $result = ['model' => $title, 'count' => $count];
                $relatedRecords[$i] = $result;
            }
            $i++;
        }
        Log::write('debug', '$relatedRecords');
        Log::write('debug', $relatedRecords);
        return $relatedRecords;
    }



//
//	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
//		$options['auto_search'] = false;
//		$search = $this->_table->ControllerAction->getSearchKey();
//		if (!empty($search)) {
//			// function from AdvancedNameSearchBehavior
//			$query = $this->_table->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
//		}
//	}
//
//	public function getAbsenceDaysBySettings($firstDateAbsent, $lastDateAbsent, $settingWeekdays){
//		$stampFirstDateAbsent = strtotime($firstDateAbsent);
//		$stampLastDateAbsent = strtotime($lastDateAbsent);
//
//		$totalWeekdays = 0;
//		while($stampFirstDateAbsent <= $stampLastDateAbsent){
//			$weekday = strtolower(date('l', $stampFirstDateAbsent));
//			if(in_array($weekday, $settingWeekdays)){
//				$totalWeekdays++;
//			}
//
//			$stampFirstDateAbsent = strtotime('+1 day', $stampFirstDateAbsent);
//		}
//
//		return $totalWeekdays;
//	}
//
//	public function getWeekdaysBySetting(){
//		$weekdaysArr = array(
//			1 => 'monday',
//			2 => 'tuesday',
//			3 => 'wednesday',
//			4 => 'thursday',
//			5 => 'friday',
//			6 => 'saturday',
//			7 => 'sunday'
//		);
//
//		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
//
//		$settingFirstWeekDay = $ConfigItems->value('first_day_of_week');
//		if(empty($settingFirstWeekDay) || !in_array($settingFirstWeekDay, $weekdaysArr)){
//			$settingFirstWeekDay = 'monday';
//		}
//
//		$settingDaysPerWek = intval($ConfigItems->value('days_per_week'));
//		if(empty($settingDaysPerWek)){
//			$settingDaysPerWek = 5;
//		}
//
//		foreach($weekdaysArr AS $index => $weekday){
//			if($weekday == $settingFirstWeekDay){
//				$firstWeekdayIndex = $index;
//				break;
//			}
//		}
//
//		$newIndex = $firstWeekdayIndex + $settingDaysPerWek;
//
//		$weekdays = array();
//		for($i=$firstWeekdayIndex; $i<$newIndex; $i++){
//			if($i<=7){
//				$weekdays[] = $weekdaysArr[$i];
//			}else{
//				$weekdays[] = $weekdaysArr[$i%7];
//			}
//		}
//
//		return $weekdays;
//	}
//
//	// public function beforeFind(Event $event, Query $query, $options) {
//	// 	$query
//	// 		->join([
//	// 			'table' => 'institution_students',
//	// 			'alias' => 'InstitionStudents',
//	// 			'type' => 'INNER',
//	// 			'conditions' => 'Users.id = InstitionStudents.security_user_id',
//	// 		])
//	// 		->group('Users.id');
//	// }
//
//	// public function implementedEvents() {
//	// 	$events = parent::implementedEvents();
//	// 	$newEvent = [
//	// 		'ControllerAction.Model.beforeAction' => 'beforeAction',
//	// 		'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction'
//	// 	];
//	// 	$events = array_merge($events,$newEvent);
//	// 	return $events;
//	// }
//
//	// public function beforeAction(Event $event) {
//	// 	$this->_table->fields['super_admin']['visible'] = false;
//	// 	$this->_table->fields['status']['visible'] = false;
//	// 	$this->_table->fields['date_of_death']['visible'] = false;
//	// 	$this->_table->fields['last_login']['visible'] = false;
//	// 	$this->_table->fields['photo_name']['visible'] = false;
//	// }
//
//	// public function indexBeforeAction(Event $event) {
//	// 	$this->_table->ControllerAction->addField('Picture', [
//	// 		'type' => 'element',
//	// 		'element' => 'Student.Students/picture'
//	// 	]);
//	// 	$this->_table->fields['username']['visible']['index'] = false;
//	// 	$this->_table->fields['birthplace_area_id']['visible']['index'] = false;
//	// 	$this->_table->fields['photo_content']['visible']['index'] = false;
//
//	// 	$indexDashboard = 'Student.Students/dashboard';
//	// 	$this->_table->controller->set('indexDashboard', $indexDashboard);
//	// }


}
