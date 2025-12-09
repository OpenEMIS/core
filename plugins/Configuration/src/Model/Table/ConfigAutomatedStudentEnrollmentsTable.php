<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;
use Cake\Http\ServerRequest;
use PDOException;

class ConfigAutomatedStudentEnrollmentsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    { 
        $this->setTable('area_programme_institutions');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        // Associations
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'foreignKey' => 'education_programme_id']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas','foreignKey' => 'id']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives','foreignKey' => 'id']);
        // Removed the AreaAdministratives association as it's now in the dependent table
        //$this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives','foreignKey' => 'area_administrative_id']); 
        //$this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
        $this->hasMany('ConfigAutomatedStudentEnrollmentsAreas', ['className' => 'Configuration.ConfigAutomatedStudentEnrollmentsAreas', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'area_programme_institution_id', 'joinType' => 'LEFT']);
        
        //$ConfigAutomatedStudentEnrollmentsAreas = TableRegistry::get('Configuration.ConfigAutomatedStudentEnrollmentsAreas');
   
        $this->toggle('edit', 'delete', false);
    }


    public function beforeAction(Event $event, ArrayObject $extra)
    {   
        $this->field('area_name', ['visible' => true]);    
        $this->field('modified_by', ['visible' => false]);
        $this->field('created_by', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period_id',
            'institution_id',
            'education_programme_id',
            'area_name'            
        ]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extram)
    {
        $condition = [];
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('institution_id', ['visible' => true]);
        $this->field('education_programme_id', ['visible' => true]);
        $this->field('area_administrative_id', ['visible' => true]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        // Join the related tables
        $query->join([
            'ConfigAutomatedStudentEnrollmentsArea' => [
                'table' => 'area_programme_institution_areas',  // adjust table name if necessary
                'type' => 'LEFT',
                'conditions' => 'ConfigAutomatedStudentEnrollments.id = ConfigAutomatedStudentEnrollmentsArea.area_programme_institution_id'
            ],
            'Area' => [
                'table' => 'area_administratives',  // adjust table name if necessary
                'type' => 'LEFT',
                'conditions' => 'Area.id = ConfigAutomatedStudentEnrollmentsArea.area_administrative_id'
            ]
        ]);

        // Select fields and apply GROUP_CONCAT logic
        $query->select([
            'ConfigAutomatedStudentEnrollments.id',
            'ConfigAutomatedStudentEnrollments.academic_period_id',
            'ConfigAutomatedStudentEnrollments.institution_id',
            'ConfigAutomatedStudentEnrollments.education_programme_id',
            'ConfigAutomatedStudentEnrollments.modified_by',
            'ConfigAutomatedStudentEnrollments.modified',
            'ConfigAutomatedStudentEnrollments.created_by',
            'ConfigAutomatedStudentEnrollments.created',
            
            // Apply GROUP_CONCAT for area_administrative_id
            'field_value' => '(GROUP_CONCAT(CASE 
                WHEN ConfigAutomatedStudentEnrollmentsArea.area_administrative_id IS NOT NULL THEN ConfigAutomatedStudentEnrollmentsArea.area_administrative_id
                ELSE NULL 
                END SEPARATOR \',\'))',

            // Apply GROUP_CONCAT for Area names
            'area_names' => '(GROUP_CONCAT(CASE 
                WHEN Area.name IS NOT NULL THEN Area.name
                ELSE NULL 
                END SEPARATOR \',\'))'
        ]);

        // Group by to avoid duplicates
        $query->group('ConfigAutomatedStudentEnrollments.id');

        // Print SQL query for debugging
        // echo $query->sql();
        // exit;

        // Return the modified query
        return $query;
    }

    public function onGetAreaName(Event $event, Entity $entity)
    { //echo "<pre>";print_r($entity);exit;
        if ($this->action == 'index' || $this->action == 'view') {
            $areaName = $entity->area_names;
            //echo "<pre>";print_r($entity);exit;
            return $areaName;
        }
        return $entity->Area_name;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {   //echo "<pre>";print_r($query);exit;
        
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        //echo "here";exit;
        //$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        if ($action = 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList();

            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);
        $institutionList = $institutionQuery->toArray();
        //echo "<pre>";print_r($institutionList);exit;
        //if ($action = 'add') {
            //$periodOptions = $this->AcademicPeriods->getYearList();
            

            $attr['options'] = $institutionList;
            $attr['onChangeReload'] = true;
        //}
        return $attr;
    }


    public
    function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : '';
        $institutionId = !is_null($request->getData($this->aliasField('institution_id'))) ? $request->getData($this->aliasField('institution_id')) : '';

        //echo "<pre>";print_r($academicPeriodId);exit;
        if (!empty($academicPeriodId) && !empty($institutionId)) {
            // Assuming you have a method to get the education_programme_id based on academic_period_id and institution_id
            //$this->education_programme_id = $this->getEducationProgrammeId($this->academic_period_id, $this->institution_id);
        
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

            if ($action == 'add' || $action == 'edit') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                    ->toArray();

                $attr['options'] = $programmeOptions;
                //$attr['onChangeReload'] = 'changeEducationProgrammeId';

            } 
        }
        return $attr;
    }
    }

    public function onUpdateFieldAreaAdministrativeIdOrg(Event $event, array $attr, $action, ServerRequest $request)
    {
       // echo "kkk";exit;
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $validateAreaAdministrativeLevel = $ConfigItems->value('institution_validate_area_administrative_level_id');
        
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $AreaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
        $areaOptions = $AreaAdministratives
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where([$AreaAdministratives->aliasField('area_administrative_level_id') => $validateAreaAdministrativeLevel])
            ->order([$AreaAdministratives->aliasField('name')])
            ->toArray();
        $attr['options'] = $areaOptions;    
     
        return $attr;
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        // Check config
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $validateAreaAdministrativeLevel = $ConfigItems->value('institution_validate_area_administrative_level_id');
        
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $AreaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
        $areaOptions = $AreaAdministratives
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where([$AreaAdministratives->aliasField('area_administrative_level_id') => $validateAreaAdministrativeLevel])
            ->order([$AreaAdministratives->aliasField('name')])
            ->toArray();
        $this->field('area_name', ['visible' => false]);    
        $this->field('area_administrative_id', [
            'type' => 'chosenSelect',
            'options' =>  $areaOptions,
            'after' => 'education_programme_id',
            'attr' => ['required' => true]  
        ]);
        
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        // $this->field('area_education_id', [
        //     'type' => 'select',
        //     'entity' => $entity
        // ]);
        $this->field('institution_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

      


        $this->setFieldOrder([
            'academic_period_id',
            'institution_id',
            'education_programme_id'            
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $districtNames = [];
        $areaAdIds = [];

        foreach ($entity->config_automated_student_enrollments_areas as $area) {
            $areaAdIds[] = $area->area_administrative_id;
        }
        
        if(!empty($areaAdIds)) {
            $AreaAdministratives = TableRegistry::get('Area.AreaAdministratives');
            $districtNames = $AreaAdministratives
                ->find()
                ->where(['id IN' => $areaAdIds])
                ->extract('name')
                ->toArray();
        }

        $districtNamesString = implode(", ", $districtNames);
        $entity->area_names = $districtNamesString;
        
    }



    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $request = $this->request;
        //echo "<pre>";print_r($request);exit;
        $areaAdministrativeId = $request->getData('ConfigAutomatedStudentEnrollments')['area_administrative_id']['_ids'];
        
        // Get the Table for saving dependent data
        $ConfigAutomatedStudentEnrollmentsAreas = TableRegistry::get('Configuration.ConfigAutomatedStudentEnrollmentsAreas');
        
        // Ensure area_programme_institution_id is available (it's the saved entity's ID)
        $areaProgrammeInstitutionId = $entity->get('id');
        
        // If the entity ID is not set, log and exit the function (data integrity error)
        if (!$areaProgrammeInstitutionId) {
            $this->log('Error: Invalid area_programme_institution_id (ID is null). Cannot save dependent data.', 'error');
            return false;
        }

        // Fetch existing dependent records for this parent entity
        $existingAreaData = $ConfigAutomatedStudentEnrollmentsAreas
            ->find()
            ->where(['area_programme_institution_id' => $areaProgrammeInstitutionId])
            ->toArray();
        
        // Convert existing area_administrative_ids to a simple array for easier comparison
        $existingAreaIds = array_column($existingAreaData, 'area_administrative_id');
        
        // Find new areas to be added (those that are in the form data but not in existing records)
        $areasToAdd = array_diff($areaAdministrativeId, $existingAreaIds);
        
        // Find areas that are no longer selected (those that exist in the database but not in form data)
        $areasToRemove = array_diff($existingAreaIds, $areaAdministrativeId);

        // Remove unselected areas
        if (!empty($areasToRemove)) {
            $ConfigAutomatedStudentEnrollmentsAreas->deleteAll([
                'area_programme_institution_id' => $areaProgrammeInstitutionId,
                'area_administrative_id IN' => $areasToRemove
            ]);
        }

        // Insert new areas that were selected
        foreach ($areasToAdd as $areaId) {
            $areaData = [
                'area_programme_institution_id' => $areaProgrammeInstitutionId,
                'area_administrative_id' => $areaId
            ];
            
            $areaEntity = $ConfigAutomatedStudentEnrollmentsAreas->newEntity($areaData);
            
            if (!$ConfigAutomatedStudentEnrollmentsAreas->save($areaEntity)) {
                $this->log('Error: Failed to save ConfigAutomatedStudentEnrollmentsAreas for area_programme_institution_id: ' . $areaProgrammeInstitutionId, 'error');
                return false;
            }
        }

        return true; // Return true if the process completes successfully
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ConfigAutomatedStudentEnrollmentsAreas']);
        //echo "<pre>";print_r($query);exit;
    }

   
}
