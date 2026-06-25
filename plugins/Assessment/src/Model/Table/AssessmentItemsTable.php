<?php

namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\Routing\Router;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Text;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsToMany('AssessmentPeriods', [
            'className' => 'Assessment.AssessmentPeriods',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_period_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('AssessmentGradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view']
        ]);
        
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(EventInterface $event, $scope, $action, $extra)
    {
        if ($action == 'index' || $action == 'view') {
            // check for the user permission to view here
            $event->stopPropagation();
            return true;
        }
    }

    //POCOR-8889
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);

        $validator
            ->allowEmptyString('weight') // Allow weight to be null or an empty string
            ->add('weight', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
                'message' => 'Value is not a valid decimal',
            ])
            ->add('weight', 'ruleWeightRange', [
                'rule' => ['range', 0, 2],
                'message' => 'Value must be positive and less than 2.0',
                'last' => true
            ]);

        return $validator;
    }


    public function populateAssessmentItemsArray($gradeId)
    {
        $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
        $gradeSubjects = $EducationGradesSubjects->find()
            ->contain('EducationSubjects')
            ->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
            ->order(['EducationSubjects.order'])
            ->toArray();

        $assessmentItems = [];
        foreach ($gradeSubjects as $key => $gradeSubject) {
            if (!empty($gradeSubject->education_subject)) {
                $assessmentItems[] = [
                    'education_subject_id' => $gradeSubject->education_subject->id,
                    'education_subject' => $gradeSubject->education_subject,
                    'weight' => '0.00'
                ];
            }
        }
        return $assessmentItems;
    }


    public function findStaffSubjects(Query $query, array $options)
    {
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $classId = $options['class_id'];
            $staffId = $options['staff_id'];
            $query->where([
                // For subject teachers
                'EXISTS (
                        SELECT 1
                        FROM institution_subjects InstitutionSubjects
                        INNER JOIN institution_class_subjects InstitutionClassSubjects
                            ON InstitutionClassSubjects.institution_class_id = ' . $classId . '
                            AND InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id
                        INNER JOIN institution_subject_staff InstitutionSubjectStaff
                            ON InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id
                            AND InstitutionSubjectStaff.staff_id = ' . $staffId . '
                        WHERE InstitutionSubjects.education_subject_id = ' . $this->aliasField('education_subject_id') . ')'
            ]);

            return $query;
        }
    }

    //Pocor-5758 copy of findStaffSubjects
    public function findCopyStaffSubjects(Query $query, array $options)
    {
        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);
        $name = $queryString['query'];
        $domain = substr($name, strpos($name, "="));
        $test = base64_decode($domain);
        $variable = substr($test, 0, strpos($test, "}"));
        $newVaridable = $variable . "}";
        $data = json_decode($newVaridable);
        $institutionId = $data->institution_id;
        $academinPeriod = $data->academic_period_id;
        $ClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $educationSubject = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];
        $staffSubject = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');

        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $classId = $options['class_id'];
            $staffId = $options['staff_id'];
            $query
                //->contain('EducationSubjects.InstitutionSubjects')
                ->innerJoin([$ClassSubjects->getAlias() => $ClassSubjects->getTable()], [
                    $ClassSubjects->aliasField('institution_class_id') => $classId
                ])
                ->leftJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                    $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                    $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
                ])
                ->leftJoin(
                    [$staffSubject->getAlias() => $staffSubject->getTable()],
                    [
                        $staffSubject->aliasField('institution_subject_id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                    ])
                ->select([
                    $this->aliasField('education_subject_id'),
                    $this->aliasField('id'),
                    $this->aliasField('assessment_id'),
                    $this->aliasField('weight'),
                    $this->aliasField('classification'),
                    $InstitutionSubjects->aliasField('education_subject_id'),
                    $InstitutionSubjects->aliasField('id'),
                    $InstitutionSubjects->aliasField('name'),
                    $educationSubject->aliasField('id'),
                ])
                ->where([
                    $this->aliasField('assessment_id') => $assessmentId,
                    $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                    $InstitutionSubjects->aliasField('academic_period_id') => $academinPeriod,
                    $staffSubject->aliasField('staff_id') => $staffId,
                ]);

            return $query;

        }
    }

    public function findAssessmentItemsInClass(Query $query, array $options)
    {
        $ClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];

        $query
            ->contain('EducationSubjects')
            ->innerJoin([$ClassSubjects->getAlias() => $ClassSubjects->getTable()], [
                $ClassSubjects->aliasField('institution_class_id') => $classId
            ])
            ->innerJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
            ])
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name']);

        return $query;
    }

    public function findSubjectNewTab(Query $query, array $options)
    {
//        $this->log('findSubjectNewTab', 'debug');
//        $this->log($options, 'debug');
        $logged_in_user_id = $options['user']['id'];
        $super_admin = $options['user']['super_admin'];
        $institution_id = $options['institution_id'];
        $academic_period_id = $options['academic_period_id'];
        $assessment_id = $options['assessment_id'];
        $class_id = $options['class_id'];
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $EducationSubject = TableRegistry::get('Education.EducationSubjects');
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $isHomeRoomTeacherOrSecondaryTeacher = 0;

        //POCOR-9487[START]
        //Check for Homeroom/Secondary teacher
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $InstitutionClassesData = $InstitutionClasses->find()
                                ->where([$InstitutionClasses->aliasField('id') => $class_id,
                                    $InstitutionClasses->aliasField('staff_id') => $logged_in_user_id
                                    ])
                                ->toArray();

        $InstitutionClassesSecondaryStaffData = $InstitutionClassesSecondaryStaff->find()
                                ->where([$InstitutionClassesSecondaryStaff->aliasField('institution_class_id') => $class_id,
                                    $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $logged_in_user_id
                                    ])
                                ->toArray();
        if(!empty($InstitutionClassesData) || !empty($InstitutionClassesSecondaryStaffData)){
            $isHomeRoomTeacherOrSecondaryTeacher = 1;
        }
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityFunctionsData = $securityFunctions
            ->find()
            ->select([
                'SecurityFunctions.id'
            ])
            ->where([
                'SecurityFunctions.name' => 'Assessments',
                'SecurityFunctions.controller' => 'Institutions',
                'SecurityFunctions.module' => 'Institutions',
                'SecurityFunctions.category' => 'Students'
            ])
            ->first();
        //POCOR-9491
        $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['index'];
        if(!empty($permission_id)){
            $securityRoleFunctions =  TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');

            $securityRoleFunctionsData = $securityRoleFunctions
            ->find('all')
            ->where([
                'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                'SecurityRoleFunctions.security_role_id IN' => $permission_id,
            ])
            ->toArray();
        

        $roleIds = array_map(function($entity) {
        return $entity->security_role_id;
        }, $securityRoleFunctionsData);

        if(!empty($roleIds)){
            $SecurityRoleTable = TableRegistry::get('Security.SecurityRoles');
            $SecurityRoleTableData = $SecurityRoleTable
            ->find('all')
            ->where([
                $SecurityRoleTable->aliasField('id IN ') => $roleIds
            ])
            ->toArray();
        }

        $hasPrincipal = 0;
        $isEditable = 0;

        foreach ($SecurityRoleTableData as $role) {
            if ($role->code === 'PRINCIPAL') {
                $hasPrincipal = 1;
                $securityRoleFunctionsData1 = $securityRoleFunctions
                ->find()
                ->where([
                'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                'SecurityRoleFunctions.security_role_id' => $role->id,
                ])
                ->first();
                if($securityRoleFunctionsData1->_edit == 1){
                    $isEditable = 1;
                }
            }
        }
        if($hasPrincipal == 1 &&  $isEditable == 1){
            $isPrinciple = 1;
        }
        }
        //POCOR-9491
        
        // $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['edit'];
        // if(!empty($permission_id)){
        //     $securityRoleFunctions =  TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');

        //     $securityRoleFunctionsData = $securityRoleFunctions
        //     ->find()
        //     ->where([
        //         'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
        //         'SecurityRoleFunctions.security_role_id IN' => $permission_id,
        //     ])
        //     ->first();
        //     }
        // if(!empty($securityRoleFunctionsData)){
        //     $SecurityRoleTable = TableRegistry::get('Security.SecurityRoles');
        //     $SecurityRoleTableData = $SecurityRoleTable
        //     ->find()
        //     ->where([
        //         $SecurityRoleTable->aliasField('id') => $securityRoleFunctionsData->security_role_id
        //     ])
        //     ->first();
        // }
        // if ($SecurityRoleTableData->code == 'PRINCIPAL') {
        //     if($securityRoleFunctionsData->_edit == 1){
        //         $isPrinciple = 1;
        //     }
        // }
        //POCOR-9487[END]

        $query
            ->select([
                $this->aliasField('education_subject_id'),
                $this->aliasField('id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('weight'),
                $this->aliasField('classification'),
                $InstitutionSubjects->aliasField('education_subject_id'),
                $InstitutionSubjects->aliasField('id'),
                $InstitutionSubjects->aliasField('name'),
                $EducationSubject->aliasField('id'),
                $EducationSubject->aliasField('name'),
            ])
            ->contain('EducationSubjects')
            ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [
                $Assessments->aliasField('id = ') . $this->aliasField('assessment_id')
            ])
            ->innerJoin([$ClassSubjects->getAlias() => $ClassSubjects->getTable()], [
                $ClassSubjects->aliasField('institution_class_id') => $class_id
            ])
            ->leftJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
                $InstitutionSubjects->aliasField('education_grade_id = ') . $Assessments->aliasField('education_grade_id')
            ])
            ->where([
                $this->aliasField('assessment_id') => $assessment_id,
                $InstitutionSubjects->aliasField('institution_id') => $institution_id,
                $InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id,
            ])
            // ->group([
            //     $this->aliasField('education_subject_id'), //POCOR-9291
            //     $EducationSubject->aliasField('id'),
            //     $EducationSubject->aliasField('name')
            // ])
            ->group([
                $InstitutionSubjects->aliasField('id'),  //POCOR-9468
                $EducationSubject->aliasField('name')
            ])
            ->order([
                'EducationSubjects.order',
                'EducationSubjects.code',
                'EducationSubjects.name'
            ]);

        //POCOR-5999 starts
        $query
            ->formatResults(function (ResultSetInterface $results) use (
                $InstitutionSubjectStaff,
                $logged_in_user_id,
                $super_admin,
                $institution_id,
                $isHomeRoomTeacherOrSecondaryTeacher,
                $isPrinciple
            ) {
                return $results->map(function ($row) use (
                    $InstitutionSubjectStaff,
                    $logged_in_user_id,
                    $super_admin,
                    $institution_id,
                    $isHomeRoomTeacherOrSecondaryTeacher,
                    $isPrinciple
                ) {
                    $row['education_subject_id'] = $row->education_subject_id;
                    $row['id'] = $row->id;
                    $row['assessment_id'] = $row->assessment_id;
                    $row['weight'] = $row->weight;
                    $row['classification'] = $row->classification;
                    $row['education_subject'] = [
                        'id' => $row->education_subject->id,
                        'code_name' => " - "
                    ];
                    $row['InstitutionSubjects'] = [
                        'education_subject_id' => $row->InstitutionSubjects['education_subject_id'],
                        'id' => $row->InstitutionSubjects['id'],
                        'name' => $row->InstitutionSubjects['name']
                    ];
                    if ($super_admin == 1) {
                        $row['is_editable'] = 1;
                        return (array) $row;
                    }
                    $subjectId = $row->InstitutionSubjects['id'];
                    $data = $InstitutionSubjectStaff->find()
                        ->where([
                            $InstitutionSubjectStaff->aliasField('staff_id') => $logged_in_user_id,
                            $InstitutionSubjectStaff->aliasField('institution_subject_id') => $subjectId
                        ])
                        ->toArray();
                    if (!empty($data)) {
                        $row['is_editable'] = 1;
                        return (array)$row;
                    }
                    //checking whether logged in user is admin or not
                    //POCOR-7551 start
                    $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
                    $SecurityInstitutionsTable = TableRegistry::get('Security.SecurityGroupInstitutions');
                    $SecurityRoleFunTable = TableRegistry::get('Security.SecurityRoleFunctions');
                    $SecurityRoleTable = TableRegistry::get('Security.SecurityRoles');
                    $SecurityGroupTable=TableRegistry::get('Security.UserGroups');

                    //POCOR-9487[START]
                    //Get the dynamic value of security function
                    $permissionModule = ['Assessments'];
                    $categories = ['Students'];
                    $SecurityFunctionsTbl = TableRegistry::get('Security.SecurityFunctions');
                    $SecurityFunctions = $SecurityFunctionsTbl->find()
                        ->select([$SecurityFunctionsTbl->aliasField('id')])
                        ->where([
                            $SecurityFunctionsTbl->aliasField('name IN') => $permissionModule,
                            $SecurityFunctionsTbl->aliasField('category IN') => $categories,
                        ])->enableHydration(false)->toArray();
                    $funArr = [];
                    if (!empty($SecurityFunctions)) {
                        foreach ($SecurityFunctions as $funkey => $funval) {
                            $funArr[$funkey] = $funval['id'];
                        }
                    }
                    //POCOR-9487[END]

                    $securityGroupUserData = $SecurityGroupUsersTable->find('all')
                            ->select([$SecurityGroupUsersTable->aliasField('security_role_id'),
                                    'edit' => $SecurityRoleFunTable->aliasField('_edit'),
                                    // $SecurityGroupTable->aliasField('id'),
                                    "group_id"=>$SecurityGroupUsersTable->aliasField('security_group_id'),
                                    "role_order"=>$SecurityRoleTable->aliasField('order'),
                                ]
                            )
                            -> distinct([$SecurityGroupUsersTable->aliasField('security_role_id'),
                            'edit'])
                           
                            ->where([$SecurityGroupUsersTable->aliasField('security_user_id') => $logged_in_user_id,
                            ])
                            ->innerJoin(
                                [$SecurityRoleFunTable->getAlias() => $SecurityRoleFunTable->getTable()],
                                [
                                    $SecurityRoleFunTable->aliasField('security_role_id = ') .
                                    $SecurityGroupUsersTable->aliasField('security_role_id'),
                                    $SecurityRoleFunTable->aliasField('security_function_id IN') => $funArr,
                                    // $SecurityRoleFunTable->aliasField('_edit') => '1'
                                ]
                            )
                            ->innerJoin(
                                [$SecurityRoleTable->getAlias() => $SecurityRoleTable->getTable()],
                                [
                                    $SecurityRoleTable->aliasField('id = ') .
                                    $SecurityGroupUsersTable->aliasField('security_role_id')
                                ]
                            )
                            ->toArray();
                    
                            //for checking role order
                            $securityGroupUserEditAccessCount=$securityGroupUserData[0]['edit'];
                            $min_val =$securityGroupUserData[0]['role_order']; 
                            foreach($securityGroupUserData as $val) {
                               if ($min_val>$val['role_order']) {
                                  $min_val = $val['role_order'];
                                  $securityGroupUserEditAccessCount=$val['edit'];
                               }
                            }
                    //POCOR-7551 end
//                    $this->log($securityGroupUserEditAccessCount, 'debug');
                    if ($securityGroupUserEditAccessCount > 0) {
                        //POCOR-9487[START]
                        if($isHomeRoomTeacherOrSecondaryTeacher == 1){
                            $row['is_editable'] = 1;
                            return (array) $row;
                        }

                        else if($isPrinciple == 1){
                            $row['is_editable'] = 1;
                            return (array) $row;
                        }
                        else{
                            if ($securityGroupUserEditAccessCount > 0) { //POCOR-9535
                                $row['is_editable'] = 1;
                                return (array)$row;
                            } else {
                                $row['is_editable'] = 0;
                                return (array)$row;
                            }
                        }
                        //POCOR-9487[END]
                    } else {
                        if($isPrinciple == 1){
                             $row['is_editable'] = 1;
                        }else{
                            $row['is_editable'] = 0;
                        }
                    }
                    //POCOR-7541 end
                    return (array) $row;
                });
            });
        //POCOR-5999 ends
        return $query;
    }

    public function getSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->innerJoinWith('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_name' => 'EducationSubjects.name',
                'subject_id' => $this->aliasField('education_subject_id'),
                'subject_weight' => $this->aliasField('weight'),
            ])
            ->order(['EducationSubjects.order'])
            ->enableHydration(false)
            ->toArray();
        return $subjectList;
    }

    public function getAssessmentItemSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->matching('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_id' => 'EducationSubjects.id',
                'education_subject_name' => $this->find()->func()->concat([
                    'EducationSubjects.code' => 'literal',
                    " - ",
                    'EducationSubjects.name' => 'literal'
                ])
            ])
            ->order(['EducationSubjects.order'])
            ->enableHydration(false)
            ->toArray();
        return $subjectList;
    }

    public function afterDelete()
    {
        // delete all AssessmentItemsGradingTypes by education_subject_id and assessment_id
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $entity['assessment_items'] = array();
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $entity['assessment_items'] = array();
    }

}
