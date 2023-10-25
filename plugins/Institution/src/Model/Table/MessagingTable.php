<?php

namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Utility\Security;
class MessagingTable extends ControllerActionTable
{
    //recipient levels (hard coded)
    const INSTITUTION = 1;
    const PROGRAMME = 2;
    const GRADE=3;
    const GRADE_CLASS = 4;
    const SUBJECT=5;
    //status
    const DRAFT = 0;
    const SEND = 1;
    private $recipientlevelOptions = [];

    public function initialize(array $config)
    {
        $this->table('messaging');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->recipientlevelOptions = [
            '1' => __('Institution'),
            '2' => __('Programme'),
            '3' => __('Grade'),
            '4' => __('Class'),
            '5' => __('Subject')
        ];
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message');
        $this->field('institution_id', ['visible' =>  ['index' => false, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('academic_period_id'); 
        $this->field('created',['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('created_user_id', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
        $this->field('recipient_level_id');
        $this->field('recipient_group_id');
        $this->field('subject',['sort'=>false]);
        $this->field('status', ['visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]]);
    }
    public function onGetRecipientLevelId(Event $event, Entity $entity)
    {
        $value="";
       switch($entity->recipient_level_id){
            case self::INSTITUTION:
                $value="Institution";
                break;
            case self::PROGRAMME:
                $value="Programme";
                break;
            case self::GRADE:
                $value = "Grade";
                break;
            case self::GRADE_CLASS:
                $value = "Class";
                break;
            case self::SUBJECT:
                $value = "Subject";
                break;
            default:
                $value="";
       }
       return $value;
    }
    public function onGetRecipientGroupId(Event $event, Entity $entity)
    {
        $institution_id = $this->Session->read('Institution.Institutions.id');
        $academicPeriodId = $entity->academic_period_id;
        $option=$this->getRecipientGroupOptions($entity->recipient_level_id);
        $result= $option[$entity->recipient_group_id];
        return $result;
    }
    public function onUpdateFieldRecipientLevelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit'
        ) {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->recipientlevelOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }
    public function onUpdateFieldRecipientGroupId(Event $event, array $attr, $action, Request $request)
    {
        if (
            $action == 'add' || $action == 'edit'
        ) {
            
            $recipient_level_id =$request->data['Messaging']['recipient_level_id'];
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['options'] = $this->getRecipientGroupOptions($recipient_level_id);

        }

        return $attr;
    }
    public function getRecipientGroupOptions($recipient_level_id){
       
        $institution_id=$this->Session->read('Institution.Institutions.id');
        $academicPeriodId =TableRegistry::get('AcademicPeriod.AcademicPeriods')->getCurrent();
        
        $option=[];
        switch ($recipient_level_id) {
            case self::INSTITUTION:
            case "Institution":
                $option[$institution_id]= $this->Session->read('Institution.Institutions.name');
                 break;
            case self::PROGRAMME:
            case "Programme":
                $result= $this->getSelectOptions($institution_id, $academicPeriodId);
                $programmeData=$result->group('EducationProgrammes.id')->toArray();
                foreach($programmeData as $key => $value) {
                    $option[$value->education_programme_id] = $value->education_programme_name;
                }
                break;
            case self::GRADE:
            case "Grade":
                $gradeData = $this->getSelectOptions($institution_id, $academicPeriodId)->toArray();
                foreach ($gradeData as $key => $value) {
                    $option[$value->education_grade_id] = $value->education_grade_name;
                }
                break;
            case self::GRADE_CLASS:
            case "Class":
                $result = $this->getClassOptions($institution_id, $academicPeriodId);
                $classData = $result->group('InstitutionClasses.id')->toArray();
                foreach ($classData as $key => $value) {
                    $option[$value->id] = $value->name;
                }
                break;
            case self::SUBJECT:
            case "Subject":
                $classData = $this->getClassOptions($institution_id, $academicPeriodId)->toArray();
                foreach ($classData as $key => $value) {
                    foreach($value->institution_subjects as $Key => $Value){ 
                        $option[$Value->_joinData->id] = $value->name." ".$Value->name;
                    }
                }
                $option=array_unique($option);
                break;
            default:
                $value = "";
        }
        return $option;
    }
    public function getSelectOptions($institution_id, $academicPeriodId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $programmeOptions = [];

        $query = $InstitutionGrades
            ->find()
            ->select([
                'education_programme_id' => 'EducationProgrammes.id',
                'education_programme_name' => 'EducationProgrammes.name',
                'education_grade_id' => 'EducationGrades.id',
                'education_grade_name' => 'EducationGrades.name'
            ])
            ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems']) //POCOR-6803 
            ->where(['EducationSystems.academic_period_id' => $academicPeriodId,
                     'InstitutionGrades.institution_id' => $institution_id
            ]); //POCOR-6803 
        return $query;
    }
    public function getClassOptions($institution_id, $academicPeriodId)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $query=$InstitutionClasses->find()->contain('InstitutionSubjects')
                        ->where([
                            $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                            $InstitutionClasses->aliasField('institution_id') => $institution_id
                        ]);
        return $query;

    }
    public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, Request $request){
        $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $options = $SecurityRoles->find('list', [
            'keyField' => 'id',
            'valueField' => 'name',
        ])->where([
            $SecurityRoles->aliasField('name IN') => ['Student', 'Guardian']
        ])->toArray();
        $attr['type'] = 'chosenSelect';
        $attr['options'] = $options;
        return $attr;
    }
     public function onUpdateFieldMessage(Event $event, array $attr, $action, Request $request){
        $attr['type'] = 'text';
        return $attr;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('security_role_id');
        $this->field('message');
        $this->setFieldOrder(['academic_period_id','recipient_level_id', 'recipient_group_id','security_role_id','subject','message']);
    }
    // 
    public function indexAfterAction(Event $event, Query $query)
    {
        $this->field('message',['visible'=>false]);
        $this->field('academic_period_id', ['visible' => false]);
    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);

        $extra['elements']['control'] = [
            'name' => 'Institution.Messaging/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriod'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];
    }
    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit'
        ) {
            if (isset($request->query) && array_key_exists('period', $request->query)
            ) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 
    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'add') {
            $originalButtons = $buttons->getArrayCopy();
            
            $sendButton = [
                [
                    'name' =>'<i class="fa fa-check"></i>'.__('Send'),
                    'attr' => [
                        'class' => 'btn btn-default btn-save',
                        'name' => 'submit',
                        'value' => 'sendMessage',
                        'div' => false
                    ]
                ]
            ];

            array_splice($originalButtons, 1, 0, $sendButton);
            $buttons->exchangeArray($originalButtons);
        }
    }
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions'],
                $this->aliasField('institution_id') =>  $this->Session->read('Institution.Institutions.id')
            ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
        
    }
    
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($entity->status == "Send" || $entity->status == self::SEND) {
            unset($buttons['edit']);
        }
        return $buttons;
    }
    public function onGetStatus(Event $event, Entity $entity)
    {
       
         if($entity->status == self::DRAFT){
            return "Draft";
         }
         else if($entity->status == self::SEND){
            return "Send";
         }
    }
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    { if($action == 'edit'||$action=="add") {

            $selectedPeriod  = $this->getSelectedAcademicPeriod($this->request->query('period'));
            $attr['attr']['value'] = $this->AcademicPeriods->get($selectedPeriod)->name;
            $attr['type'] = 'readonly';
            $attr['value'] = $selectedPeriod;
        }
        return $attr;
    }
    
}
