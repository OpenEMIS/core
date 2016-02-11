<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class QualificationSpecialisationsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('qualification_specialisations');
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'qualification_specialisation_id']);

		$this->belongsToMany('EducationSubjects', [
			'className' => 'Education.EducationSubjects',
			'joinTable' => 'qualification_specialisation_subjects',
			'foreignKey' => 'qualification_specialisation_id',
			'targetForeignKey' => 'education_subject_id',
			'through' => 'Education.QualificationSpecialisationSubjects',
			'dependent' => false
		]);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('name', __('Please enter a name.'))
			->notEmpty('default', __('Please choose a default.'))
		;

		return $validator;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {

		$fieldOptionValueData = $this->ControllerAction->request->data;
		// pr($fieldOptionValueData);
		// die;
		if (array_key_exists('FieldOptionValues', $fieldOptionValueData) && 
			array_key_exists('education_subjects', $fieldOptionValueData['FieldOptionValues']) &&
			array_key_exists('_ids', $fieldOptionValueData['FieldOptionValues']['education_subjects'])
			 ) {
			$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
			$QualificationSpecialisationSubjects = TableRegistry::get('Staff.QualificationSpecialisationSubjects');
			$currEducationSubject = (!empty($fieldOptionValueData['FieldOptionValues']['education_subjects']['_ids']))? $fieldOptionValueData['FieldOptionValues']['education_subjects']['_ids']: [];

			$prevEducationSubjectData = $QualificationSpecialisationSubjects->find()
				->select([])
				->where([
					$QualificationSpecialisationSubjects->aliasField('qualification_specialisation_id') => $entity->id
				])
				;
			$prevEducationSubject = [];
			foreach ($prevEducationSubjectData->toArray() as $key => $value) {
				$prevEducationSubject[] = $value->education_subject_id;
			}

			$newEducationSubject = array_diff($currEducationSubject, $prevEducationSubject);

			foreach ($newEducationSubject as $key => $value) {
				$newRecord = $QualificationSpecialisationSubjects->newEntity(['qualification_specialisation_id' => $entity->id, 'education_subject_id' => $value]);
				$QualificationSpecialisationSubjects->save($newRecord);
			}
		}
	}

	// this is used in the index but not view - the same logic can be found in QualificationSpecialisationsBehavior.php
	// needs to fix logic in future for field options so the logic can be in 1 place
	public function onGetEducationSubjects(Event $event, Entity $entity) {
		if (!$entity->has('education_subjects')) {
			$query = $this->find()
			->where([$this->aliasField($this->primaryKey()) => $entity->id])
			->contain(['EducationSubjects'])
			;
			$data = $query->first();
		}
		else {
			$data = $entity;
		}

		$educationSubjects = [];
		if ($data->has('education_subjects')) {
			foreach ($data->education_subjects as $key => $value) {
				$educationSubjects[] = $value->name;
			}
		}

		return (!empty($educationSubjects))? implode(', ', $educationSubjects): ' ';
	}
}
