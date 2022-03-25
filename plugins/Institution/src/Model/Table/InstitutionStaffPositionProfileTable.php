<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * Get the Staff  details in excel file 
 * POCOR-6581
 */
class InstitutionStaffPositionProfileTable extends AppTable
{

    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
        $this->belongsTo('Nationalities', ['className' => 'FieldOption.Nationalities']);
        $this->hasMany('InstitutionStaffLeave', ['className' => 'Institution.InstitutionStaffLeave']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.InstitutionStaff']);
        parent::initialize($config);
        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');

    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
        $reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $subject = TableRegistry::get('Institution.InstitutionSubjects');
        $academic_period = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institution = TableRegistry::get('Institutions');
        $staffStatus = TableRegistry::get('Staff.StaffStatuses');
        $positions = TableRegistry::get('Institution.InstitutionPositions');
        $grade = TableRegistry::get('Institution.StaffPositionGrades');
        $title = TableRegistry::get('Institution.StaffPositionTitles');
        $SecurityUser = TableRegistry::get('User.Users');
            $query
            ->select([
                $this->aliasField('id'),
                'positionsNumber' => 'InstitutionPositions.position_no',
                'title' => 'StaffPositionTitles.name',
                'positionsType' => 'StaffPositionTitles.type',
                'grade' => 'StaffPositionGrades.name',
                'institution'=> 'Institutions.name',
                'is_home' => $positions->aliasField('is_homeroom'),
                'openemis_no'=> $this->aliasfield('openemis_no'),
                'fname_Staff'=> $this->aliasField('first_name'),
                'mname_Staff'=> $this->aliasField('middle_name'),
                'tname_Staff'=> $this->aliasField('third_name'),
                'lname_Staff' => $this->aliasField('last_name'), 
                'fte' => 'InstitutionStaff.FTE',
                'staff_id' => 'InstitutionStaff.staff_id',
                'staffStatus' => $staffStatus->aliasField('name'),
                'identityType' => 'IdentityTypes.name',
                'identity_number' => $this->aliasField('identity_number'),
                'academic_period'=> 'AcademicPeriods.name',
                'class_name' => 'InstitutionClasses.name',
                'subject_name' => 'InstitutionSubjects.name',
               // 'absences_day' => $this->find()->func()->sum('InstitutionStaffLeave.number_of_days'),
                'assigneefName' => $SecurityUser->aliasField('first_name'),
                'assigneemName' => $SecurityUser->aliasField('middle_name'),
                'assigneetName' => $SecurityUser->aliasField('third_name'),
                'assigneelName' => $SecurityUser->aliasField('last_name'),
                      
            ])
            ->contain([
                'IdentityTypes' => [
                    'fields' => [
                        'IdentityTypes.name'
                    ]
                ],
            ])
            
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                            $this->aliasfield('id') . ' = '.'InstitutionStaff.staff_id',
                        ])
            ->leftJoin(
                [$institution->alias() => $institution->table()],
                [$institution->aliasField('id = ') . 'InstitutionStaff.institution_id']
            )
            ->leftJoin(
                [$staffStatus->alias() => $staffStatus->table()],
                [$staffStatus->aliasField('id = ') . 'InstitutionStaff.staff_status_id']
            )
            ->leftJoin(
                [$positions->alias() => $positions->table()],
                [$positions->aliasField('id = ') . 'InstitutionStaff.institution_position_id']
            )
            ->leftJoin(
                [$grade->alias() => $grade->table()],
                [$grade->aliasField('id = ') . 'InstitutionPositions.staff_position_grade_id']
            )
            ->leftJoin(
                [$title->alias() => $title->table()],
                [$title->aliasField('id = ') . 'InstitutionPositions.staff_position_title_id']
            )
            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            'InstitutionStaff.staff_id' . ' = '.'InstitutionSubjectStaff.staff_id',
                        ])
            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                            'InstitutionStaff.staff_id' . ' = '.'InstitutionClasses.staff_id',
                        ])
            ->leftJoin(
                [$subject->alias() => $subject->table()],
                [$subject->aliasField('id = ') . 'InstitutionSubjectStaff.institution_subject_id']
            )
            ->leftJoin(
                [$academic_period->alias() => $academic_period->table()],
                [$academic_period->aliasField('id = ') . 'InstitutionClasses.academic_period_id']
            )

            /*->leftJoin(['InstitutionStaffLeave' => ' institution_staff_leave'], [
                            'InstitutionStaff.staff_id' . ' = '.'InstitutionStaffLeave.staff_id',
                        ])*/
            ->leftJoin(
                [$SecurityUser->alias() => $SecurityUser->table()],
                [$SecurityUser->aliasField('id = ') . 'InstitutionPositions.assignee_id']
            )
            //->group(['InstitutionStaff.staff_id'])
        ->where([
            'InstitutionClasses.academic_period_id' => $academicPeriodId,
            'InstitutionStaff.institution_id' => $institutionId,
            $this->aliasField('is_staff') => 1,
        ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        {
            return $results->map(function ($row)
            {
                    $row['referrer_full_name'] = $row['fname_Staff'] .' '. $row['lname_Staff'];
                    if($row['is_home']==1){
                        $row['referrer_is_home'] = 'Yes';
                    }else{
                        $row['referrer_is_home'] = 'No';
                    }
                    if($row['positionsType']==1){
                        $row['referrer_is_type'] = 'Teaching';
                    }else{
                        $row['referrer_is_type'] = 'Non-Teaching';
                    }
                    $row['referrer_position_type'] = $row['title'] .'-'. $row['referrer_is_type'];

                    $row['assignee_user_full_name'] = $row['assigneefName'] .' '. $row['assigneelName'];
                return $row;
            });
        });
    
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'positionsNumber',
            'field' => 'positionsNumber',
            'type'  => 'string',
            'label' => __('Number'),
        ];
        $newFields[] = [
            'key'   => 'referrer_position_type',
            'field' => 'referrer_position_type',
            'type'  => 'string',
            'label' => __('Title'),
        ];
        $newFields[] = [
            'key'   => 'grade',
            'field' => 'grade',
            'type'  => 'string',
            'label' => __('Grade'),
        ];
        $newFields[] = [
            'key'   => 'institution',
            'field' => 'institution',
            'type'  => 'string',
            'label' => __('Institution'),
        ];
        $newFields[] = [
            'key'   => 'assignee_user_full_name',
            'field' => 'assignee_user_full_name',
            'type'  => 'string',
            'label' => __('Assignee'),
        ];
        $newFields[] = [
            'key'   => 'referrer_is_home',
            'field' => 'referrer_is_home',
            'type'  => 'string',
            'label' => __('Homeroom Teacher'),
        ];
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'integer',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Staff'),
        ];
        $newFields[] = [
            'key'   => 'fte',
            'field' => 'fte',
            'type'  => 'integer',
            'label' => __('FTE'),
        ];
        $newFields[] = [
            'key'   => 'staffStatus',
            'field' => 'staffStatus',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $newFields[] = [
            'key'   => 'identityType',
            'field' => 'identityType',
            'type'  => 'string',
            'label' => __('Identity Type'),
        ];
        
        $newFields[] = [
            'key'   => 'identity_number',
            'field' => 'identity_number',
            'type'  => 'integer',
            'label' => __('Identity Number'),
        ];
        
        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'class_name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Classes (homeroom teacher)'),
        ];
        $newFields[] = [
            'key'   => 'subject_name',
            'field' => 'subject_name',
            'type'  => 'string',
            'label' => __('Subject (if he is a teacher)'),
        ];
        $newFields[] = [
            'key'   => 'staff_absence_day',
            'field' => 'staff_absence_day',
            'type'  => 'integer',
            'label' => __('Number of absence Day'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * Get staff absences days
     */
    public function onExcelGetStaffAbsenceDay(Event $event, Entity $entity)
    {
        $userid =  $entity->staff_id;
        $Institutionstaff = TableRegistry::get('Institution.InstitutionStaff');
        $staffleave = TableRegistry::get('Institution.InstitutionStaffLeave');
        $absenceDay = $staffleave->find()
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], ['InstitutionStaff.staff_id = '. $staffleave->aliasField('staff_id')])
            ->select([
                'days' => "SUM(".$staffleave->aliasField('number_of_days').")"

            ])
           // ->group(['InstitutionStaffLeave.staff_id'])
            ->where([$staffleave->aliasField('staff_id') => $userid]);
            if($absenceDay!=null){
                $data = $absenceDay->toArray();
                $entity->staff_absence_day = '';
                foreach($data as $key=>$val){
                    $entity->staff_absence_day = $val['days'];
                }
                 return $entity->staff_absence_day;
            }
            return '';
    }

    
}
