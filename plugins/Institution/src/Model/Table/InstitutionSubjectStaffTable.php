<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\I18n\time;
use Cake\ORM\TableRegistry;

class InstitutionSubjectStaffTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);

	}

    public function addStaffToSubject($staffId, $institutionSubjectId) 
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
                                    $InstitutionStaffTable->aliasField('staff_id') => $staffId
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
                'institution_id' => $institutionStaff->institution_id,
                'institution_subject_id' => $institutionSubjectId
            ]);
            $result = $this->save($entity);
        } else {
            $result = $existingRecord;
        }

        return $result;
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

    public function updateSubjectStaffEndDate($endData, $staffId, $institutionId) 
    {
        $this->updateAll( 
            ['end_date' => $endData],
            [
                'staff_id' => $staffId,
                'institution_id' => $institutionId
            ]
        );
    }

}
