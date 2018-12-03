<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class InstitutionStaffAttendanceActivitiesTable extends ControllerActionTable 
{
    private $allDayOptions = [];
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users',       ['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Activity');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('view', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('field');
        $this->field('old_value', ['sort' => false]);
        $this->field('new_value', ['sort' => false]);
        $this->field('created', ['sort' => false]);
        $this->field('created_user_id');
        $this->field('model', ['visible' => false]);

        $this->setFieldOrder(['field', 'old_value', 'new_value', 'created_user_id', 'created']);
        $toolbarButtons = $extra['toolbarButtons'];
        $extra['toolbarButtons']['back'] = [
            'url' => [
                'plugin' => 'Staff',
                'controller' => 'Staff',
                'action' => 'StaffAttendances',
                '0' => 'index',
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ]
        ];
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

        $periodOptions = $AcademicPeriod->getYearList();

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

            $this->advancedSelectOptions($weekOptions, $selectedWeek);
            $this->controller->set(compact('weekOptions', 'selectedWeek'));
            // end setup weeks

            // Setup day options
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');
            $schooldays = [];

            for ($i=0; $i<$daysPerWeek; $i++) {
                // sunday should be '7' in order to be displayed
                $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
            }

            $week = $weeks[$selectedWeek];
            if (is_null($this->request->query('mode'))) {
                $dayOptions = [-1 => ['value' => -1, 'text' => __('All Days')]];
            }
            $firstDayOfWeek = $week[0]->copy();
            $firstDay = -1;
            $today = null;

            do {
                if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
                    if ($firstDay == -1) {
                        $firstDay = $firstDayOfWeek->dayOfWeek;
                    }
                    if ($firstDayOfWeek->isToday()) {
                        $today = $firstDayOfWeek->dayOfWeek;
                    }
                    $dayOptions[$firstDayOfWeek->dayOfWeek] = [
                        'value' => $firstDayOfWeek->dayOfWeek,
                        'text' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ')',
                    ];
                    $this->allDayOptions[strtolower($firstDayOfWeek->format('l'))] = [
                        'date' => $firstDayOfWeek->format('Y-m-d'),
                        'text' => __($firstDayOfWeek->format('l'))
                    ];
                }
                $firstDayOfWeek->addDay();
            } while ($firstDayOfWeek->lte($week[1]));

            $selectedDay = -1;
            if (isset($this->request->query['day'])) {
                $selectedDay = $this->request->query('day');
                if (!array_key_exists($selectedDay, $dayOptions)) {
                    $selectedDay = $firstDay;
                }
            } else {
                if (!is_null($today)) {
                    $selectedDay = $today;
                } else {
                    $selectedDay = $firstDay;
                }
            }
            $dayOptions[$selectedDay][] = 'selected';

            $currentDay = $week[0]->copy();
            if ($selectedDay != -1) {
                if ($currentDay->dayOfWeek != $selectedDay) {
                    $this->selectedDate = $currentDay->next($selectedDay);
                } else {
                    $this->selectedDate = $currentDay;
                }
            } else {
                $this->selectedDate = $week;
            }
            $this->controller->set(compact('dayOptions', 'selectedDay'));
            // End setup days

            $conditions = [
                    $InstitutionStaffAttendances->aliasField('academic_period_id') => $selectedPeriod,
                    $InstitutionStaffAttendances->aliasField('institution_id') => $institutionId,
                ];
            if ($selectedDay == -1) {
                $startDate = $weekStartDate;
                $endDate = $weekEndDate;
                $selectedFormatStartDate = date_format($startDate, 'Y-m-d');
                $selectedFormatEndDate = date_format($endDate, 'Y-m-d');
                $dateConditions = [
                    $InstitutionStaffAttendances->aliasField('date >=') => $selectedFormatStartDate,
                    $InstitutionStaffAttendances->aliasField('date <=') => $selectedFormatEndDate
                ];
                $conditions = array_merge($conditions, $dateConditions);
            } else {
                $startDate = $this->selectedDate;
                $endDate = $startDate;

                $selectedFormatStartDate = date_format($startDate, 'Y-m-d');
                $dateConditions = [
                    $InstitutionStaffAttendances->aliasField('date') => $selectedFormatStartDate
                ];
                $conditions = array_merge($conditions, $dateConditions);
            }

            $query
                ->find('all')
                ->innerJoin([$InstitutionStaffAttendances->alias() => $InstitutionStaffAttendances->table()], [
                    $this->aliasField('model_reference = ') .  $InstitutionStaffAttendances->aliasField('id'),
                ])
                ->where($conditions);

            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'created_user_id') {
            return __('Last Modified By');
        } else if ($field == 'created') {
            return  __('Last Modified On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
