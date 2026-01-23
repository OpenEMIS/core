<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Cake\Database\Connection;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;


class StudentBehavioursTable extends AppTable  {

    public function initialize(array $config): void {
    
        parent::initialize($config);
        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
        $this->belongsTo('StudentBehaviourClassifications', ['className' => 'Student.StudentBehaviourClassifications','foreignKey' => 'student_behaviour_classification_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->hasMany('StudentBehaviourAttachments', [
            'className' => 'Institution.StudentBehaviourAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');
      
    }
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $areaId = $requestData->area_education_id;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $where=[];
        //area filter
        $area_level_id = $requestData->area_level_id;
        $institutionId = $requestData->institution_id;

        $selectedArea = $requestData->area_education_id;
        if ($areaId != -1 && !empty($areaId)) {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $where['Institutions.area_id IN'] = $allselectedAreas;
        }

        $Statuses1 = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $query->select([
             "institution_code"=>'Institutions.code',
             "institution_name"=>"Institutions.name",
             "date_of_behaviour"=>$this->aliasField('date_of_behaviour'),
             "time"=>$this->aliasField('time_of_behaviour'),
             "openemis_no"=>"Students.openemis_no",
             "status_name"=>$Statuses1->aliasField('name'),
             "name"=> $this->find()->func()->concat([
                 'Students.first_name' => 'literal',
                " ",
                'Students.last_name' => 'literal'
             ]),
           
             "description"=>$this->aliasField('description'),
             "behaviour_category"=>"StudentBehaviourCategories.name",
             "behaviour_classification"=>"StudentBehaviourClassifications.name"
            ]);
        $query->contain(['Institutions','Institutions.Areas','AcademicPeriods','Students','StudentBehaviourCategories','StudentBehaviourClassifications'])
        ->InnerJoin([$Statuses1->getAlias()=>$Statuses1->getTable()],[
                $Statuses1->aliasField('id')."=(`StudentBehaviours`.`status_id`)"
        ])      
        ->where([$where]);
        if ($institutionId != 0) {
            $query->where([
                $this->aliasField('institution_id') => $institutionId
            ]);
        }
     
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $time = new Time($row->time_of_behaviour);
                $row->time_of_behaviour = $time->i18nFormat('HH:mm:ss');
                return $row;
            });
        });
    
}
 public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key' => 'StudentBehaviour.Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $extraField[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $extraField[] = [
            'key' => 'StudentBehaviour.date_of_behaviour',
            'field' => 'date_of_behaviour',
            'type' => 'date',
            'label' => __('Date Of Behaviour')
        ];
        $extraField[] = [
            'key' => 'StudentBehaviour.time_of_behaviour',
            'field' => 'time_of_behaviour',
            'type' => 'string',
            'label' => __('Time Of Behaviour')
        ];
        $extraField[] = [
            'key' => 'Statuses.name',
            'field' => 'status_name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $extraField[] = [
            'key' => 'StudentBehaviour.assignee_id',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => __('Assignee')
        ];
        $extraField[] = [
            'key' => 'Student.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $extraField[] = [
            'key' => 'Student.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Student Name')
        ];
        
        $extraField[] = [
            'key' => 'BehaviourCategories',
            'field' => 'behaviour_category',
            'type' => 'string',
            'label' => __('Student Behaviour category')
        ];
        $extraField[] = [
            'key' => 'behaviour_classification',
            'field' => 'behaviour_classification',
            'type' => 'string',
            'label' => __('Student Behaviour Classification')
        ];
        $extraField[] = [
            'key' => 'StudentBehaviour.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Description')
        ];
        $extraField[] = [
            'key' => 'StudentBehaviour.action',
            'field' => 'action',
            'type' => 'string',
            'label' => __('Action')
        ];
      
        $fields->exchangeArray($extraField);
    }

    public function onExcelRenderDate(EventInterface $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};
        
        if (!empty($field)) {
            if ($field instanceof FrozenTime || $field instanceof FrozenDate) {
                return $this->formatDate($field);
            } else {
                $date = new FrozenTime($field);
                return $this->formatDate($date);
            }
        } else {
            return $field;
        }
    }

    public function getChildren($id, $idArray) 
    {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

}