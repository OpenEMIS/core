<?php
namespace Area\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class AreasTable extends ControllerActionTable
{
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
        $this->behaviors()->get('ControllerAction')->config([
            'actions' => [
                'remove' => 'restrict'
            ],
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['ControllerAction.Model.synchronize'] = 'synchronize';
        return $events;
    }

    public function synchronize(Event $mainEvent, ArrayObject $extra)
    {
        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];
        $this->fields = []; // reset all the fields

        $entity = $this->newEntity();

        $url = $this->onGetUrl();
        $this->field('data_url', [
            'type' => 'readonly',
            'attr' => ['label' => __('Data will be synchronized from'), 'value' => $url]
        ]);

        $isApiValid = $this->isApiValid($url);

        if (!$isApiValid) {
            // API not valid. When saving the API was valid, then the link become invalid.
            // Display error message
            $this->Alert->error('Areas.api_invalid');

            // redirect to index page
            $url = $this->url('index');
            $mainEvent->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            // API valid, run the process
            $model = $this;
            $extra = [];

            $areasTableArray = $this->onGetAreasTableArrays();
            $jsonArray = $this->onGetJsonArrays($url);
            $newAreaLists = $this->onGetNewAreaLists($url);

            $missingAreaArray = $this->onGetMissingArea($areasTableArray, $jsonArray);

            if ($this->request->is(['get'])) {
                // getAssociatedRecords and passed it to the sync_server.ctp to be displayed
                $associatedRecords = $this->onGetAssociatedRecords($missingAreaArray);

                $this->field('sync_server', [
                    'type' => 'element',
                    'element' => 'Area.Areas/sync_server'
                ]);
                $this->controller->set('associatedRecords', $associatedRecords);
                $this->controller->set('newAreaLists', $newAreaLists);
            } else if ($this->request->is(['post', 'put'])) {
                // update the related table
                $requestData = $this->request->data;
                $this->doUpdateAssociatedRecord($requestData);
                $this->doReplaceAreaTable($missingAreaArray, $jsonArray);

                // redirect to index page
                $url = $this->url('index');
                $mainEvent->stopPropagation();
                return $this->controller->redirect($url);
            }
        }
        $this->controller->set('data', $entity);
        return $entity;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('area_level_id');
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

        $this->field('lft', ['visible' => false]);
        $this->field('rght', ['visible' => false]);

        // Unset the Edit and remove toolbar buttons when the API is set
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (!empty($this->onGetUrl())) {
            unset($toolbarButtonsArray['edit']);
            unset($toolbarButtonsArray['remove']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function rebuildLftRght()
    {
        $this->recover();
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
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
            return $this->controller->redirect($this->url('index'));
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
                $obj['pnid'] = NULL;
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

    public function onGetNewAreaLists($url)
    {
        $jsonArray = $this->onGetJsonArrays($url);
        $newAreaLists = [];
        foreach ($jsonArray as $key => $obj) {
            $newAreaLists[$obj['id']] = $obj['name'];
        }
        return $newAreaLists;
    }

    public function onGetMissingArea($areasTableArray, $jsonArray)
    {
        // get the missing area that not available on the json array data(api)
        $missingAreaArray = [];

        foreach ($areasTableArray as $key => $obj) {
            if ((!empty($areasTableArray)) && (!array_key_exists($obj['id'], $jsonArray))) {
                $missingAreaArray[$key] = $obj;
            }
        }

        return $missingAreaArray;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // Add breadcrumb
        $toolbarElements = [
            ['name' => 'Area.breadcrumb', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->field('parent_id', ['visible' => false]);

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

                $action = $this->url('index');
                $action['parent'] = $parentId;
                return $this->controller->redirect($action);
            }
        }

        //logic to hide 'add' button and display sync button if the API set
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (!empty($this->onGetUrl())) {
            $toolbarButtonsArray['sync'] = $toolbarButtonsArray['add'];
            $toolbarButtonsArray['sync']['label'] = '<i class="fa fa-refresh"></i>';
            $toolbarButtonsArray['sync']['attr']['title'] = __('Synchronize');
            $toolbarButtonsArray['sync']['url'][0] = 'synchronize';

            unset($toolbarButtonsArray['add']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        if ($parentId != null) {
            $query->where([$this->aliasField('parent_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('parent_id') . ' IS NULL']);
        }
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
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

            $this->field('parent', [
                'type' => 'readonly',
                'attr' => ['value' => $parentPath]
            ]);

            //array_unshift($this->_fieldOrder, "parent");
        }
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $event->subject()->HtmlField->link($entity->name, [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => $this->alias,
            'index',
            'parent' => $entity->id
        ]);
    }

    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
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
    public function autocomplete($search)
    {
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
            case 'synchronize':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                break;
        }
    }

    public function onGetUrl()
    {
        // get the url from the config table
        $resultURL = [];
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $resultURL = $configItems
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'value'
            ])
            ->where([$configItems->aliasField('type') => 'Administrative Boundaries'])
            ->first();
        return $resultURL;
    }

    public function onGetAssociatedRecords($missingAreaArray)
    {
        // get the associated data to be displayed and pass it to Sync page.
        $model = $this;
        $primaryKey = $this->getPrimaryKey($model);
        $idKey = $model->aliasField($primaryKey);

        $extra = new ArrayObject([]);
        $extra['excludedModels'] = [$this->Areas->alias()];

        $associatedRecords = [];

        foreach ($missingAreaArray as $key => $obj) {
            $id = $obj['id'];

            if ($model->exists([$idKey => $id])) {
                $entity = $model->find()->where([$idKey => $id])->first();
                $records = $this->getAssociatedRecords($model, $entity, $extra);
                $associatedRecords[$key] = [
                    'id' => $id,
                    'code' => $obj['code'],
                    'name' => $obj['name'],
                    'institution' => $records['Institutions']['count'],
                    'security_group' => $records['SecurityGroups']['count'],
                ];
            }
        }

        return $associatedRecords;
    }

    public function isApiValid($url=null)
    {
        // check if API is valid, have value and contain expected keys.
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

    public function doUpdateAssociatedRecord($requestData)
    {
        $securityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');

        if (array_key_exists($this->alias(), $requestData)) {
            if (array_key_exists('transfer_areas', $requestData[$this->alias()])) {
                foreach ($requestData[$this->alias()]['transfer_areas'] as $key => $obj) {
                    // update the association data (institution and securityGroupAreas)
                    $areaId = $obj['area_id'];
                    $newAreaId = $obj['new_area_id'];

                    $institutionResult = $this->Institutions->find()
                    ->where(['area_id' => $key])
                    ->toArray();

                    // Update the Institutions table
                    if (!empty($institutionResult)) {
                        foreach ($institutionResult as $key => $institution) {
                            $institutionEntity =$this->newEntity([
                                'id' => $institution->id,
                                'area_id' => $newAreaId],
                                ['validate' => false]);
                            $this->Institutions->save($institutionEntity);
                        }
                    }

                    $OldGroupAreas = clone $securityGroupAreas;

                    // Query to delete the wrong records
                    $groupIdQuery = $OldGroupAreas->find()
                        ->select(['group_id' => $OldGroupAreas->aliasField('security_group_id')])
                        ->where([$OldGroupAreas->aliasField('area_id') => $areaId]);

                    $deleteQuery = $this->query()
                        ->select(['group_id' => 'GroupAreas.group_id'])
                        ->from(['GroupAreas' => $groupIdQuery]);

                    $securityGroupAreas->deleteAll(['area_id' => $newAreaId, 'security_group_id IN' => $deleteQuery]);

                    // Update the security group areas table
                    $securityGroupAreas->updateAll(
                        ['area_id' => $newAreaId],
                        ['area_id' => $areaId]
                    );
                }
            }
        }
    }

    public function doReplaceAreaTable($missingAreaArray, $jsonArray)
    {
        // Delete missing areas from areasTable
        if (!empty($missingAreaArray)) {
            foreach ($missingAreaArray as $key => $obj) {
                $this->delete($this->get($key));
            }
        }

        // Add JsonArray data to the areasTable
        if (!empty($jsonArray)) {
            foreach ($jsonArray as $key => $obj) {
                $areasEntity = $this->newEntity([
                    'id' => $obj['id'],
                    'parent_id' => $obj['parent_id'],
                    'code' => $obj['code'],
                    'name' => $obj['name'],
                    'area_level_id' => $obj['area_level_id'],
                    'order' => $obj['order']
                ]);
                $this->save($areasEntity);
            }
        }

        $this->rebuildLftRght();
    }
}
