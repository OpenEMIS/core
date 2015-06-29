<?php
namespace Institution\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
        $this->addBehavior('TrackActivity', ['target' => 'Institution.InstitutionSiteActivities', 'key' => 'institution_site_id', 'session' => 'Institutions.id']);
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

		$this->hasMany('CustomFieldValues', ['className' => 'Institution.InstitutionCustomFieldValues', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'Institution.InstitutionCustomTableCells', 'foreignKey' => 'institution_site_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		// pr($this->validator());
		$this->addBehavior('CustomField.Record', [
			'recordKey' => 'institution_site_id'
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('date_opened', 'ruleCompare', [
					'rule' => array('comparison', 'notequal', '0000-00-00'),
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

	public function beforeAction($event) {
		$this->ControllerAction->field('year_opened', ['visible' => false]);
		$this->ControllerAction->field('year_closed', ['visible' => false]);
		$this->ControllerAction->field('security_group_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_area_id', ['visible' => false]);
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);

		$this->ControllerAction->field('institution_site_locality_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_type_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_ownership_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_status_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_sector_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_provider_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('institution_site_gender_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);
		$this->ControllerAction->field('area_administrative_id', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'select'
		]);

		$this->ControllerAction->field('alternative_name', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('address', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('postal_code', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('telephone', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('fax', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('email', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('website', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('date_opened', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('date_closed', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('longitude', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('latitude', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);
		$this->ControllerAction->field('contact_person', [
			'visible' => ['view'=>true, 'edit'=>true],
			'type' => 'string'
		]);

		$this->ControllerAction->field('area_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->ControllerAction->field('code', [
			'visible' => true,
			'type' => 'string'
		]);
		$this->ControllerAction->field('name', [
			'visible' => true,
			'type' => 'string'
		]);
		$this->ControllerAction->field('institution_site_type_id', [
			'visible' => true,
			'type' => 'string'
		]);


		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}

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


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$this->Session->delete('Institutions.id');

		$indexDashboard = 'Institution.Institutions/dashboard';
		$this->controller->set('indexDashboard', $indexDashboard);

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'area_id', 'institution_site_type_id'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$query = $request->query;
		if (!array_key_exists('sort', $query) && !array_key_exists('direction', $query)) {
			$options['order'][$this->aliasField('name')] = 'asc';
		}
		return $options;
	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addEditAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('addEditAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('addEditAfterAction', [$event, $entity]));
		}
		return $entity;
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

	public function viewAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('viewAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('viewAfterAction', [$event, $entity]));
		}

		return $entity;
	}
}
