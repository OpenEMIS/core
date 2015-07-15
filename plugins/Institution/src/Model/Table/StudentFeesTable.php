<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentFeesTable extends AppTable {
	public $institutionId = 0;
	private $_selectedAcademicPeriodId = -1;
	private $_academicPeriodOptions = [];
	private $_selectedEducationGradeId = -1;
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
		return $validator;
	}

	public function beforeAction(Event $event) {
    	$this->ControllerAction->field('amount', ['type' => 'float', 					'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('payment_date', ['type' => 'date', 				'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('comments', ['type' => 'string', 				'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	$this->ControllerAction->field('security_user_id', ['type' => 'select', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('institution_site_fee_id', ['type' => 'select', 	'visible' => ['edit'=>true]]);

    	$this->ControllerAction->field('openemis_no', ['type' => 'string', 				'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('name', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('total', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('outstanding', ['type' => 'string', 				'visible' => ['index'=>true]]);

		$session = $this->request->session();
		$this->institutionId = $session->read('Institutions.id');

		// $ConfigItems = TableRegistry::get('ConfigItems');
  //   	$currency = $ConfigItems->value('currency');
  //   	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $currency, 'visible' => ['view'=>true, 'edit'=>true]]);

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'name', 'total', 'amount', 'outstanding'
		]);

		// $Fees = $this;
 	// 	$institutionsId = $this->Session->read('Institutions.id');

		// $this->advancedSelectOptions($this->_academicPeriodOptions, $this->_selectedAcademicPeriodId, [
		// 	'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noProgrammeGradeFees')),
		// 	'callable' => function($id) use ($Fees, $institutionsId) {
		// 		return $Fees->find()->where(['institution_site_id'=>$institutionsId, 'academic_period_id'=>$id])->count();
		// 	}
		// ]);

		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $this->institutionId
		);
		$academicPeriodOptions = $this->InstitutionSiteFees->Institutions->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId);

		$gradeOptions = $this->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId);

		$toolbarElements = [
            ['name' => 'Institution.StudentFees/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions,
	            	'gradeOptions'=>$gradeOptions,
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
		
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $paginateOptions) {
		$paginateOptions['conditions'][] = [
						'InstitutionSiteFees.institution_site_id' => $this->institutionId,
						'InstitutionSiteFees.academic_period_id' => $this->_selectedAcademicPeriodId,
						'InstitutionSiteFees.education_grade_id' => $this->_selectedEducationGradeId
					];
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event) {
   		$this->ControllerAction->setFieldOrder([
			'institution_site_fee_id', 'security_user_id', 'payment_date', 'amount', 'comments'
		]);
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function viewBeforeAction(Event $event) {
		// if ($model->alias() == 'StudentFees') {
	    	// $this->ControllerAction->models[$model->alias()]['actions'][] = 'add';
	    	// $this->ControllerAction->defaultActions = $this->ControllerAction->models[$model->alias()]['actions'];

			// $this->ControllerAction->model($model->alias(), $this->ControllerAction->models[$model->alias()]['actions']);
	  //   	pr($this->ControllerAction->models[$model->alias()]['actions']);
		// }

		// $this->ControllerAction->vars()['toolbarButtons']['edit'] = [
  //                   'url' => [],
  //                   'type' => 'button',
  //                   'label' => '',
  //                   'attr' => [
  //                           'class' => 'btn btn-xs btn-default',
  //                           'data-toggle' => 'tooltip',
  //                           'data-placement' => 'bottom',
  //                           'escape' => '',
  //                           'title' => 'Edit'
  //                       ]
  //               	];
  //   	pr($this->ControllerAction->vars()['toolbarButtons']);
   		$this->ControllerAction->setFieldOrder([
			'institution_site_fee_id', 'security_user_id', 'payment_date', 'amount', 'comments'
		]);
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
		// return $event->subject()->Html->link($entity->name, [
		// 	'plugin' => $this->controller->plugin,
		// 	'controller' => $this->controller->name,
		// 	'action' => $this->alias,
		// 	'index',
		// 	'parent' => $entity->id
		// ]);
	}

	public function onGetName(Event $event, Entity $entity) {
		return $entity->user->name;
	}

	public function onGetTotal(Event $event, Entity $entity) {
		return $entity->institution_site_fee->total;
	}

	public function onGetOutstanding(Event $event, Entity $entity) {
		return ($entity->institution_site_fee->total - $entity->amount);
	}

	// public function onUpdateOpenemisNo(Event $event, array $attr, $action, $request) {
	// 	return $attr;
	// }

	// public function onGetInstitutionSiteFeeId(Event $event, Entity $entity) {
	// 	die('onGetInstitutionSiteFeeId');
	// 	return 'chak';
	// }

	// public function onUpdateInstitutionSiteFeeId(Event $event, array $attr, $action, $request) {
	// 	die('chak');
	// 	$attr['value'] = 'chak';
	// 	$attr['type'] = 'string';
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
