<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class ConfigStudentCreationRulesTable extends ControllerActionTable //POCOR-9385: student creation config UI
{
    public function initialize(array $config): void
    {
        $this->setTable('config_items'); //POCOR-9385: shares config_items table
        parent::initialize($config);

        $this->addBehavior('Configuration.ConfigItems'); //POCOR-9385: config items behavior
        $this->toggle('add', false);
        $this->toggle('remove', false);

        $this->SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles'); //POCOR-9385: for excluded roles chosenSelect
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //POCOR-9385: show only the two student creation config items
        $query->where([
            $this->aliasField('type') => 'Add New Student',
            $this->aliasField('code IN') => ['restrict_student_creation', 'student_creation_excluded_roles'],
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => ['index' => true]]); //POCOR-9385: show name on index
        $this->field('code', ['type' => 'hidden']);
        $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('default_value', ['visible' => ['view' => true]]);
        $this->field('editable', ['visible' => false]);
        $this->field('visible', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity); //POCOR-9385: setup fields for view
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity); //POCOR-9385: setup fields for edit
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if (!$entity->has('code')) {
            return;
        }
        //POCOR-9385: pre-populate chosenSelect with saved role IDs
        if ($entity->code === 'student_creation_excluded_roles' && !empty($entity->value)) {
            $roleIds = array_filter(explode(',', $entity->value));
            $roles = $this->SecurityRoles->find()
                ->where([$this->SecurityRoles->aliasField('id IN') => $roleIds])
                ->all();
            $entity->value = $roles;
        }
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $submit = isset($data['submit']) ? $data['submit'] : 'save';
        if ($submit !== 'save') {
            return;
        }
        //POCOR-9385: implode chosenSelect role IDs into comma-separated string
        if (($data['code'] ?? '') === 'student_creation_excluded_roles') {
            if (isset($data['value']['_ids']) && !empty($data['value']['_ids'])) {
                $data['value'] = implode(',', $data['value']['_ids']);
            } else {
                $data['value'] = '';
            }
        }
    }

    public function onUpdateFieldValue(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = $attr['entity'];
        if (!$entity->has('code')) {
            return $attr;
        }

        switch ($entity->code) {
            case 'restrict_student_creation': //POCOR-9385: toggle — Enabled/Disabled dropdown
                $attr['type'] = 'select';
                $attr['options'] = $this->getToggleOptions();
                $attr['select'] = false;
                break;

            case 'student_creation_excluded_roles': //POCOR-9385: multi-select security roles
                if ($action === 'edit') {
                    $attr['type'] = 'chosenSelect';
                    $attr['placeholder'] = __('Select Security Roles');
                    $attr['options'] = $this->SecurityRoles->find('list')->toArray();
                }
                break;
        }

        return $attr;
    }

    public function onGetValue(EventInterface $event, Entity $entity)
    {
        if (!$entity->has('code')) {
            return '';
        }

        switch ($entity->code) {
            case 'restrict_student_creation': //POCOR-9385: display human label for toggle
                $options = $this->getToggleOptions();
                return $options[$entity->value] ?? $entity->value;

            case 'student_creation_excluded_roles': //POCOR-9385: display role names
                if (empty($entity->value)) {
                    return __('None');
                }
                $roleIds = array_filter(explode(',', $entity->value));
                $names = $this->SecurityRoles->find('list')
                    ->where([$this->SecurityRoles->aliasField('id IN') => $roleIds])
                    ->toArray();
                return implode(', ', $names);
        }

        return '';
    }

    private function setupFields(Entity $entity): void //POCOR-9385: field order for view/edit
    {
        $this->field('value', ['entity' => $entity]);
        $this->setFieldOrder(['type', 'label', 'value', 'default_value']);
    }

    private function getToggleOptions(): array //POCOR-9385: Enabled/Disabled from config_item_options
    {
        $ConfigItemOptions = TableRegistry::getTableLocator()->get('Configuration.ConfigItemOptions');
        return $ConfigItemOptions->find('list', [
            'keyField' => 'value',
            'valueField' => 'option',
        ])->where(['option_type' => 'student_creation_toggle'])->toArray();
    }
}
