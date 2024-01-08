<?php

namespace Institution\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Hash;


class StaffAttendancesArchivedTable extends ControllerActionTable
{


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
        ini_set("memory_limit", "2048M");
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'autoFields' => false
        ]);

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $data = $this->request->query;
        $query = $query->select([
            'id',
            'staff_id',
            'institution_id',
            'academic_period_id',
            'time_in',
            'time_out',
            'date',
            'date_to' => 'date',
            'date_from' => 'date']);

        $academic_period_id = $data['academic_period_id'];
        $selected_day = $data['selected_day'];
        if ($selected_day instanceof Time || $selected_day instanceof Date) {
            $selected_day = $selected_day->format('Y-m-d');
        } else {
            $selected_day = date('Y-m-d', strtotime($selected_day));
        }
        $institution_id = $this->Session->read('Institution.Institutions.id');
        $query->where([
            $this->aliasField('institution_id') => $institution_id,
            $this->aliasField('academic_period_id') => $academic_period_id,
            $this->aliasField('date') => $selected_day
        ]);
        $StaffLeaveTable = ArchiveConnections::getArchiveTable('institution_staff_leave');
        $whereLeaveTable = [
            $StaffLeaveTable->aliasField("date_to >= '") . $selected_day . "'",
            $StaffLeaveTable->aliasField("date_from <= '") . $selected_day . "'"
        ];
        $squery = $StaffLeaveTable->find('all')->select(['id',
            'staff_id',
            'institution_id',
            'academic_period_id',
            'time_in' => 'start_time',
            'time_out' => 'end_time',
            'date' => "'" . $selected_day . "'", // POCOR-7895
            'date_to',
            'date_from'])->where([
            $StaffLeaveTable->aliasField('institution_id ') => $institution_id,
            $StaffLeaveTable->aliasField('academic_period_id') => $academic_period_id,
            $whereLeaveTable]);
        $query = $query->union($squery);

        $allStaffLeaves = $StaffLeaveTable
            ->find('all')
            ->where([
                $StaffLeaveTable->aliasField('institution_id ') => $institution_id,
                $StaffLeaveTable->aliasField('academic_period_id') => $academic_period_id,
                $whereLeaveTable
            ])
            ->hydrate(false)
            ->toArray();

        $leaveByStaffIdRecords = Hash::combine($allStaffLeaves, '{n}.id', '{n}', '{n}.staff_id');


        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($leaveByStaffIdRecords, $selected_day) {
            return $results->map(function ($row) use ($leaveByStaffIdRecords, $selected_day) {
                $staff_id = $row->staff_id;
                $name = $this->getRelatedNameWithId('User.Users', $row->staff_id);
                $leaveRecords = [];
                $staffLeaveRecords = [];
                $leaveByStaffIdRecordsStr = print_r($leaveByStaffIdRecords, true);
                $row['leaveByStaffIdRecords'] = $leaveByStaffIdRecords;
                if (array_key_exists($staff_id, $leaveByStaffIdRecords)) {
                    $staffLeaveRecords = $leaveByStaffIdRecords[$staff_id];
                    $staffLeaveRecords = array_slice($staffLeaveRecords, 0, 2);
                }
                $staffLeaveRecordsStr = print_r($staffLeaveRecords, true);
                $row['staffLeaveRecords'] = $staffLeaveRecords;
                foreach ($staffLeaveRecords as $staffLeaveRecord) {
                    $dateFrom = $staffLeaveRecord['date_from']->format('Y-m-d');
                    $dateTo = $staffLeaveRecord['date_to']->format('Y-m-d');
                    $tableName = 'staff_leave_types';
                    $relatedField = $staffLeaveRecord['staff_leave_type_id'];
                    $leaveTypeName = $this->getRelatedName($tableName, $relatedField);
                    $leaveRecordsStr = "";

                    if ($dateFrom <= $selected_day && $dateTo >= $selected_day) {
                        $leaveRecordString = '';
                        $isFullDay = $staffLeaveRecord['full_day'];
                        if($isFullDay){
                            $leaveRecordString = $leaveRecordString . "Full Day\n";
                        }
                        $startTime = $this->formatTime($staffLeaveRecord['start_time']);
                        if($startTime){
                            $leaveRecordString = $leaveRecordString . "Start: $startTime\r\n";
                        }
                        $endTime = $this->formatTime($staffLeaveRecord['end_time']);
                        if($endTime){
                            $leaveRecordString = $leaveRecordString . "End: $endTime\r\n";
                        }
                        if($leaveTypeName){
                            $leaveRecordString = $leaveRecordString . "$leaveTypeName\r\n";
                        }
                        $leaveRecordsStr = $leaveRecordsStr . $leaveRecordString ;
                    }
                }

                $row['leave'] = $leaveRecordsStr;
                $academic_period = $this->getRelatedName(
                    'AcademicPeriod.AcademicPeriods',
                    $row->academic_period_id);
                $institution = $this->getRelatedName(
                    'Institution.Institutions',
                    $row->institution_id);
                $time_in = isset($row->time_in) ? $this->formatTime($row->time_in) : "UPW";
                $time_out = isset($row->time_out) ? $this->formatTime($row->time_out) : "WPS";
//                $this->log('$row', 'debug');
//                $this->log($row, 'debug');
                $row['staff_id'] = $name;
                $row['academic_period_id'] = $academic_period;
                $row['institution_id'] = $institution;

                $row['time_in'] = $time_in;
                $row['time_out'] = $time_out;
//                $this->log('$row', 'debug');
//                $this->log($row, 'debug');
                return $row;
            });
        });

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


    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => 'Institution',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'academic_period_id',
            'type' => 'string',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'staff_id',
            'type' => 'string',
            'label' => 'Staff',
        ];

        $newFields[] = [
            'key' => 'date',
            'field' => 'date',
            'type' => 'date',
            'label' => 'Date',
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
            'key' => 'leave',
            'field' => 'leave',
            'type' => 'string',
            'label' => 'Leave'
        ];


        $newFields[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'text',
            'label' => 'Comment',
        ];

        $fields->exchangeArray($newFields);
    }


}
