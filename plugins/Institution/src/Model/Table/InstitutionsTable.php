<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

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
		
		$this->hasMany('Attachments', 						['className' => 'Institution.InstitutionSiteAttachments']);
		$this->hasMany('Additional', 						['className' => 'Institution.Additional']);

		$this->hasMany('Positions', 						['className' => 'Institution.InstitutionSitePositions']);
		$this->hasMany('Programmes', 						['className' => 'Institution.InstitutionSiteProgrammes']);
		$this->hasMany('Shifts', 							['className' => 'Institution.InstitutionSiteShifts']);
		$this->hasMany('Sections', 							['className' => 'Institution.InstitutionSiteSections']);
		$this->hasMany('Classes', 							['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('Infrastructures', 					['className' => 'Institution.InstitutionSiteInfrastructures']);

		$this->hasMany('Staff', 							['className' => 'Institution.InstitutionSiteStaff']);
		$this->hasMany('StaffBehaviours', 					['className' => 'Institution.StaffBehaviours']);
		$this->hasMany('StaffAbsences', 					['className' => 'Institution.InstitutionSiteStaffAbsences']);

		$this->hasMany('InstitutionSiteStudents', 			['className' => 'Institution.InstitutionSiteStudents']);
		// $this->hasMany('Students', 							['className' => 'Institution.InstitutionSiteStudents']);
		$this->hasMany('StudentBehaviours', 				['className' => 'Institution.StudentBehaviours']);
		$this->hasMany('StudentAbsences', 					['className' => 'Institution.InstitutionSiteStudentAbsences']);

		$this->hasMany('BankAccounts', 						['className' => 'Institution.InstitutionSiteBankAccounts']);
		$this->hasMany('Fees', 								['className' => 'Institution.InstitutionSiteFees']);
		$this->hasMany('StudentFees', 						['className' => 'Institution.StudentFees']);

		$this->hasMany('NewSurveys', 						['className' => 'Institution.SurveyNew']);
		$this->hasMany('InstitutionSiteSurveyDrafts', 		['className' => 'Institution.InstitutionSiteSurveyDrafts']);
		$this->hasMany('InstitutionSiteSurveyCompleted', 	['className' => 'Institution.InstitutionSiteSurveyCompleted']);

		$this->hasMany('InstitutionSiteAssessmentResults', 	['className' => 'Institution.InstitutionSiteAssessmentResults']);

		$this->hasMany('InstitutionSiteGrades', 			['className' => 'Institution.InstitutionSiteGrades']);
		// $this->hasMany('InstitutionSiteCustomFields', ['className' => 'Institution.InstitutionSiteCustomFields']);
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

	public function indexBeforeAction(Event $event) {
		$this->Session->delete('Institutions.id');
		$this->fields['alternative_name']['visible']['index'] = false;
		$this->fields['address']['visible']['index'] = false;
		$this->fields['postal_code']['visible']['index'] = false;
		$this->fields['telephone']['visible']['index'] = false;
		$this->fields['fax']['visible']['index'] = false;
		$this->fields['email']['visible']['index'] = false;
		$this->fields['website']['visible']['index'] = false;
		$this->fields['date_opened']['visible']['index'] = false;
		$this->fields['date_closed']['visible']['index'] = false;
		$this->fields['longitude']['visible']['index'] = false;
		$this->fields['latitude']['visible']['index'] = false;
		$this->fields['contact_person']['visible']['index'] = false;

		$indexDashboard = 'Institution.Institutions/dashboard';
		$this->controller->set('indexDashboard', $indexDashboard);
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

	public function beforeAction($event) {
		/**
		 * 
		 */
		$this->fields['year_opened']['visible'] = false;
		$this->fields['year_closed']['visible'] = false;
		$this->fields['security_group_id']['visible'] = false;
		$this->fields['institution_site_area_id']['visible'] = false;

		$this->fields['institution_site_locality_id']['type'] = 'select';
		$this->fields['institution_site_type_id']['type'] = 'select';
		$this->fields['institution_site_ownership_id']['type'] = 'select';
		$this->fields['institution_site_status_id']['type'] = 'select';
		$this->fields['institution_site_sector_id']['type'] = 'select';
		$this->fields['institution_site_provider_id']['type'] = 'select';
		$this->fields['institution_site_gender_id']['type'] = 'select';

		$this->fields['area_id']['type'] = 'select';
		$this->fields['area_administrative_id']['type'] = 'select';
		// pr($this->fields);die;
		// $areaId = false;
		// $areaAdministrativeId = false;
		if ($this->action == 'add') {
			// $this->fields['area_id']['options'] = $areaOptions;
			// $this->fields['area_id']['attr'] = ['onchange' => "$('#reload').click()"];

			// start Education Grade field
			// pr($this->fields['area_id']['order']);die;
			// $this->ControllerAction->addField('area', [
			// 	'type' => 'element', 
			// 	'order' => ($this->fields['area_id']['order']),
			// 	'element' => 'Area.areas'
			// ]);

			// $programmeId = key($programmeOptions);
			// if ($this->request->data($this->aliasField('education_programme_id'))) {
			// 	$programmeId = $this->request->data($this->aliasField('education_programme_id'));
			// }
			// TODO-jeff: need to check if programme id is empty
			// $Areas = $this->Areas;
			// $areadata = $Areas->find()
			// 	->find('visible')->find('order')
			// 	->all();

			// $this->fields['area']['data'] = $areadata;
			// $this->fields['area']['selectedId'] = $areaId;
			// end Education Grade field
			if ($this->request->is('post')) {
				// $dateOpened = $this->request->data['InstitutionSite']['date_opened'];
				// $dateClosed = $this->request->data['InstitutionSite']['date_closed'];
				// if(!empty($dateOpened)) {
				// 	$this->request->data['InstitutionSite']['year_opened'] = date('Y', strtotime($dateOpened));
				// }
				// if(!empty($dateClosed)) {
				// 	$this->request->data['InstitutionSite']['year_closed'] = date('Y', strtotime($dateClosed));
				// }
				// $this->InstitutionSite->set($this->request->data);
				
				// if ($this->InstitutionSite->validates()) {
				// 	$result = $this->InstitutionSite->save($this->request->data);
				// 	$institutionSiteId = $result['InstitutionSite']['id'];
				// 	$this->Session->write('InstitutionSiteId', $institutionSiteId);
				// 	$this->Message->alert('general.add.success');
				// 	$this->redirect(array('controller' => 'InstitutionSites', 'action' => $this->indexPage, $institutionSiteId));
				// }
				// $areaId = $this->request->data['InstitutionSite']['area_id'];
				// $areaAdministrativeId = $this->request->data['InstitutionSite']['area_administrative_id'];
			}
		}

		// $this->ControllerAction->addField('education_level', ['type' => 'select']);
		// $EducationLevels = TableRegistry::get('Education.EducationLevels');
		// $levelOptions = $EducationLevels
		// 	->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
		// 	->find('withSystem')
		// 	->toArray();			
		// $this->fields['education_level']['options'] = $levelOptions;
		// $this->fields['education_level']['attr'] = ['onchange' => "$('#reload').click()"];

		// $this->fields['education_programme_id']['type'] = 'select';
		if ($this->action == 'add') {
			// // TODO-jeff: write validation logic to check for loaded $levelOptions
			// $levelId = key($levelOptions);
			// if ($this->request->data($this->aliasField('education_level'))) {
			// 	$levelId = $this->request->data($this->aliasField('education_level'));
			// }
			// $programmeOptions = $this->EducationProgrammes
			// 	->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			// 	->find('withCycle')
			// 	->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $levelId])
			// 	->toArray();
			
			// $this->fields['education_programme_id']['options'] = $programmeOptions;
			// $this->fields['education_programme_id']['attr'] = ['onchange' => "$('#reload').click()"];

			// // start Education Grade field
			// $this->ControllerAction->addField('education_grade', [
			// 	'type' => 'element', 
			// 	'order' => 5,
			// 	'element' => 'Institution.Programmes/grades'
			// ]);

			// $programmeId = key($programmeOptions);
			// if ($this->request->data($this->aliasField('education_programme_id'))) {
			// 	$programmeId = $this->request->data($this->aliasField('education_programme_id'));
			// }
			// // TODO-jeff: need to check if programme id is empty
			// $EducationGrades = $this->EducationProgrammes->EducationGrades;
			// $gradeData = $EducationGrades->find()
			// 	->find('visible')->find('order')
			// 	->where([$EducationGrades->aliasField('education_programme_id') => $programmeId])
			// 	->all();

			// $this->fields['education_grade']['data'] = $gradeData;
			// end Education Grade field
		}

	}

}
