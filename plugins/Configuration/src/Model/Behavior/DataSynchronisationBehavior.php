<?php
namespace Configuration\Model\Behavior;

use Exception;
use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Http\Client;
use Cake\Network\Exception\NotFoundException;

class DataSynchronisationBehavior extends Behavior {
    private $type = 'None';
    private $attributes = [];
    private $firstNameMapping = null;
    private $middleNameMapping = null;
    private $thirdNameMapping = null;
    private $lastNameMapping = null;
    private $genderMapping = null;
    private $dateOfBirthMapping = null;
    private $nationalityMapping = null;
    private $identityTypeMapping = null;
    private $identityNumberMapping = null;
    private $userEndpoint = null;
    private $authEndpoint = null;

    private $newValues = [];

    public function initialize(array $config) {
        parent::initialize($config);
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $type = $ConfigItems->find()->where([
                $ConfigItems->aliasField('code') => 'external_data_source_type',
            ])
            ->first()
            ->value;

        $this->type = $type;

        if ($this->type != 'None') {
            $ExternalDataSourceAttributesTable = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
            $this->attributes = $ExternalDataSourceAttributesTable
                ->find('list', [
                    'keyField' => 'attribute_field',
                    'valueField' => 'value'
                ])
                ->where([
                    $ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => $this->type
                ])
                ->toArray();

            $this->firstNameMapping = $this->attributes['first_name_mapping'];
            $this->middleNameMapping = $this->attributes['middle_name_mapping'];
            $this->thirdNameMapping = $this->attributes['third_name_mapping'];
            $this->lastNameMapping = $this->attributes['last_name_mapping'];
            $this->genderMapping = $this->attributes['gender_mapping'];
            $this->dateOfBirthMapping = $this->attributes['date_of_birth_mapping'];
            $this->nationalityMapping = $this->attributes['nationality_mapping'];
            $this->identityTypeMapping = $this->attributes['identity_type_mapping'];
            $this->identityNumberMapping = $this->attributes['identity_number_mapping'];
            $this->authEndpoint = $this->attributes['token_uri'];
            $this->userEndpoint = $this->attributes['user_endpoint_uri'];
        }

    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.pull'] = 'pull';
        return $events;
    }

    public function pull(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->action = 'pull';
        $request = $model->request;
        $extra['config']['form'] = true;
        $extra['patchEntity'] = true;

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforeAction', [$extra], $this);
        if ($event->isStopped()) { return $event->result; }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeAction', [$extra], $this);
        if ($event->isStopped()) { return $event->result; }
        if ($event->result instanceof Table) {
            $model = $event->result;
        }

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

        $entity = false;

        if ($model->exists($idKeys)) {
            $query = $model->find()->where($idKeys);

            $event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.beforeQuery', [$query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeQuery', [$query, $extra], $this);

            $entity = $query->first();
        }

        $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterQuery', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        if ($entity) {
            if ($request->is(['get'])) {
                $event = $model->dispatchEvent('ControllerAction.Model.edit.onInitialize', [$entity, $extra], $this);
                if ($event->isStopped()) { return $event->result; }
            } else if ($request->is(['post', 'put'])) {
                $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
                $patchOptions = new ArrayObject([]);
                $requestData = new ArrayObject($request->data);

                $params = [$entity, $requestData, $patchOptions, $extra];

                if ($submit == 'save') {
                    $event = $model->dispatchEvent('ControllerAction.Model.addEdit.beforePatch', $params, $this);
                    if ($event->isStopped()) { return $event->result; }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforePatch', $params, $this);
                    if ($event->isStopped()) { return $event->result; }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    if ($extra['patchEntity']) {
                        $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterPatch', $params, $this);
                        if ($event->isStopped()) { return $event->result; }
                    }

                    $process = function ($model, $entity) {
                        return $model->save($entity);
                    };

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.beforeSave', [$entity, $requestData, $extra], $this);
                    if ($event->isStopped()) { return $event->result; }
                    if (is_callable($event->result)) {
                        $process = $event->result;
                    }

                    $result = $process($model, $entity);

                    if (!$result) {
                        Log::write('debug', $entity->errors());
                    }

                    $event = $model->dispatchEvent('ControllerAction.Model.edit.afterSave', $params, $this);
                    if ($event->isStopped()) { return $event->result; }

                    if ($result) {
                        $mainEvent->stopPropagation();
                        return $model->controller->redirect($model->url('view'));
                    }
                } else {
                    $patchOptions['validate'] = false;
                    $methodKey = 'on' . ucfirst($submit);

                    // Event: addEditOnReload
                    $eventKey = 'ControllerAction.Model.addEdit.' . $methodKey;
                    $method = 'addEdit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                    if ($event->isStopped()) { return $event->result; }

                    // Event: editOnReload
                    $eventKey = 'ControllerAction.Model.edit.' . $methodKey;
                    $method = 'edit' . ucfirst($methodKey);
                    $event = $this->dispatchEvent($model, $eventKey, $method, $params);
                    if ($event->isStopped()) { return $event->result; }

                    $patchOptionsArray = $patchOptions->getArrayCopy();
                    $request->data = $requestData->getArrayCopy();
                    $entity = $model->patchEntity($entity, $request->data, $patchOptionsArray);
                }
            }
            $model->controller->set('data', $entity);
        }

        $event = $model->dispatchEvent('ControllerAction.Model.addEdit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        $event = $model->dispatchEvent('ControllerAction.Model.edit.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        if (!$entity) {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }

    public function viewAfterAction(Event $event, Entity $entity, $extra)
    {
        $externalReference = $entity->getOriginal('external_reference');

        if (!empty($externalReference)) {
            $http = new Client();
            $credentialToken = TableRegistry::get('Configuration.ExternalDataSourceAttributes')->generateServerAuthorisationToken($this->type);
            $data = [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $credentialToken
            ];
            try {
                // Getting access token
                $response = $http->post($this->authEndpoint, $data);
                if ($response->statusCode() != '200') {
                    throw new NotFoundException('Not a successful response');
                }
                $body = json_decode($response->body(), true);
                if (!is_array($body) && !isset($body['access_token']) && !isset($body['token_type'])) {
                    throw new NotFoundException('Response body is in wrong format');
                }

                $placeHolder = '{external_reference}';
                $url = str_replace($placeHolder, $externalReference, $this->userEndpoint);

                // Getting data
                // Getting access token
                $http = new Client([
                    'headers' => ['Authorization' => $body['token_type'].' '.$body['access_token']]
                ]);
                $response = $http->get($url);
                if ($response->statusCode() != '200') {
                    throw new NotFoundException('Not a successful response');
                }
                $body = json_decode($response->body(), true);
                if (!is_array($body) && !isset($body['data'])) {
                    throw new NotFoundException('Response body is in wrong format');
                }

                $fieldOrder = array_keys($this->_table->fields);
                $this->newValues['first_name'] = $this->setChanges($entity->first_name, $this->getValue($body['data'], $this->firstNameMapping));
                $this->newValues['middle_name'] = $this->setChanges($entity->middle_name, $this->getValue($body['data'], $this->middleNameMapping));
                $this->newValues['third_name'] = $this->setChanges($entity->third_name, $this->getValue($body['data'], $this->thirdNameMapping));
                $this->newValues['last_name'] = $this->setChanges($entity->last_name, $this->getValue($body['data'], $this->lastNameMapping));
                $this->newValues['identity_number'] = $this->setChanges($entity->identity_number, $this->getValue($body['data'], $this->identityNumberMapping));
                $this->newValues['date_of_birth'] = $this->setDateChanges($entity->date_of_birth, $this->getValue($body['data'], $this->dateOfBirthMapping));
                $NationalitiesTable = TableRegistry::get('FieldOption.Nationalities');
                $nationalityName = trim($this->getValue($body['data'], $this->nationalityMapping));
                $nationalityArr = [
                    'id' => null,
                    'name' => ''
                ];
                if ($nationalityName) {
                    $nationality = $NationalitiesTable
                        ->find()
                        ->where([$NationalitiesTable->aliasField('name') => $nationalityName])
                        ->first();
                    if (empty($nationality)) {
                        $nationality = $NationalitiesTable->newEntity([
                            'name' => $nationalityName,
                            'visible' => 1,
                            'editable' => 1,
                            'default' => 0
                        ]);
                        $nationality = $NationalitiesTable->save($nationality);
                    }

                    $nationalityArr['id'] = $nationality->id;
                    $nationalityArr['name'] = $nationality->name;
                }
                $this->newValues['nationality_id'] = $this->setChanges($entity->main_nationality, $nationalityArr);

                $IdentityTypesTable = TableRegistry::get('FieldOption.IdentityTypes');
                $identityTypeName = trim($this->getValue($body['data'], $this->identityTypeMapping));
                $identityTypeArr = [
                    'id' => null,
                    'name' => ''
                ];
                if ($identityTypeName) {
                    $identityType = $IdentityTypesTable
                        ->find()
                        ->where([$IdentityTypesTable->aliasField('name') => $identityTypeName])
                        ->first();
                    if (empty($identityType)) {
                        $identityType = $IdentityTypesTable->newEntity([
                            'name' => $identityTypeName,
                            'visible' => 1,
                            'editable' => 1,
                            'default' => 0
                        ]);
                        $identityType = $IdentityTypesTable->save($identityType);
                    }
                    $identityTypeArr['id'] = $identityType->id;
                    $identityTypeArr['name'] = $identityType->name;
                }
                $this->newValues['identity_type_id'] = $this->setChanges($entity->main_identity_type, $identityTypeArr);

                $toolbarButton = [
                    'type' => 'button',
                    'label' => '<i class="fa fa-refresh"></i>',
                    'attr' => [
                        'class' => 'btn btn-xs btn-default',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false,
                        'title' => __('Synchronisation')
                    ],
                    'url' => [
                        'plugin' => $this->_table->controller->plugin,
                        'controller' => $this->_table->controller->name,
                        'action' => $this->_table->alias(),
                        '0' => 'pull',
                        '1' => $this->_table->paramsEncode(['id' => $entity->getOriginal('id')])
                    ]
                ];

                $toolbarButton['url'] = $this->_table->setQueryString($toolbarButton['url'], $this->newValues);

                $extra['toolbarButtons']['synchronise'] = $toolbarButton;

                $this->_table->field('first_name');
                $this->_table->field('middle_name');
                $this->_table->field('third_name');
                $this->_table->field('last_name');
                $this->_table->field('identity_number');
                $this->_table->field('date_of_birth');
                $this->_table->field('nationality_id');
                $this->_table->setFieldOrder($fieldOrder);
            } catch (NotFoundException $e) {
                $this->_table->Alert->error('general.failConnectToExternalSource');
            } catch (Exception $e) {
                $this->_table->Alert->error('general.failConnectToExternalSource');
            }
        }
    }

    public function onGetFirstName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['first_name'])) {
            return $this->newValues['first_name'];
        }
    }

    public function onGetMiddleName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['middle_name'])) {
            return $this->newValues['middle_name'];
        }
    }

    public function onGetThirdName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['third_name'])) {
            return $this->newValues['third_name'];
        }
    }

    public function onGetLastName(Event $event, Entity $entity)
    {
        if (isset($this->newValues['last_name'])) {
            return $this->newValues['last_name'];
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        if (isset($this->newValues['identity_number'])) {
            return $this->newValues['identity_number'];
        }
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        if (isset($this->newValues['date_of_birth'])) {
            return $this->newValues['date_of_birth'];
        }
    }

    public function onGetNationalityId(Event $event, Entity $entity)
    {
        if (isset($this->newValues['nationality_id'])) {
            return $this->newValues['nationality_id'];
        }
    }

    public function onGetIdentityTypeId(Event $event, Entity $entity)
    {
        if (isset($this->newValues['identity_type_id'])) {
            return $this->newValues['identity_type_id'];
        }
    }

    private function setDateChanges($oldDate, $newDate)
    {
        $oldValue = $this->_table->formatDate(new Time($oldDate));
        $newValue = $this->_table->formatDate(new Time($newDate));

        if ($oldValue != $newValue) {
            return '<span class="status past">'.$oldValue.'</span> <span class="transition-arrow"></span> <span class="status highlight">'.$newValue.'</span>';
        } else {
            return null;
        }
    }

    private function setChanges($oldValue, $newValue)
    {
        if (is_array($newValue)) {
            $oldValueId = isset($oldValue['id']) ? $oldValue['id'] : null;
            $oldValueName = isset($oldValue['name']) ? $oldValue['name'] : '';
            if ($oldValueId != $newValue['id']) {
                return '<span class="status past">'.__($oldValueName).'</span> <span class="transition-arrow"></span> <span class="status highlight">'.__($newValue['name']).'</span>';
            } else {
                return null;
            }
        } else if ($oldValue != $newValue) {
            return '<span class="status past">'.$oldValue.'</span> <span class="transition-arrow"></span> <span class="status highlight">'.$newValue.'</span>';
        } else {
            return null;
        }
    }

    private function getValue($body, $value)
    {
        return isset($body[$value]) ? $body[$value] : '';
    }
}
