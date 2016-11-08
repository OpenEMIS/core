<?php
namespace Area\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class AreaAdministrativesTable extends ControllerActionTable
{
    private $_fieldOrder = ['visible', 'code', 'name', 'area_administrative_level_id'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AreaAdministrativeParents', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'parent_id']);
        $this->belongsTo('AreaAdministrativeLevels', ['className' => 'Area.AreaAdministrativeLevels', 'foreignKey' => 'area_administrative_level_id']);
        $this->hasMany('AreaAdministratives', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'parent_id']);
        $this->hasMany('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('UsersAddressAreas', ['className' => 'Directory.Directories', 'foreignKey' => 'address_area_id']);
        $this->hasMany('UsersBirthplaceAreas', ['className' => 'Directory.Directories', 'foreignKey' => 'birthplace_area_id']);
        $this->addBehavior('Tree');
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'parent_id',
            ]);
        }

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffRoom' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, arrayObject $extra)
    {
        $this->field('area_administrative_level_id');
        $this->field('is_main_country', ['visible' => false]);
        $this->field('name');
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

    public function afterAction(Event $event, arrayObject $extra)
    {
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function onGetConvertOptions(Event $event, Entity $entity, Query $query)
    {
        $level = $entity->area_administrative_level_id;
        $query->where([
                $this->aliasField('area_administrative_level_id') => $level
            ]);
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
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
            $crumbs = $this->prepareCrumbs($crumbs);

            $this->controller->set('crumbs', $crumbs);
        } else {
            // Always redirect by selecting World as the parent
            $results = $this
                ->find()
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
    }

    public function editAfterAction(Event $event, Entity $entity, arrayObject $extra)
    {
        $this->request->data[$this->alias()]['area_administrative_level_id'] = $entity->area_administrative_level_id;
        $this->field('is_main_country');
    }

    public function addBeforeAction(Event $event, arrayObject $extra)
    {
        $this->field('is_main_country');
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
        $this->_fieldOrder = ['area_administrative_level_id', 'code', 'name'];

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
            $crumbs = $this->prepareCrumbs($crumbs);

            $parentPath = '';
            foreach ($crumbs as $crumb) {
                $parentPath .= $crumb->name;
                $parentPath .= $crumb === end($crumbs) ? '' : ' > ';
            }

            $this->field('parent', [
                'type' => 'readonly',
                'attr' => ['value' => $parentPath]
            ]);

            array_unshift($this->_fieldOrder, "parent");
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

    public function onUpdateFieldIsMainCountry(Event $event, array $attr, $action, Request $request)
    {
        if ($action=='add') {
            $attr['visible'] = true;
            $areaAdministrativeLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
            if ($areaAdministrativeLevelId == 1) {
                $attr['options'] = $this->getSelectOptions('general.yesno');
                return $attr;
            } else {
                $attr['value'] = 0;
                $attr['type'] = 'hidden';
                return $attr;
            }
        } elseif ($action == 'edit') {
            $attr['visible'] = true;
            $areaAdministrativeLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
            if ($areaAdministrativeLevelId == 1) {
                $attr['options'] = $this->getSelectOptions('general.yesno');
                return $attr;
            } else {
                $attr['value'] = 0;
                $attr['type'] = 'hidden';
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaAdministrativeLevelId(Event $event, array $attr, $action, Request $request)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        $results = $this
            ->find()
            ->select([
                $this->aliasField('parent_id'),
                $this->aliasField('area_administrative_level_id')
            ])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        $attr['type'] = 'select';
        if (!$results->isEmpty()) {
            $data = $results
                ->first();
            // $parentId = $data->parent_id;
            $levelId = $data->area_administrative_level_id;

            if ($data->parent_id == null) { //World
                $levelOptions = $this->AreaAdministrativeLevels
                    ->find('list')
                    ->where([$this->AreaAdministrativeLevels->aliasField('level') => 0])
                    ->toArray();

                $attr['options'] = $levelOptions;
            } else {
                // Filter levelOptions by Country
                $levelResults = $this->AreaAdministrativeLevels
                    ->find()
                    ->select([
                        $this->AreaAdministrativeLevels->aliasField('level'),
                        $this->AreaAdministrativeLevels->aliasField('area_administrative_id')
                    ])
                    ->where([$this->AreaAdministrativeLevels->aliasField('id') => $levelId])
                    ->all();

                if (!$levelResults->isEmpty()) {
                    $level = $levelResults
                        ->first()
                        ->level;
                    $countryId = $levelResults
                        ->first()
                        ->area_administrative_id;
                    $countryId = $level < 1 ? $parentId : $countryId;   //null => World, 0 => Country

                    $levelOptions = $this->AreaAdministrativeLevels
                        ->find('list')
                        ->where([
                            $this->AreaAdministrativeLevels->aliasField('area_administrative_id') => $countryId,
                            $this->AreaAdministrativeLevels->aliasField('level >') => $level
                        ])
                        ->toArray();

                    $attr['options'] = $levelOptions;
                }
            }
            if (!isset($request->data[$this->alias()]['area_administrative_level_id'])) {
                $request->data[$this->alias()]['area_administrative_level_id'] = key($attr['options']);
            }
        }

        return $attr;
    }

    public function onUpdateFieldName(Event $event, array $attr, $action, Request $request)
    {
        $parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        $results = $this
            ->find()
            ->select([$this->aliasField('parent_id'), $this->aliasField('area_administrative_level_id')])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        if (!$results->isEmpty()) {
            $data = $results
                ->first();
            $parentId = $data->parent_id;

            if ($parentId == null) {    //World
                $Countries = TableRegistry::get('FieldOption.Countries');
                $countryOptions = $Countries
                    ->find('list', ['keyField' => 'name', 'valueField' => 'name'])
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['options'] = $countryOptions;
            }
        }

        return $attr;
    }

    public function prepareCrumbs(array $crumbs)
    {
        // Replace the code and name of World with All
        foreach ($crumbs as $key => $crumb) {
            if ($crumb->parent_id == null) {
                $crumb->code = __('All');
                $crumb->name = __('All');
                $crumbs[$key] = $crumb;
                break;
            }
        }

        return $crumbs;
    }
}
