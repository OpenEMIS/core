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

class Uis10Table extends AppTable
{
    private $uisTabsData = [1 => "UIS-A10(1)"];
    public function initialize(array $config)       
    {
        $this->table('summary_programme_sector_qualification_genders');
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
        
        if ($uisType == 'UIS-A10(1)')
        {
            $SummaryProgrammeSectorQualificationGenders = TableRegistry::get('summary_programme_sector_qualification_genders');
            $res = $query->select([
                'academic_period_name4' => 'academic_period_name',
                'education_system_name4' => 'education_system_name',
                'education_level_isced_level4' => 'education_level_isced_level',
                'education_level_isced_name4' => 'education_level_isced_name',
                'education_level_name4' => 'education_level_name',
                'education_cycle_name4' => 'education_cycle_name',
                'education_programme_code4' => 'education_programme_code',
                'education_programme_name4' => 'education_programme_name',
               
                'gender_name4' => 'staff_gender_name',
                'student_age4' => 'staff_qualification_title_name',
                'total_staffs' => 'total_staff_teaching',
                'total_new_staff' => 'total_staff_teaching_newly_recruited',
            ])
            ->where(['academic_period_id' => $academic_period_id]);
                
        }

        
       
    }
}