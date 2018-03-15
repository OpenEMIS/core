<?php
namespace Area\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class AreaAdministrativesTable extends ControllerActionTable
{
    private $fieldsOrder = ['visible', 'code', 'name', 'area_administrative_level_id'];
    private $worldId;

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
            'StaffRoom' => ['index'],
            'SgTree' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('is_main_country', 'ruleValidateAreaAdministrativeMainCountry', [
                'rule' => ['validateAreaAdministrativeMainCountry'],
                'on' => function ($context) {
                    $query = $this->find()
                            ->select([$this->aliasField('id')])
                            ->where([$this->aliasField('parent_id').' IS NULL'])
                            ->first();
                    return $context['data']['parent_id'] == $query->id;
                },
                'provider' => 'table'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('area_administrative_level_id');
        
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

        $query = $this->find()
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id').' IS NULL'])
                ->first();

        $this->worldId = $query->id;
    }

    public function rebuildLftRght()
    {
        $this->recover();
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }

    public function onGetConvertOptions(Event $event, Entity $entity, Query $query)
    {
        $level = $entity->area_administrative_level_id;
        $query->where([
                $this->aliasField('area_administrative_level_id') => $level
            ]);
    }

    public function findAreaList(Query $query, array $options)
    {
        $selected = !empty($options['selected']) && $options['selected'] != 'null' ? $options['selected'] : null;

        if (isset($options['recordOnly']) && $options['recordOnly']) {
            return $query
                ->contain(['AreaAdministrativeLevels'])
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('name'),
                    $this->aliasField('parent_id'),
                    'AreaAdministrativeLevels.name',
                    $this->aliasField('order')
                ])
                ->where([$this->aliasField('id') => $selected])
                ->hydrate(false)
                ->formatResults(function ($results) use ($selected) {
                    $results = $results->toArray();
                    foreach ($results as &$result) {
                        $result['name'] = $result['name'] . ' - ' . __($result['area_administrative_level']['name']);
                        $result['selected'] = true;
                    }
                    $defaultSelect = ['id' => null, 'name' => __('-- Select --')];
                    if (is_null($selected)) {
                        $defaultSelect['selected'] = true;
                    }
                    array_unshift($results, $defaultSelect);
                    return $results;
                });
        }

        $authorisedAreaIds = [];
        $worldId = $this
                ->find()
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id').' IS NULL'])
                ->first();

        $conditions = [$this->aliasField('parent_id').' IS NOT NULL'];
        if (isset($options['displayCountry']) && !$options['displayCountry']) {
            $authorisedAreaIds = $this
                ->find()
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('lft'),
                    $this->aliasField('rght')
                ])
                ->where([
                    $this->aliasField('is_main_country') => true,
                    $this->aliasField('parent_id') => $worldId->id
                ])
                ->hydrate(false)
                ->toArray();

            foreach ($authorisedAreaIds as $area) {
                $conditions[] = [
                    $this->aliasField('lft').' >= ' => $area['lft'],
                    $this->aliasField('rght').' <= ' => $area['rght']
                ];
            }

            $removeAreas = $this
                ->find()
                ->select([$this->aliasField('id')])
                ->where([
                    $this->aliasField('is_main_country') => false,
                    $this->aliasField('parent_id') => $worldId->id
                ])
                ->hydrate(false)
                ->toArray();
            $removeAreas = array_column($removeAreas, 'id');

            if (!empty($removeAreas)) {
                $query->where([$this->aliasField('id').' NOT IN ' => $removeAreas]);
            }
        } else {
            $authorisedAreaIds = $this
                ->find()
                ->select([$this->aliasField('id')])
                ->where([
                    $this->aliasField('parent_id') => $worldId->id
                ])
                ->hydrate(false)
                ->toArray();
        }

        $authorisedAreaIds = array_column($authorisedAreaIds, 'id');

        return $query
            ->find('threaded', [
                'parentField' => 'parent_id',
                'order' => ['lft' => 'ASC']
            ])
            ->contain(['AreaAdministrativeLevels'])
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                $this->aliasField('parent_id'),
                'AreaAdministrativeLevels.name',
                $this->aliasField('order')
            ])
            ->hydrate(false)
            // Remove world record
            ->where($conditions)
            ->formatResults(function ($results) use ($authorisedAreaIds, $selected) {
                $results = $results->toArray();
                $this->unsetEmptyArr($results, $authorisedAreaIds, $selected);
                $order = array_column($results, 'order');
                array_multisort($order, SORT_ASC, $results);
                unset($order);
                $defaultSelect = ['id' => null, 'name' => __('-- Select --')];
                if (is_null($selected)) {
                    $defaultSelect['selected'] = true;
                }
                array_unshift($results, $defaultSelect);
                return $results;
            });
    }

    private function unsetEmptyArr(&$array, &$authorisedAreaIds, $selected)
    {
        foreach ($array as &$value) {
            if (isset($value['id'])) {
                if (!in_array($value['id'], $authorisedAreaIds)) {
                    $value['disabled'] = true;
                } else {
                    $authorisedAreaIds = array_merge($authorisedAreaIds, array_column($value['children'], 'id'));
                }
                if ($value['id'] == $selected) {
                    $value['selected'] = true;
                }
            }
            if (is_array($value) && empty($value)) {
                unset($value);
            } elseif (is_array($value)) {
                if (isset($value['name']) && isset($value['area_administrative_level']) && isset($value['area_administrative_level']['name'])) {
                    $value['name'] = __($value['name']) . ' - ' . __($value['area_administrative_level']['name']);
                }
                if (isset($value['children'])) {
                    $children = $value['children'];
                    $order = array_column($children, 'order');
                    array_multisort($order, SORT_ASC, $children);
                    $value['children'] = $children;
                    unset($children);
                    unset($order);
                }
                $this->unsetEmptyArr($value, $authorisedAreaIds, $selected);
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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

        //to hide / show is main country field on index
        $request = $this->request;
        if (array_key_exists('parent', $request->query)) {
            if ($request->query['parent'] != $this->worldId) {
                $this->fields['is_main_country']['visible'] = false;
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->data[$this->alias()]['area_administrative_level_id'] = $entity->area_administrative_level_id;
        $this->field('is_main_country');
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
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
        $this->fieldsOrder = ['area_administrative_level_id', 'code', 'name'];

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

            array_unshift($this->fieldsOrder, "parent");
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

    public function onGetIsMainCountry(Event $event, Entity $entity)
    {
        ($entity->is_main_country) ? $return = __('Yes') : $return = __('No');

        return $return;
    }

    public function onUpdateFieldIsMainCountry(Event $event, array $attr, $action, Request $request)
    {
        if ($action=='add') {
            $attr['visible'] = true;
            $areaAdminLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
            if ($areaAdminLevelId == 1) {
                $attr['options'] = $this->getSelectOptions('general.yesno');
                return $attr;
            } else {
                $attr['value'] = 0;
                $attr['type'] = 'hidden';
                return $attr;
            }
        } elseif ($action == 'edit') {
            $attr['visible'] = true;
            $areaAdminLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
            if ($areaAdminLevelId == 1) {
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('is_main_country')) {
            if ($entity->is_main_country == 1) { //if set as main country

                // update the rest of areas to non main country
                $this->updateAll(
                    ['is_main_country' => 0],
                    [
                        'parent_id' => $this->worldId,
                        'id <> ' => $entity->id
                    ]
                );
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Institution.Institutions'),
            TableRegistry::get('Directory.Directories')
        ];

        $this->dispatchEventToModels('Model.AreaAdministrative.afterDelete', [$entity], $this, $listeners);    
    }

}
