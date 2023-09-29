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

class Uis102Table extends AppTable
{
    private $uisTabsData = [0 => "UIS-A10(2)"];
    public function initialize(array $config)       
    {
        $this->table('summary_programme_sector_specialization_genders');
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
        
        

        if ($uisType == 'UIS-A10(2)')
        {
            $SummaryProgrammeSectorSpecializationGenders = TableRegistry::get('summary_programme_sector_specialization_genders');
            $res = $query->select([
                'academic_period_name5' => 'summary_programme_sector_specialization_genders.academic_period_name',
                'education_system_name5' => 'summary_programme_sector_specialization_genders.education_system_name',
                'education_level_isced_level5' => 'summary_programme_sector_specialization_genders.education_level_isced_level',
                'education_level_isced_name5' => 'summary_programme_sector_specialization_genders.education_level_isced_name',
                'education_level_name5' => 'summary_programme_sector_specialization_genders.education_level_name',
                'education_cycle_name5' => 'summary_programme_sector_specialization_genders.education_cycle_name',
                'education_programme_code5' => 'summary_programme_sector_specialization_genders.education_programme_code',
                'education_programme_name5' => 'summary_programme_sector_specialization_genders.education_programme_name',
                
                
                'gender_name5' => 'summary_programme_sector_specialization_genders.staff_gender_name',
                'training_category' => 'summary_programme_sector_specialization_genders.staff_training_category_name',
                'total_staffs1' => 'summary_programme_sector_specialization_genders.total_staff_teaching',
                'total_new_staff1' => 'summary_programme_sector_specialization_genders.total_staff_teaching_newly_recruited',
            ])
            ->LeftJoin([$SummaryProgrammeSectorSpecializationGenders->alias() => $SummaryProgrammeSectorSpecializationGenders->table() ], [
                $this->aliasField('academic_period_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('academic_period_id'),
                $this->aliasField('education_system_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_system_id'),
                $this->aliasField('education_level_isced_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_level_isced_id'),
                $this->aliasField('education_level_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_level_id'),
                $this->aliasField('education_cycle_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_cycle_id'),
                $this->aliasField('education_programme_id'). ' = ' . $SummaryProgrammeSectorSpecializationGenders->aliasField('education_programme_id')
                ])
            ->where(['summary_programme_sector_specialization_genders.academic_period_id' => $academic_period_id]);
                
        }
       
    }
}