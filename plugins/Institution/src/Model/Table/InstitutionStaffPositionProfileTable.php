<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

/**
 * Get the Staff  details in excel file 
 */
class InstitutionStaffPositionProfileTable extends AppTable
{

    public function initialize(array $config)
    {
        $this->table('security_users');
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
        //print_r($requestData->academic_period_id);die;
        $institutionId         = $requestData->institution_id;
        $subject = TableRegistry::get('Institution.InstitutionSubjects');
        $academic_period = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institution = TableRegistry::get('Institutions');
        $staffStatus = TableRegistry::get('Staff.StaffStatuses');
       // $positions = TableRegistry::get('Institution.InstitutionPositions');
            $query
            ->select([
                'academic_period'=> 'AcademicPeriods.name',
                'class_name' => 'InstitutionClasses.name',
                'subject_name' => 'InstitutionSubjects.name',
                'absences_day' => $this->find()->func()->sum('InstitutionStaffLeave.number_of_days'),
                'openemis_no'=> $this->aliasfield('openemis_no'),
                'institution'=> 'Institutions.name',
                'first_name'=> $this->aliasField('first_name'),
               // 'last_name' => $this->aliasField('last_name'),
                'identity_number' => $this->aliasField('identity_number'),
              //  'identityType' => 'IdentityTypes.name',
                'fte' => 'InstitutionStaff.FTE',
                'staffStatus' => 'StaffStatuses.name',
               // 'positionsNumber' => 'InstitutionPositions.position_no',
                //'is_home' => 'InstitutionPositions.is_homeroom',
                //'assigneeName' => $this->aliasField('first_name'),
            ])

            /*->contain([
                'IdentityTypes' => [
                    'fields' => [
                        'IdentityTypes.id'
                    ]
                ],
            ])*/
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
            /*->leftJoin(
                [$positions->alias() => $positions->table()],
                [$positions->aliasField('assignee_id = ') . $this->aliasfield('id')]
            )
            ->leftJoin(
                [$positions->alias() => $positions->table()],
                [$positions->aliasField('id = ') . 'InstitutionStaff.institution_position_id']
            )*/
            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            $this->aliasfield('id') . ' = '.'InstitutionSubjectStaff.staff_id',
                        ])
            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                            $this->aliasfield('id') . ' = '.'InstitutionClasses.staff_id',
                        ])
            ->leftJoin(
                [$subject->alias() => $subject->table()],
                [$subject->aliasField('id = ') . 'InstitutionSubjectStaff.institution_subject_id']
            )
            ->leftJoin(
                [$academic_period->alias() => $academic_period->table()],
                [$academic_period->aliasField('id = ') . 'InstitutionClasses.academic_period_id']
            )

            ->leftJoin(['InstitutionStaffLeave' => ' institution_staff_leave'], [
                            $this->aliasfield('id') . ' = '.'InstitutionStaffLeave.staff_id',
                        ])
            
        ->where([
            //'InstitutionClasses.academic_period_id' => $academicPeriodId,
            //'InstitutionStaff.institution_id' => $institutionId,
           // 'SecurityGroupUsers.security_role_id'=>[5,6]
            $this->aliasField('is_staff') => 1,
        ]);
        print_r($query->Sql());die('pkk');
    
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
       // print_r($fields);die;
        $newFields = [];
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
            'key'   => 'absences_day',
            'field' => 'absences_day',
            'type'  => 'integer',
            'label' => __('Number of absences Day'),
        ];
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'integer',
            'label' => __('openemis_no'),
        ];$newFields[] = [
            'key'   => 'institution',
            'field' => 'institution',
            'type'  => 'string',
            'label' => __('institution'),
        ];$newFields[] = [
            'key'   => 'identity_number',
            'field' => 'identity_number',
            'type'  => 'integer',
            'label' => __('identity_number'),
        ];$newFields[] = [
            'key'   => 'fte',
            'field' => 'fte',
            'type'  => 'integer',
            'label' => __('fte'),
        ];
        $newFields[] = [
            'key'   => 'staffStatus',
            'field' => 'staffStatus',
            'type'  => 'staring',
            'label' => __('staffStatus'),
        ];
        $fields->exchangeArray($newFields);
    }

    
}
