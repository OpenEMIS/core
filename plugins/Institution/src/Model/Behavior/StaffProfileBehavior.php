<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class StaffProfileBehavior extends Behavior
{
    public function staffPositionProfilesGetAssociatedModelData(Event $event, ArrayObject $params)
    {
        $model = $this->_table;
        $alias = $model->alias();

        $staffId = $params['staff_id'];
        $institutionId = $params['institution_id'];
        $institutionPositionId = $params['institution_position_id'];
        $academicPeriodId = $params['academic_period_id'];
        $originalStartDate = $params['original_start_date'];
        $newStartDate = $params['new_start_date'];

        $conditions = [];
        switch ($alias) {
            case 'InstitutionClasses':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('academic_period_id >=') => $academicPeriodId
                ];
                break;

            case 'StaffAbsences':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ];
                break;

            case 'StaffPositionProfiles':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ];
                break;

            case 'StaffLeave':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('date_from >=') => $originalStartDate,
                    $model->aliasField('date_from <=') => $newStartDate
                ];
                break;

            case 'InstitutionStudentsReportCardsComments':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('academic_period_id >=') => $academicPeriodId
                ];
                break;

            case 'StaffAppraisals':
                $conditions = [
                    $model->aliasField('created_user_id') => $staffId,
                    $model->aliasField('academic_period_id >=') => $academicPeriodId
                ];
                break;

            case 'StaffBehaviours':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('date_of_behaviour >=') => $originalStartDate,
                    $model->aliasField('date_of_behaviour <=') => $newStartDate
                ];
                break;

            case 'StaffTransferRequests':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('status') => 0,
                    $model->aliasField('previous_institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ];
                break;

            case 'Salaries':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('salary_date >=') => $originalStartDate,
                    $model->aliasField('salary_date <=') => $newStartDate
                ];
                break;

            case 'StaffSubjects':
                $conditions = [
                    $model->aliasField('staff_id') => $staffId,
                    $model->aliasField('institution_id') => $institutionId,
                    $model->aliasField('start_date >=') => $originalStartDate,
                    $model->aliasField('start_date <=') => $newStartDate
                ];
                break;
        }

        $dataCount = $model->find()
            ->where($conditions)
            ->count();

        return $dataCount;
    }
}
