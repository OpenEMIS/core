<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

class AreasTable extends AppTable {
    private $_fieldOrder = ['visible', 'code', 'name', 'area_level_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AreaParents', ['className' => 'Area.Areas', 'foreignKey' => 'parent_id']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels', 'foreignKey' => 'area_level_id']);
        $this->hasMany('Areas', ['className' => 'Area.Areas', 'foreignKey' => 'parent_id']);
        $this->hasMany('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsToMany('SecurityGroups', [
            'className' => 'Security.UserGroups',
            'joinTable' => 'security_group_areas',
            'foreignKey' => 'area_id',
            'targetForeignKey' => 'security_group_id',
            'through' => 'Security.SecurityGroupAreas',
            'dependent' => false,
        ]);
        $this->addBehavior('Tree');
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'parent_id',
            ]);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        return $events;
    }

    public function sync()
    {
        $url = $this->onGetUrl();
        $entity = $this->newEntity();
        $isApiValid = $this->isApiValid($url);

        if (!$isApiValid) {
            // API not valid, redirect to index and output error message
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.APIInvalid';
            $session->write($sessionKey, 'Areas.api_invalid');

            $url = $this->ControllerAction->url('index');
            return $this->controller->redirect($url);
        } else {
            // API valid run the process
            $securityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
            $model = $this;
            $extra = [];

            $missingAreaArray = [];
            $updateAreaArray = [];
            $associatedRecords = [];

            $areasTableArray = $this->onGetAreasTableArrays();
            $jsonArray = $this->onGetJsonArrays($url);
            $newAreaLists = $this->onGetNewAreaLists($url);
            $jsonCodeLists = $this->onGetJsonCodeLists($url);

            // hide all the unnecessary field
            foreach ($this->fields as $field => $attr) {
                $this->fields[$field]['visible'] = false;
            }

            $this->ControllerAction->field('data_will_be_synced_from', [
                'type' => 'readonly',
                'attr' => ['value' => $url]
            ]);

            // get the missing area that not available on the json array data(api)
            foreach ($areasTableArray as $key => $obj) {
                if ((!empty($areasTableArray)) && (!array_key_exists($obj['id'], $jsonArray))) {
                    $missingAreaArray[$key] = $obj;
                }
            }

            // do checking on the missing code. In case same code with different ID.
            foreach ($missingAreaArray as $key => $obj) {
                if (array_key_exists($obj['code'], $jsonCodeLists)) {
                    $updateAreaArray[$key] = $obj;
                }
            }

            // Pass data to ctp file to be displayed (sync_server.ctp)
            if ($this->request->is(['get'])) {
                $primaryKey = $this->ControllerAction->getPrimaryKey($model);
                $idKey = $model->aliasField($primaryKey);

                $extra = new ArrayObject([]);
                $extra['deleteStrategy'] = 'transfer';
                $extra['excludedModels'] = [$this->Areas->alias()];

                foreach ($missingAreaArray as $key => $obj) {
                    $id = $obj['id'];

                    if ($model->exists([$idKey => $id])) {
                        $entity = $model->find()->where([$idKey => $id])->first();
                        $records = $this->ControllerAction->getAssociatedRecords($model, $entity, $extra);
                        $associatedRecords[$key] = [
                            'id' => $id,
                            'code' => $obj['code'],
                            'name' => $obj['name'],
                            'institution' => $records['Institutions']['count'],
                            'security_group' => $records['SecurityGroups']['count'],
                        ];
                    }
                }

                $this->ControllerAction->field('sync_server', [
                    'type' => 'element',
                    'element' => 'Area.Areas/sync_server'
                ]);
                $this->controller->set('associatedRecords', $associatedRecords);
                $this->controller->set('newAreaLists', $newAreaLists);
            // end pass data
            } else if ($this->request->is(['post', 'put'])) {
                $submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
                if ($submit == 'save') {

                    // updating the association records (institution and security group area)
                    $requestData = $this->request->data;
                    if (array_key_exists($this->alias(), $requestData)) {
                        if (array_key_exists('transfer_areas', $requestData[$this->alias()])) {
                            foreach ($requestData[$this->alias()]['transfer_areas'] as $key => $obj) {
                                // update the association data (institution and securityGroupAreas)
                                $areaId = $obj['area_id'];
                                $newAreaId = $obj['new_area_id'];
                                $query = $this->Institutions->updateAll(
                                    ['area_id' => $newAreaId],
                                    ['area_id' => $areaId]
                                );

                                $securityGroupAreas->updateAll(
                                    ['area_id' => $newAreaId],
                                    ['area_id' => $areaId]
                                );
                            }
                        }
                    }
                    // End of updating the association records

                    // Update areas
                    if (!empty($updateAreaArray)) {
                        foreach ($updateArray as $key => $obj) {
                            $this->updateAll(
                                ['id' => $obj['new_area_id']],
                                ['id' => $obj['area_id']]
                            );
                        }
                    }

                    // Delete missing areas from areasTable
                    if (!empty($missingAreaArray)) {
                        foreach ($missingAreaArray as $key => $obj) {
                            $this->deleteAll([
                                $this->aliasField('code') => $obj['code']
                            ]);
                        }
                    }

                    // Update areasTable with data from jsonArray
                    if (!empty($jsonArray)) {
                        foreach ($jsonArray as $key => $obj) {
                            $areasArray = $this->newEntity([
                                'id' => $obj['id'],
                                'parent_id' => $obj['parent_id'],
                                'code' => $obj['code'],
                                'name' => $obj['name'],
                                'area_level_id' => $obj['area_level_id'],
                                'order' => $obj['order']
                            ]);
                            $this->save($areasArray);
                        }
                    }

                    $this->rebuildLftRght();

                    // redirect to index page
                    $url = $this->ControllerAction->url('index');
                    unset($url['section']);

                    return $this->controller->redirect($url);
                } else {
                    pr('reload');
                }
            }
        }

        $this->controller->set('data', $entity);

        $this->ControllerAction->renderView('/ControllerAction/edit');
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('area_level_id');
        $count = $this->find()->where([
                'OR' => [
                    [$this->aliasField('lft').' IS NULL'],
                    [$this->aliasField('rght').' IS NULL']
                ]
            ])
            ->count();
        if ($count) {
            $this->rebuildLftRght();
        }
        $this->fields['lft']['visible'] = false;
        $this->fields['rght']['visible'] = false;
    }

    public function rebuildLftRght()
    {
        $this->recover();
    }

    public function afterAction(Event $event)
    {
        $this->ControllerAction->setFieldOrder($this->_fieldOrder);
    }

    public function onBeforeDelete(Event $event, ArrayObject $options, $id)
    {
        $transferTo = $this->request->data['transfer_to'];
        $transferFrom = $id;
        // Require to update the parent id of the children before removing the node from the tree
        $this->updateAll(
                [
                    'parent_id' => $transferTo,
                    'lft' => null,
                    'rght' => null
                ],
                ['parent_id' => $transferFrom]
            );

        $entity = $this->get($id);
        $left = $entity->lft;
        $right = $entity->rght;

        // The left and right value of the children will all have to be rebuilt
        $this->updateAll(
                [
                    'lft' => null,
                    'rght' => null
                ],
                [
                    'lft > ' => $left,
                    'rght < ' => $right
                ]
            );

        $this->rebuildLftRght();
    }

    public function onGetConvertOptions(Event $event, Entity $entity, Query $query)
    {
        $level = $entity->area_level_id;
        $query->where([
            $this->aliasField('area_level_id') => $level
        ]);

        // if do not have any siblings but have child, can not be deleted
        if ($query->count() == 0 && $this->childCount($entity, true) > 0) {
            $this->Alert->warning('general.notTransferrable');
            $event->stopPropagation();
            return $this->controller->redirect($this->ControllerAction->url('index'));
        }
    }

    public function onGetAreasTableArrays()
    {
        $areasTableArray = [];

        $results = $this->find()
            ->where([$this->aliasField('visible') => 1])
            ->toArray()
            ;

        foreach ($results as $key => $obj) {
            $areasTableArray[$obj->id] = [
                'id' => $obj->id,
                'parent_id' => $obj->parent_id,
                'code' => $obj->code,
                'name' => $obj->name,
                'area_level_id' => $obj->area_level_id,
                'order' => $obj->order
            ];
        }

        return $areasTableArray;
    }

    public function onGetJsonArrays($url)
    {
        $json = json_decode(file_get_contents($url), true);
        $jsonAreaArray = $json['areas'];

        $jsonArray = [];
        $orderArray = [];
        foreach ($jsonAreaArray as $key => $obj) {
            // Null is the root parent id, as per cake update
            if ($obj['pnid'] === '-1') {
                $obj['pnid'] = null;
            }
            $level = $obj['lvl'];
            $orderArray[$level] = array_key_exists($level, $orderArray) ? ++$orderArray[$level] : 1;

            $jsonArray[$obj['nid']] = [
                'id' => $obj['nid'],
                'parent_id' => $obj['pnid'],
                'code' => $obj['id'],
                'name' => $obj['name'],
                'area_level_id' => $level,
                'order' => $orderArray[$level]
            ];
        }
        return $jsonArray;
    }

    public function onGetJsonCodeLists($url)
    {
        $jsonArray = $this->onGetJsonArrays($url);
        $jsonCodeLists = [];
        foreach ($jsonArray as $key => $obj) {
            $jsonCodeLists[$obj['code']] = $obj;
        }
        return $jsonCodeLists;
    }

    public function onGetNewAreaLists($url)
    {
        $jsonArray = $this->onGetJsonArrays($url);
        $newAreaLists = [];
        foreach ($jsonArray as $key => $obj) {
            $newAreaLists[$obj['id']] = $obj['name'];
        }
        return $newAreaLists;
    }

    public function indexBeforeAction(Event $event)
    {
        // Add breadcrumb
        $toolbarElements = [
            ['name' => 'Area.breadcrumb', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->fields['parent_id']['visible'] = false;

        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        if ($parentId != null) {
            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();
            $this->controller->set('crumbs', $crumbs);
        } else {
            $results = $this
                ->find('all')
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id') . ' IS NULL'])
                ->all();

            if ($results->count() == 1) {
                $parentId = $results
                    ->first()
                    ->id;

                $action = $this->ControllerAction->url('index');
                $action['parent'] = $parentId;
                return $this->controller->redirect($action);
            }
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        if ($parentId != null) {
            $query->where([$this->aliasField('parent_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('parent_id') . ' IS NULL']);
        }
    }

    public function indexAfterAction(Event $event, $data)
    {
        // display the redirected error message when API is invalid
        $session = $this->request->session();
        $sessionKey = $this->registryAlias() . '.APIInvalid';
        if ($session->check($sessionKey)) {
            $messageKey = $session->read($sessionKey);
            $this->Alert->error($messageKey);
            $session->delete($sessionKey);
        }
    }

    public function addEditBeforeAction(Event $event) {
        //Setup fields
        $this->_fieldOrder = ['area_level_id', 'code', 'name'];

        $this->fields['parent_id']['type'] = 'hidden';
        $parentId = $this->request->query('parent');

        if (is_null($parentId)) {
            $this->fields['parent_id']['attr']['value'] = null;
        } else {
            $this->fields['parent_id']['attr']['value'] = $parentId;

            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();

            $parentPath = '';
            foreach ($crumbs as $crumb) {
                $parentPath .= $crumb->name;
                $parentPath .= $crumb === end($crumbs) ? '' : ' > ';
            }

            $this->ControllerAction->field('parent', [
                'type' => 'readonly',
                'attr' => ['value' => $parentPath]
            ]);

            //array_unshift($this->_fieldOrder, "parent");
        }
    }

    public function onGetName(Event $event, Entity $entity) {
        return $event->subject()->HtmlField->link($entity->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => $this->alias,
            'index',
            'parent' => $entity->id
        ]);
    }

    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request) {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        $results = $this
            ->find()
            ->select([$this->aliasField('area_level_id')])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        $attr['type'] = 'select';
        if (!$results->isEmpty()) {
            $data = $results->first();
            $areaLevelId = $data->area_level_id;

            $levelResults = $this->AreaLevels
                ->find()
                ->select([$this->AreaLevels->aliasField('level')])
                ->where([$this->AreaLevels->aliasField('id') => $areaLevelId])
                ->all();
            if (!$levelResults->isEmpty()) {
                $levelData = $levelResults->first();
                $level = $levelData->level;

                $levelOptions = $this->AreaLevels
                    ->find('list')
                    ->where([$this->AreaLevels->aliasField('level >') => $level])
                    ->toArray();
                $attr['options'] = $levelOptions;
            }
        }

        return $attr;
    }

    // autocomplete used for UserGroups
    public function autocomplete($search) {
        $search = sprintf('%%%s%%', $search);

        $list = $this
            ->find()
            ->contain('AreaLevels')
            ->where([
                'OR' => [
                    $this->aliasField('name') . ' LIKE' => $search,
                    $this->aliasField('code') . ' LIKE' => $search,
                    'AreaLevels.name LIKE' => $search
                ]
            ])
            ->order(['AreaLevels.level', $this->aliasField('order')])
            ->all();

        $data = array();
        foreach($list as $obj) {
            $data[] = [
                'label' => sprintf('%s - %s (%s)', $obj->area_level->name, $obj->name, $obj->code),
                'value' => $obj->id
            ];
        }
        return $data;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        // if the API is set the add button will be replaced by sync button.(toolbar buttons)
        if ($action == 'index' && !empty($this->onGetUrl())) {
            $toolbarButtons['add']['type'] = 'hidden';
            $toolbarButtons['edit'] = $buttons['edit'];
            $toolbarButtons['edit']['label'] = '<i class="fa fa-refresh"></i>';
            $toolbarButtons['edit']['type'] = 'button';
            $toolbarButtons['edit']['attr'] = $attr;
            $toolbarButtons['edit']['attr']['title'] = __('Synchronize');
            $toolbarButtons['edit']['url'][0] = 'sync';
        }

        // on the view page when the API is set, the edit button will be hidden.
        if ($action == 'view' && !empty($this->onGetUrl())) {
            $toolbarButtons['edit']['type'] = 'hidden';
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        // when the API is set, the edit and remove action buttons will be unset.
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!empty($this->onGetUrl())) {
            unset($buttons['remove']);
            unset($buttons['edit']);
        }

        return $buttons;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        // on the sync page the save button was renamed to confirm button.
        switch ($this->action) {
            case 'sync':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                break;
        }
    }

    public function onGetUrl()
    {
        // get the url from the config table
        $resultURL = [];
        $configAdministrativeBoundaries = TableRegistry::get('Configuration.ConfigAdministrativeBoundaries');
        $resultURL = $configAdministrativeBoundaries
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'url'
            ])
            ->first();
        return $resultURL;
    }

    public function isApiValid($url=null)
    {
        // check if API is valid,
        if (is_null($url)) {
            return false;
        } else {
            // add @ in front file_get_contents to turn off warning message, will be cater on the condition.
            $content = @file_get_contents($url);

            if ($content === false) {
                // contain invalid url, validation will display error message.
                return false;
            } else {
                // Url is the correct format, will check the value inside, if its empty will show error message.
                $jsonArray = json_decode($content, true);
                $areas = $jsonArray['areas'];

                if (empty($areas)) {
                    return false;
                }

                $expectedKeys = ['nid', 'pnid', 'id', 'name', 'lvl'];
                // will check if the json array contain the expected keys.
                // if jsonArray have the key it will be removed.
                // if $tempkeys not empty means the json array not in correct format.
                foreach ($areas as $count => $area) {
                    $tempKeys = $expectedKeys;
                    foreach ($area as $key => $value) {
                        if (in_array($key, $tempKeys)) {
                            $tempKeys = array_diff($tempKeys, [$key]);
                        }
                    }
                    if (!empty($tempKeys)) {
                        return false;
                    }
                }
            }

            return true;
        }
    }
}
