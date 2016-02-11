<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
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

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['EducationSubjects']);	
	}

	public function afterAction(Event $event) {
		$this->field('education_subjects', ['after' => 'national_code']);
	}

	public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request) {
		switch ($action) {
			 case 'edit': case 'add':
				$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
				$subjectOptions = $EducationSubjects
					->find('list')
					->find('visible')
					->find('order')
					->toArray();
				
				$attr['type'] = 'chosenSelect';
				$attr['options'] = $subjectOptions;
				$attr['model'] = 'QualificationSpecialisations';
				break;
			
			default:
				# code...
				break;
		}
		return $attr;
	}

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
