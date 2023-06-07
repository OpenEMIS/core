<?php
namespace Directory\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Network\Session;


class CounsellingsTable extends ControllerActionTable
{
    const ASSIGNED = 1;
    public function initialize(array $config)
    {
        $this->table('counsellings');
        parent::initialize($config);
        $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes', 'foreign_key' => 'guidance_type_id']);
        $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        $this->belongsTo('Requesters', ['className' => 'Security.Users', 'foreign_key' => 'requester_id']);
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->toggle('view', true);
        $this->toggle('edit', true);
      
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
    }
    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator->allowEmpty('file_content');
    }
    public function addEditBeforeAction(Event $event, ArrayObject $extra){
        $session = $this->request->session();
        $StudentId = $session->read('Student.Students.id');
        $table=TableRegistry::get('institution_students');
        $institutionStudent=$table->find('all')->select('institution_id')->where([
            $table->aliasField('student_id')=>$StudentId,
            $table->aliasField('student_status_id')=>1
        ])->first();
        $institutionId=$institutionStudent->institution_id;
        $requestorOptions=$this->getRequesterOptions($institutionId);
        $counselorOptions=$this->getCounselorOptions($institutionId);
        // $this->field('file_name', ['visible' => false]);
        // $this->field('file_content', ['visible' => false]);
        // $this->field('file_name', ['visible' => true]);
        $this->fields['requester_id']['type'] = 'select';
        $this->fields['requester_id']['options'] = $requestorOptions;
        $this->fields['guidance_type_id']['type'] = 'select';
        // $this->fields['guidance_type_id']['options'] = $guidanceOptions;
        $this->fields['counselor_id']['type'] = 'select';
        $this->fields['counselor_id']['options'] = $counselorOptions;
        $this->fields['date']['type'] = 'date';
        $this->fields['date']['value'] = Time::now()->format('d-m-Y');
        $this->setFieldOrder(['date','counselor_id','guidance_type_id','requester_id', 'guidance_utilized', 'description', 'intervention', 'comment', 'attachment']);
        
    }
    
    public function getCounselorOptions($institutionId)
    {
      
        // get the staff that assigned from the institution from security user
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        $counselorOptions = $this->Counselors
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->innerJoin(
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('staff_id = ') . $this->Counselors->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id') => $institutionId,
                        $InstitutionStaff->aliasField('staff_status_id') => self::ASSIGNED
                    ]
                )
            ->order([
                $this->Counselors->aliasField('first_name'),
                $this->Counselors->aliasField('last_name')
            ])
            ->toArray();
           
   
        return $counselorOptions;
    }

    public function getRequesterOptions($institutionId)
    {        
       
        $InstitutionStaff = TableRegistry::get('institution_staff');
        $InstitutionStudents = TableRegistry::get('institution_students');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $UserData = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $join = [];
        $join[''] = [
        'type' => 'inner',
        'table' => "(SELECT institution_students.student_id user_id
                        FROM institution_students
                        INNER JOIN academic_periods
                        ON academic_periods.id = institution_students.academic_period_id
                        WHERE academic_periods.id = $academicPeriodId
                        AND institution_students.institution_id = $institutionId
                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                        GROUP BY institution_students.student_id

                        UNION ALL

                        SELECT institution_staff.staff_id user_id
                        FROM institution_staff
                        INNER JOIN academic_periods
                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                        WHERE academic_periods.id = $academicPeriodId
                        AND institution_staff.institution_id = $institutionId
                        AND institution_staff.staff_status_id = 1
                        GROUP BY institution_staff.staff_id
                            ) subq",
                            'conditions' => ['subq.user_id = Users.id'],
                ];
        $requestorOptions = $UserData
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->select([
                    'id'=> $UserData->aliasField('id'),
                    $UserData->aliasField('openemis_no'),
                    $UserData->aliasField('first_name'),
                    $UserData->aliasField('middle_name'),
                    $UserData->aliasField('third_name'),
                    $UserData->aliasField('last_name')
            ]);

          $data =   $requestorOptions->join($join)->toArray();
            return $data;
    }

    public function getGuidanceTypesOptions($institutionId)
        {
            // should be auto, if auto the reorder and visible not working
            $guidanceTypesOptions = $this->GuidanceTypes
                ->find('list')
                ->find('visible')
                ->find('order')
                ->toArray();
                
            return $guidanceTypesOptions;
    
        }

            
        // public function indexAfterAction(Event $event)
        // {
        //     $this->field('file_content', ['visible' => false]);
        //     $this->field('file_name', ['visible' => false]);
        //     $this->field('comment', ['visible' => false]);
        //     $this->field('guidance_utilized', ['visible' => false]);
            
        //     $this->setFieldOrder(['date', 'description', 'intervention', 'counselor_id', 'guidance_type_id', 'requester_id',  'Actions']);
        // }

        // public function viewBeforeAction(Event $event, ArrayObject $extra){
           
        //     $this->field('file_name', ['visible' => false]);
        //     $this->setFieldOrder(['date','counselor_id','guidance_type_id','requester_id', 'guidance_utilized', 'description', 'intervention', 'comment', 'attachment']);
        // }
        public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'file_content':
                return __('Attachment');
            case 'modified_user_id';
                return __('Modified By');
            case 'modified';
                return __('Modified On');
            case 'created_user_id';
                return __('Created By');
            case 'created';
                return __('Created On');
            
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    // public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    // {
       
       
    //      $this->field('file_name', ['visible' => false]);
    //     $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    //     $this->setupFields($entity);
    // }
    private function setupFields(Entity $entity)
    {
      $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
      
    }
    // private function setupFields(Entity $entity)
    // {
    //     // $this->field('severe', ['after' => 'description']);
    //     // $this->field('health_allergy_type_id', ['type' => 'select', 'after' => 'comment']);
    //     $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    // }
    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
       
    }
    // public function viewBeforeAction(Event $event, ArrayObject $extra)
    // {
    //     echo "<pre>";
    //     print_r($extra);
    //     exit;
    // 
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        
          
        //     $institutionId = $session->read('Institution.Institutions.id');
        //     $curricularStudent = TableRegistry::get('institution_curricular_students');
        //     $users = TableRegistry::get('security_users');
    
            $query->select([
                    'id',
                    'date',
                    'guidance_utilized',
                    'description',
                    'intervention',
                    'comment',
                    'file_name',
                    'file_content',
                    'counselor_id',
                    'student_id',
                    'guidance_type_id',
                    'requester_id',
                    'modified_user_id',
                    'modified',
                    'created_user_id',
                    'created',
            ])->contain('Counselors');
         
        //         ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'],
        //         $this->aliasField('institution_id') => $institutionId])
        //         ->group([$this->aliasField('id')]);
    
        //     if (!$sortable) {
        //         $query
        //             ->order([
        //                 $this->aliasField('name') => 'ASC'
        //             ]);
        //     }
            $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
            $query = $this->request->query;
            $this->field('file_content', ['visible' => false]);
            $this->field('file_name', ['visible' => false]);
            $this->field('comment', ['visible' => false]);
            $this->field('guidance_utilized', ['visible' => false]);
            
            $this->field('modified_user_id', ['visible' => false]);
            $this->field('modified', ['visible' => false]);
            $this->field('created_user_id', ['visible' => false]);
            $this->field('created', ['visible' => false]);
            $this->setFieldOrder([
                'name', 'staff_id','category','total_male_students', 'total_female_students', 'total_students'
            ]);
            $this->setFieldOrder(['date', 'description', 'intervention', 'counselor_id', 'guidance_type_id', 'requester_id',  'Actions']);
  
            
        // }
      
    }
    }