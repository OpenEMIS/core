<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentsEnrollmentSummaryTable extends AppTable  {
    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        //POCOR-5863 starts
        $this->addBehavior('StudentsEnrollmentSummaryExcel',[
            'pages' => false,
            'autoFields' => false
        ]);
        //POCOR-5863 ends
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Name')
        ];  

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Code')
        ];

        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => '',
            'field' => 'count',
            'type' => 'string',
            'label' => __('Number of Students')
        ];  
        
        $newFields = $extraFields;
        $fields->exchangeArray($newFields);
    }

}
