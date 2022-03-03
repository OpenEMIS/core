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
 * Get the Staff Training details in excel file with specific tabs
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * @ticket POCOR-6548
 */
class InstitutionStaffPositionProfileTable extends AppTable
{
    private $_type = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
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
        $sheet_tabs = [
            'StaffPosition'
        ];
        foreach($sheet_tabs as $sheet_tab_name) {  
            $sheets[] = [
                'sheetData'   => ['student_tabs_type' => $sheet_tab_name],
                'name'        => $sheet_tab_name,
                'table'       => $this,
                'query'       => $this->find(),
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $sheetData             = $settings['sheet']['sheetData'];
        $sheet_tab_name        = $sheetData['student_tabs_type'];
        $academicPeriodId      = $requestData->academic_period_id;
        //print_r($requestData->academic_period_id);die;
        $institutionId         = $requestData->institution_id;
        $selectable            = [];
        $group_by              = [];
        $subject = TableRegistry::get('Institution.InstitutionSubjects');
        $academic_period = TableRegistry::get('AcademicPeriods');
        if ( $sheet_tab_name == 'StaffPosition' ) {
            $query
            ->select([
                'staff_id'=> $this->aliasfield('staff_id'),
                'academic_period'=> 'AcademicPeriods.name',
                'class_name' => 'InstitutionClasses.name',
                'subject_name' => 'InstitutionSubjects.name',
                'absences_day' => $this->find()->func()->sum('InstitutionStaffLeave.number_of_days'),
            ])
            ->contain([
                'SecurityGroupUsers' => [
                    'fields' => [
                        'SecurityGroupUsers.id'
                    ]
                ],
            ])
            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            $this->aliasfield('staff_id') . ' = '.'InstitutionSubjectStaff.staff_id',
                        ])
            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                            $this->aliasfield('staff_id') . ' = '.'InstitutionClasses.staff_id',
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
                            $this->aliasfield('staff_id') . ' = '.'InstitutionStaffLeave.staff_id',
                        ])
            ->group('staff_id')
        ->where([
            'InstitutionClasses.academic_period_id' => $academicPeriodId,
            $this->aliasfield('institution_id') => $institutionId,
            'SecurityGroupUsers.security_role_id'=>[5,6]
        ]);
    }
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'string',
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
        $fields->exchangeArray($newFields);
    }

    
}
