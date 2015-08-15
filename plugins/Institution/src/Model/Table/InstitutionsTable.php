<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;

use App\Model\Table\AppTable;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
        parent::initialize($config);

		/**
		 * fieldOption tables
		 */
		$this->belongsTo('InstitutionSiteLocalities', 		['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', 			['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', 		['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', 		['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', 			['className' => 'Institution.Sectors']);
		$this->belongsTo('Providers',				 		['className' => 'Institution.Providers', 'foreignKey' => 'institution_site_provider_id']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);

		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);

		$this->hasMany('InstitutionSiteActivities', 		['className' => 'Institution.InstitutionSiteActivities', 'dependent' => true]);
		$this->hasMany('InstitutionSiteAttachments', 		['className' => 'Institution.InstitutionSiteAttachments', 'dependent' => true]);

		$this->hasMany('InstitutionSitePositions', 			['className' => 'Institution.InstitutionSitePositions', 'dependent' => true]);
		$this->hasMany('InstitutionSiteProgrammes', 		['className' => 'Institution.InstitutionSiteProgrammes', 'dependent' => true]);
		$this->hasMany('InstitutionSiteShifts', 			['className' => 'Institution.InstitutionSiteShifts', 'dependent' => true]);
		$this->hasMany('InstitutionSiteSections', 			['className' => 'Institution.InstitutionSiteSections', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteClasses', 			['className' => 'Institution.InstitutionSiteClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('Infrastructures',					['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->hasMany('InstitutionSiteStaff', 				['className' => 'Institution.InstitutionSiteStaff', 'dependent' => true]);
		$this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours', 'dependent' => true]);
		$this->hasMany('InstitutionSiteStaffAbsences', 		['className' => 'Institution.InstitutionSiteStaffAbsences', 'dependent' => true]);

		$this->hasMany('Students', 							['className' => 'Institution.Students', 'dependent' => true, 'foreignKey' => 'institution_id']);
		$this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours', 'dependent' => true]);
		$this->hasMany('InstitutionSiteStudentAbsences', 	['className' => 'Institution.InstitutionSiteStudentAbsences', 'dependent' => true]);

		$this->hasMany('InstitutionSiteBankAccounts', 		['className' => 'Institution.InstitutionSiteBankAccounts', 'dependent' => true]);
		$this->hasMany('InstitutionSiteFees', 				['className' => 'Institution.InstitutionSiteFees', 'dependent' => true]);

		$this->hasMany('InstitutionSiteGrades', 			['className' => 'Institution.InstitutionSiteGrades', 'dependent' => true]);
		
		$this->hasMany('StudentPromotion', 					['className' => 'Institution.StudentPromotion', 'foreignKey' => 'institution_id', 'dependent' => true]);

		$this->belongsToMany('SecurityGroups', [
			'className' => 'Security.SystemGroups',
			'joinTable' => 'security_group_institution_sites',
			'foreignKey' => 'institution_site_id', 
			'targetForeignKey' => 'security_group_id',
			'through' => 'Security.SecurityGroupInstitutions',
			'dependent' => true
		]);

		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'institution_custom_field_id',
			'tableColumnKey' => 'institution_custom_table_column_id',
			'tableRowKey' => 'institution_custom_table_row_id',
			'formKey' => 'institution_custom_form_id',
			'filterKey' => 'institution_custom_filter_id',
			'formFieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFields'],
			'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'recordKey' => 'institution_site_id',
			'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
		$this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionSiteActivities', 'key' => 'institution_site_id', 'session' => 'Institutions.id']);
        $this->addBehavior('AdvanceSearch');
        $this->addBehavior('Excel', ['excludes' => ['security_group_id']]);
        $this->addBehavior('Security.Institution');
        $this->addBehavior('Area.Areapicker');
        $this->addBehavior('HighChart', ['institution_site' => ['_function' => 'getNumberOfInstitutionsByModel']]);


	}

	public function onExcelGenerate(Event $event, $writer, $settings) {
		// pr($settings);
		// $generate = function() { pr('dsa'); };
		// return $generate;
	}

	public function onExcelBeforeQuery(Event $event, Query $query) {
		// pr($this->Session->read($this->aliasField('id')));die;
		// $query->where(['Institutions.id' => 2]);
	}

	// public function onExcelGetLabel(Event $event, $column) {
	// 	return 'asd';
	// }

	public function validationDefault(Validator $validator) {
		$validator
			->add('date_opened', [
					'ruleCompare' => [
						'rule' => ['comparison', 'notequal', '0000-00-00'],
					],
					'ruleCheckDateInput' => [
			            'rule' => ['checkDateInput'],
		        		'last' => true
		    	    ]
				])

	        ->allowEmpty('date_closed')
 	        ->add('date_closed', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'date_opened', false]
	    	    ])

	        ->allowEmpty('longitude')
			->add('longitude', 'ruleLongitude', [
					'rule' => 'checkLongitude'
				])
		
	        ->allowEmpty('latitude')
			->add('latitude', 'ruleLatitude', [
					'rule' => 'checkLatitude'
				])
		
			// ->add('address', 'ruleMaximum255', [
			// 		'rule' => ['maxLength', 255],
			// 		'message' => 'Maximum allowable character is 255',
			// 		'last' => true
			// 	])

			->add('code', 'ruleUnique', [
	        		'rule' => 'validateUnique',
	        		'provider' => 'table',
	        		// 'message' => 'Code has to be unique'
			    ])

	        ->allowEmpty('email')
			->add('email', [
					'ruleUnique' => [
		        		'rule' => 'validateUnique',
		        		'provider' => 'table',
		        		// 'message' => 'Email has to be unique',
		        		'last' => true
				    ],
					'ruleValidEmail' => [
						'rule' => 'email'
					]
				])
	        ;
		return $validator;
	}

	public function onGetName(Event $event, Entity $entity) {
		$name = $entity->name;

		if ($this->AccessControl->check([$this->controller->name, 'dashboard'])) {
			$name = $event->subject()->Html->link($entity->name, [
				'plugin' => $this->controller->plugin,
				'controller' => $this->controller->name,
				'action' => 'dashboard',
				'0' => $entity->id
			]);
		}
		
		return $name;
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('security_group_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_area_id', ['visible' => false]);
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);

		$this->ControllerAction->field('institution_site_type_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_locality_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_status_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_sector_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_provider_id', ['type' => 'select']);
		$this->ControllerAction->field('institution_site_gender_id', ['type' => 'select']);
		$this->ControllerAction->field('area_administrative_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas']);
		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$SecurityGroup = TableRegistry::get('Security.SystemGroups');
		$SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');

        if ($entity->isNew()) {
			$obj = $SecurityGroup->newEntity(['name' => $entity->name]);
			$securityGroup = $SecurityGroup->save($obj);
			if ($securityGroup) {
				// add the relationship of security group and institutions
				$securityInstitution = $SecurityInstitutions->newEntity([
					'security_group_id' => $securityGroup->id, 
					'institution_site_id' => $entity->id
				]);
				$SecurityInstitutions->save($securityInstitution);

				$this->trackActivity = false;
				$entity->security_group_id = $securityGroup->id;
				if (!$this->save($entity)) {
					return false;
				}
			} else {
				return false;
			}

        } else {
			$securityGroupId = $entity->security_group_id;
			if (!empty($securityGroupId)) {
				$obj = $SecurityGroup->get($securityGroupId);
				if (is_object($obj)) {
					$data = ['name' => $entity->name];
					$obj = $SecurityGroup->patchEntity($obj, $data);
					$securityGroup = $SecurityGroup->save($obj);
					if (!$securityGroup) {
						return false;
					}
				}
			}

        }
        return true;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$securityGroupId = $entity->security_group_id;
		$SecurityGroup = TableRegistry::get('Security.SystemGroups');

		$groupEntity = $SecurityGroup->get($securityGroupId);
		$SecurityGroup->delete($groupEntity);
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if ($this->action == 'index') {
			$institutionRecords = $this->find();

			// Total Institutions: number
			$institutionCount = $institutionRecords
				->count();

			$models = [
				['InstitutionSiteTypes', 'institution_site_type_id', 'Type'],
				['InstitutionSiteSectors', 'institution_site_sector_id', 'Sector'],
				['InstitutionSiteLocalities', 'institution_site_locality_id', 'Locality'],
			];

			foreach ($models as $key => $model) {
				$institutionSiteArray[$key] = $this->getDonutChart('institution_site', $model);
			}

			$indexDashboard = 'dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [ 
	            	'model' => 'institutions',
	            	'modelCount' => $institutionCount,
	            	'modelArray' => $institutionSiteArray,
	            ],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	    $config['formButtons'] = false;
	}

	public function getNumberOfInstitutionsByModel($params=[]) {

		if (!empty($params)) {
			$conditions = isset($params['conditions']) ? $params['conditions'] : [];
			$_conditions = [];

			$modelName = $params[0];
			$modelId = $params[1];
			$key = $params[2];
			$params['key'] = $key;

			foreach ($conditions as $key => $value) {
				$_conditions[$modelName.'.'.$key] = $value;
			}
			$institutionRecords = $this->find();
			
			$selectString = $modelName.'.name';
			$institutionSiteTypesCount = $institutionRecords
				->contain([$modelName])
				->select([
					'count' => $institutionRecords->func()->count($modelId),
					'name' => $selectString
				])
				->group($modelId)
				->toArray();

			// Creating the data set		
			$dataSet = [];
			foreach ($institutionSiteTypesCount as $key => $value) {
	            // Compile the dataset
				$dataSet[] = [$value['name'], $value['count']];
			}
			$params['dataSet'] = $dataSet;
		}
		return $params;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->Session->delete('Institutions.id');

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'area_id', 'institution_site_type_id'
		]);

		$this->ControllerAction->setFieldVisible(['index'], [
			'code', 'name', 'area_id', 'institution_site_type_id'
		]);
	}

	public function onGetAreaId(Event $event, Entity $entity) {
		$areaName = $entity->Areas['name'];

		if($this->action == 'index'){
			// Getting the system value for the area
			$ConfigItems = TableRegistry::get('ConfigItems');
			$areaLevel = $ConfigItems->value('institution_area_level_id');

			// Getting the current area id
			$areaId = $entity->area_id;
			try {
				if ($areaId > 0) {
					$path = $this->Areas
						->find('path', ['for' => $areaId])
						->toArray();

					foreach($path as $value){
						if ($value['area_level_id'] == $areaLevel) {
							$areaName = $value['name'];
						}
					}
				}
			} catch (InvalidPrimaryKeyException $ex) {
				$this->log($ex->getMessage(), 'error');
			}
		}

		return $areaName;
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'area_id' && $this->action == 'index') {
			// Getting the system value for the area
			$ConfigItems = TableRegistry::get('ConfigItems');
			$areaLevel = $ConfigItems->value('institution_area_level_id');

			$AreaTable = TableRegistry::get('Area.AreaLevels');
			return $AreaTable->get($areaLevel)->name;
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		// the query options are setup so that Security.InstitutionBehavior can reuse it
		$options['query'] = [
			'contain' => ['InstitutionSiteTypes'],
			'select' => [
				$this->aliasField('id'), $this->aliasField('code'), $this->aliasField('name'),
				$this->aliasField('area_id'), 'Areas.name', 'InstitutionSiteTypes.name'
			],
			'join' => [
				[
					'table' => 'areas', 'alias' => 'Areas', 'type' => 'INNER',
					'conditions' => ['Areas.id = ' . $this->aliasField('area_id')]
				]
			]
		];
		$options['auto_contain'] = false;
		$query->contain($options['query']['contain']);
		$query->select($options['query']['select']);
		$query->join($options['query']['join']);

		$queryParams = $request->query;
		if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
			$query->order([$this->aliasField('name') => 'asc']);
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$search = $this->ControllerAction->getSearchKey();
		if (empty($search)) {
			// redirect to school dashboard if it is only one record and no add access
			$addAccess = $this->AccessControl->check(['Institutions', 'add']);
			if ($data->count() == 1 && !$addAccess) {
				$entity = $data->first();
				$event->stopPropagation();
				$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'dashboard', $entity->id];
				return $this->controller->redirect($action);
			}
		}
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'name', 'alternative_name', 'code', 'institution_site_provider_id', 'institution_site_sector_id', 'institution_site_type_id', 
			'institution_site_ownership_id', 'institution_site_gender_id', 'institution_site_status_id', 'date_opened', 'date_closed',
			
			'address', 'postal_code', 'institution_site_locality_id', 'latitude', 'longitude',

			'area_id', 'area_administrative_id',

			'contact_person', 'telephone', 'fax', 'email', 'website'
		]);
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	
	// autocomplete used for UserGroups
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this
			->find()
			->where([
				'OR' => [
					$this->aliasField('name') . ' LIKE' => $search,
					$this->aliasField('code') . ' LIKE' => $search
				]
			])
			->order([$this->aliasField('name')])
			->all();
		
		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s (%s)', $obj->name, $obj->code),
				'value' => $obj->id
			];
		}
		return $data;
	}

	public function onUpdateFieldInstitutionSiteTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['onChangeReload'] = true;
		}

		return $attr;
	}
}
