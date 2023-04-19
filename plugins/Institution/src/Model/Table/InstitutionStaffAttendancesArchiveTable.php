<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class InstitutionStaffAttendancesArchiveTable extends ControllerActionTable 
{
    private $allDayOptions = [];
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users',       ['className' => 'User.Users', 'foreignKey'=>'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Activity');
        ini_set("memory_limit", "2048M");
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'autoFields' => false
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('view', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('absence_type_id', ['visible' => false]);
        $this->field('openemis_no');
        $this->field('institution_name');
        $this->field('leave');
        $this->field('name');
        $this->field('staff_id', ['visible' => false]);

        // $this->field('institution_id');
        $this->setFieldOrder(['institution_name', 'date','openemis_no','name', 'time_in','time_out',  'leave',]);
        $toolbarButtons = $extra['toolbarButtons'];
        // $extra['toolbarButtons']['back'] = [
        //     'url' => [
        //         'plugin' => 'Student',
        //         'controller' => 'Students',
        //         'action' => 'Absences',
        //         '0' => 'index',
        //     ],
        //     'type' => 'button',
        //     'label' => '<i class="fa kd-back"></i>',
        //     'attr' => [
        //         'class' => 'btn btn-xs btn-default',
        //         'data-toggle' => 'tooltip',
        //         'data-placement' => 'bottom',
        //         'escape' => false,
        //         'title' => __('Back')
        //     ]
        // ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Setup period options
        $InstitutionStaffAttendances = TableRegistry::get('Staff.InstitutionStaffAttendances');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $institutionId = $this->Session->read('Institution.Institutions.id');
        if ($this->request->query('user_id') !== null) {
            $staffId = $this->request->query('user_id');
            $this->Session->write('Staff.Staff.id', $staffId);
        } else {
            $staffId = $this->Session->read('Staff.Staff.id');
        }

        $academic_period_result = $this->find('all', array(
            'fields'=>'academic_period_id',
            'group' => 'academic_period_id'
        ));
        if(!empty($academic_period_result)){
            foreach($academic_period_result AS $academic_period_data){
                $archived_academic_period_arr[] = $academic_period_data['academic_period_id'];
            }
        }

        $periodOptions = $AcademicPeriod->getArchivedYearList($archived_academic_period_arr);
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->request->query['academic_period_id'];
        // To add the academic_period_id to export
        // if (isset($extra['toolbarButtons']['export']['url'])) {
        //     $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedPeriod;
        // }

        $this->request->query['academic_period_id'] = $selectedPeriod;
        $this->advancedSelectOptions($periodOptions, $selectedPeriod);
        // End setup periods

        if ($selectedPeriod != 0) {
            $todayDate = date("Y-m-d");
            $this->controller->set(compact('periodOptions', 'selectedPeriod'));

            // Setup week options
            $weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
            $weekOptions = [];
            $currentWeek = null;
            foreach ($weeks as $index => $dates) {
                if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
                    $weekStr = __('Current Week') . ' %d (%s - %s)';
                    $currentWeek = $index;
                } else {
                    $weekStr = __('Week').' %d (%s - %s)';
                }
                $weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
            }
            $weekOptions = ['-1' => __('All Weeks')] + $weekOptions;
            $conditions = [
                $this->aliasField('academic_period_id') => $selectedPeriod,
                $this->aliasField('institution_id') => $institutionId,
                ];
            if(!empty($this->request->query('week')) && $this->request->query('week') != '-1'){
                $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
                $startYear = $academicPeriodObj->start_year;
                $endYear = $academicPeriodObj->end_year;
                if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentWeek)) {
                    $selectedWeek = !is_null($this->request->query('week')) ? $this->request->query('week') : $currentWeek;
                } else {
                    $selectedWeek = $this->queryString('week', $weekOptions);
                }

                $weekStartDate = $weeks[$selectedWeek][0];
                $weekEndDate = $weeks[$selectedWeek][1];
                // end setup weeks
                $startDate = $weekStartDate;
                $endDate = $weekEndDate;
                $selectedFormatStartDate = date_format($startDate, 'Y-m-d');
                $selectedFormatEndDate = date_format($endDate, 'Y-m-d');
                $dateConditions = [
                    $this->aliasField('date >=') => $selectedFormatStartDate,
                    $this->aliasField('date <=') => $selectedFormatEndDate
                ];
                $conditions = array_merge($conditions, $dateConditions);
            }else{
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            $this->advancedSelectOptions($weekOptions, $selectedWeek);
                $this->controller->set(compact('weekOptions', 'selectedWeek'));
            $StaffLeaveTable = TableRegistry::get('Institution.StaffLeave');
            $StaffLeaveTypesTable = TableRegistry::get('Staff.StaffLeaveTypes');
            $StaffUser = TableRegistry::get('User.Users');
            $query
            ->select([
                'institution_id' => $this->aliasField('institution_id'),
                'date' => $this->aliasField('date'),
                'time_in' => $this->aliasField('time_in'),
                'time_out' => $this->aliasField('time_out'),
                'comment' => $this->aliasField('comment'),
                'staff_id' => $this->aliasField('staff_id'),
                'leave' => $StaffLeaveTypesTable->aliasField('name'),
                'name' => $StaffUser->find()->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
                'openemis_no' => $StaffUser->aliasField('openemis_no')

            ])
            ->leftJoin(
                [$StaffUser->alias() => $StaffUser->table()], [
                    $this->aliasField('staff_id = ') . $StaffUser->aliasField('id')
                ]
            )
            ->leftJoin(
                [$StaffLeaveTable->alias() => $StaffLeaveTable->table()], [
                    $this->aliasField('staff_id = ') . $StaffLeaveTable->aliasField('staff_id')
                ]
            )
            ->leftJoin(
                [$StaffLeaveTypesTable->alias() => $StaffLeaveTypesTable->table()], [
                    $StaffLeaveTable->aliasField('staff_leave_type_id = ') . $StaffLeaveTypesTable->aliasField('id')
                ]
            )
            ->where($conditions);
            // echo "<pre>";print_r($query->toArray());die;

            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $data = $this->request->query;
        $academic_period_id = $data['academic_period_id'];
        $selected_week = $data['selected_week'];
        $selected_day = $data['selected_day'];
        if ($selected_day instanceof Time || $selected_day instanceof Date) {
            $selected_day = $selected_day->format('Y-m-d');
        } else {
            $selected_day = date('Y-m-d', strtotime($selected_day));
        }
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query->where([
            $this->aliasField('institution_id')=>$institutionId,
            $this->aliasField('academic_period_id')=>$academic_period_id,
            $this->aliasField('date')=>$selected_day
        ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $UserData = TableRegistry::get('User.Users');
                $UserDataRow = $UserData
                            ->find()
                            ->where([$UserData->alias('id')=>$row->staff_id])
                            ->first();

                $StaffLeaveTable = TableRegistry::get('Institution.StaffLeave');
                $StaffLeaveTypes = $StaffLeaveTable
                    ->find()
                    ->matching('StaffLeaveTypes')
                    ->where([
                        $StaffLeaveTable->aliasField('staff_id') => $row->staff_id,
                    ])
                    ->first();

                $firstName = $this->Auth->user('first_name');
                $lastName = $this->Auth->user('last_name');
                $name = $UserDataRow->first_name . " " . $UserDataRow->last_name;
                $row['name'] = $name;
                $row['openemis_no'] = $UserDataRow->openemis_no;
                $row['time_in'] = isset($row->time_in) ? $row->time_in->format('h:i:s') : '';
                $row['time_out'] = isset($row->time_out) ? $row->time_out->format('h:i:s') : '';
                $row['leave_types'] = $StaffLeaveTypes->_matchingData['StaffLeaveTypes']->name;
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffAttendancesArchive.date',
            'field' => 'date',
            'type' => 'date',
            'label' => 'Date',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'integer',
            'label' => 'Name',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'time_in',
            'type' => 'string',
            'label' => 'Time In',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'time_out',
            'type' => 'string',
            'label' => 'Time Out'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'leave_types',
            'type' => 'text',
            'label' => 'Leave',
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffAttendancesArchive.comment',
            'field' => 'comment',
            'type' => 'text',
            'label' => 'Comment',
        ];

        $fields->exchangeArray($newFields);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetInstitutionName(Event $event, Entity $entity)
    {
        return $entity->institution->name;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_name') {
            return __('Institution');
        } else if ($field == 'staff_id') {
            return  __('Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
