<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentFeesTable extends AppTable {
	private $_selectedAcademicPeriodId = 0;
	private $_academicPeriodOptions = [];
	private $_selectedGradeId = 0;
	private $_gradeOptions = [];


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteFees', ['className' => 'Institution.InstitutionSiteFees']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		/**
		 * Shortcuts
		 */
		$this->AcademicPeriods = $this->InstitutionSiteFees->AcademicPeriods;	
		$this->InstitutionSiteGrades = $this->InstitutionSiteFees->Institutions->InstitutionSiteGrades;	
	}

	public function validationDefault(Validator $validator) {
		// 'amount' => array(
		// 	'ruleRequired' => array(
		// 		'rule' => 'notEmpty',
		// 		'required' => true,
		// 		'message' => 'Please enter a valid Amount'
		// 	)
		// )

		return $validator;
	}

	public function beforeAction($event) {

    	$this->ControllerAction->field('amount', ['type' => 'float', 					'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('payment_date', ['type' => 'date', 				'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('comments', ['type' => 'string', 				'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	$this->ControllerAction->field('security_user_id', ['type' => 'select', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('institution_site_fee_id', ['type' => 'select', 	'visible' => ['edit'=>true]]);

    	$this->ControllerAction->field('openemis_id', ['type' => 'string', 				'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('name', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('total', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('outstanding', ['type' => 'string', 				'visible' => ['index'=>true]]);

		// $ConfigItems = TableRegistry::get('ConfigItems');
  //   	$currency = $ConfigItems->value('currency');
  //   	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $currency, 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $this->_academicPeriodOptions);

 		$institutionsId = $this->Session->read('Institutions.id');
		$this->_gradeOptions = $this->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$this->_selectedGradeId = $this->queryString('grade_id', $this->_gradeOptions);

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
		$this->ControllerAction->setFieldOrder([
			'openemis_id', 'name', 'total', 'amount', 'outstanding'
		]);

		// $Fees = $this;
 	// 	$institutionsId = $this->Session->read('Institutions.id');

		// $this->advancedSelectOptions($this->_academicPeriodOptions, $this->_selectedAcademicPeriodId, [
		// 	'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammeGradeFees')),
		// 	'callable' => function($id) use ($Fees, $institutionsId) {
		// 		return $Fees->find()->where(['institution_site_id'=>$institutionsId, 'academic_period_id'=>$id])->count();
		// 	}
		// ]);

		$toolbarElements = [
            ['name' => 'Institution.StudentFees/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$this->_academicPeriodOptions,
	            	'gradeOptions'=>$this->_gradeOptions,
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
		
	}

	public function indexBeforePaginate($event, $request, $paginateOptions) {
		// $paginateOptions['finder'] = ['withProgrammes' => []];
		return $paginateOptions;
	}

    public function findWithProgrammes(Query $query, array $options) {
    	return $query->contain(['EducationGrades'=>['EducationProgrammes']]);
    }


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	// public function onGetEducationProgramme(Event $event, Entity $entity) {
	// 	return $entity->education_grade->education_programme->name;
	// }

	// public function onUpdateFieldFeeTypes(Event $event, array $attr, $action, $request) {
	// 	$attr['options'] = $this->InstitutionSiteFeeTypes->FeeTypes->getList();
	// 	return $attr;
	// }

	// public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
	// 	$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
	// 	$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
	// 	$attr['options'] = $this->_academicPeriodOptions;
	// 	return $attr;
	// }

	// public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
	// 	if ($this->_selectedAcademicPeriodId ==0) {
	// 		$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
	// 		$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
	// 	}
 // 		$institutionsId = $this->Session->read('Institutions.id');
	// 	$this->_gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
	// 	$attr['options'] = $this->_gradeOptions;
	// 	return $attr;
	// }



}
