<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionClassesTable extends AppTable
{

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users',                       'foreignKey' => 'staff_id']);
        $this->belongsTo('SecondaryStaff', ['className' => 'User.Users',                        'foreignKey' => 'secondary_staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts',    'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',         'foreignKey' => 'institution_id']);

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

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelGetInstitutionShiftId(Event $event, Entity $entity)
    {
        return $entity->shift_name;
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
        ->contain('Institutions.AreaAdministratives')
        ->contain('Institutions.Types')
        ->contain('EducationGrades')
        ->contain('InstitutionShifts.ShiftOptions')
        ->contain('AcademicPeriods')
        ->select([
            'institution_code' => 'Institutions.code',
            'institution_name' => 'Institutions.name',
            'area_name' => 'Areas.name',
            'area_code' => 'Areas.code',
            'institution_type' => 'Types.name',
            'shift_name' => 'ShiftOptions.name',
            'area_administrative_code' => 'AreaAdministratives.code',
            'area_administrative_name' => 'AreaAdministratives.name'
        ])
        ->order([
            'AcademicPeriods.order',
            'Institutions.code'
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        //redeclare all for sorting purpose.
        $newFields[] = [
            'key' => 'InstitutionClasses.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Types.institution_type',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.institution_shift_id',
            'field' => 'institution_shift_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionClasses.secondary_staff_id',
            'field' => 'secondary_staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }
}
