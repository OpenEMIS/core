<?php
namespace User\Model\Table;


use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\ORM\Query;

class ContactTypesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('contact_types');
		parent::initialize($config);

		$this->belongsTo('ContactOptions', ['className' => 'User.ContactOptions']);
		$this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'contact_type_id'])
		;
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'contact_option_id',
			]);
		}

		$this->addBehavior('FieldOption.FieldOption');
		$this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
	}

	public function findWithContactOptions(Query $query, array $options)
	{
		return $query
			->contain(['ContactOptions']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('validation_pattern', ['after' => 'name', 'sort' => false]);
		$this->field('contact_option_id', ['visible' => 'false']);
		$this->field('default', ['visible' => 'false']);
		$this->field('editable', ['visible' => 'false']);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$parentFieldOptions = $this->ContactOptions->find('list')->toArray();
		$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

		if (!empty($selectedParentFieldOption)) {
			$query->where([$this->aliasField('contact_option_id') => $selectedParentFieldOption]);
		}

		$this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('default', ['visible' => 'false']);
		$this->field('contact_option_id');
	}

	public function onUpdateFieldContactOptionId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add' || $action == 'edit') {
			$parentFieldOptions = $this->ContactOptions->find('list')->toArray();
			$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

			$attr['type'] = 'readonly';
			$attr['value'] = $selectedParentFieldOption;
			$attr['attr']['value'] = $parentFieldOptions[$selectedParentFieldOption];
		}
		return $attr;
	}
}
