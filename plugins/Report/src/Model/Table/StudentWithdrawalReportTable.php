<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;

class StudentWithdrawalReportTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_student_withdraw');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
   
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
       
       $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelGetInstitutionName(EventInterface $event, Entity $entity)
    {
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        
        $where = [];
        if ( $entity->institution_id > 0) {
            $where = [$Institutions->aliasField('id = ') => $entity->institution_id];

        } else {
            $where = [];
        }

        $institutionName = $Institutions->find()->select(['name','code'])->where($where)->first();
        return $institutionName->name;
    }

    public function onExcelGetInstitutionCode(EventInterface $event, Entity $entity)
    {
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        
        $where = [];
        if ( $entity->institution_id > 0) {
            $where = [$Institutions->aliasField('id = ') => $entity->institution_id];

        } else {
            $where = [];
        }

        $institutionCode = $Institutions->find()->select(['name','code'])->where($where)->first();
        return $institutionCode->code;
    }


    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelGetStudentName(EventInterface $event, Entity $entity)
    {
        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';

        return implode(' ', $studentName);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
       $requestData = json_decode($settings['process']['params']);
        $academic_period_id = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $Statuses = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $StudentWithdrawReasons = TableRegistry::getTableLocator()->get('Student.StudentWithdrawReasons');
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $where = [];
        if ( $institution_id > 0) {
            $where = [$this->aliasField('institution_id = ') => $institution_id];
        } else {
            $where = [];
        }

        if (!empty($academic_period_id)) {
            $where[$this->aliasField('academic_period_id')] = $academic_period_id;
        }
         if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id')] = $areaId;
        }
        $query            
            ->select([
                 'openemis_no' => 'Users.openemis_no',
                 'student_first_name' => 'Users.first_name ',
                 'student_middle_name' => 'Users.middle_name',
                 'student_third_name' => 'Users.third_name',
                 'student_last_name' => 'Users.last_name',
                 'education_grade' => $EducationGrades->aliasField('name'),
                 'student_withdraw_reason' => $StudentWithdrawReasons->aliasField('name'),
                 'status' => $Statuses->aliasField('name'),
                 'institution_id',
                 'comment',
            ])
            
             ->leftJoin(
                 [$Users->getAlias() => $Users->getTable()],
                    [
                         $Users->aliasField('id = ') . $this->aliasField('student_id')
                     ]
             ) 
            ->leftJoin(
                    [$EducationGrades->getAlias() => $EducationGrades->getTable()],
                    [
                        $EducationGrades->aliasField('id = ') . $this->aliasField('education_grade_id')
                    ]
                )
            ->leftJoin(
                    [$StudentWithdrawReasons->getAlias() => $StudentWithdrawReasons->getTable()],
                    [
                        $StudentWithdrawReasons->aliasField('id = ') . $this->aliasField('student_withdraw_reason_id')
                    ]
                )
            ->leftJoin(
                    [$Statuses->getAlias() => $Statuses->getTable()],
                    [
                        $Statuses->aliasField('id = ') . $this->aliasField('status_id')
                    ]
                )
            ->leftJoin(
                 [$Institutions->getAlias() => $Institutions->getTable()],
                    [
                         $Institutions->aliasField('id = ') . $this->aliasField('institution_id')
                     ]
             ) 
            ->where([$where]);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
       $newArray = [];
      
        
       $newArray[] = [
            'key' => 'Institutions.code',
            'field' => 'institutionCode',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
          
        $newArray[] = [
            'key' => 'Institutions.name',
            'field' => 'institutionName',
            'type' => 'string',
            'label' => __('Institution Name')
        ];       

        $newArray[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Student OpenEMIS ID')
        ];

        $newArray[] = [
            'key' => 'Users.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $newArray[] = [
            'key' => '',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
         $newArray[] = [
            'key' => '',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Student Status')
        ];

        $newArray[] = [
            'key' => '',
            'field' => 'student_withdraw_reason',
            'type' => 'string',
            'label' => __('Reason')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];

        $fields->exchangeArray($newArray);
       
    }
}
