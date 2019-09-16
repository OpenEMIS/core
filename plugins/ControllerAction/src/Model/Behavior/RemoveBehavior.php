<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;

class RemoveBehavior extends Behavior
{
    private $recordHasAssociatedRecords = false;
    private $showForceDeleteFields = false;
    private $selectedForceDelete = false;

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.remove'] = 'remove';
        $events['ControllerAction.Model.transfer'] = 'transfer';
        $events['ControllerAction.Model.transfer.afterAction'] = ['callable' => 'transferAfterAction', 'priority' => 5];
        $events['ControllerAction.Model.afterAction'] = ['callable' => 'afterAction', 'priority' => 100];
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    public function validationForceDelete(Validator $validator)
    {
        $validator = $this->_table->validationDefault($validator);
        return $validator
            ->requirePresence('password')
            ->notEmpty('password')
            ->add('password', 'ruleCheckPassword', [
                'rule' => 'checkPassword',
                'provider' => 'table',
                'message' => __('Incorrect password.')
            ]);
    }

    public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $model->request;
        if (($model->actions('remove') == 'transfer') && $request->is('delete') && $extra['result'] == true) {
            $convertFrom = $model->getIdKeys($model, $entity, false);
            $convertTo = $entity->convert_to;

            foreach ($model->associations() as $assoc) {
                if (!$assoc->dependent()) {
                    if ($assoc->type() == 'oneToMany') {
                        $this->updateHasManyAssociations($assoc, $convertFrom, $convertTo);
                    } else if ($assoc->type() == 'manyToMany') {
                        $this->updateBelongsToManyAssociations($assoc, $convertFrom, $convertTo);
                    }
                }
            }
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $visibleFields = [''];
        if ($model->action == 'transfer') {
            $entity = $extra['entity'];
            $convertOptions = $extra['convertOptions'];
            $cells = $extra['cells'];

            $model->fields = [];
            $primaryKey = $model->primaryKey();
            if (is_array($primaryKey)) {
                foreach ($primaryKey as $key) {
                    $model->field($key, ['type' => 'hidden']);
                }
            } else {
                $model->field($primaryKey, ['type' => 'hidden']);
            }
            $model->field('convert_from', ['type' => 'readonly', 'attr' => ['value' => $entity->name]]);
            $model->field('convert_to', ['type' => 'select', 'options' => $convertOptions, 'attr' => ['required' => 'required']]);
            $model->field('apply_to', [
                'type' => 'table',
                'headers' => [__('Feature'), __('No of Records')],
                'cells' => $cells
            ]);
        } else if ($model->actions('remove') == 'restrict' && $model->action == 'remove') {
            $entity = $extra['entity'];
            $cells = $extra['cells'];
            $model->fields = [];
            $primaryKey = $model->primaryKey();
            if (is_array($primaryKey)) {
                foreach ($primaryKey as $key) {
                    $model->field($key, ['type' => 'hidden']);
                }
            } else {
                $model->field($primaryKey, ['type' => 'hidden']);
            }

            $deletedValue = '';
            if ($entity->has('showDeletedValueAs')) {
                $deletedValue = $entity->showDeletedValueAs;
            } else if ($entity->has('name')) {
                $deletedValue = $entity->name;
            }

            $model->field('to_be_deleted', ['type' => 'readonly', 'attr' => ['value' => $deletedValue]]);
            $model->field('associated_records', [
                'type' => 'table',
                'headers' => [__('Feature'), __('No of Records')],
                'cells' => $cells
            ]);

            // force delete fields for super admin
            // POCOR-4999
            /*if ($this->showForceDeleteFields) {
                $model->field('force_delete', [
                    'type' => 'select',
                    'options' => [1 => __('Yes'), 0 => __('No')],
                    'default' => 0,  // default selected is no
                    'onChangeReload' => true
                ]);

                $passwordType = $this->selectedForceDelete ? 'password' : 'hidden';
                $model->field('password', ['type' => $passwordType]);

                $model->setFieldOrder(['to_be_deleted', 'associated_records', 'force_delete', 'password']);
            }*/
        }
    }

    public function remove(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $model->request;
        $extra['options'] = [];
        $extra['excludedModels'] = [];
        $extra['redirect'] = $model->url('index', 'QUERY');

        $event = $model->dispatchEvent('ControllerAction.Model.delete.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }

        $passwordErrors = [];
        $forceDeleteRecord = false;
        if (isset($request->data['submit']) && isset($request->data[$model->alias()]['force_delete'])) {
            $this->selectedForceDelete = $request->data[$model->alias()]['force_delete'];

            if ($this->selectedForceDelete && $request->data['submit'] == 'save') {
                $tempEntity = $model->newEntity($request->data, ['validate' => 'forceDelete']);
                if (array_key_exists('password', $tempEntity->errors())) {
                    $passwordErrors = $tempEntity->errors('password');
                } else {
                    $forceDeleteRecord = true; // allow delete if password is correct
                }
            }
        }

        $primaryKey = $model->primaryKey();
        $result = true;
        $entity = null;

        if (!$request->is(['delete']) && !$forceDeleteRecord && $model->actions('remove') == 'restrict' ) {
            // Logic for restrict delete
            $entity = $model->newEntity();
            $controller = $model->controller;
            $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
            $sessionKey = $model->registryAlias() . '.primaryKey';
            if (empty($ids)) {
                if ($model->Session->check($sessionKey)) {
                    $ids = $model->Session->read($sessionKey);
                } else if (!empty($model->ControllerAction->getQueryString())) {
                    // Query string logic not implemented yet, will require to check if the query string contains the primary key
                    $primaryKey = $model->primaryKey();
                    $ids = $model->ControllerAction->getQueryString($primaryKey);
                }
            }
            $idKeys = $model->getIdKeys($model, $ids);
            if ($model->exists($idKeys)) {
                $entity = $model->get($idKeys);

                $query = $model->find();
                $event = $model->dispatchEvent('ControllerAction.Model.delete.onInitialize', [$entity, $query, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $associations = $this->getAssociatedRecords($model, $entity, $extra);
                if ($extra->offsetExists('excludedModels')) {
                    $associations = array_diff_key($associations, array_flip($extra['excludedModels']));
                }
                if ($extra->offsetExists('associatedRecords')) {
                    $associations = array_merge($associations, $extra['associatedRecords']);
                }
                $cells = [];
                $totalCount = 0;
                $associatedRecordLimit = 100;
                $exceedAssociatedRecordLimit = false;

                foreach ($associations as $row) {
                    $modelName = Inflector::humanize(Inflector::underscore($row['model']));
                    $cells[] = [__($modelName), $row['count']];
                    if ($row['count'] > $associatedRecordLimit) {
                        $exceedAssociatedRecordLimit = true;
                    }
                    $totalCount += $row['count'];
                }
                if ($totalCount > 0) {
                    $model->Alert->error('general.delete.restrictDeleteBecauseAssociation');
                    $this->recordHasAssociatedRecords = true;
                } else {
                    // Change the method to delete if the record can be deleted
                    $extra['config']['form'] = ['type' => 'DELETE'];
                    $this->recordHasAssociatedRecords = false;
                }
                $extra['cells'] = $cells;

                // check if force delete fields should be displayed
                // cannot force delete records where association is manually set in $extra['associatedRecords'] or where there are too many associated records or disableForceDelete is manually set in $extra['disableForceDelete']
                if ($model->AccessControl->isAdmin() && $this->recordHasAssociatedRecords) {
                    if (!$exceedAssociatedRecordLimit && !$extra->offsetExists('associatedRecords') && !$extra->offsetExists('disableForceDelete')) {
                        $this->showForceDeleteFields = true;

                        if ($this->selectedForceDelete) {
                            $model->Alert->warning('general.delete.cascadeDelete', ['reset' => true]);
                            if (!empty($passwordErrors)) {
                                $entity->errors('password', $passwordErrors); // set password errors
                            }
                        }
                    }
                }

                $controller->set('data', $entity);
            }
            return $entity;
        } else if ($request->is('delete') || $forceDeleteRecord) {
            $ids = [];

            if ($model->actions('remove') == 'restrict') {
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        if (!empty($request->data[$model->alias()][$key])) {
                            $ids[$model->aliasField($key)] = $request->data[$model->alias()][$key];
                        } else {
                            $ids = [];
                            break;
                        }
                    }
                } else {
                    if (!empty($request->data[$model->alias()][$primaryKey])) {
                        $ids[$model->aliasField($primaryKey)] = $request->data[$model->alias()][$primaryKey];
                    } else {
                        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
                    }
                }
            } else {
                $modalPrimaryKeys = array_key_exists('primaryKey', $request->data) ? $model->paramsDecode($request->data['primaryKey']) : [];
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        if (!empty($modalPrimaryKeys[$key])) {
                            $ids[$model->aliasField($key)] = $modalPrimaryKeys[$key];
                        } else {
                            $ids = [];
                            break;
                        }
                    }
                } else {
                    if (!empty($modalPrimaryKeys[$primaryKey])) {
                        $ids[$model->aliasField($primaryKey)] = $modalPrimaryKeys[$primaryKey];
                    } else {
                        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
                    }
                }
            }
            if (!empty($ids)) {
                try {
                    $entity = $model->get($ids);
                } catch (RecordNotFoundException $exception) { // to handle concurrent deletes
                    $mainEvent->stopPropagation();
                    return $model->controller->redirect($extra['redirect']);
                }
                $result = $this->doDelete($entity, $extra);
            }
        }
        $extra['result'] = $result;
        $extra['forceDeleteRecord'] = $forceDeleteRecord;

        $event = $model->dispatchEvent('ControllerAction.Model.delete.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }

        $mainEvent->stopPropagation();
        return $model->controller->redirect($extra['redirect']);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $model = $this->_table;
        if ($model->action == 'remove' && $model->actions('remove') == 'restrict') {
            if ($this->recordHasAssociatedRecords && !$this->selectedForceDelete) {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        }
    }

    public function transfer(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $controller = $model->controller;
        $request = $model->request;
        $extra['config']['form'] = ['type' => 'DELETE'];
        $extra['options'] = [
            'keyField' => $model->primaryKey(),
            'valueField' => 'name'
        ];

        $event = $model->dispatchEvent('ControllerAction.Model.transfer.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }

        $result = true;
        $entity = $model->newEntity();

        if ($request->is('get')) {
            $ids = $model->paramsDecode($model->paramsPass(0));
            $idKeys = $model->getIdKeys($model, $ids);
            if ($model->exists($idKeys)) {
                $entity = $model->get($idKeys);

                $query = $model->find();
                $event = $model->dispatchEvent('ControllerAction.Model.transfer.onInitialize', [$entity, $query, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $notIdKeys = [];

                foreach ($idKeys as $key => $value) {
                    $notIdKeys[$key.' <>'] = $value;
                }

                $convertOptionsList = $query->find()
                    ->where($notIdKeys)
                    ->toArray();

                $convertOptions = [];
                $primaryKey = $model->primaryKey();

                foreach ($convertOptions as $value) {
                    $keysToEncode = $model->getIdKeys($model, $value, false);
                    $encodedKey = $model->paramsEncode($keysToEncode);
                    $convertOptions[$encodedKey] = $value->{$extra['options']['valueField']};
                }

                if (empty($convertOptions)) {
                    $convertOptions[''] = __('No Available Options');
                }

                $associations = $this->getAssociatedRecords($model, $entity, $extra);
                $cells = [];
                foreach ($associations as $row) {
                    $modelName = Inflector::humanize(Inflector::underscore($row['model']));
                    $cells[] = [__($row['model']), $row['count']];
                }

                $extra['convertOptions'] = $convertOptions;
                $extra['cells'] = $cells;

                $controller->set('data', $entity);
            }

            $event = $model->dispatchEvent('ControllerAction.Model.transfer.afterAction', [$entity, $extra], $this);
            if ($event->isStopped()) {
                $mainEvent->stopPropagation();
                return $event->result;
            }

            // Need to review the following code
            if (empty($entity) || empty($entity->id)) {
                $mainEvent->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }
            return $entity;
        } else if ($request->is('delete')) {
            $primaryKey = $model->primaryKey();
            $idKeys = $model->getIdKeys($model, $request->data($this->alias()));
            if (!empty($idKeys)) {
                try {
                    $entity = $model->get($idKeys);
                } catch (RecordNotFoundException $exception) { // to handle concurrent deletes
                    $mainEvent->stopPropagation();
                    return $model->controller->redirect($model->url('index', 'QUERY'));
                }

                $convertTo = $model->paramsDecode($request->data($model->aliasField('convert_to')));
                $entity->convert_to = $convertTo;
                $doDelete = true;

                if (empty($convertTo)) {
                    $extra['deleteStrategy'] = 'transfer';
                    if ($this->hasAssociatedRecords($model, $entity, $extra)) {
                        $doDelete = false;
                    }
                }

                $result = false;
                if ($doDelete) {
                    $result = $this->doDelete($entity, $extra);
                }
                $extra['result'] = $result;

                $event = $model->dispatchEvent('ControllerAction.Model.transfer.afterAction', [$entity, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $mainEvent->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }
        }
    }

    private function doDelete($entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $process = function ($model, $entity, $options) {
            return $model->delete($entity, $options);
        };

        $event = $model->dispatchEvent('ControllerAction.Model.onBeforeDelete', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }
        if (is_callable($event->result)) {
            $process = $event->result;
        }

        $options = $extra['options'];
        $result = $process($model, $entity, $options);

        return $result;
    }

    public function getAssociatedRecords($model, $entity, $extra)
    {
        $dependent = [true, false];
        if ($extra->offsetExists('deleteStrategy')) {
            switch ($extra['deleteStrategy']) {
                case 'restrict':
                    $dependent = [true, false];
                    break;
                case 'transfer':
                    $dependent = [false];
                    break;
            }
        }
        $primaryKey = $model->primaryKey();
        $ids = [];
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key) {
                $ids[$key] = $entity->{$key};
            }
        } else {
            $ids[$primaryKey] = $entity->{$primaryKey};
        }
        $associations = [];
        foreach ($model->associations() as $assoc) {
            if (in_array($assoc->dependent(), $dependent)) {
                if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                    if (!array_key_exists($assoc->alias(), $associations)) {
                        $count = 0;
                        $assocTable = $assoc;
                        if ($assoc->type() == 'manyToMany') {
                            $assocTable = $assoc->junction();
                        }
                        $bindingKey = $assoc->bindingKey();
                        $foreignKey = $assoc->foreignKey();

                        $conditions = [];

                        if (is_array($foreignKey)) {
                            foreach ($foreignKey as $index => $key) {
                                $conditions[$assocTable->aliasField($key)] = $ids[$bindingKey[$index]];
                            }
                        } else {
                            $conditions[$assocTable->aliasField($foreignKey)] = $ids[$bindingKey];
                        }

                        $query = $assocTable->find()->where($conditions);
                        $event = $model->dispatchEvent('ControllerAction.Model.getAssociatedRecordConditions', [$query, $assocTable, $extra], $this);

                        $count = $query->count();
                        $title = $assoc->name();
                        $event = $assoc->dispatchEvent('ControllerAction.Model.transfer.getModelTitle', [], $this);
                        if (!is_null($event->result)) {
                            $title = $event->result;
                        }

                        $isAssociated = true;
                        if ($extra->offsetExists('excludedModels')) {
                            if (in_array($title, $extra['excludedModels'])) {
                                $isAssociated = false;
                            }
                        }
                        if ($isAssociated) {
                            $associations[$assoc->alias()] = ['model' => $title, 'count' => $count];
                        }
                    }
                }
            }
        }
        return $associations;
    }

    public function hasAssociatedRecords($model, $entity, $extra)
    {
        $records = $this->getAssociatedRecords($model, $entity, $extra);
        $found = false;
        foreach ($records as $count) {
            if ($count['count'] > 0) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    private function updateHasManyAssociations($association, $from, $to)
    {
        $bindingKey = $association->bindingKey();
        $foreignKey = $association->foreignKey();

        $fromConditions = [];

        if (is_array($foreignKey)) {
            foreach ($foreignKey as $index => $key) {
                $fromConditions[$key] = $from[$bindingKey[$index]];
            }
        } else {
            $fromConditions[$foreignKey] = $from[$bindingKey];
        }

        $toConditions = [];

        if (is_array($foreignKey)) {
            foreach ($foreignKey as $index => $key) {
                $toConditions[$key] = $to[$bindingKey[$index]];
            }
        } else {
            $toConditions[$foreignKey] = $to[$bindingKey];
        }

        $association->updateAll(
            [$toConditions],
            [$fromConditions]
        );
    }

    private function updateBelongsToManyAssociations($association, $from, $to)
    {
        $modelAssociationTable = $association->junction();

        $bindingKey = $association->bindingKey();
        $foreignKey = $association->foreignKey();

        $toConditions = [];

        if (is_array($foreignKey)) {
            foreach ($foreignKey as $index => $key) {
                $toConditions[$key] = $to[$bindingKey[$index]];
            }
        } else {
            $toConditions[$foreignKey] = $to[$bindingKey];
        }

        $fromConditions = [];

        if (is_array($foreignKey)) {
            foreach ($foreignKey as $index => $key) {
                $fromConditions[$key] = $from[$bindingKey[$index]];
            }
        } else {
            $fromConditions[$foreignKey] = $from[$bindingKey];
        }

        $targetForeignKey = $association->targetForeignKey();

        // List of the target foreign keys for subqueries
        $targetForeignKeys = $modelAssociationTable->find()
            ->select($targetForeignKey)
            ->where($toConditions);

        $notUpdateQuery = $modelAssociationTable->query()
            ->select($targetForeignKey)
            ->from(['TargetTable' => $targetForeignKeys]);

        if (!empty($notUpdateQuery)) {
            $condition = [];

            $targetForeignKeyString = '';
            if (is_array($targetForeignKey)) {
                $targetForeignKeyString = '('. impode(', ', $targetForeignKey) . ')';
            } else {
                $targetForeignKeyString = $targetForeignKey;
            }

            $notCondition = $fromConditions;
            $notCondition[$association->targetForeignKey().' IN '] = $notUpdateQuery;

            $condition = [
                $fromConditions,
                'NOT' => $notCondition
            ];

            // Update all transfer records
            $modelAssociationTable->updateAll(
                $toConditions,
                $condition
            );

            // Delete orphan records
            $modelAssociationTable->deleteAll(
                $fromConditions
            );
        }
    }

    public static function checkPassword($field, array $globalData)
    {
        $Users = TableRegistry::get('User.Users');
        $model = $globalData['providers']['table'];
        return ((new DefaultPasswordHasher)->check($field, $Users->get($model->Auth->user('id'))->password));
    }
}
