<?php

namespace Directory\Model\Behavior;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

/**
 * Class MergeBehavior
 * @package Directory\Model\Behavior
 * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
 */
class MergeBehavior extends Behavior
{
    /**
     * @param array $config
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function initialize(array $config)
    {
        $this->_table->addBehavior('User.AdvancedNameSearch');
        $this->_table->addBehavior('User.AdvancedNameSearch');
        $this->_table->addBehavior('OpenEmis.Autocomplete');
    }

    /**
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.merge'] = 'merge';
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        $events['ControllerAction.Model.merge.beforeSave'] = ['callable' => 'mergeBeforeSave', 'priority' => 100];
        $events['ControllerAction.Model.merge.afterSave'] = ['callable' => 'mergeAfterSave', 'priority' => 100];
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        $events['ControllerAction.Model.merge.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        $events['ControllerAction.Model.onGetFieldLabel'] = ['callable' => 'onGetFieldLabel', 'priority' => 100];
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    /**
     * @param Event $mainEvent
     * @param ArrayObject $extra
     * @return Entity|mixed|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function merge(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;

        $first_entity = $this->getUserEntity($model, 'first_id');

        $merge_entity = $this->getUserEntity($model, 'merge_id');

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $model->fields = []; // reset all the fields

        $model->field('first_id', [
            'type' => 'readonly',
            'entity' => $first_entity
        ]);

        $model->field('merge_id');

        $extra = $this->addBackButton($extra, $model);
        // end back button
        if ($merge_entity) {
            $merging_fields = $this->getMergeFields($extra, $first_entity, $merge_entity, $model);
            $extra['merge_fields'] = $merging_fields;
            $model->controller->set('merge_fields', $merging_fields);
            $associations = $this->getAssociations($extra, $merge_entity, $first_entity, $model);
            $extra['associations'] = $associations;
            $model->controller->set('associations', $associations);
        }
        $model->controller->set('data', $first_entity);

        $request = $model->request;

        if ($request->is(['post', 'put'])) {
            $entity = $first_entity;
            $submit = isset($request->data['submit']) ? $request->data['submit'] : 'merge';
            $patchOptions = new ArrayObject([]);
            $patchOptions['associations'] = $associations;
            $requestData = new ArrayObject($request->data);

            $params = [$entity, $requestData, $extra];

            if ($submit == 'merge') {
                $process = function ($model, $entity) {
                    return $model->save($entity);
                };

                $event = $model->dispatchEvent('ControllerAction.Model.merge.beforeSave', [$entity, $requestData, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }
                if (is_callable($event->result)) {
                    $process = $event->result;
                }
                $result = $process($model, $entity);

                if (!$result) {
                    Log::write('debug', $entity->errors());
                }

                $event = $model->dispatchEvent('ControllerAction.Model.merge.afterSave', $params, $this);
                if ($event->isStopped()) {
                    return $event->result;
                }
                if ($result) {
                    $mainEvent->stopPropagation();
                    return $model->controller->redirect($model->url('view'));
                }
            }
        }
        return $first_entity;
    }

    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldFirstId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'merge') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->id;
            $attr['attr']['value'] = $entity->name_with_id;
        }
        return $attr;
    }

    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldMergeId(Event $event, array $attr, $action, Request $request)
    {
        $model = $this->_table;
        if ($action == 'merge') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'merge_id', 'name' => $model->aliasField('merge_id')];
            $attr['noResults'] = __('No Merge User found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $urlAction = $model->alias();
            $attr['url'] = ['controller' => $model->controller->name, 'action' => $urlAction, 'ajaxUserAutocomplete'];
            $Users = TableRegistry::get('User.Users');
            $requestData = $model->request->data;
            if (isset($requestData) && !empty($requestData[$model->alias()]['merge_id'])) {
                $mergeId = $requestData[$model->alias()]['merge_id'];
                $mergeName = $Users->get($mergeId)->name_with_id;
                $attr['attr']['value'] = $mergeName;

            }
            $iconSave = '<i class="fa fa-check"></i> ' . __('Merge');
            $attr['onNoResults'] = "$('.btn-save').hide()";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('merge')";
            $attr['onSelect'] = "$('#reload').click();";
        }
        return $attr;
    }

    /**
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function ajaxUserAutocomplete()
    {
        $this->_table->controller->autoRender = false;
        $this->_table->ControllerAction->autoRender = false;

        if ($this->_table->request->is(['ajax'])) {
            $term = $this->_table->request->query['term'];

            $Users = TableRegistry::get('User.Users');
            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $Users
                ->find()
                ->select([
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                    $Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $Users->aliasField('id')
                    ]
                )
                ->group([
                    $Users->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);
            if (!empty($term)) {
                $query = $this->_table->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
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
     * @param Event $event
     * @param $module
     * @param $field
     * @param $language
     * @param bool $autoHumanize
     * @return string|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {

        switch ($field) {
            case 'first_id':
                return __('Base Account');
            case 'merge_id':
                return __('Account to be merged');
            default:
                return $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * @param ArrayObject $extra
     * @param \Cake\ORM\Table $model
     * @return ArrayObject
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function addBackButton(ArrayObject $extra, \Cake\ORM\Table $model)
    {
        $action = 'view';
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $model->url($action);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        return $extra;
    }

    /**
     * @param Event $event
     * @param ArrayObject $extra
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (isset($toolbarButtonsArray['edit'])) {
            $toolbarButtonsArray['merge'] = $toolbarButtonsArray['edit'];
            $toolbarButtonsArray['merge']['url'][0] = 'merge';
            $toolbarButtonsArray['merge']['label'] = '<i class="fa kd-reassign"></i>';
            $toolbarButtonsArray['merge']['attr']['title'] = __('Merge');
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @param ArrayObject $options
     * @param ArrayObject $extra
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function mergeBeforeSave(Event $event, Entity $entity, ArrayObject $options, ArrayObject $extra)
    {
        $model = $this->_table;
        try {
            $merge_fields = $extra['merge_fields'];
            foreach ($merge_fields as $merge_field) {
                if ($merge_field['to_change']) {
                    $field = $merge_field['field'];
                    $entity->{$field} = $merge_field['result_value'];
                }
            }
        } catch (\Exception $exception) {
            $model->log($exception->getMessage(), 'debug');
        }
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @param ArrayObject $options
     * @param ArrayObject $extra
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function mergeAfterSave(Event $event, Entity $entity, ArrayObject $options, ArrayObject $extra)
    {
        /** POCOR-6677 starts- added AND condition to not do anything when model is SecurityRoles*/
        $model = $this->_table;

        $associations = $extra['associations'];

        $base_id = $options[$model->alias()]['first_id'];
        $merge_id = $options[$model->alias()]['merge_id'];
        $connection = ConnectionManager::get('default'); // Replace 'default' with your connection name
        $connection->disableForeignKeys();
        $success = true;
        $connection->execute("SET FOREIGN_KEY_CHECKS = 0");

        try {
            foreach ($associations as $key => $association) {
                $table_name = $association['table_name'];
                $column_name = $association['column_name'];
                try {
                    $connection->execute("ALTER TABLE $table_name DISABLE KEYS");
                } catch (\Exception $exception) {
                    $model->log($exception->getMessage(), 'debug');
                }
                $sql = "UPDATE $table_name SET $column_name = $base_id WHERE $column_name = $merge_id";
                try {
                    $connection->execute($sql);
                } catch (\Exception $exception) {
                    $model->log($exception->getMessage(), 'debug');
                }
                try {
                    $connection->execute("ALTER TABLE $table_name ENABLE KEYS");
                } catch (\Exception $exception) {
                    $model->log($exception->getMessage(), 'debug');
                }
            }
            if ($base_id && $merge_id) {
                $sql = "UPDATE security_users set `status` = 0 where `id` = $merge_id";
                try {
                    $connection->execute($sql);
                } catch (\Exception $exception) {
                    $model->log($exception->getMessage(), 'debug');
                }
            }
            $connection->commit();
        } catch (Exception $e) {
            // Handle any exceptions or errors that occur during the operation
            $connection->rollback();
            $success = false;
        }
        $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
        if ($success) {
            $model->Alert->success(__('User Accounts Are Merged Successfully'), ['type' => 'string', 'reset' => true]);
        }
        if (!$success) {
            $model->Alert->error(__('User Accounts Were Not Merged'), ['type' => 'string', 'reset' => true]);
        }
        $connection->enableForeignKeys();

    }

    /**
     * @param $model
     * @param $user_field
     * @return Entity|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getUserEntity($model, $user_field)
    {
        $requestData = $model->request->data;
//        Log::write('debug', $requestData);
        if ($user_field == 'first_id') {
            $encodedParam = $model->request->params['pass'][1];
            $user_id = $model->ControllerAction->paramsDecode($encodedParam)['id'];
        } else {
            $user_id = $requestData[$model->alias()][$user_field];
        }
        $user_entity = null;
        $user_ids = empty($user_id) ? ['id' => -1] : ['id' => $user_id];
        $user_id_keys = $model->getIdKeys($model, $user_ids);
        $contain = [];
        if ($model->exists([$user_id_keys])) {
            $query = $model->find()->where($user_id_keys)->contain($contain);
            $user_entity = $query->first();
        }
        return $user_entity;
    }

    /**
     * @param $merge_id
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getRelatedRecords($base_id, $merge_id)
    {
//         Get a database connection
        $relatedRecords = [];
        $connection = ConnectionManager::get('default');
        $connectionConfig = $connection->config();
        $database = $connectionConfig['database'];
        $query = $connection->newQuery();
        $query->select(['COLUMN_NAME', 'TABLE_NAME'])
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where([
                'COLUMN_NAME IN' => [
                    'security_user_id', 'student_id', 'user_id', 'core_user_id',
                    'staff_id', 'secondary_staff_id', 'assignee_id', 'guardian_id'
                ],
                'COLUMN_NAME NOT IN' => ['modified_user_id', 'created_user_id'],
                'TABLE_NAME NOT LIKE' => 'z%',
                'TABLE_SCHEMA' => $database
            ]);
        $results = $query->execute();
        $i = 0;
        foreach ($results as $result) {


            $column_name = $result['COLUMN_NAME'];
            $table_name = $result['TABLE_NAME'];
            $table = TableRegistry::get($table_name);
            $count = 0;
            try {
                $count = $table->find()
                    ->where([$table->aliasField($column_name) => $merge_id])
                    ->count();
            } catch (\Exception $exception) {
                Log::write('error', $exception->getMessage());
            }
            $title = Inflector::humanize(Inflector::underscore($table_name));
            if ($count > 0) {
                $result = ['model' => $title,
                    'count' => $count,
                    'table_name' => $table_name,
                    'column_name' => $column_name,
                    'base_id' => $base_id,
                    'merge_id' => $merge_id];
                $relatedRecords[$i] = $result;
            }
            $i++;
        }

        return $relatedRecords;
    }

    /**
     * @param ArrayObject $extra
     * @param Entity $merge_entity
     * @param Entity $first_entity
     * @param \Cake\ORM\Table $model
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getAssociations(ArrayObject $extra, Entity $merge_entity, Entity $first_entity, \Cake\ORM\Table $model)
    {
        $associations = [];
        if ($merge_entity) {
            $associations = $this->getRelatedRecords($first_entity->id, $merge_entity->id);
        }
        $cells = [];
        $totalCount = 0;
        foreach ($associations as $key => $row) {
            $modelName = $row['model'];
            $cells[] = [0 => __($modelName), 1 => $row['count']];
            $totalCount += $row['count'];
        }

        if ($totalCount > 0) { //POCOR-6964
            $model->Alert->warning(__('There are related records. They will be overwritten. This operation can not be undone'), ['type' => 'string', 'reset' => true]);
            $extra['cells'] = $cells;
            $model->field('associated_records', [
                'type' => 'table',
                'headers' => [__('External Table'), __('No of Records')],
                'cells' => $cells,
            ]);
        }
        return $associations;
    }


    /**
     * Compare two Cake\ORM\Entity objects and generate a comparison array.
     *
     * @param Entity $base_entity The old entity.
     * @param Entity $merge_entity The new entity.
     * @param array $exclude_fields An array of field names to exclude from comparison.
     * @return array An array of field comparisons with 'field', 'old_value', 'new_value', and 'changed' keys.
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function compareEntities(Entity $base_entity, Entity $merge_entity, $exclude_fields = [])
    {
        $comparison = [];
        if (empty($exclude_fields)) {
            $exclude_fields = ['id',
                'password',
                'status',
                'created_user_id',
                'created',
                'modified_user_id',
                'modified',
                'name',
                'name_with_id',
                'name_with_id_role',
                'default_identity_type',
                'has_special_needs'];
        }

        $related_fields = [
            'address_area_id' => 'area_administratives',
            'birthplace_area_id' => 'area_administratives',
            'gender_id' => 'genders',
            'nationality_id' => 'nationalities',
            'identity_type_id' => 'identity_types',
        ];

        $date_fields = ['date_of_birth', 'date_of_death'];

        // Get the list of fields in both entities
        $fields = array_merge($base_entity->toArray(), $merge_entity->toArray());

        foreach ($fields as $field => $merge_value) {
            if (in_array($field, $exclude_fields)) {
                continue; // Skip excluded fields
            }

            $base_value = trim($base_entity->get($field)) ? trim($base_entity->get($field)) : null;
            $merge_value = trim($merge_value) ? trim($merge_value) : null;
            $result_value = $base_value;
            $to_change = false;
            if (empty($result_value)) {
                $result_value = $merge_value;
                $to_change = true;
            }
            $changed = ($base_value !== $merge_value);
            if ($changed) {
                $field_name = Inflector::humanize(Inflector::underscore($field));
                $base_value_to_show = $base_value;
                $merge_value_to_show = $merge_value;
                $result_value_to_show = $result_value;
                if (array_key_exists($field, $related_fields)) {
                    $base_value_to_show = self::getRelatedName($related_fields[$field], $base_value);
                    $merge_value_to_show = self::getRelatedName($related_fields[$field], $merge_value_to_show);
                    $result_value_to_show = self::getRelatedName($related_fields[$field], $result_value_to_show);
                }
                if (in_array($field, $date_fields)) {
                    $base_value_to_show = date_create($base_value)->format('Y-m-d');
                    $merge_value_to_show = date_create($merge_value_to_show)->format('Y-m-d');
                    $result_value_to_show = date_create($result_value_to_show)->format('Y-m-d');
                }
                $comparison[] = [
                    'field_name' => $field_name,
                    'field' => $field,
                    'base_value' => $base_value,
                    'merge_value' => $merge_value,
                    'result_value' => $result_value,
                    'base_value_to_show' => $base_value_to_show,
                    'merge_value_to_show' => $merge_value_to_show,
                    'result_value_to_show' => $result_value_to_show,
                    'to_change' => $to_change,
                ];
            }
        }
        return $comparison;
    }

    /**
     * @param $tableName
     * @param $relatedField
     * @return string|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->name;
        } catch (RecordNotFoundException $e) {
            return 'RecordNotFoundException';
        }
        return null;
    }

    /**
     * @param Event $event
     * @param ArrayObject $buttons
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $model = $this->_table;
        switch ($model->action) {
            case 'merge':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Merge');
                $buttons[0]['attr']['value'] = 'merge';
                $buttons[1]['url'] = $model->url('view');
                break;
        }
    }

    /**
     * @param ArrayObject $extra
     * @param Entity $first_entity
     * @param Entity $merge_entity
     * @param \Cake\ORM\Table $model
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getMergeFields(ArrayObject $extra, Entity $first_entity, Entity $merge_entity, \Cake\ORM\Table $model)
    {
        $merging_fields = $this->compareEntities($first_entity, $merge_entity);

        $cells = [];
        $totalCount = 0;
        foreach ($merging_fields as $key => $row) {

            $cells[] = [
                0 => $row['field_name'],
                1 => $row['base_value_to_show'],
                2 => $row['merge_value_to_show'],
                3 => $row['result_value_to_show']
            ];
            $totalCount += 1;
        }

        if ($totalCount > 0) { //POCOR-6964
//            $model->Alert->error(__('There are related records. They will be overwritten. This operation can not be undone'), ['type' => 'string', 'reset' => true]);
            $model->field('merge_fields', [
                'type' => 'table',
                'headers' => [__('Field'), __('Base Value'), __('Merge Value'), __('Result')],
                'cells' => $cells,
            ]);
        }
        return $merging_fields;
    }


}
