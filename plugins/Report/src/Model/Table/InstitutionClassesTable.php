<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionClassesTable extends AppTable  {

    public function initialize(array $config) 
    {
		$this->table('institution_classes');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts', 		['className' => 'Institution.InstitutionShifts',	'foreignKey' => 'institution_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_id']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);
        
		$this->addBehavior('Excel', ['excludes' => ['class_number']]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
    {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

    public function onExcelGetInstitutionId(Event $event, Entity $entity) 
    {
        return $entity->institution->code_name;
    }

    public function onExcelGetInstitutionShiftId(Event $event, Entity $entity) 
    {
        return $entity->institution_shift->shift_option->name;
    }

    public function onExcelGetEducationGrades(Event $event, Entity $entity) 
    {
        $classGrades = [];
        if ($entity->education_grades) {
           foreach ($entity->education_grades as $key => $value) {
                $classGrades[] = $value->name;
            } 
        }
        
        return implode(', ', $classGrades); //display as comma seperated
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $query
        ->contain('Institutions.Areas')
        ->contain('EducationGrades')
        ->contain('InstitutionShifts.ShiftOptions')
        ->select([
            'area_name' => 'Areas.name', 
            'area_code' => 'Areas.code'
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {   
        $extraField[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Areas.area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Areas.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields = array_merge($extraField, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }
}
