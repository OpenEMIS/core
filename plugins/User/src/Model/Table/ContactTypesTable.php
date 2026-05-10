<?php
namespace User\Model\Table;


use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class ContactTypesTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		$this->setTable('contact_types');
		parent::initialize($config);

		$this->belongsTo('ContactOptions', ['className' => 'User.ContactOptions']);
		$this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'contact_type_id'])
		;
		if ($this->behaviors()->has('Reorder')) {
			// $this->behaviors()->get('Reorder')->config([
			// 	'filter' => 'contact_option_id',
			// ]);
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'contact_option_id');
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

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		return $validator
            ->requirePresence('contact_option_id')
			->requirePresence('name');
	}

	public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
	{
		$this->field('validation_pattern', ['after' => 'name', 'sort' => false]);
		$this->field('contact_option_id', ['visible' => 'false']);
		$this->field('default', ['visible' => 'false']);
		$this->field('editable', ['visible' => 'false']);
	}

	public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
	{
		$parentFieldOptions = $this->ContactOptions->find('list')->toArray();
		$selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

		if (!empty($selectedParentFieldOption)) {
			$query->where([$this->aliasField('contact_option_id') => $selectedParentFieldOption]);
		}

		$this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
	}

	public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
	{
		$this->field('default', ['visible' => 'false']);
		$this->field('contact_option_id');
	}

	public function onUpdateFieldContactOptionId(EventInterface $event, array $attr, $action, ServerRequest $request)
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

	public function beforeSave(EventInterface $event)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
