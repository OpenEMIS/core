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

class QualificationTitlesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'qualification_specialisation_id']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		
		$this->addBehavior('FieldOption.FieldOption');
	}

	public function afterAction(Event $event) {
		$this->field('qualification_level_id', [
			'type' => 'select',
			'after' => 'national_code'
		]);
	}

	// public function onUpdateFieldEducationSubjects(Event $event, array $attr, $action, Request $request) {
	// 	switch ($action) {
	// 		 case 'edit': case 'add':
	// 			$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
	// 			$subjectData = $EducationSubjects
	// 				->find()
	// 				->select([$EducationSubjects->aliasField($EducationSubjects->primaryKey()), $EducationSubjects->aliasField('name'), $EducationSubjects->aliasField('code')])
	// 				->find('visible')
	// 				->find('order')
	// 				->toArray();

	// 			$subjectOptions = [];
	// 			foreach ($subjectData as $key => $value) {
	// 				$subjectOptions[$value->id] = $value->code . ' - ' . $value->name;
	// 			}

	// 			$attr['type'] = 'chosenSelect';
	// 			$attr['options'] = $subjectOptions;
	// 			$attr['model'] = 'QualificationSpecialisations';
	// 			break;

	// 		default:
	// 			# code...
	// 			break;
	// 	}
	// 	return $attr;
	// }

	// public function onGetEducationSubjects(Event $event, Entity $entity) {
	// 	if (!$entity->has('education_subjects')) {
	// 		$query = $this->find()
	// 		->where([$this->aliasField($this->primaryKey()) => $entity->id])
	// 		->contain(['EducationSubjects'])
	// 		;
	// 		$data = $query->first();
	// 	}
	// 	else {
	// 		$data = $entity;
	// 	}

	// 	$educationSubjects = [];
	// 	if ($data->has('education_subjects')) {
	// 		foreach ($data->education_subjects as $key => $value) {
	// 			$educationSubjects[] = $value->name;
	// 		}
	// 	}

	// 	return (!empty($educationSubjects))? implode(', ', $educationSubjects): ' ';
	// }
}
