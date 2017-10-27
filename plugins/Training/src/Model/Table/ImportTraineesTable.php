<?php
namespace Training\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportTraineesTable extends AppTable
{
    private $trainingSessionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin'=>'Training', 
            'model'=>'TrainingSessionsTrainees',
            'custom_text'=>__('Please enter either OpenEMIS ID or an Identity Code and number')
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction($event) 
    {
        $trainingId = $this->request->params['trainingId'];
        $this->trainingSessionId = $this->paramsDecode($trainingId)['id'];
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) 
    {
        if ($action == 'add' || $action == 'results') {
            $backUrl = [
                'plugin' => 'Training',
                'controller' => 'Trainings',
                'action' => 'Sessions',
                0 => 'edit',
                1 => $this->request->params['trainingId']
            ];
            $toolbarButtons['back']['url'] = $backUrl;
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //validate OpenEMIS ID
        $tempRow['trainee_id'] = '';
        if (!empty($tempRow['openemis_no'])) {
            $Users = TableRegistry::get('User.Users');

            $query = $Users->find()
                    ->where([
                        $Users->aliasField('openemis_no') => $tempRow['openemis_no']
                    ]);

            if ($query->count() < 1) {
                $rowInvalidCodeCols['openemis_no'] = __('OpenEMIS ID could not be found');
                return false;
            } else {
                 $tempRow['trainee_id'] = $query->first()->id;
            }
        } else {
            //validate Identity Type and Number Pair
            if (!empty($tempRow['identity_type_id']) && !empty($tempRow['identity_number'])) {
                $Identities = TableRegistry::get('User.Identities');

                $query = $Identities->find()
                        ->where([
                           $Identities->aliasField('identity_type_id') => $tempRow['identity_type_id'],
                           $Identities->aliasField('number') => $tempRow['identity_number']
                        ]);

                if ($query->count() < 1) {
                    $rowInvalidCodeCols['identity'] = __('Identity Type and Number pair cannot be found');
                    return false;
                } else {
                     $tempRow['trainee_id'] = $query->first()->security_user_id;
                }
            } else {
                if (empty($tempRow['identity_type_id'])) {
                    $rowInvalidCodeCols['identity_type_id'] = __('Identity Type cannot be empty');
                } else if (empty($tempRow['identity_number'])) {
                    $rowInvalidCodeCols['identity_number'] = __('Identity Number cannot be empty');
                }
                return false;
            }
        }

        if (!empty($tempRow['trainee_id'])) {

            //check against target population
            $TargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
            $Staff = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $Positions = TableRegistry::get('Institution.InstitutionPositions');

            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

            $targetPopulationIds = $TargetPopulations
                                ->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
                                ->matching('TrainingCourses.TrainingSessions')
                                ->where(['TrainingSessions.id' => $this->trainingSessionId])
                                ->toArray();

            if (!empty($targetPopulationIds)) {

                $positionCondition = [];
                if (current($targetPopulationIds) != -1) {
                    $positionCondition['Positions.staff_position_title_id IN'] = $targetPopulationIds;
                }

                $query = $Staff->find()
                        ->matching('Positions', function ($q) use ($Positions, $targetPopulationIds, $positionCondition) {
                            return $q
                                ->find('all')
                                ->where($positionCondition);
                        })
                        ->where([
                            $Staff->aliasField('staff_id') => $tempRow['trainee_id']
                        ]);

                if ($query->count() < 1) {
                    $rowInvalidCodeCols['trainee'] = __('Trainee Position not in Training Course Target Population');
                    return false;
                } else {
                    //to store institution id for either single or multiple
                    $institutionId  = [];
                    foreach ($query->toArray() as $key => $value) {
                        $institutionId[] = $value->institution_id;
                    }

                    //check assigned status
                    if (!empty($institutionId)) {
                        $query = $Staff->find()
                            ->where([
                                $Staff->aliasField('staff_id') => $tempRow['trainee_id'],
                                $Staff->aliasField('institution_id IN ') => $institutionId,
                                $Staff->aliasField('staff_status_id') => $assignedStatus
                            ]);

                        if ($query->count() < 1) {
                            $rowInvalidCodeCols['trainee'] = __('Trainee does not have Assigned status');
                            return false;
                        }

                        $tempRow['training_session_id'] = $this->trainingSessionId;
                        $tempRow['status'] = 1;
                        return true;
                    }
                }
            } else {
                $rowInvalidCodeCols['course'] = __('No Target Population defined on the Training Course');
                return false;
            }
        } else {
            $rowInvalidCodeCols['trainee'] = __('Provided Trainee data is not valid');
            return false;
        }
    }
}
