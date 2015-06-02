<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class RubricTemplatesTable extends AppTable {
	private $weightingType = [
		1 => ['id' => 1, 'name' => 'Points'],
		2 => ['id' => 2, 'name' => 'Percentage']
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('RubricSections', ['className' => 'Rubric.RubricSections', 'dependent' => true]);
		$this->hasMany('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		$weightingTypeOptions = [];
		foreach ($this->weightingType as $key => $weightingType) {
			$weightingTypeOptions[$weightingType['id']] = __($weightingType['name']);
		}

		if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['weighting_type']['type'] = 'select';
			$this->fields['weighting_type']['options'] = $weightingTypeOptions;
		}
	}

	public function beforeSave(Event $event, Entity $entity) {
		if ($entity->isNew()) {
			$data = [
				'rubric_template_options' => [
					['name' => 'Good', 'weighting' => 3, 'color' => '#00ff00', 'order' => 1],
					['name' => 'Normal', 'weighting' => 2, 'color' => '#000ff0', 'order' => 2],
					['name' => 'Bad', 'weighting' => 1, 'color' => '#ff0000', 'order' => 3],
				]
			];

			$entity = $this->patchEntity($entity, $data);
		}
	}
}
