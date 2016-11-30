<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Datasource\Exception\RecordNotFoundException;

class RemoveBehavior extends Behavior
{
    private $recordHasAssociatedRecords = false;

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

    public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $model->request;
        if (($model->actions('remove') == 'transfer') && $request->is('delete') && $extra['result'] == true) {
            $convertFrom = $entity->id;
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

            $model->field('to_be_deleted', ['type' => 'readonly', 'attr' => ['value' => $entity->name]]);
            $model->field('associated_records', [
                'type' => 'table',
                'headers' => [__('Feature'), __('No of Records')],
                'cells' => $cells
            ]);
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

        $primaryKey = $model->primaryKey();
        $result = true;
        $entity = null;

        if ($request->is('get') && $model->actions('remove') == 'restrict') {
            // Logic for restrict delete
            $entity = $model->newEntity();
            $controller = $model->controller;
            $ids = $model->ControllerAction->paramsDecode($model->paramsPass(0));
            $idKeys = [];
            // May still be empty
            if (!empty($ids)) {
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        $idKeys[$model->aliasField($key)] = $ids[$key];
                    }
                } else {
                    $idKeys[$model->aliasField($primaryKey)] = $ids[$primaryKey];
                }

            }
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

                foreach ($associations as $row) {
                    $modelName = Inflector::humanize(Inflector::underscore($row['model']));
                    $cells[] = [__($modelName), $row['count']];
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

                $controller->set('data', $entity);
            }
            return $entity;
        } else if ($request->is('delete')) {
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
                        $ids = [];
                    }
                }
            } else {
                $modalPrimaryKeys = $model->ControllerAction->paramsDecode($request->data['primaryKey']);
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
                        $ids = [];
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
            if ($this->recordHasAssociatedRecords) {
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
            'keyField' => 'id',
            'valueField' => 'name'
        ];

        $event = $model->dispatchEvent('ControllerAction.Model.transfer.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->result;
        }

        $primaryKey = $model->primaryKey();
        $idKey = $model->aliasField($primaryKey);

        $result = true;
        $entity = $model->newEntity();

        if ($request->is('get')) {
            $id = $model->paramsPass(0);
            if ($model->exists([$idKey => $id])) {
                $entity = $model->get($id);

                $query = $model->find();
                $event = $model->dispatchEvent('ControllerAction.Model.transfer.onInitialize', [$entity, $query, $extra], $this);
                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $convertOptions = $query->find('list', $extra['options'])
                                ->where([$idKey . ' <> ' => $id])
                                ->toArray();

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

            if (empty($entity) || empty($entity->id)) {
                $mainEvent->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }
            return $entity;
        } else if ($request->is('delete')) {
            $id = $request->data($model->aliasField($primaryKey));
            if (!empty($id)) {
                try {
                    $entity = $model->get($id);
                } catch (RecordNotFoundException $exception) { // to handle concurrent deletes
                    $mainEvent->stopPropagation();
                    return $model->controller->redirect($model->url('index', 'QUERY'));
                }

                $convertTo = $request->data($model->aliasField('convert_to'));
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
                $ids[$key] = $entity->$key;
            }
        } else {
            $ids[$primaryKey] = $entity->$primaryKey;
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
                        foreach ($foreignKey as $index => $key) {
                            $conditions[$assocTable->aliasField($key)] = $ids[$bindingKey[$index]];
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
        $association->updateAll(
            [$association->foreignKey() => $to],
            [$association->foreignKey() => $from]
        );
    }

    private function updateBelongsToManyAssociations($association, $from, $to)
    {
        $modelAssociationTable = $association->junction();

        $foreignKey = $association->foreignKey();
        $targetForeignKey = $association->targetForeignKey();

        // List of the target foreign keys for subqueries
        $targetForeignKeys = $modelAssociationTable->find()
            ->select(['target' => $modelAssociationTable->aliasField($association->targetForeignKey())])
            ->where([
                $modelAssociationTable->aliasField($association->foreignKey()) => $to
            ]);

        $notUpdateQuery = $modelAssociationTable->query()
            ->select(['target_foreign_key' => 'TargetTable.target'])
            ->from(['TargetTable' => $targetForeignKeys]);

        if (!empty($notUpdateQuery)) {
            $condition = [];

            $condition = [
                $association->foreignKey() => $from,
                'NOT' => [
                    $association->foreignKey() => $from,
                    $association->targetForeignKey().' IN ' => $notUpdateQuery
                ]
            ];

            // Update all transfer records
            $modelAssociationTable->updateAll(
                [$association->foreignKey() => $to],
                $condition
            );

            // Delete orphan records
            $modelAssociationTable->deleteAll(
                [$association->foreignKey() => $from]
            );
        }
    }
}
