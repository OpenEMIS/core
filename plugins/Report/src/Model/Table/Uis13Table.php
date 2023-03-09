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

class Uis13Table extends AppTable
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