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

// Get a database connection
        $connection = ConnectionManager::get('default');

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
                'TABLE_SCHEMA' => 'openemis_core'
            ]);
        $results = $query->execute();
        $i = 0;
        foreach ($results as $result){
            $i++;
            echo "<br \>\n$i<br \>\n";
            print_r($result['COLUMN_NAME']);
            echo "<br \>\n";
            print_r($result['TABLE_NAME']);

        }

//        ConnectionManager::get('default')->schemaCollection()->listTables();
//        $model = $this->_table;
//        $model->log($results, 'debug');
        die();
        $model->log('merge--', 'debug');
        $requestData = $model->request->data;
        $first_id = $requestData[$model->alias()]['first_id'];
        $merge_id = $requestData[$model->alias()]['merge_id'];

//        $model->log($first_id, 'debug');
//        $model->log($merge_id, 'debug');
//
        $session = $model->request->session();
        $first_ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
        $merge_ids = empty($merge_id) ? [] : ['id' => $merge_id];
        $first_id_keys = $model->getIdKeys($model, $first_ids);
        $merge_id_keys = $model->getIdKeys($model, $merge_ids);
        $first_entity = false;
        $merge_entity = false;
        $contain = [];
        // need to change this part
//        $model->log($first_ids, 'debug');
//        $model->log($first_id_keys, 'debug');
        if ($model->exists([$first_id_keys])) {
            $query = $model->find()->where($first_id_keys)->contain($contain);
            $first_entity = $query->first();
        }

        if ($model->exists([$merge_id_keys])) {
            $query = $model->find()->where($merge_id_keys)->contain($contain);
            $merge_entity = $query->first();
        }

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $model->fields = []; // reset all the fields

        $model->field('first_id', [
            'type' => 'readonly',
            'entity' => $first_entity
        ]);

        $model->field('merge_id');

        $extra = $this->addBackButton($extra, $model);
        // end back button

        $associations = $model->getAssociatedRecords($model, $merge_entity, $extra);

        if ($extra->offsetExists('excludedModels')) {
            $associations = array_diff_key($associations, array_flip($extra['excludedModels']));
        }
        if ($extra->offsetExists('associatedRecords')) {
            $associations = array_merge($associations, $extra['associatedRecords']);
        }
        //        $model->log('$associations', 'debug');
//        $model->log($associations, 'debug');
        $cells = [];
        $totalCount = 0;
        $associatedRecordLimit = 100;
        $exceedAssociatedRecordLimit = false;

        foreach ($associations as $row) {
            $modelName = Inflector::humanize(Inflector::underscore($row['model']));
            $cells[] = [0 => __($modelName), 1 => $row['count']];
            if ($row['count'] > $associatedRecordLimit) {
                $exceedAssociatedRecordLimit = true;
            }
            $totalCount += $row['count'];
        }
        if ($extra['associatedRecordsss'][0]['count'] > 0) { //POCOR-6964
            $model->Alert->error('general.delete.restrictDeleteBecauseAssociation');
            $this->recordHasAssociatedRecords = true;
        } elseif ($extra['associatedRecords'][1]['count'] > 0) {// POCOR-6975
            $model->Alert->error('general.delete.restrictDeleteBecauseAssociation');
            $this->recordHasAssociatedRecords = true;
        } else {
            // Change the method to delete if the record can be deleted
            $extra['config']['form'] = ['type' => 'DELETE'];
            $this->recordHasAssociatedRecords = false;
        }

        $extra['cells'] = $cells;
//        $model->log($cells,'debug');
        $model->field('associated_fields', [
            'type' => 'table',
            'headers' => [__('Field'), __('New Value'), __('Old Value')],
            'cells' => [[1 => 2, 3 => 4, 5 => 6]],
        ]);

        $model->field('associated_records', [
            'type' => 'table',
            'headers' => [__('Feature'), __('No of Records')],
            'cells' => $cells,
        ]);
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
