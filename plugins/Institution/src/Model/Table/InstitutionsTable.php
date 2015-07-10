<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

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
		$this->belongsTo('InstitutionSiteProviders', 		['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);

		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);

		/**
		 * This model uses TrackActivityBehavior
		 */
		$this->hasMany('InstitutionSiteActivities', 		['className' => 'Institution.InstitutionSiteActivities']);
		
		$this->hasMany('InstitutionSiteAttachments', 		['className' => 'Institution.InstitutionSiteAttachments']);
		$this->hasMany('Additional', 						['className' => 'Institution.Additional']);

		$this->hasMany('InstitutionSitePositions', 			['className' => 'Institution.InstitutionSitePositions']);
		$this->hasMany('InstitutionSiteProgrammes', 		['className' => 'Institution.InstitutionSiteProgrammes']);
		$this->hasMany('InstitutionSiteShifts', 			['className' => 'Institution.InstitutionSiteShifts']);
		$this->hasMany('InstitutionSiteSections', 			['className' => 'Institution.InstitutionSiteSections']);
		$this->hasMany('InstitutionSiteClasses', 			['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('InstitutionSiteInfrastructures', 	['className' => 'Institution.InstitutionSiteInfrastructures']);

		$this->hasMany('InstitutionSiteStaff', 				['className' => 'Institution.InstitutionSiteStaff']);
		$this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours']);
		$this->hasMany('InstitutionSiteStaffAbsences', 		['className' => 'Institution.InstitutionSiteStaffAbsences']);

		$this->hasMany('InstitutionSiteStudents', 			['className' => 'Institution.InstitutionSiteStudents']);
		// $this->hasMany('Students', 							['className' => 'Institution.InstitutionSiteStudents']);
		$this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours']);
		$this->hasMany('InstitutionSiteStudentAbsences', 	['className' => 'Institution.InstitutionSiteStudentAbsences']);

		$this->hasMany('InstitutionSiteBankAccounts', 		['className' => 'Institution.InstitutionSiteBankAccounts']);
		$this->hasMany('InstitutionSiteFees', 				['className' => 'Institution.InstitutionSiteFees']);
		$this->hasMany('StudentFees', 						['className' => 'Institution.StudentFees']);

		$this->hasMany('NewSurveys', 						['className' => 'Institution.SurveyNew']);
		$this->hasMany('InstitutionSiteSurveyDrafts', 		['className' => 'Institution.InstitutionSiteSurveyDrafts']);
		$this->hasMany('InstitutionSiteSurveyCompleted', 	['className' => 'Institution.InstitutionSiteSurveyCompleted']);

		$this->hasMany('InstitutionSiteAssessmentResults', 	['className' => 'Institution.InstitutionSiteAssessmentResults']);

		$this->hasMany('InstitutionSiteGrades', 			['className' => 'Institution.InstitutionSiteGrades']);
		// $this->hasMany('InstitutionSiteCustomFields', ['className' => 'Institution.InstitutionSiteCustomFields']);


		$this->hasMany('InstitutionSiteClassStaff', 		['className' => 'Institution.InstitutionSiteClassStaff']);
		$this->hasMany('InstitutionSiteClassStudents', 		['className' => 'Institution.InstitutionSiteClassStudents']);
		$this->hasMany('InstitutionSiteSectionClasses', 	['className' => 'Institution.InstitutionSiteSectionClasses']);

		// pr($this->validator());
		$this->addBehavior('CustomField.Record', [
			'recordKey' => 'institution_site_id',
			'fieldValueKey' => ['className' => 'Institution.InstitutionCustomFieldValues', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellKey' => ['className' => 'Institution.InstitutionCustomTableCells', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
		$this->addBehavior('Year', ['date_opened' => 'year_opened', 'date_closed' => 'year_closed']);
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionSiteActivities', 'key' => 'institution_site_id', 'session' => 'Institutions.id']);
        $this->addBehavior('AdvanceSearch');
	}

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
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => 'dashboard',
			'0' => $entity->id
		]);
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
		//$this->ControllerAction->field('area_id', ['type' => 'select']);
		$this->ControllerAction->field('area_administrative_id', ['type' => 'area', 'source_model'=>'Area.AreaAdministratives']);
		//$this->ControllerAction->field('area_administrative_id', ['type' => 'area', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('area_id', ['type' => 'area', 'source_model' => 'Area.Areas']);
		//$this->ControllerAction->field('area_id', ['type' => 'element', 'element' => 'Institution.Institutions/area', 'valueClass' => 'table-full-width', 'id-id' => 'xxx', 'source_model' => 'Area.Areas']);
		// pr($this->fields['area_administrative_id']);
		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}

		//$datatest = ['1' => 'data1', '2' => 'data2'];
		//$this->controller->set('datatest', $datatest);
	}

	// public function onUpdateFieldAreaAdministrativeId(Event $event, $attr) {
	// 	$attr['type'] = 'area';
	// 	$attr['model'] = 'something';
	// 	return $attr;
	// }

	public function onGetAreaElement(Event $event, $action, Entity $entity, $attr, $options) {
		$includes = [
			'area' => ['include' => true, 'js' => 'area']
		];

		$controller = $this->controller;
		$HtmlField = $event->subject();
		$HtmlField->includes = array_merge($HtmlField->includes, $includes);
		$Url = $HtmlField->Url;
		$Form = $HtmlField->Form;
		$targetModel = $attr['source_model'];
		$targetTable = TableRegistry::get($targetModel);

		$parentId = -1;
		$worldRecord = "World";

		$areaOptions = $targetTable
			->find('list')
			->where(['parent_id'=>$parentId])
			->toArray();

		// Find the children of World
		if($attr['source_model']=='Area.AreaAdministratives'){
			// Using the primary key of the World record
			$parentId = key($areaOptions);
			$areaOptions = $targetTable
			->find('list')
			->where(['parent_id'=>$parentId])
			->toArray();
		}

		$fieldName = $attr['model'] . '.' . $attr['field'];
		$options['onchange'] = "Area.reload(this)";
		$options['url'] = $Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
		$options['data-source'] = $attr['source_model'];
		//$options['class'] = 'areapicker';
		$options['options'] = $areaOptions;
		$options['id'] = 'areapicker';
		$value = "<div class='areapicker'>";
		$value .= $Form->input($fieldName, $options);
		$value .= $Form->hidden($attr['model'].'.'.$attr['field'], ['value' => ""]);
		$value .= "</div>";
		return $value;
	
	}

	public function afterSave(Event $event, Entity $entity, $options) {
		// echo 'Entity<br/>';pr($entity);pr('<hr/>');
		// echo 'Options<br/>';pr($options);pr('<hr/>');
		// echo 'Operation<br/>';pr($operation);pr('<hr/>');
		// die('afterSave');
        if ($entity->isNew()) {
			// $addSecurityGroupParams = array(
			// 	'SecurityGroup' => array(
			// 		'name' => $this->data['InstitutionSite']['name']
			// 	),
			// 	'GroupInstitutionSite' => array(
			// 		'0' => array(
			// 			'institution_site_id' => $this->data['InstitutionSite']['id']
			// 		)
			// 	)
			// );
			// $securityGroup = $this->SecurityGroup->save($addSecurityGroupParams);
			// if ($securityGroup) {
			// 	$this->trackActivity = false;
			// 	$this->data['InstitutionSite']['security_group_id'] = $securityGroup['SecurityGroup']['id'];
			// 	if (!$this->save()) {
			// 		return false;
			// 	}
			// } else {
			// 	return false;
			// }
        } else {
			// $securityGroupId = $this->field('security_group_id');
			// if (!empty($securityGroupId)) {
			// 	$this->SecurityGroup->read(null, $securityGroupId);
			// 	if (is_object($this->SecurityGroup)) {
			// 		$editSecurityGroupParams = array(
			// 			'SecurityGroup' => array(
			// 				'id' => $securityGroupId,
			// 				'name' => $this->data['InstitutionSite']['name']
			// 			)
			// 		);
			// 		if (!$this->SecurityGroup->save($editSecurityGroupParams)) {
			// 			return false;
			// 		}
			// 	}
			// }
        }
        return true;
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if ($this->action == 'index') {
			$indexDashboard = 'Institution.Institutions/dashboard';
			$this->controller->viewVars['indexElements']['mini_dashboard'] = [
	            'name' => $indexDashboard,
	            'data' => [],
	            'options' => [],
	            'order' => 1
	        ];
	    }
	    $config['formButtons'] = false;
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

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$query = $request->query;
		if (!array_key_exists('sort', $query) && !array_key_exists('direction', $query)) {
			$options['order'][$this->aliasField('name')] = 'asc';
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
}
