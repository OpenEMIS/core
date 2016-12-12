<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
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

        $this->hasMany('InstitutionClassGrades',    ['className' => 'Institution.InstitutionClassGrades']);
        
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
        $shiftOptions = TableRegistry::get('Institutions.ShiftOptions');
        
        return $shiftOptions->get($entity->institution_shift->shift_option_id)->name;
    }

    public function onExcelGetEducationGrade(Event $event, Entity $entity) 
    {
        //seek for education grade for single or multi grade class
        $query = $this
                ->find()
                ->contain('InstitutionClassGrades.EducationGrades')
                ->where([
                    $this->aliasfield('id') => $entity->id
                ])
                ->toArray();

        $classGrades = [];

        foreach ($query as $key => $value) {
            $institutionClassGrades = $value['institution_class_grades'];
            if ($institutionClassGrades) {
                foreach ($institutionClassGrades as $index => $val) {
                    $educationGrades = $val['education_grade'];
                    if ($educationGrades) {
                        $classGrades[] = $educationGrades->name;
                    }
                }
            }
        }

        return implode(', ',$classGrades); //display as comma seperated
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $query
        ->contain('Institutions.Areas')
        ->select([
            'area_name' => 'Areas.name', 
            'area_code' => 'Areas.code'
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {   
        $extraField[] = [
            'key' => 'EducationGrades.education_grade',
            'field' => 'education_grade',
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
