<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;

use Cake\Datasource\ConnectionManager;

class RemoveBehavior extends Behavior
{
    private $recordHasAssociatedRecords = false;
    private $showForceDeleteFields = false;
    private $selectedForceDelete = false;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.remove'] = 'remove';
        $events['ControllerAction.Model.transfer'] = 'transfer';
        $events['ControllerAction.Model.transfer.afterAction'] = ['callable' => 'transferAfterAction', 'priority' => 5];
        $events['ControllerAction.Model.afterAction'] = ['callable' => 'afterAction', 'priority' => 100];
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    public function validationForceDelete(Validator $validator): Validator
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
            $primaryKey = $model->getPrimaryKey();
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
            $primaryKey = $model->getPrimaryKey();
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
            return $event->getResult();
        }

        $passwordErrors = [];
        $forceDeleteRecord = false;
        if (isset($request->getData()['submit']) && isset($request->getData()[$model->getAlias()]['force_delete'])) {
            $this->selectedForceDelete = $request->getData()[$model->getAlias()]['force_delete'];

            if ($this->selectedForceDelete && $request->getData()['submit'] == 'save') {
                $tempEntity = $model->newEntity($request->getData(), ['validate' => 'forceDelete']);
                if (array_key_exists('password', $tempEntity->getErrors())) {
                    $passwordErrors = $tempEntity->getErrors('password');
                } else {
                    $forceDeleteRecord = true; // allow delete if password is correct
                }
            }
        }

        $primaryKey = $model->getPrimaryKey();
        $result = true;
        $entity = null;
        if (!$request->is(['delete']) && !$forceDeleteRecord && $model->actions('remove') == 'restrict' ) {

            // Logic for restrict delete
            $entity = $model->newEntity([]);
            $controller = $model->controller;
            $modelNameArray = ['institution_students', 'institution_staff', 'institution_classes' , 'institution_subjects', 'institution_textbooks', 'institution_positions'];//POCOR-8333
            if(in_array($model->getTable(), $modelNameArray)){//POCOR-8333 starts
                $ids = empty($model->paramsPass(1)) ? [] : $model->paramsDecode($model->paramsPass(1));
            }else{ // POCOR-8333 ends
                $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
            }
            $sessionKey = $model->getRegistryAlias() . '.primaryKey';
            if (empty($ids)) {
                if ($model->Session->check($sessionKey)) {
                    $ids = $model->Session->read($sessionKey);
                } else if (!empty($model->ControllerAction->getQueryString())) {
                    // Query string logic not implemented yet, will require to check if the query string contains the primary key
                    $primaryKey = $model->getPrimaryKey();
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
                    return $event->getResult();
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
                if ($extra['associatedRecordsss'][0]['count'] > 0 ) { //POCOR-6964
                    $model->Alert->error('general.delete.restrictDeleteBecauseAssociation');
                    $this->recordHasAssociatedRecords = true;
                }elseif($extra['associatedRecords'][1]['count'] > 0){// POCOR-6975
                    $model->Alert->error('general.delete.restrictDeleteBecauseAssociation');
                    $this->recordHasAssociatedRecords = true;
                } else {
                    // Change the method to delete if the record can be deleted
                    $extra['config']['form'] = ['type' => 'DELETE'];
                    $this->recordHasAssociatedRecords = false;
                }

                /** Start POCOR-7253 */
                if(!empty($cells)){
                    foreach($cells as $key => $cell_val){
                        if(in_array($cell_val[0], array('Academic Periods','Institution Custom Fields','User Groups'))){
                            unset($cells[$key]);
                        }
                    }
                }
                /** End POCOR-7253 */
                $extra['cells'] = $cells;

                // check if force delete fields should be displayed
                // cannot force delete records where association is manually set in $extra['associatedRecords'] or where there are too many associated records or disableForceDelete is manually set in $extra['disableForceDelete']
                if ($model->AccessControl->isAdmin() && $this->recordHasAssociatedRecords) {
                    if (!$exceedAssociatedRecordLimit && !$extra->offsetExists('associatedRecords') && !$extra->offsetExists('disableForceDelete')) {
                        $this->showForceDeleteFields = true;

                        if ($this->selectedForceDelete) {
                            $model->Alert->warning('general.delete.cascadeDelete', ['reset' => true]);
                            if (!empty($passwordErrors)) {
                                $entity->getErrors('password', $passwordErrors); // set password errors
                            }
                        }
                    }
                }

                $controller->set('data', $entity);
            }
            else{  //POCOR-9202[START]
                $model->Alert->error('general.notExists');
                return $model->controller->redirect($extra['redirect']);
            }//POCOR-9202[END]
            return $entity;

        } else if ($request->is('delete') || $forceDeleteRecord ) {

            $ids = [];

            if ($model->actions('remove') == 'restrict') {
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        if (!empty($request->getData()[$model->getAlias()][$key])) {
                            $ids[$model->aliasField($key)] = $request->getData()[$model->getAlias()][$key];
                        } else {
                            $ids = [];
                            break;
                        }
                    }
                } else {
                    if (!empty($request->getData()[$model->getAlias()][$primaryKey])) {
                        $ids[$model->aliasField($primaryKey)] = $request->getData()[$model->getAlias()][$primaryKey];
                    } else {
                        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));
                    }
                }
            } else {
                $modalPrimaryKeys = array_key_exists('primaryKey', $request->getData()) ? $model->paramsDecode($request->getData('primaryKey')) : [];
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
           // echo "<pre>"; print_r($ids); die;
            if (!empty($ids)) {
                try {

                    //POCOR-8072 add conditon if else
                    if($request->getParam('plugin') == 'Scholarship' && $request->getParam('action') == 'ScholarshipApplicationInstitutionChoices'){
                        $array = $ids;
                        $ids = reset($array);
                        $entity = $model->get($ids);
                    }elseif($request->getParam('plugin') == 'Scholarship' && $request->getParam('action') == 'ScholarshipApplicationAttachments'){
                        $array = $ids;
                        $ids = reset($array);
                        $entity = $model->get($ids);
                    }else{
                     $entity = $model->get($ids);
                    }

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
            return $event->getResult();
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
            'keyField' => $model->getPrimaryKey(),
            'valueField' => 'name'
        ];

        $event = $model->dispatchEvent('ControllerAction.Model.transfer.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
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
                    return $event->getResult();
                }

                $notIdKeys = [];

                foreach ($idKeys as $key => $value) {
                    $notIdKeys[$key.' <>'] = $value;
                }

                $convertOptionsList = $query->find()
                    ->where($notIdKeys)
                    ->toArray();

                $convertOptions = [];
                $primaryKey = $model->getPrimaryKey();

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
                return $event->getResult();
            }

            // Need to review the following code
            if (empty($entity) || empty($entity->id)) {
                $mainEvent->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }
            return $entity;
        } else if ($request->is('delete')) {
            $primaryKey = $model->getPrimaryKey();
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
                    return $event->getResult();
                }

                $mainEvent->stopPropagation();
                return $model->controller->redirect($model->url('index', 'QUERY'));
            }
        }
    }

    private function doDelete($entity, ArrayObject $extra)
    {

        /** Start POCOR-7253 */
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 0');
        /** End POCOR-7253 */
        $model = $this->_table;
        $process = function ($model, $entity, $options) {
            return $model->delete($entity, $options);
        };

        $event = $model->dispatchEvent('ControllerAction.Model.onBeforeDelete', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->getResult(); }
        if (is_callable($event->getResult())) {
            $process = $event->getResult();
        }

        $options = $extra['options'];
        $result = $process($model, $entity, $options);

        /** Start POCOR-7253 */
        $connection->execute('SET foreign_key_checks = 1');
        /** End POCOR-7253 */

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
        $primaryKey = $model->getPrimaryKey();
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
            if (in_array($assoc->getDependent(), $dependent)) {
                if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                    // POCOR-8683-start
                    try{
                        $assocTable = self::getDynamicTableInstance($assoc->getAlias());
                    }catch (\Exception $exception){
                        continue;
                    }
                    // POCOR-8683-end
                    if (!array_key_exists($assoc->getAlias(), $associations)) {
//                        $count = 0; // POCOR-8683-start
                        $assocTable =$assoc;
                        if ($assoc->type() == 'manyToMany') {
                            //$assocTable = $assoc->junction()->getAlias(); // POCOR-8683-start
                            $assocTable = $assoc->junction(); // POCOR-8861
                        }
//                        Log::write('debug', $assoc);
                        $bindingKey = $assoc->getBindingKey();
                        $foreignKey = $assoc->getForeignKey();
                        $conditions = [];

                        if (is_array($foreignKey)) {
                            foreach ($foreignKey as $index => $key) {
                                $conditions[$assocTable->aliasField($key)] = $ids[$bindingKey[$index]];
                            }
                        } else {
                            $conditions[$assocTable->aliasField($foreignKey)] = $ids[$bindingKey];
                        }
//                        Log::write('debug', $conditions);

                        $query = $assocTable->find()->where($conditions);
                        $event = $model->dispatchEvent('ControllerAction.Model.getAssociatedRecordConditions', [$query, $assocTable, $extra], $this);

                        $count = $query->count();
                        $title = $assoc->getName();
                        $event = $assoc->dispatchEvent('ControllerAction.Model.transfer.getModelTitle', [], $this);
                        if (!is_null($event->getResult())) {
                            $title = $event->getResult();
                        }

                        $isAssociated = true;
                        if ($extra->offsetExists('excludedModels')) {
                            if (in_array($title, $extra['excludedModels'])) {
                                $isAssociated = false;
                            }
                        }
                        if ($isAssociated) {
                            if($count){
                            $associations[$assoc->getAlias()] = ['model' => $title, 'count' => $count];
                            }
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

    /*
     * POCOR-8683
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

}
