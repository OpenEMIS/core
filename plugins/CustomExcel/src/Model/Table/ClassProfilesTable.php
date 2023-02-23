<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;
use DateTime;//POCOR-6328
use Cake\I18n\Time;//POCOR-6328
use Cake\Datasource\ConnectionManager;
/**
 * 
 * This class is used to generate data from placeholders from from template
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 * 
 */
class ClassProfilesTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);
        ini_set("pcre.backtrack_limit", "5000000"); //POCOR-6996

        $this->addBehavior('CustomExcel.ClassExcelReport', [
            'templateTable' => 'ProfileTemplate.ClassTemplates',
            'templateTableKey' => 'class_profile_template_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'Profiles',
                'Institutions',
                'Principal',
                'DeputyPrincipal',
                'StaffPositions',
                'InstitutionCommittees',
                'ReportStudentAssessmentSummary',//POCOR-6519
                'InfrastructureRoomCustomFields',//POCOR-6519
                'StudentDetails',//POCOR-6628 - registering function
            ]
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseProfiles'] = 'onExcelTemplateInitialiseProfiles';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialisePrincipal'] = 'onExcelTemplateInitialisePrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseDeputyPrincipal'] = 'onExcelTemplateInitialiseDeputyPrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStaffPositions'] = 'onExcelTemplateInitialiseStaffPositions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionCommittees'] = 'onExcelTemplateInitialiseInstitutionCommittees';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseReportStudentAssessmentSummary'] = 'onExcelTemplateInitialiseReportStudentAssessmentSummary';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInfrastructureRoomCustomFields'] = 'onExcelTemplateInitialiseInfrastructureRoomCustomFields';//POCOR-6519
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentDetails'] = 'onExcelTemplateInitialiseStudentDetails';//POCOR-6628 - triggering event
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $ClassProfiles = TableRegistry::get('Institution.ClassProfiles');
        if (!$ClassProfiles->exists($params)) {
            // insert institution report card record if it does not exist
            $params['status'] = $ClassProfiles::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $ClassProfiles->newEntity($params);
            $ClassProfiles->save($newEntity);
        } else {
            // update status to in progress if record exists
            $ClassProfiles->updateAll([
                'status' => $ClassProfiles::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $ClassesProfiles = TableRegistry::get('Institution.ClassProfiles');
        $classProfileData = $ClassesProfiles
            ->find()
            ->select([
                $ClassesProfiles->aliasField('academic_period_id'),
                $ClassesProfiles->aliasField('institution_id'),
                $ClassesProfiles->aliasField('institution_class_id'),
                $ClassesProfiles->aliasField('class_profile_template_id')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'InstitutionClasses' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'ClassTemplates' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ])
            ->where([
                $ClassesProfiles->aliasField('academic_period_id') => $params['academic_period_id'],
                $ClassesProfiles->aliasField('institution_id') => $params['institution_id'],
                $ClassesProfiles->aliasField('class_profile_template_id') => $params['class_profile_template_id'],
                $ClassesProfiles->aliasField('institution_class_id') => $params['institution_class_id'],
            ])
            ->first();
        // set filename
        $fileName = $classProfileData->academic_period->name . '_' . $classProfileData->class_template->code. '_' . $classProfileData->institution->name. '_' . $classProfileData->institution_class->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $ClassesProfiles::GENERATED;
        // save file
        $ClassesProfiles->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);
        // delete institution report card process
        $ClassProfileProcesses = TableRegistry::Get('ReportCard.ClassProfileProcesses');
        $ClassProfileProcesses->deleteAll([
            'class_profile_template_id' => $params['class_profile_template_id'],
            'institution_id' => $params['institution_id'],
            'institution_class_id' => $params['institution_class_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'ProfileTemplate',
            'controller' => 'ProfileTemplates',
            'action' => 'ClassProfiles',
            'index',
            'class_profile_template_id' => $params['class_profile_template_id'],
            'academic_period_id' => $params['academic_period_id'],
            'institution_id' => $params['institution_id'],
            'institution_class_id' => $params['institution_class_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }
    
    public function onExcelTemplateInitialiseProfiles(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_profile_template_id', $params)) {
            //$ProfileTemplates = TableRegistry::get('ProfileTemplate.ProfileTemplates');
            $ProfileTemplates = TableRegistry::get('ProfileTemplate.ClassTemplates');
            $entity = $ProfileTemplates->get($params['class_profile_template_id'], ['contain' => ['AcademicPeriods']]);

            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;

            return $entity->toArray();
        }
    }
    
    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types', 'Genders', 'Sectors', 'Providers','Ownerships','Areas','InstitutionLands']]); //POCOR-6328 
            $shift_types = [1=>'Single Shift Owner',
                            2=>'Single Shift Occupier',
                            3=>'Multiple Shift Owner',
                            4=>'Multiple Shift Occupier'
                            ];
            //POCOR-6519 starts
            $entity->shift_type_name = '';
            if($entity->shift_type != 0){
                if($shift_types[$entity->shift_type]) {
                    $entity->shift_type_name = $shift_types[$entity->shift_type];
                }
            }//POCOR-6519 ends
            $entity->date_opened = $entity->date_opened->format('Y-m-d');//POCOR-6328 
            return $entity;
        }
    }
    
    public function onExcelTemplateInitialisePrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $principalRoleId = $SecurityRoles->getPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $principalRoleId
                ])
                ->first();
            return $entity;
        }
    }
    
    public function onExcelTemplateInitialiseDeputyPrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $deputyPrincipalRoleId = $SecurityRoles->getDeputyPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $deputyPrincipalRoleId
                ])
                ->first();

            return $entity;
        }
    }
    
    public function onExcelTemplateInitialiseStaffPositions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $StaffPositionTitles = TableRegistry::get('staff_position_titles');
            $entity = $StaffPositionTitles
                ->find()
                ->select([
                    'first_name' => 'Users.first_name',
                    'last_name' => 'Users.last_name'
                ])
                ->innerJoin(
                ['InstitutionPositions' => 'institution_positions'],
                [
                    'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
                ]
                )
                ->innerJoin(
                ['InstitutionPositions' => 'institution_positions'],
                [
                    'InstitutionPositions.staff_position_title_id = '. $StaffPositionTitles->aliasField('id')
                ]
                )
                ->innerJoin(
                ['InstitutionStaff' => 'institution_staff'],
                [
                    'InstitutionStaff.institution_position_id = InstitutionPositions.id'
                ]
                )
                ->innerJoin(
                ['Users' => 'security_users'],
                [
                    'Users.id = InstitutionStaff.staff_id'
                ]
                )
                ->where([$StaffPositionTitles->aliasField('security_role_id') => 2])
                ->where(['InstitutionStaff.institution_id' => $params['institution_id']])
                ->where(['InstitutionPositions.institution_id' => $params['institution_id']])
                ->hydrate(false)
                ->toArray()
            ;
            $result = [];
            foreach ($entity as $key => $value) {
                $result = [
                    'name' => $value['first_name'].' '.$value['last_name'],
                ];
            }
            return $result;
        }
    }
    
    public function onExcelTemplateInitialiseInstitutionCommittees(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionCommittees = TableRegistry::get('institution_committees');
            $entity = $InstitutionCommittees
                ->find()
                ->where([$InstitutionCommittees->aliasField('academic_period_id') => $params['academic_period_id']])
                ->where([$InstitutionCommittees->aliasField('institution_id') => $params['institution_id']])
                ->hydrate(false)
                ->first()
            ;
            return $entity;
        }
    }   
    
    /**
     * Create a placeholder to display institution data from this table report_student_assessment_summary
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function onExcelTemplateInitialiseReportStudentAssessmentSummary(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $ReportStudentAssessmentSummary = TableRegistry::get('report_student_assessment_summary');
            $AssessmentSummaryData = $ReportStudentAssessmentSummary->find()
                ->select([
                //'id' => $ReportStudentAssessmentSummary->aliasField('id'),
                'academic_period_code' => $ReportStudentAssessmentSummary->aliasField('academic_period_code'),
                'academic_period_name' => $ReportStudentAssessmentSummary->aliasField('academic_period_name'),
                'area_code' => $ReportStudentAssessmentSummary->aliasField('area_code'),
                'area_name' => $ReportStudentAssessmentSummary->aliasField('area_name'),
                'institution_code' => $ReportStudentAssessmentSummary->aliasField('institution_code'),
                'institution_name' => $ReportStudentAssessmentSummary->aliasField('institution_name'),
                'grade_code' => $ReportStudentAssessmentSummary->aliasField('grade_code'),
                'grade_name' => $ReportStudentAssessmentSummary->aliasField('grade_name'),
                'subject_code' => $ReportStudentAssessmentSummary->aliasField('subject_code'),
                'subject_name' => $ReportStudentAssessmentSummary->aliasField('subject_name'),
                'subject_weight' => $ReportStudentAssessmentSummary->aliasField('subject_weight'),
                'assessment_code' => $ReportStudentAssessmentSummary->aliasField('assessment_code'),
                'assessment_name' => $ReportStudentAssessmentSummary->aliasField('assessment_name'),
                'period_code' => $ReportStudentAssessmentSummary->aliasField('period_code'),
                'period_name' => $ReportStudentAssessmentSummary->aliasField('period_name'),
                'period_weight' => $ReportStudentAssessmentSummary->aliasField('period_weight'),
                'average_marks' => $ReportStudentAssessmentSummary->aliasField('average_mark')//POCOR-6708-alter column name as per table column average_mark
                ])
                ->where([$ReportStudentAssessmentSummary->aliasField('institution_id') => $params['institution_id']])    
                ->where([$ReportStudentAssessmentSummary->aliasField('academic_period_id') => $params['academic_period_id']])    
                ->hydrate(false)
                ->toArray(); 
            $entity = [];
            if(empty($AssessmentSummaryData)){
                return $entity;
            }

            foreach ($AssessmentSummaryData as $e_key => $e_val) {
                $entity[] = [
                    'id' => $e_key,
                    'academic_period_code' => (!empty($e_val['academic_period_code']) ? $e_val['academic_period_code'] : ''),
                    'academic_period_name' => (!empty($e_val['academic_period_name']) ? $e_val['academic_period_name'] : ''),
                    'area_code' => (!empty($e_val['area_code']) ? $e_val['area_code'] : ''),
                    'area_name' => (!empty($e_val['area_name']) ? $e_val['area_name'] : ''),
                    'institution_code' => (!empty($e_val['institution_code']) ? $e_val['institution_code'] : ''),
                    'institution_name' => (!empty($e_val['institution_name']) ? $e_val['institution_name'] : ''),
                    'grade_code' => (!empty($e_val['grade_code']) ? $e_val['grade_code'] : ''),
                    'grade_name' => (!empty($e_val['grade_name']) ? $e_val['grade_name'] : ''),
                    'subject_code' => (!empty($e_val['subject_code']) ? $e_val['subject_code'] : ''),
                    'subject_name' => (!empty($e_val['subject_name']) ? $e_val['subject_name'] : ''),
                    'subject_weight' => (!empty($e_val['subject_weight']) ? $e_val['subject_weight'] : ''),
                    'assessment_code' => (!empty($e_val['assessment_code']) ? $e_val['assessment_code'] : ''),
                    'assessment_name' => (!empty($e_val['assessment_name']) ? $e_val['assessment_name'] : ''),
                    'period_code' => (!empty($e_val['period_code']) ? $e_val['period_code'] : ''),
                    'period_name' => (!empty($e_val['period_name']) ? $e_val['period_name'] : ''),
                    'period_weight' => (!empty($e_val['period_weight']) ? $e_val['period_weight'] : ''),
                    'average_marks' => (!empty($e_val['average_marks']) ? $e_val['average_marks'].' ' : '')
                ];
            }
            return $entity;
        }
    }

    /**
     * Create a placeholder to display custom field values and results in Infrastructure Room Custom Fields
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function onExcelTemplateInitialiseInfrastructureRoomCustomFields(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionRooms = TableRegistry::get('institution_rooms');
            $RoomTypes = TableRegistry::get('room_types');
            $RoomCustomFieldValues = TableRegistry::get('room_custom_field_values');
            $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
            $InstitutionRoomsData = $InstitutionRooms->find()
                ->select([
                    'id' => $InstitutionRooms->aliasField('id'),
                    'code' => $InstitutionRooms->aliasField('code'),
                    'name' => $InstitutionRooms->aliasField('name'),
                    'area' => $InstitutionRooms->aliasField('area'),
                    'room_type' => $RoomTypes->aliasField('name')
                ])
                ->LeftJoin([$RoomTypes->alias() => $RoomTypes->table()], [
                    $InstitutionRooms->aliasField('room_type_id') . '= ' . $RoomTypes->aliasField('id')
                ])
                ->where([$InstitutionRooms->aliasField('institution_id') => $params['institution_id']])    
                ->where([$InstitutionRooms->aliasField('academic_period_id') => $params['academic_period_id']])  
                ->hydrate(false)
                ->toArray()
                ;
            
            $entity = [];
            if(empty($InstitutionRoomsData)){
                return $entity;
            }
            $i=0;
            foreach ($InstitutionRoomsData as $e_key => $e_val) {
                $RoomCustomFieldValuesData = $RoomCustomFieldValues->find()
                        ->select([
                            'id' => $RoomCustomFieldValues->aliasField('id'),
                            'infrastructure_custom_field_id' => $RoomCustomFieldValues->aliasField('infrastructure_custom_field_id'),
                            'custom_field_name' => $InfrastructureCustomFields->aliasField('name')
                        ])
                        ->LeftJoin([$InfrastructureCustomFields->alias() => $InfrastructureCustomFields->table()], [
                            $RoomCustomFieldValues->aliasField('infrastructure_custom_field_id') . '= ' . $InfrastructureCustomFields->aliasField('id')
                        ])
                        ->where([$RoomCustomFieldValues->aliasField('institution_room_id') => $e_val['id']])
                        ->group([$RoomCustomFieldValues->aliasField('infrastructure_custom_field_id')]) 
                        ->hydrate(false)
                        ->toArray(); 
                if(!empty($RoomCustomFieldValuesData)){
                    foreach ($RoomCustomFieldValuesData as $r_key => $r_val) {
                        //get Custom fields Values by room _id and infrastructure_custom_field_id 
                        $val_result = $this->getInfrastructureRoomCustomFieldValues($e_val['id'], $r_val['infrastructure_custom_field_id']);    
                        $entity[$i] = [
                            'id' => $r_val['id'],
                            'code' => (!empty($e_val['code']) ? $e_val['code'] : ''),
                            'name' => (!empty($e_val['name']) ? $e_val['name'] : ''),
                            'area' => (!empty($e_val['area']) ? $e_val['area'] : ''),
                            'room_type' => (!empty($e_val['room_type']) ? $e_val['room_type'] : ''),
                            'infrastructure_custom_field_id' => $r_val['infrastructure_custom_field_id'],
                            'custom_field_name' => $r_val['custom_field_name'],
                            'custom_field_value' => $val_result
                        ];
                        $i++;
                    }                     
                }else{
                    $entity[$i] = [
                        'id' => $e_val['id'],
                        'code' => (!empty($e_val['code']) ? $e_val['code'] : ''),
                        'name' => (!empty($e_val['name']) ? $e_val['name'] : ''),
                        'area' => (!empty($e_val['area']) ? $e_val['area'] : ''),
                        'room_type' => (!empty($e_val['room_type']) ? $e_val['room_type'] : ''),
                        'infrastructure_custom_field_id' => '',
                        'custom_field_name' => '',
                        'custom_field_value' => ''
                    ];
                    $i++;
                }
            }
            return $entity; 
        }
    }
    /**
     * Get Infrastructure Room Custom Field Values using params $room_id & $room_custom_field_id)
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @ticket POCOR-6519
     */
    public function getInfrastructureRoomCustomFieldValues($room_id, $room_custom_field_id){
        $RoomCustomFieldTbl = TableRegistry::get('room_custom_field_values');
        $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
        $InfrastructureCustomFieldOptions = TableRegistry::get('infrastructure_custom_field_options');
        $RoomCustomFieldValues[] = $RoomCustomFieldTbl
                        ->find()
                        ->select([
                                'text_value' => $RoomCustomFieldTbl->aliasField('text_value'),
                                'number_value' => $RoomCustomFieldTbl->aliasField('number_value'),
                                'decimal_value' => $RoomCustomFieldTbl->aliasField('decimal_value'),
                                'textarea_value' => $RoomCustomFieldTbl->aliasField('textarea_value'),
                                'date_value' => $RoomCustomFieldTbl->aliasField('date_value'),
                                'time_value' => $RoomCustomFieldTbl->aliasField('time_value'),
                                'institution_room_id' => $RoomCustomFieldTbl->aliasField('institution_room_id'),
                                'infrastructure_custom_field_id' => $RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id'),
                                'id' => $InfrastructureCustomFields->aliasField('id'),
                                'field_type' => $InfrastructureCustomFields->aliasField('field_type'),
                            ])
                        ->LeftJoin([$InfrastructureCustomFields->alias() => $InfrastructureCustomFields->table()], [
                            $RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id') . '= ' . $InfrastructureCustomFields->aliasField('id')
                        ])
                        ->where([$RoomCustomFieldTbl->aliasField('institution_room_id') => $room_id])
                        ->where([$RoomCustomFieldTbl->aliasField('infrastructure_custom_field_id') => $room_custom_field_id])
                        ->hydrate(false)
                        ->toArray();
        $result = [];
        if(!empty($RoomCustomFieldValues)){
            foreach ($RoomCustomFieldValues as $field_key => $field_val) {
                if($field_val[0]['field_type'] == 'CHECKBOX'){
                    $check_num = [];
                    foreach ($field_val as $f_k => $f_v) {
                        $check_data = $InfrastructureCustomFieldOptions
                                    ->find()
                                    ->select([
                                            'name' => $InfrastructureCustomFieldOptions->aliasField('name')
                                        ])
                                    ->where([$InfrastructureCustomFieldOptions->aliasField('id IN') => $f_v['number_value']])
                                    ->hydrate(false)
                                    ->toArray();
                        $check_num[] = $check_data[0]['name'];
                    }
                    $checkbox = implode(',', $check_num);                    
                    $result['name'] = !empty($checkbox) ? $checkbox : '';
                }else if($field_val[0]['field_type'] == 'TEXT'){
                    $result['name'] = !empty($field_val[0]['text_value']) ? $field_val[0]['text_value'] : ' ';
                }else if($field_val[0]['field_type'] == 'NUMBER'){
                    $result['name'] = !empty($field_val[0]['number_value']) ? $field_val[0]['number_value'].' ' : '0 ';
                }else if($field_val[0]['field_type'] == 'DECIMAL'){
                    $result['name'] = !empty($field_val[0]['decimal_value']) ? $field_val[0]['decimal_value'].' ' : '0.00 ';
                }else if($field_val[0]['field_type'] == 'TEXTAREA'){
                    $result['name'] = !empty($field_val[0]['textarea_value']) ? $field_val[0]['textarea_value'] : '';
                }else if($field_val[0]['field_type'] == 'DROPDOWN'){
                    $check_data = $InfrastructureCustomFieldOptions
                                    ->find()
                                    ->select([
                                            'name' => $InfrastructureCustomFieldOptions->aliasField('name')
                                        ])
                                    ->where([$InfrastructureCustomFieldOptions->aliasField('id IN') => $field_val[0]['number_value']])
                                    ->hydrate(false)
                                    ->toArray();
                    $result['name'] = !empty($check_data[0]['name']) ? $check_data[0]['name'] : '';
                }else if($field_val[0]['field_type'] == 'DATE'){
                    $result['name'] = !empty($field_val[0]['date_value']) ? date("Y-m-d", strtotime($field_val[0]['date_value'])) : '';
                }else if($field_val[0]['field_type'] == 'TIME'){
                    $result['name'] = !empty($field_val[0]['time_value']) ? date("H: i: s", strtotime($field_val[0]['time_value'])) : '';
                }else if($field_val[0]['field_type'] == 'COORDINATES'){
                    if(!empty($field_val[0]['text_value'])){
                        $cordinate = json_decode($field_val[0]['text_value'], true);
                        $result['name'] = 'latitude: '.$cordinate['latitude'] .', longitude: '.$cordinate['longitude'] ;
                    }else{
                        $result['name'] = '';
                    }
                } 
            }
        }  
        return $result['name'];
    }//POCOR-6519 ends

    /**
     * fetching data to display on class profile generated report
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6628
     */
    public function onExcelTemplateInitialiseStudentDetails(Event $event, array $params, ArrayObject $extra)
    {
        ini_set("memory_limit", "1G"); //POCOR-6996
        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('institution_class_id', $params)) {
            $Users = TableRegistry::get('User.Users');
            $studentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsencesDay = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
            $studentAssessmentSummary = TableRegistry::get('report_student_assessment_summary');
            $userIdentity = TableRegistry::get('User.Identities');
            $studentData = $studentAssessmentSummary->find()
                            ->select([
                                'openemis_no' => $Users->aliasField('openemis_no'),
                                'student_name' => $studentAssessmentSummary->aliasField('student_name'),
                                'grade_name' => $studentAssessmentSummary->aliasField('grade_name'),
                                'class_name' => $studentAssessmentSummary->aliasField('institution_classes_name'),
                                'subject_name' => $studentAssessmentSummary->aliasField('subject_name'),
                                'homeroom_teacher' => $studentAssessmentSummary->aliasField('homeroom_teacher_name'),
                                'individual_result' => $studentAssessmentSummary->aliasField('latest_mark'),
                                'avg_marks' => $studentAssessmentSummary->aliasField('average_mark'),
                                'student_id' => $studentAssessmentSummary->aliasField('student_id'),
                                'institution_average_mark' => $studentAssessmentSummary->aliasField('institution_average_mark'),
                                'area_average_mark' => $studentAssessmentSummary->aliasField('area_average_mark') 
                            ])
                            ->innerJoin([$Users->alias() => $Users->table()], [
                                $studentAssessmentSummary->aliasField('student_id ='). $Users->aliasField('id')
                            ])
                            ->order([$studentAssessmentSummary->aliasField('student_name')])  
                            ->where([
                                $studentAssessmentSummary->aliasField('academic_period_id') => $params['academic_period_id'],
                                $studentAssessmentSummary->aliasField('institution_id') => $params['institution_id'],
                                $studentAssessmentSummary->aliasField('institution_classes_id') => $params['institution_class_id']
                            ])
                            ->hydrate(false)
                            ->toArray();
            
            $result = [];
            $entity = [];
            if (!empty($studentData)) {
               foreach ($studentData as $key => $data) {
                    $identityObj = $userIdentity->find()
                                    ->select(['identity_number' => $userIdentity->aliasField('number')])
                                    ->leftJoin(['IdentityTypes' => 'identity_types'], [
                                        'IdentityTypes.id = '. $userIdentity->aliasField('identity_type_id'),
                                        'IdentityTypes.default =' . 1
                                    ])
                                    ->where([$userIdentity->aliasField('security_user_id') => $data['student_id']])
                                    ->hydrate(false)
                                    ->first();
                    $absenceDays = $studentAbsencesDay->find()
                                        ->select(['absent_days' => $studentAbsencesDay->aliasField('absent_days')])
                                        ->where([
                                            $studentAbsencesDay->aliasField('institution_id') => $params['institution_id'],
                                            $studentAbsencesDay->aliasField('student_id') => $data['student_id']
                                        ])
                                        ->hydrate(false)
                                        ->toArray();
                    $absenceDaysArr = [];
                    if (!empty($absenceDays)) {
                        foreach ($absenceDays as $days) {
                            $absenceDaysArr[] = $days['absent_days'];
                        }
                    }
                    $absenceDaysCount = array_sum($absenceDaysArr);
                    $result = [
                        'id' => $key,
                        'grade_name' => !empty($data['grade_name']) ? $data['grade_name'] : '',
                        'openemis_no' => !empty($data['openemis_no']) ? $data['openemis_no'] : '',
                        'identity_number' => !empty($identityObj['identity_number']) ? $identityObj['identity_number'] : '',
                        'student_name' => $data['student_name'],
                        'class_name' => !empty($data['class_name']) ? $data['class_name'] : '',
                        'subject_name' => !empty($data['subject_name']) ? $data['subject_name'] : '',
                        'homeroom_teacher' => !empty($data['homeroom_teacher']) ? $data['homeroom_teacher'] : '',
                        'absence_day' => !empty($absenceDaysCount) ? $absenceDaysCount : 0,
                        'individual_result' => !empty($data['individual_result']) ? $data['individual_result'] : 0,
                        'average_marks' => $data['avg_marks'],
                        'institution_average_mark' => $data['institution_average_mark'],
                        'area_average_mark' => $data['area_average_mark']
                    ];
                    $entity[] = $result;
                }
            } 
            
            return $entity;
        }
    }
}
