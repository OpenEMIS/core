<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class StaffProfileBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.StaffPositionProfiles.getAssociatedModelData'] = 'staffPositionProfilesGetAssociatedModelData';
        return $events;
    }

    public function staffPositionProfilesGetAssociatedModelData(Event $event, ArrayObject $params)
    {
        $model = $this->_table;
        $alias = $model->alias();
        $query = $model->find();

        $staffId = $params['staff_id'];
        $institutionId = $params['institution_id'];
        $institutionPositionId = $params['institution_position_id'];
        $originalStartDate = $params['original_start_date'];
        $newStartDate = $params['new_start_date'];

        $conditions = [];
        $workflowPendingRecords = false;
        switch ($alias) {
            case 'InstitutionClasses':
                $query->contain(['AcademicPeriods'])
                    ->where([
                        $model->aliasField('staff_id') => $staffId,
                        $model->aliasField('institution_id') => $institutionId,
                        'AcademicPeriods.start_date <= ' => $newStartDate
                    ])
                ;
                break;

            case 'StaffAbsences':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ]);
                break;

            case 'StaffPositionProfiles':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ]);
                break;

            case 'StaffLeave':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('date_from >=') => $originalStartDate,
                    $model->aliasField('date_from <=') => $newStartDate
                ]);
                break;

            case 'InstitutionStudentsReportCardsComments':
                $query->contain(['AcademicPeriods'])
                    ->where([
                        $model->aliasField('staff_id') => $staffId,
                        $model->aliasField('institution_id') => $institutionId,
                        'AcademicPeriods.start_date <= ' => $newStartDate
                    ])
                ;
                break;

            case 'StaffAppraisals':
                $query->contain(['AcademicPeriods'])
                    ->where([
                        $model->aliasField('created_user_id') => $staffId,
                        'AcademicPeriods.start_date <= ' => $newStartDate
                    ])
                ;
                break;

            case 'StaffBehaviours':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('date_of_behaviour >=') => $originalStartDate,
                    $model->aliasField('date_of_behaviour <=') => $newStartDate
                ]);
                break;

<<<<<<< HEAD
            case 'StaffTransferRequests':
                $query->where([
=======
            case 'StaffTransferOut':
                $conditions = [
>>>>>>> d4e6897c0af7b3ced56a2bb12523cff5048c64dd
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('previous_institution_id') => $institutionId,
<<<<<<< HEAD
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ]);
=======
                    $model->aliasField('previous_end_date') . ' IS NOT NULL',
                    $model->aliasField('previous_end_date >=') => $originalStartDate,
                    $model->aliasField('previous_end_date <=') => $newStartDate
                ];
                $workflowPendingRecords = true;
>>>>>>> d4e6897c0af7b3ced56a2bb12523cff5048c64dd
                break;

            case 'Salaries':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('salary_date >=') => $originalStartDate,
                    $model->aliasField('salary_date <=') => $newStartDate
                ]);
                break;

            case 'StaffSubjects':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ]);
                break;
        }

<<<<<<< HEAD
        $dataCount = $query->count();
=======
        $dataCount = $model->find()
            ->where($conditions);

        if ($workflowPendingRecords) {
            $doneStatus = $model::DONE;
            $dataCount->matching('Statuses', function ($q) use ($doneStatus) {
                    return $q->where(['category <> ' => $doneStatus]);
                });
        }
>>>>>>> d4e6897c0af7b3ced56a2bb12523cff5048c64dd

        return $dataCount->count();
    }
}
