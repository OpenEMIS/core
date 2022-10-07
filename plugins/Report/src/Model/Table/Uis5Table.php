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

class Uis5Table extends AppTable
{
    private $uisTabsData = [0 => "UIS-A13"];
    public function initialize(array $config)       
    {
        $this->table('summary_isced_sectors');
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
        if ($UISType == 'UIS-A2')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name", "type" => "integer", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "institution_sector_name", "type" => "integer", "label" => "Sector"];
            $extraField[] = ["key" => "", "field" => "education_system_name", "type" => "integer", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name", "type" => "integer", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "gender_name", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "total_students", "type" => "integer", "label" => "Number of Students"];
        }
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
        if ($UISType == 'UIS-A5')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name2", "type" => "string", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "education_system_name2", "type" => "string", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level2", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name2", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name2", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name2", "type" => "string", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code2", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name2", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "education_grade_code2", "type" => "integer", "label" => "Education Grade Code"];
            $extraField[] = ["key" => "", "field" => "education_grade_name2", "type" => "integer", "label" => "Education Grade Name"];
            $extraField[] = ["key" => "", "field" => "student_gender_name2", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "student_age2", "type" => "integer", "label" => "Age"];
            $extraField[] = ["key" => "", "field" => "total_students2", "type" => "integer", "label" => "Number of Students"];
            $extraField[] = ["key" => "", "field" => "repeater_Student", "type" => "integer", "label" => "Repeater Stuents"];

        }
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
        if ($UISType == 'UIS-A9')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name", "type" => "integer", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "institution_sector_name", "type" => "integer", "label" => "Sector"];
            $extraField[] = ["key" => "", "field" => "education_system_name", "type" => "integer", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name", "type" => "integer", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "gender_name", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "total_staff_teaching", "type" => "integer", "label" => "Number of Staff"];
            $extraField[] = ["key" => "", "field" => "total_staff_teaching_newly_recruited", "type" => "integer", "label" => "Number of Newly Recruited Staff"];

            
        }
        if ($UISType == 'UIS-A10(1)')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name4", "type" => "integer", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "institution_sector_name4", "type" => "integer", "label" => "Sector"];
            $extraField[] = ["key" => "", "field" => "education_system_name4", "type" => "integer", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level4", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name4", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name4", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name4", "type" => "integer", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code4", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name4", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "gender_name4", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "student_age4", "type" => "integer", "label" => "Qualification"];
            $extraField[] = ["key" => "", "field" => "total_staffs", "type" => "integer", "label" => "Number of Staff"];
            $extraField[] = ["key" => "", "field" => "total_new_staff", "type" => "integer", "label" => "Number of Newly Recruited Staff"];
            
        }
        if ($UISType == 'UIS-A10(2)')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name5", "type" => "integer", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "institution_sector_name5", "type" => "integer", "label" => "Sector"];
            $extraField[] = ["key" => "", "field" => "education_system_name5", "type" => "integer", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level5", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name5", "type" => "integer", "label" => "ISCED Name"];
            $extraField[] = ["key" => "", "field" => "education_level_name5", "type" => "integer", "label" => "Education Level"];
            $extraField[] = ["key" => "", "field" => "education_cycle_name5", "type" => "integer", "label" => "Education Cycle"];
            $extraField[] = ["key" => "", "field" => "education_programme_code5", "type" => "integer", "label" => "Education Programme Code"];
            $extraField[] = ["key" => "", "field" => "education_programme_name5", "type" => "integer", "label" => "Education Programme Name"];
            $extraField[] = ["key" => "", "field" => "gender_name5", "type" => "integer", "label" => "Gender"];
            $extraField[] = ["key" => "", "field" => "training_category", "type" => "integer", "label" => "Training Category"];
            $extraField[] = ["key" => "", "field" => "total_staffs1", "type" => "integer", "label" => "Number of Staff"];
            $extraField[] = ["key" => "", "field" => "total_new_staff1", "type" => "integer", "label" => "Number of Newly Recruited Staff"];

            
        }

        if ($UISType == 'UIS-A13')
        {
            $extraField[] = ["key" => "", "field" => "academic_period_name6", "type" => "integer", "label" => "Academic Period"];
            $extraField[] = ["key" => "", "field" => "institution_sector_name6", "type" => "integer", "label" => "Sector"];
            $extraField[] = ["key" => "", "field" => "education_system_name6", "type" => "integer", "label" => "Education System"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_level6", "type" => "integer", "label" => "ISCED Level"];
            $extraField[] = ["key" => "", "field" => "education_level_isced_name6", "type" => "integer", "label" => "ISCED Name"];

            $extraField[] = ["key" => "", "field" => "total_instiutions", "type" => "integer", "label" => "Number of Institutions"];
            $extraField[] = ["key" => "", "field" => "total_electricity_institutions", "type" => "integer", "label" => "Number of Institutions with Electricity"];
            $extraField[] = ["key" => "", "field" => "total_computer_institutions", "type" => "integer", "label" => "Number of Institutions with Computers for Teaching"];
            $extraField[] = ["key" => "", "field" => "total_internet_institutions", "type" => "integer", "label" => "Number of Institutions with Internet"];
            $extraField[] = ["key" => "", "field" => "total_improved_toilet_institutions", "type" => "integer", "label" => "Number of Institutions with Improved Toilets"];
            $extraField[] = ["key" => "", "field" => "total_in_use_single_sex_toilet_institutions", "type" => "integer", "label" => "Number of Institutions with Single-sex Improved Toilets"];

            $extraField[] = ["key" => "", "field" => "total_improved_in_use_single_sex_toilet_institutions", "type" => "integer", "label" => "Number of Institutions with Usable Single-sex Improved Toilets"];
            $extraField[] = ["key" => "", "field" => "total_drinking_water_institutions", "type" => "integer", "label" => "Number of Institutions with Drinking Water Source"];
            $extraField[] = ["key" => "", "field" => "total_functional_drinking_water_institutions", "type" => "integer", "label" => "Number of Institutions with Usable Drinking Water Source"];
            $extraField[] = ["key" => "", "field" => "total_handwashing_facility_institutions", "type" => "integer", "label" => "Number of Institutions with Handwashing Facilities"];
            $extraField[] = ["key" => "", "field" => "total_accessible_room_institutions", "type" => "integer", "label" => "Number of Institutions with Adapted Infrastructure for Students with Disabilities"];

            
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
        
        // if ($uisType == 'UIS-A2')
        // {
        //     $summaryProgrammeSectorGendersTypes = TableRegistry::get('summary_programme_sector_genders');
        //     $res = $query->select([
        //         'academic_period_name' => 'academic_period_name',
        //         'institution_sector_name' => 'institution_sector_name',
        //         'education_system_name' => 'education_system_name',
        //         'education_level_isced_level' => 'education_level_isced_level',
        //         'education_level_isced_name' => 'education_level_isced_name',
        //         'education_level_name' => 'education_level_name',
        //         'education_cycle_name' => 'education_cycle_name',
        //         'education_programme_code' => 'education_programme_code',
        //         'education_programme_name' => 'education_programme_name',
        //         'gender_name' => 'gender_name',
        //         'total_students' => 'total_students',
        //     ])
        //     ->where(['academic_period_id' => $academic_period_id]);
        // }   

        // if ($uisType == 'UIS-A3')
        // {   
        //     $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
        //     $res = $query->select([
        //         'academic_period_name1' => 'summary_grade_gender_ages.academic_period_name',
        //         'education_system_name1' => 'summary_grade_gender_ages.education_system_name',
        //         'education_level_isced_level1' => 'summary_grade_gender_ages.education_level_isced_level',
        //         'education_level_isced_name1' => 'summary_grade_gender_ages.education_level_isced_name',
        //         'education_level_name1' => 'summary_grade_gender_ages.education_level_name',
        //         'education_cycle_name1' => 'summary_grade_gender_ages.education_cycle_name',
        //         'education_programme_code1' => 'summary_grade_gender_ages.education_programme_code',
        //         'education_programme_name1' => 'summary_grade_gender_ages.education_programme_name',
        //         'education_grade_code1' => 'summary_grade_gender_ages.education_grade_code',
        //         'education_grade_name1' => 'summary_grade_gender_ages.education_grade_name',
        //         'student_gender_name1' => 'summary_grade_gender_ages.student_gender_name',
        //         'student_age1' => 'summary_grade_gender_ages.student_age',
        //         'total_students1' => 'summary_grade_gender_ages.total_students',
        //     ])
        //     ->LeftJoin([$SummaryGradeGenderAges->alias() => $SummaryGradeGenderAges->table() ], [
        //          $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeGenderAges->aliasField('academic_period_id'),
        //          $this->aliasField('education_system_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_system_id'),
        //          $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_isced_id'),
        //          $this->aliasField('education_level_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_id'),
        //          $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_cycle_id'),
        //          $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_programme_id')
        //          ])
        //     ->where(['summary_grade_gender_ages.academic_period_id' => $academic_period_id]);
        // }

        // if ($uisType == 'UIS-A5')
        // {
        //     $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
        //     $SummaryGradeStatusGenders = TableRegistry::get('summary_grade_status_genders');
        //     $res = $query->select([
        //         'academic_period_id2' => 'summary_grade_gender_ages.academic_period_id',
        //         'academic_period_name2' => 'summary_grade_gender_ages.academic_period_name',
        //         'education_system_id' => 'summary_grade_gender_ages.education_system_id',
        //         'education_system_name2' => 'summary_grade_gender_ages.education_system_name',
        //         'education_level_isced_id' => 'summary_grade_gender_ages.education_level_isced_id',
        //         'education_level_isced_level2' => 'summary_grade_gender_ages.education_level_isced_level',
        //         'education_level_isced_name2' => 'summary_grade_gender_ages.education_level_isced_name',
        //         'education_level_id' => 'summary_grade_gender_ages.education_level_id',
        //         'education_level_name2' => 'summary_grade_gender_ages.education_level_name',
        //         'education_cycle_id' => 'summary_grade_gender_ages.education_cycle_id',
        //         'education_cycle_name2' => 'summary_grade_gender_ages.education_cycle_name',
        //         'education_programme_id' => 'summary_grade_gender_ages.education_programme_id',
        //         'education_programme_code2' => 'summary_grade_gender_ages.education_programme_code',
        //         'education_programme_name2' => 'summary_grade_gender_ages.education_programme_name',
        //         'education_grade_id' => 'summary_grade_gender_ages.education_grade_id',
        //         'education_grade_code2' => 'summary_grade_gender_ages.education_grade_code',
        //         'education_grade_name2' => 'summary_grade_gender_ages.education_grade_name',
        //         'student_gender_id' => 'summary_grade_gender_ages.student_gender_id',
        //         'student_gender_name2' => 'summary_grade_gender_ages.student_gender_name',
        //         'student_age2' => 'summary_grade_gender_ages.student_age',
        //         'total_students2' => 'summary_grade_gender_ages.total_students',
        //         //'repeater_Student' => 'summary_grade_gender_ages.total_students', //should change
        //     ])
        //     ->LeftJoin([$SummaryGradeGenderAges->alias() => $SummaryGradeGenderAges->table() ], [
        //          $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeGenderAges->aliasField('academic_period_id'),
        //          $this->aliasField('education_system_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_system_id'),
        //          $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_isced_id'),
        //          $this->aliasField('education_level_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_id'),
        //          $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_cycle_id'),
        //          $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_programme_id')
        //          ])
        //     ->LeftJoin([$SummaryGradeStatusGenders->alias() => $SummaryGradeStatusGenders->table() ], [
        //         $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('academic_period_id'),
        //         $this->aliasField('education_system_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_system_id'),
        //         $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_isced_id'),
        //         $this->aliasField('education_level_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_id'),
        //         $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_cycle_id'),
        //         $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_programme_id')
        //         ])
        //     ->where(['summary_grade_gender_ages.academic_period_id' => $academic_period_id]);

        //     $query->formatResults(function ($results) {
        //         return $results->map(function ($row)  { 
        //             $SummaryGradeStatusGenders1 = TableRegistry::get('summary_grade_status_genders');
        //             $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
               
        //             $sumGradeData = $SummaryGradeStatusGenders1->find('all',['conditions'=>[
        //                 'student_status_id' =>8,
        //                 'academic_period_id' => $row->academic_period_id2,
        //                 'education_system_id' => $row->education_system_id,
        //                 'education_level_isced_id' => $row->education_level_isced_id,
        //                 'education_level_id' => $row->education_level_id,
        //                 'education_cycle_id' => $row->education_cycle_id,
        //                 'education_programme_id' => $row->education_programme_id,
        //                 'education_grade_id' => $row->education_grade_id,
        //                 'student_gender_id' => $row->student_gender_id,
        //                 ]
        //             ])->count();
        //             $row['repeater_Student'] = $sumGradeData;
        //             return $row;
        //         });
        //     });

        // }

        // if ($uisType == 'UIS-A6')
        // {
        //     $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
        //     $SummaryGradeStatusGenders = TableRegistry::get('summary_grade_status_genders');
        //     $res = $query->select([
        //         'academic_period_id2' => 'summary_grade_gender_ages.academic_period_id',
        //         'academic_period_name3' => 'summary_grade_gender_ages.academic_period_name',
        //         'education_system_id' => 'summary_grade_gender_ages.education_system_id',
        //         'education_system_name3' => 'summary_grade_gender_ages.education_system_name',
        //         'education_level_isced_id' => 'summary_grade_gender_ages.education_level_isced_id',
        //         'education_level_isced_level3' => 'summary_grade_gender_ages.education_level_isced_level',
        //         'education_level_isced_name3' => 'summary_grade_gender_ages.education_level_isced_name',
        //         'education_level_id' => 'summary_grade_gender_ages.education_level_id',
        //         'education_level_name3' => 'summary_grade_gender_ages.education_level_name',
        //         'education_cycle_id' => 'summary_grade_gender_ages.education_cycle_id',
        //         'education_cycle_name3' => 'summary_grade_gender_ages.education_cycle_name',
        //         'education_programme_id' => 'summary_grade_gender_ages.education_programme_id',
        //         'education_programme_code3' => 'summary_grade_gender_ages.education_programme_code',
        //         'education_programme_name3' => 'summary_grade_gender_ages.education_programme_name',
        //         'education_grade_id' => 'summary_grade_gender_ages.education_grade_id',
        //         'education_grade_code3' => 'summary_grade_gender_ages.education_grade_code',
        //         'education_grade_name3' => 'summary_grade_gender_ages.education_grade_name',
        //         'student_gender_id' => 'summary_grade_gender_ages.student_gender_id',
        //         'student_gender_name3' => 'summary_grade_gender_ages.student_gender_name',
        //         'student_age3' => 'summary_grade_gender_ages.student_age',
        //         'total_students3' => 'summary_grade_gender_ages.total_students',
        //         //'repeater_Student1' => 'summary_grade_gender_ages.total_students', //should change
        //     ])
        //     ->LeftJoin([$SummaryGradeGenderAges->alias() => $SummaryGradeGenderAges->table() ], [
        //          $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeGenderAges->aliasField('academic_period_id'),
        //          $this->aliasField('education_system_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_system_id'),
        //          $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_isced_id'),
        //          $this->aliasField('education_level_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_level_id'),
        //          $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_cycle_id'),
        //          $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeGenderAges->aliasField('education_programme_id')
        //          ])
        //     ->LeftJoin([$SummaryGradeStatusGenders->alias() => $SummaryGradeStatusGenders->table() ], [
        //         $this->aliasField('academic_period_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('academic_period_id'),
        //         $this->aliasField('education_system_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_system_id'),
        //         $this->aliasField('education_level_isced_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_isced_id'),
        //         $this->aliasField('education_level_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_level_id'),
        //         $this->aliasField('education_cycle_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_cycle_id'),
        //         $this->aliasField('education_programme_id'). ' = ' . $SummaryGradeStatusGenders->aliasField('education_programme_id')
        //         ])
        //     ->where(['summary_grade_gender_ages.academic_period_id' => $academic_period_id]);   
            
        //     $query->formatResults(function ($results) {
        //         return $results->map(function ($row)  { 
        //             $SummaryGradeStatusGenders1 = TableRegistry::get('summary_grade_status_genders');
        //             $SummaryGradeGenderAges = TableRegistry::get('summary_grade_gender_ages');
               
        //             $sumGradeData = $SummaryGradeStatusGenders1->find('all',['conditions'=>[
        //                 'student_status_id' =>8,
        //                 'academic_period_id' => $row->academic_period_id2,
        //                 'education_system_id' => $row->education_system_id,
        //                 'education_level_isced_id' => $row->education_level_isced_id,
        //                 'education_level_id' => $row->education_level_id,
        //                 'education_cycle_id' => $row->education_cycle_id,
        //                 'education_programme_id' => $row->education_programme_id,
        //                 'education_grade_id' => $row->education_grade_id,
        //                 'student_gender_id' => $row->student_gender_id,
        //                 ]
        //             ])->count();
        //             $row['repeater_Student1'] = $sumGradeData;
        //             return $row;
        //         });
        //     });
        // }

        // if ($uisType == 'UIS-A9')
        // {
        //     $res = $query->select([
        //         'academic_period_name' => 'academic_period_name',
        //         'institution_sector_name' => 'institution_sector_name',
        //         'education_system_name' => 'education_system_name',
        //         'education_level_isced_level' => 'education_level_isced_level',
        //         'education_level_isced_name' => 'education_level_isced_name',
        //         'education_level_name' => 'education_level_name',
        //         'education_cycle_name' => 'education_cycle_name',
        //         'education_programme_code' => 'education_programme_code',
        //         'education_programme_name' => 'education_programme_name',
        //         'gender_name' => 'gender_name',
        //         'total_students' => 'total_students',
        //         'total_staff_teaching_newly_recruited' => 'total_staff_teaching_newly_recruited'
        //     ])
        //     ->where(['academic_period_id' => $academic_period_id]);
            
        // }

        // if ($uisType == 'UIS-A10(1)')
        // {
        //     $SummaryProgrammeSectorQualificationGenders = TableRegistry::get('summary_programme_sector_qualification_genders');
        //     $res = $query->select([
        //         'academic_period_name4' => 'summary_programme_sector_qualification_genders.academic_period_name',
        //         'education_system_name4' => 'summary_programme_sector_qualification_genders.education_system_name',
        //         'education_level_isced_level4' => 'summary_programme_sector_qualification_genders.education_level_isced_level',
        //         'education_level_isced_name4' => 'summary_programme_sector_qualification_genders.education_level_isced_name',
        //         'education_level_name4' => 'summary_programme_sector_qualification_genders.education_level_name',
        //         'education_cycle_name4' => 'summary_programme_sector_qualification_genders.education_cycle_name',
        //         'education_programme_code4' => 'summary_programme_sector_qualification_genders.education_programme_code',
        //         'education_programme_name4' => 'summary_programme_sector_qualification_genders.education_programme_name',
               
        //         'gender_name4' => 'summary_programme_sector_qualification_genders.staff_gender_name',
        //         'student_age4' => 'summary_programme_sector_qualification_genders.staff_qualification_title_name',
        //         'total_staffs' => 'summary_programme_sector_qualification_genders.total_staff_teaching',
        //         'total_new_staff' => 'summary_programme_sector_qualification_genders.total_staff_teaching_newly_recruited',
        //     ])
        //     ->LeftJoin([$SummaryProgrammeSectorQualificationGenders->alias() => $SummaryProgrammeSectorQualificationGenders->table() ], [
        //         $this->aliasField('academic_period_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('academic_period_id'),
        //         $this->aliasField('education_system_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('education_system_id'),
        //         $this->aliasField('education_level_isced_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('education_level_isced_id'),
        //         $this->aliasField('education_level_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('education_level_id'),
        //         $this->aliasField('education_cycle_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('education_cycle_id'),
        //         $this->aliasField('education_programme_id'). ' = ' . $SummaryProgrammeSectorQualificationGenders->aliasField('education_programme_id')
        //         ])
        //     ->where(['summary_programme_sector_qualification_genders.academic_period_id' => $academic_period_id]);
                
        // }

        // if ($uisType == 'UIS-A10(2)')
        // {
        //     $SummaryProgrammeSectorSpecializationGenders = TableRegistry::get('summary_programme_sector_specialization_genders');
        //     $res = $query->select([
        //         'academic_period_name5' => 'summary_programme_sector_specialization_genders.academic_period_name',
        //         'education_system_name5' => 'summary_programme_sector_specialization_genders.education_system_name',
        //         'education_level_isced_level5' => 'summary_programme_sector_specialization_genders.education_level_isced_level',
        //         'education_level_isced_name5' => 'summary_programme_sector_specialization_genders.education_level_isced_name',
        //         'education_level_name5' => 'summary_programme_sector_specialization_genders.education_level_name',
        //         'education_cycle_name5' => 'summary_programme_sector_specialization_genders.education_cycle_name',
        //         'education_programme_code5' => 'summary_programme_sector_specialization_genders.education_programme_code',
        //         'education_programme_name5' => 'summary_programme_sector_specialization_genders.education_programme_name',
                
                
        //         'gender_name5' => 'summary_programme_sector_specialization_genders.staff_gender_name',
        //         'training_category' => 'summary_programme_sector_specialization_genders.staff_training_category_name',
        //         'total_staffs1' => 'summary_programme_sector_specialization_genders.total_staff_teaching',
        //         'total_new_staff1' => 'summary_programme_sector_specialization_genders.total_staff_teaching_newly_recruited',
        //     ])
        //     ->LeftJoin([$SummaryProgrammeSectorSpecializationGenders->alias() => $SummaryProgrammeSectorSpecializationGenders->table() ], [
        //         $this->aliasField('academic_period_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('academic_period_id'),
        //         $this->aliasField('education_system_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_system_id'),
        //         $this->aliasField('education_level_isced_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_level_isced_id'),
        //         $this->aliasField('education_level_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_level_id'),
        //         $this->aliasField('education_cycle_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_cycle_id'),
        //         $this->aliasField('education_programme_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_programme_id')
        //         ])
        //     ->where(['summary_programme_sector_specialization_genders.academic_period_id' => $academic_period_id]);
                
        // }

        if ($uisType == 'UIS-A13')
        {
            
            $res = $query->select([
                'academic_period_name6' => 'academic_period_name',
                'institution_sector_name6' => 'institution_sector_name',
                'education_system_name6' => 'education_system_name',
                'education_level_isced_level6' => 'education_level_isced_name',
                
                'education_level_isced_name6' => 'education_level_isced_name',
                'total_instiutions' => 'total_instiutions',
                'total_electricity_institutions' => 'total_electricity_institutions',
                'total_computer_institutions' => 'total_computer_institutions',
                'total_internet_institutions' => 'total_internet_institutions',
                'total_toilet_institutions' => 'total_toilet_institutions',
                'total_improved_toilet_institutions' => 'total_improved_toilet_institutions',
                'total_in_use_toilet_institutions' => 'total_in_use_toilet_institutions',
                'total_improved_single_sex_toilet_institutions' => 'total_improved_single_sex_toilet_institutions',

                'total_in_use_single_sex_toilet_institutions' => 'total_in_use_single_sex_toilet_institutions',
                'total_improved_in_use_single_sex_toilet_institutions' => 'total_improved_in_use_single_sex_toilet_institutions',
                'total_drinking_water_institutions' => 'total_drinking_water_institutions',
                'total_functional_drinking_water_institutions' => 'total_functional_drinking_water_institutions',
                'total_handwashing_facility_institutions' => 'total_handwashing_facility_institutions',
                'total_accessible_room_institutions' => 'total_accessible_room_institutions',
                
            ])
            ->where(['academic_period_id' => $academic_period_id]);
             
        }
       
    }
}