<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;

class ExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_extracurriculars');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);
		$this->addBehavior('Excel');
	}

	public function beforeAction() {
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['extracurricular_type_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['end_date']['visible'] = false;
		$this->fields['hours']['visible'] = false;
		$this->fields['points']['visible'] = false;
		$this->fields['location']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('extracurricular_type_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('extracurricular_type_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('end_date', $order++);
		$this->ControllerAction->setFieldOrder('hours', $order++);
		$this->ControllerAction->setFieldOrder('points', $order++);
		$this->ControllerAction->setFieldOrder('location', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
		;
	}
	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, $data) {
		$this->setupTabElements();
	}
	
	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
		$session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
		
		$Staff = TableRegistry::get('Security.Users');
		
         $query
            ->select([
                'name' =>  $this->aliasfield('name'),
				'hours' =>  $this->aliasfield('hours'),
				'points' =>  $this->aliasfield('points'),
				'location' =>  $this->aliasfield('location'),
				'comment' =>  $this->aliasfield('comment'),
				'start_date' =>  $this->aliasfield('start_date'),
                'end_date' =>  $this->aliasfield('end_date'),
				'openemis_no' => $Staff->aliasfield('openemis_no'),
				'staff_name' => $Staff->find()->func()->concat([
                    $Staff->aliasfield('first_name') => 'literal',
                    " - ",
                    $Staff->aliasfield('last_name') => 'literal']),
            ])
			->contain([
				'ExtracurricularTypes' => [
                    'fields' => [
                        'extracurricular_type' => 'ExtracurricularTypes.name'
                    ]
                ],
				'AcademicPeriods' => [
                    'fields' => [
                        'academic_period' => 'AcademicPeriods.name'
                    ]
                ]
			])
			->leftJoin(
				[$Staff->alias() => $Staff->table()],
				[
					$Staff->aliasField('id = ') . $this->aliasField('staff_id')
				]
			)
			->where([
				$this->aliasField('staff_id') => $staffId,
			])
			;
			 
    }
	
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
		
        $extraFields[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
		
        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
		
        $extraFields[] = [
            'key' => 'ExtracurricularTypes.name',
            'field' => 'extracurricular_type',
            'type' => 'string',
            'label' => __('Extracurricular Type')
        ];
		
        $extraFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];
		
		$extraFields[] = [
            'key' => '',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Start Date')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'end_date',
            'type' => 'date',
            'label' => __('End Date')
        ];
		
        $extraFields[] = [
            'key' => '',
            'field' => 'hours',
            'type' => 'string',
            'label' => __('Hours')
        ];		
		
        $extraFields[] = [
            'key' => '',
            'field' => 'points',
            'type' => 'string',
            'label' => __('Points')
        ];	
		
        $extraFields[] = [
            'key' => '',
            'field' => 'location',
            'type' => 'string',
            'label' => __('Location')
        ];
		
		$extraFields[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
		
       $newFields = $extraFields;
       $fields->exchangeArray($newFields);
       
   }
   
}
