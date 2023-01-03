<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\ResultSet;
use LDAP\Result;

class Uis3Table extends AppTable
{
    private $uisTabsData = [0 => "UIS-A3", 1 => "UIS-A5", 2 => "UIS-A6"];
    public function initialize(array $config)       
    {
        $this->table('summary_grade_gender_ages');
        parent::initialize($config);

        $this->addBehavior('Excel', [
            'excludes' => []
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $uisTabsData = $this->uisTabsData;
        foreach ($uisTabsData as $key => $val)
        {
            $tabsName = $val;
            $sheets[] = ['sheetData' => ['uis_tabs_type' => $val], 'name' => $tabsName, 'table' => $this, 'query' => $this->find()

            , 'orientation' => 'landscape'];
        }

        
    }
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $UISType = $sheetData['uis_tabs_type'];

        $newFields = [];
        
        if ($UISType == 'UIS-A3')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name1", "type" => "string", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "education_system_name1", "type" => "string", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level1", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name1", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name1", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name1", "type" => "string", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code1", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name1", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "education_grade_code1", "type" => "integer", "label" => "Education Grade Code"];
            $extraField[] = ["key" => "", "field" => "education_grade_name1", "type" => "integer", "label" => "Education Grade Name"];
            $extraField[] = ["key" => "", "field" => "student_gender_name1", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "student_age", "type1" => "integer", "label" => "Age"];
            $extraField[] = ["key" => "", "field" => "total_students1", "type" => "integer", "label" => "Number of Students"];

        }
        
        $fields->exchangeArray($extraField);
    }


    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $uisType = $sheetData['uis_tabs_type'];
        $areaAdministratives = TableRegistry::get('area_administratives');
        $institutions = TableRegistry::get('institutions');
        $area = TableRegistry::get('areas');
        $reqData = json_decode($settings['process']['params'], true);
        $academic_period_id = $reqData['academic_period_id'];
        
     

        if ($uisType == 'UIS-A3')
        {   
            $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
            $res = $query->select([
                'academic_period_name1' => 'academic_period_name',
                'education_system_name1' => 'education_system_name',
                'education_level_isced_level1' => 'education_level_isced_level',
                'education_level_isced_name1' => 'education_level_isced_name',
                'education_level_name1' => 'education_level_name',
                'education_cycle_name1' => 'education_cycle_name',
                'education_programme_code1' => 'education_programme_code',
                'education_programme_name1' => 'education_programme_name',
                'education_grade_code1' => 'education_grade_code',
                'education_grade_name1' => 'education_grade_name',
                'student_gender_name1' => 'student_gender_name',
                'student_age1' => 'student_age',
                'total_students1' => 'total_students',
            ])
            ->where(['academic_period_id' => $academic_period_id]);
        }
        
       
    }
}