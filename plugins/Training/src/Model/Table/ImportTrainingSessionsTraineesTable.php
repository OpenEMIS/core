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

class ImportTrainingSessionsTraineesTable extends AppTable
{
    private $trainingSessionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

       $this->addBehavior('Import.Import', ['plugin'=>'Training', 'model'=>'TrainingSessionsTrainees']);
    }

    public function beforeAction($event) 
    {
        $paramPass = $this->request->params['pass'][1];
        $this->trainingSessionId = $this->paramsDecode($paramPass)['id'];
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //validate OpenEMIS No
        $tempRow['trainee_id'] = '';
        if (!empty($tempRow['openemis_no'])) {
            $Users = TableRegistry::get('User.Users');

            $query = $Users->find()
                    ->where([
                        $Users->aliasField('openemis_no') => $tempRow['openemis_no']
                    ]);

            if ($query->count() < 1) {
                $rowInvalidCodeCols['openemis_no'] = __('OpenEMIS No could not be found');
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
                $rowInvalidCodeCols['openemis_no / identity'] = __('Please provide either OpenEMIS No or Identity informations');
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
                $query = $Staff->find()
                        ->matching('Positions', function ($q) use ($Positions, $targetPopulationIds) {
                            return $q
                                ->find('all')
                                ->where([
                                    'Positions.staff_position_title_id IN' => $targetPopulationIds
                                ]);
                        })
                        ->where([
                            $Staff->aliasField('staff_id') => $tempRow['trainee_id']
                        ]);

                if ($query->count() < 1) {
                    $rowInvalidCodeCols['trainee'] = __('Trainee Position not in Training Course Target Population');
                    return false;
                } else {
                    //check assigned status
                    $query->where([
                        $Staff->aliasField('staff_status_id') => $assignedStatus
                    ]);

                    if ($query->count() < 1) {
                        $rowInvalidCodeCols['trainee'] = __('Trainee does not have Assigned status');
                        return false;
                    }
                }
            }

            $tempRow['training_session_id'] = $this->trainingSessionId;
            $tempRow['status'] = 1;
        }

        pr(111);die;

        return true;
    }
}
