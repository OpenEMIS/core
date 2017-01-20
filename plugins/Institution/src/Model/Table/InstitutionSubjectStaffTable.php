<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\I18n\time;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class InstitutionSubjectStaffTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);

	}

    public function implementedEvents() {
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
        if ($staff->dirty('end_date')) {        
            $this->updateAll( 
                ['end_date' => $staff->end_date],
                [
                    'staff_id' => $staff->staff_id,
                    'institution_id' => $staff->institution_id
                ]
            );
        }
    }
}
