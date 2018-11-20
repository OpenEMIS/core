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
                $query->contain(['AppraisalPeriods.AcademicPeriods'])
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

            case 'StaffTransferOut':
                $query->where([
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('previous_institution_id') => $institutionId,
                    $model->aliasField('previous_end_date') . ' IS NOT NULL',
                    $model->aliasField('previous_end_date >=') => $originalStartDate,
                    $model->aliasField('previous_end_date <=') => $newStartDate
                ]);
                $workflowPendingRecords = true;
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

        if ($workflowPendingRecords) {
            $doneStatus = $model::DONE;
            $query->matching('Statuses', function ($q) use ($doneStatus) {
                    return $q->where(['category <> ' => $doneStatus]);
                });
        }

        return $query->count();
    }
}
