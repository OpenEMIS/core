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

class Uis2Table extends AppTable
{
    private $uisTabsData = [0 => "UIS-A2"];
    public function initialize(array $config)       
    {
        $this->table('summary_programme_sector_genders');
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
        
        if ($uisType == 'UIS-A2')
        {
            $summaryProgrammeSectorGendersTypes = TableRegistry::get('summary_programme_sector_genders');
            $res = $query->select([
                'academic_period_name' => 'academic_period_name',
                'institution_sector_name' => 'institution_sector_name',
                'education_system_name' => 'education_system_name',
                'education_level_isced_level' => 'education_level_isced_level',
                'education_level_isced_name' => 'education_level_isced_name',
                'education_level_name' => 'education_level_name',
                'education_cycle_name' => 'education_cycle_name',
                'education_programme_code' => 'education_programme_code',
                'education_programme_name' => 'education_programme_name',
                'gender_name' => 'gender_name',
                'total_students' => 'total_students',
            ])
            ->where(['academic_period_id' => $academic_period_id]);
            //echo "<pre>"; print_r($query->toArray());die;
        }   

       
       
    }
}