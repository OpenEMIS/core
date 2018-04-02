<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use ArrayObject;

class InstitutionSubjectStaffTable extends AppTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Staff.afterSave'] = 'staffAfterSave';
        return $events;
    }

    public function addStaffToSubject($staffId, $institutionSubjectId, $institutionId)
    {
        $result = false;
        $existingRecord = $this->find()
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_subject_id') => $institutionSubjectId
            ])
            ->first();

        if (empty($existingRecord)) {
            $todayDate = Time::now()->format('Y-m-d');

            $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
            $institutionStaff = $InstitutionStaffTable
                                ->find()
                                ->where([
                                    $InstitutionStaffTable->aliasField('staff_id') => $staffId,
                                    $InstitutionStaffTable->aliasField('institution_id') => $institutionId
                                ])
                                ->first();

            $endDate = null;
            if ($institutionStaff->end_date) {
                $endDate = $institutionStaff->end_date->format('Y-m-d');
            }

            $entity = $this->newEntity([
                'id' => Text::uuid(),
                'start_date' => $todayDate,
                'end_date' => $endDate, //institution_staff end_date as default value.
                'staff_id' => $staffId,
                'institution_id' => $institutionId,
                'institution_subject_id' => $institutionSubjectId
            ]);
            $result = $this->save($entity);
        } else {
            $result = $existingRecord;
        }

        return $result;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->start_date = Time::now();
        }
    }

    public function removeStaffFromSubject($staffId, $institutionSubjectId)
    {
        $result = false;
        $existingRecords = $this->find()
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_subject_id') => $institutionSubjectId
            ])
            ->toArray();

        $deleteCount = 0;
        if (!empty($existingRecords)) {
            foreach ($existingRecords as $key => $value) {
                if ($this->delete($value)) {
                    $deleteCount++;
                }
            }
        }

        return $deleteCount;
    }

    public function staffAfterSave(Event $event, $staff)
    {
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        // if ($staff->dirty('end_date')) {
            $selectConditions = [];
            if ($staff->isNew()) {
                $selectConditions = [
                    $InstitutionStaff->aliasField('id') => $staff->id,
                    $InstitutionStaff->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED')
                ];
            } else {
                $selectConditions = ['Users.id' => $staff->staff_id];
            }

            //get the entire information of the staff
            $StaffData = $InstitutionStaff->find()
                ->select([
                    $InstitutionStaff->aliasField('FTE'),
                    $InstitutionStaff->aliasField('start_date'),
                    $InstitutionStaff->aliasField('end_date'),
                    'Users.id',
                    'Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Institutions.code',
                    'Institutions.name',
                    'Positions.id',
                    'Positions.position_no',
                    'StaffPositionTitles.name',
                    'StaffPositionTitles.type',
                    'StaffTypes.name',
                    'StaffStatuses.code',
                    'StaffStatuses.name'
                ])
                ->find('byInstitution', ['Institutions.id' => $staff->institution_id])
                ->contain([
                    'Users',
                    'Institutions',
                    'Positions.StaffPositionTitles',
                    'StaffTypes',
                    'StaffStatuses'
                ])
                ->where($selectConditions)
                ->toArray();

            $updateEndDate = false;

            // use case: Teacher holding one teaching position, teaching position will be ended
            // expected: Teaching subject will be ended based on the position
            if (count($StaffData) == 1) {
                if ($StaffData[0]->position->staff_position_title->type == 1) { //if teaching position
                    $updateEndDate = true;
                    $endDate = $staff->end_date;
                }
            } else {
                // use case: Teacher holding one teaching position and one non-teaching position, teaching position will be ended
                // expected: Teaching subject will be ended based on the teaching position
                $endDate = '';
                foreach ($StaffData as $key => $value) { //loop through position
                    if ($value->position->staff_position_title->type == 1) { //if teaching position
                        $updateEndDate = true;

                        if (is_null($value->end_date)) { //if null, then always get it.
                            $endDate = $value->end_date;
                            break;
                        } else {
                            if (!empty($endDate)) {
                                if ($endDate < $value->end_date) {
                                    $endDate = $value->end_date;
                                }
                            } else {
                                $endDate = $value->end_date;
                            }
                        }
                    }
                }
            }

            $updateConditions = [];
            if ($updateEndDate) {
                $updateConditions = [
                    'staff_id' => $staff->staff_id,
                    'institution_id' => $staff->institution_id
                ];

                if ($staff->isNew()) {
                    if (!is_null($endDate)) {
                        $updateConditions['AND'] = [
                            'end_date IS NOT NULL',
                            'end_date > ' => $staff->start_date->format('Y-m-d'),
                            'end_date < ' => $endDate->format('Y-m-d')
                        ];
                    } else {
                        $endDate = null;
                        $updateConditions ['end_date > '] = $staff->start_date->format('Y-m-d');
                    }
                }

                $this->updateAll(
                    ['end_date' => $endDate],
                    $updateConditions
                );
            }
        // }
    }
}
