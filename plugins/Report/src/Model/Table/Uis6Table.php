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

class Uis6Table extends AppTable
{
    private $uisTabsData = [0 => "UIS-A6"];
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

        if ($UISType == 'UIS-A6')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name3", "type" => "string", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "education_system_name3", "type" => "string", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level3", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name3", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name3", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name3", "type" => "string", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code3", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name3", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "education_grade_code3", "type" => "integer", "label" => "Education Grade Code"];
            $extraField[] = ["key" => "", "field" => "education_grade_name3", "type" => "integer", "label" => "Education Grade Name"];
            $extraField[] = ["key" => "", "field" => "student_gender_name3", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "student_age3", "type" => "integer", "label" => "Age"];
            $extraField[] = ["key" => "", "field" => "total_students3", "type" => "integer", "label" => "Number of Students"];
            $extraField[] = ["key" => "", "field" => "repeater_Student1", "type" => "integer", "label" => "Repeater Stuents"];

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
        
     

        
        if ($uisType == 'UIS-A6')
        {
            
            $SummaryGradeStatusGenders = TableRegistry::get('summary_grade_status_genders');
            $res = $query->select([
                'academic_period_id3' => $this->aliasField('academic_period_id'),
                'academic_period_name3' => $this->aliasField('academic_period_name'),
                'education_system_id' => $this->aliasField('education_system_id'),
                'education_system_name3' => $this->aliasField('education_system_name'),
                'education_level_isced_id' => $this->aliasField('education_level_isced_id'),
                'education_level_isced_level3' => $this->aliasField('education_level_isced_level'),
                'education_level_isced_name3' => $this->aliasField('education_level_isced_name'),
                'education_level_id' => $this->aliasField('education_level_id'),
                'education_level_name3' => $this->aliasField('education_level_name'),
                'education_cycle_id' => $this->aliasField('education_cycle_id'),
                'education_cycle_name3' => $this->aliasField('education_cycle_name'),
                'education_programme_id' => $this->aliasField('education_programme_id'),
                'education_programme_code3' => $this->aliasField('education_programme_code'),
                'education_programme_name3' => $this->aliasField('education_programme_name'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'education_grade_code3' => $this->aliasField('education_grade_code'),
                'education_grade_name3' => $this->aliasField('education_grade_name'),
                'student_gender_id' => $this->aliasField('student_gender_id'),
                'student_gender_name3' => $this->aliasField('student_gender_name'),
                'student_age3' => $this->aliasField('student_age'),
                'total_students3' => $this->aliasField('total_students'),
                //'repeater_Student' => 'summary_grade_gender_ages.total_students', //should change
            ])
            
            ->InnerJoin([$SummaryGradeStatusGenders->alias() => $SummaryGradeStatusGenders->table() ], [
                $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('academic_period_id'),
                $this->aliasField('education_system_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_system_id'),
                $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_isced_id'),
                $this->aliasField('education_level_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_id'),
                $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_cycle_id'),
                $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_programme_id'),
                $this->aliasField('education_grade_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_grade_id'),
                $this->aliasField('student_gender_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('student_gender_id')
                ])
            ->where([$this->aliasField('academic_period_id') => $academic_period_id])
            ;

            $query->formatResults(function ($results) {
                return $results->map(function ($row)  { 
                    $SummaryGradeStatusGenders1 = TableRegistry::get('summary_grade_status_genders');
                    $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
               
                    $sumGradeData = $SummaryGradeStatusGenders1->find('all',['conditions'=>[
                        'student_status_id' =>8,
                        'academic_period_id' => $row->academic_period_id2,
                        'education_system_id' => $row->education_system_id,
                        'education_level_isced_id' => $row->education_level_isced_id,
                        'education_level_id' => $row->education_level_id,
                        'education_cycle_id' => $row->education_cycle_id,
                        'education_programme_id' => $row->education_programme_id,
                        'education_grade_id' => $row->education_grade_id,
                        'student_gender_id' => $row->student_gender_id,
                        ]
                    ])->count();
                    $row['repeater_Student1'] = $sumGradeData;
                    return $row;
                });
            });
            
       
        }
        
       
    }
}