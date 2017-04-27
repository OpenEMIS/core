<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class EducationCyclesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('EducationLevels', ['className' => 'Education.EducationLevels']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_level_id',
			]);
		}

		$this->setDeleteStrategy('restrict');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{

	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$query->where([$this->aliasField('education_level_id') => $entity->education_level_id]);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		$validator
			->add('admission_age', 'ruleValidateNumeric',  [
	            'rule' => ['numericPositive']
	        ])
	    ;
		return $validator;
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
        $this->controller->set(compact('levelOptions', 'selectedLevel'));
		$extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => [], 'options' => [], 'order' => 1];
		$query->where([$this->aliasField('education_level_id') => $selectedLevel]);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('education_level_id');
		$this->field('admission_age', ['after' => 'name', 'attr' => ['min' => 0]]);
	}

	public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request)
	{
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
		$attr['options'] = $levelOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedLevel;
		}

		return $attr;
	}

	public function getSelectOptions()
	{
		//Return all required options and their key
		$levelOptions = $this->EducationLevels->getLevelOptions();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}
}
