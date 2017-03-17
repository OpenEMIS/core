<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('EmploymentTypes', ['className' => 'FieldOption.EmploymentTypes']);

		$this->behaviors()->get('ControllerAction')->config('actions.search', false);
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '2MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);

		// setting this up to be overridden in viewAfterAction(), this code is required
		$this->behaviors()->get('ControllerAction')->config(
			'actions.download.show',
			true 
		);
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('employment_type_id', ['type' => 'select', 'before' => 'employment_date']);

		$visible = ['index' => false, 'view' => false, 'add' => true, 'edit' => true];
        $this->field('file_content', ['visible' => $visible]);

        $this->field('file_name', ['type' => 'hidden']);
        if ($this->action == 'index' || $this->action == 'view') {
        	$this->field('file_name', ['visible' => false]);
        }

		$this->setFieldOrder(['employment_type_id', 'employment_date', 'comment', 'file_content']);

        $this->setupTabElements();
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		// if has attachment, then show download button
		$showFunc = function() use ($entity) {
			$filename = $entity->file_content;
			return !empty($filename);
		};
		$this->behaviors()->get('ControllerAction')->config(
			'actions.download.show',
			$showFunc
		);
		// End
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}
}
