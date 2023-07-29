<?php

namespace Staff\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class ArchivedAttendancesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $institutionId = null;
    private $staffId = null;

    public function initialize(array $config)
    {
        $config['Modified'] = false;
        $config['Created'] = false;
        $table_name = 'institution_staff_attendances';
        $targetTableNameAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
        $targetTableName = $targetTableNameAndConnection[0];
        $targetTableConnection = $targetTableNameAndConnection[1];
        $remoteConnection = ConnectionManager::get($targetTableConnection);
        $this->connectionName = $targetTableConnection;
        $this->connection($remoteConnection);
        $this->table($targetTableName);
        parent::initialize($config);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('view', false);
        $this->toggle('search', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        $this->field('Date', ['visible' => true]);
        $this->field('absence_type_id', ['visible' => false]);

        $this->setFieldOrder(['Date', 'time_in', 'time_out', 'comment']);
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

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->staffId;
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAttendances');
    }


    /**
     * common proc to get/set main variables to use further
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setInstitutionStaffIDs()
    {

        $institutionId = $staffId = null;
        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        if (!is_null($this->request->query('user_id'))) {
            $staffId = $this->request->query('user_id');
        }
        if (!$staffId) {
            if (!is_null($this->request->query('staff_id'))) {
                $staffId = $this->request->query('staff_id');
            }
        }
        if (!$staffId) {
            if ($session->check('Institution.Staff.id')) {
                if (is_numeric($session->read('Institution.Staff.id'))) {
                    $staffId = $session->read('Institution.Staff.id');
                }
            }
        }
        if (!$staffId) {
            if ($session->check('Staff.Staff.id')) {
                if (is_numeric($session->read('Staff.Staff.id'))) {
                    $staffId = $session->read('Staff.Staff.id');
                }
            }
        }
//        if ($session->check('Directory.Staff.id')) {
//            $staffId = $session->read('Directory.Staff.id');
//        }
        $this->institutionId = $institutionId;
        $this->staffId = $staffId;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $this->setInstitutionStaffIDs();
        $selectedPeriod = $this->setAcademicPeriodsOptions($query);
        $requestWeek = $this->request->query('week');
        if ($selectedPeriod != 0) {
            $conditions = $this->setWeekOptions($query, $selectedPeriod, $requestWeek);
            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];
            $query->select(['date', 'id', 'staff_id', 'time_in', 'time_out'])
                ->find('all')
                ->where($conditions);
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'created_user_id') {
            return __('Last Modified By');
        } else if ($field == 'created') {
            return __('Last Modified On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetDate(Event $event, Entity $entity)
    {
//        $this->log('$entity->toArray()', 'debug');
//        $this->log($entity->toArray(), 'debug');
        $thisdate = $entity->date;
        $datestr = $thisdate->format('Y-m-d');
        return $datestr;
    }


    /**
     * common proc to show related field in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->name);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
    }


    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getRelatedNameWithId($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->nameWithId);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
        return $name;
    }


    /**
     * @param Query $query
     * @return array
     */
    private function setAcademicPeriodsOptions(Query $query)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $institutionId = $this->institutionId;
        $staffId = $this->staffId;
        $distinctYearQuery = clone $query;
        $distinctYears = $distinctYearQuery->find('all')
            ->where(['institution_id' => $institutionId,
                'staff_id' => $staffId])
            ->select(['academic_period_id'])
            ->distinct(['academic_period_id'])
            ->toArray();
        $distinctYearValues = array_column($distinctYears, 'academic_period_id');
//        $this->log('$distinctYearValues', 'debug');
//        $this->log($distinctYearValues, 'debug');
//        $uniqu_array = sort(array_unique($distinctYearValues));
        $uniqu_array = array_unique($distinctYearValues);
        $selectedYear = end($uniqu_array);

        $periodOptions = $AcademicPeriod->getArchivedYearList($uniqu_array);
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $selectedYear;
        }
        $selectedPeriod = $this->request->query['academic_period_id'];
        $this->advancedSelectOptions($periodOptions, $selectedPeriod);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // To add the academic_period_id to export
        // if (isset($extra['toolbarButtons']['export']['url'])) {
        //     $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedPeriod;
        // }

        $this->request->query['academic_period_id'] = $selectedPeriod;
        return $selectedPeriod;

    }

    /**
     * @param Query $query
     * @param $selectedPeriod
     */
    private function setWeekOptions(Query $query, $selectedPeriod, $requestweek)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institutionId = $this->institutionId;
        $staffId = $this->staffId;
        $todayDate = date("Y-m-d");


        // Setup week options
        $weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);

        $distinctQuery = clone $query;

// Modify the cloned query to select distinct dates
        $distinctDates = $distinctQuery->find('all')
            ->where(['institution_id' => $institutionId,
                'staff_id' => $staffId])
            ->select(['date'])
            ->distinct(['date'])
            ->toArray();
        $distinctDateValues = array_column($distinctDates, 'date');
//        $this->log('$distinctDateValues', 'debug');
//        $this->log($distinctDateValues, 'debug');
// Execute the cloned query and fetch the distinct dates

// Get the array of distinct date values
        $distinctDateValues = array_column($distinctDates, 'date');

// Filter the weeks array based on the distinct dates
        $weekOptions = [];
        $currentWeek = null;
        foreach ($weeks as $index => $dates) {
            if ($todayDate >= $dates[0]->format('Y-m-d') && $todayDate <= $dates[1]->format('Y-m-d')) {
                $weekStr = __('Current Week') . ' %d (%s - %s)';
                $currentWeek = $index;
            } else {
                $weekStr = __('Week') . ' %d (%s - %s)';
            }
            foreach ($distinctDateValues as $distinctDateValue) {
                if ($distinctDateValue >= $dates[0] && $distinctDateValue <= $dates[1]) {
                    $weekOptions[$index] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                }
            }
        }
        $weekOptions = ['-1' => __('All Weeks')] + $weekOptions;
        $conditions = [
            $this->aliasField('academic_period_id') => $selectedPeriod,
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('staff_id') => $staffId,
        ];

        if (!empty($requestweek) && $requestweek != '-1') {
            $academicPeriodObj = $AcademicPeriod->get($selectedPeriod);
            $startYear = $academicPeriodObj->start_year;
            $endYear = $academicPeriodObj->end_year;
            if (date("Y") >= $startYear && date("Y") <= $endYear && !is_null($currentWeek)) {
                $selectedWeek = !is_null($requestweek) ? $requestweek : $currentWeek;
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
        } else {
            $conditions = [
                $this->aliasField('academic_period_id') => $selectedPeriod,
                $this->aliasField('institution_id') => $institutionId,
            ];
        }
        $this->advancedSelectOptions($weekOptions, $selectedWeek);
        $this->controller->set(compact('weekOptions', 'selectedWeek'));
        return $conditions;
    }
}
