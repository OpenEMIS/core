<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Http\ServerRequest;
use Cake\View\Helper\UrlHelper;

class LeaveEntitlementsTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'name'];
    public function initialize(array $config): void
    {
        $this->setTable('staff_leave_entitlements');
        parent::initialize($config);
        $this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);


        $this->addBehavior('User.AdvancedNameSearch');

//        $this->toggle('view', false);
//        $this->toggle('add', false);
//        $this->toggle('edit', false);
//        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        return $events;
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb(__('StaffPolicies'), $header);
        $this->controller->Navigation->substituteCrumb(__('Systems'), __('Staff'));
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('staff_id', ['sort' => true]);
        $this->field('staff_leave_type_id', ['sort' => true]);
        $this->field('adjustment', ['sort' => true]);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('created', ['visible' => true]);
        $this->field('modified_user_id', ['visible' => true]);
        $this->field('modified', ['visible' => true]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {

        $queryParams = $this->request->getQuery();
        $search = $this->getSearchKey();

        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query->contain('StaffLeaveTypes');
            $query = $this->addSearchConditions($query,
                ['alias' => 'Staff', 'searchTerm' => '%' . $search . '%',
                'OR' => ['StaffLeaveTypes.name LIKE ' => '%' . $search . '%']]);
        }

        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {

    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('staff')) {
            $value = $entity->staff->openemis_no;
        }
        return $value;
    }
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
//        $this->setupFields($entity);
//        $this->setfieldOrder($this->fieldsOrder);
    }
    private function setupFields(Entity $entity)
    {
        $this->field('id', [
            'type' => 'hidden',
        ]);
        $this->field('staff_id', ['entity' => $entity, 'sort' => true]);
        $this->field('staff_leave_type_id', ['entity' => $entity, 'sort' => true]);
        $this->field('adjustment', ['sort' => true]);
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
        }

        return $attr;
    }
    public function onUpdateFieldStaffId(Event $event, array $attr, $action, ServerRequest $request)
    {

        if ($action == 'edit'){
            $attr['type'] = 'readonly';
            $entity = $attr['entity'];
            $staff_id = $entity->staff_id;
            $staff = $this->Staff->get($staff_id);
            $attr['value'] = $staff_id;
            $attr['attr']['value'] = $staff->name;
        }
        if ($action == 'add' ) {
            $dataKey = 'staff_id';

            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => $dataKey, 'name' => $this->aliasField($dataKey)];
            $attr['noResults'] = __('No Staff found.');
            $attr['onNoResultsBlockSave'] = true;
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $attr['attr']['onNoResultsBlockSave'] = true;
            // $attr['onSelect'] = "$('#reload').click();";

            $url = $event->getSubject()->url('ajaxUserAutocomplete');
            $attr['url'] = $url;

            $requestData = $this->request->getData();
            if (isset($requestData) && !empty($requestData[$this->getAlias()][$dataKey])) {
                $referrerId = $requestData[$this->getAlias()][$dataKey];
                $referrerName = $this->Staff->get($referrerId)->name_with_id;
                $attr['attr']['value'] = $referrerName;
            }

            $entity = $attr['entity'];
            if ($entity->has($dataKey) && !is_null($entity->{$dataKey})) {
                $referrerId = $entity->{$dataKey};
                $referrerName = $this->Staff->get($referrerId)->name_with_id;
                $attr['attr']['value'] = $referrerName;
            }
        }
            return $attr;

    }

    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->getQuery('term');

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Staff
                ->find()
                ->select([
                    $this->Staff->aliasField('openemis_no'),
                    $this->Staff->aliasField('first_name'),
                    $this->Staff->aliasField('middle_name'),
                    $this->Staff->aliasField('third_name'),
                    $this->Staff->aliasField('last_name'),
                    $this->Staff->aliasField('preferred_name'),
                    $this->Staff->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->getAlias() => $UserIdentitiesTable->getTable()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Staff->aliasField('id')
                    ]
                )
                ->group([
                    $this->Staff->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);
            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Staff', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }
    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
//        Log::debug(print_r($entity, true));
//        Log::debug(print_r($options, true));
//        Log::debug(print_r($event, true));
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
//        Log::debug(print_r($entity, true));
//        Log::debug(print_r($options, true));
//        Log::debug(print_r($event, true));
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

}
