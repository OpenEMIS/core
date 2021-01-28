<?php
namespace Area\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AreapickerBehavior extends Behavior
{
    private $areaByUser = [];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.edit.beforePatch'] = 'editBeforePatch';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.edit.afterQuery'] = 'editAfterQuery';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        return $events;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function onGetAreapickerElement(Event $event, $action, Entity $entity, $attr, $options)
    {
        $fieldName = $attr['model'] . '.' . $attr['field'];
        $HtmlField = $event->subject();
        $data = $HtmlField->request->data;
        $value = isset($data[$this->_table->alias()][$attr['field']]) ? $data[$this->_table->alias()][$attr['field']] : $entity->{$attr['field']};

        if ($action == 'edit') {
            $Url = $HtmlField->Url;
            $Form = $HtmlField->Form;
            $attr['display-country'] = 1;
            $attr['id'] = 'areapicker';
            $attr['fieldName'] = $fieldName;
            $attr['value'] = $value;

            $targetModel = $attr['source_model'];
            $targetTable = TableRegistry::get($targetModel);

            $areaOptions = $targetTable
                ->find('list');

            // Pick the first found parent of area administrative
            if ($targetModel == 'Area.AreaAdministratives' && (!isset($attr['displayCountry']) || (isset($attr['displayCountry']) && $attr['displayCountry']))) {
                $areaOptions = $areaOptions
                    ->where([$targetTable->aliasField('parent_id').' <> ' => -1])
                    ->order([$targetTable->aliasField('lft')]);
            }

            if ($targetModel == 'Area.Areas' && isset($attr['displayCountry'])) {
                if (!$entity->isNew()) {
                    $options['display-country'] = $entity->area_id;
                } else {
                    $options['display-country'] = 0;
                }

                // Filter the initial area list to show only the authorised area
                $authorisedArea = $this->_table->AccessControl->getAreasByUser();
                $areaCondition = [];
                foreach ($authorisedArea as $area) {
                    $areaCondition[] = [
                        $targetTable->aliasField('lft').' >= ' => $area['lft'],
                        $targetTable->aliasField('rght').' <= ' => $area['rght']
                    ];
                }
                if (!empty($authorisedArea)) {
                    $areaOptions = $areaOptions
                        ->where(['OR' => $areaCondition]);
                }
            } // If there is a restriction on the area administrative's main country to display (Use in Institution only)
            elseif ($targetModel == 'Area.AreaAdministratives' && isset($attr['displayCountry']) && !$attr['displayCountry']) {
                $options['display-country'] = 1;
            }

            $areaOptions = $areaOptions->toArray();
            $areaKeys = array_keys($areaOptions);
            $areaKeys[] = null;
            $session = $HtmlField->request->session();

            $areaKeys = array_merge($areaKeys, [$entity->{$attr['field']}]);
            // Temporary disabled for further investigation
            // $session->write('FormTampering.'.$fieldName, $areaKeys);
            return $event->subject()->renderElement('Area.sg_tree', ['attr' => $attr]);
        }
        return $value;
    }

    public function editAfterQuery(Event $event, Entity $entity)
    {
        $userId = $this->_table->Auth->user('id');
        $areasByUser = $this->_table->AccessControl->getAreasByUser($userId);
        $this->areaByUser = $areasByUser;

        // $areasByUser will always be empty for system groups because system groups are linked directly to schools
        if (!$this->_table->AccessControl->isAdmin() && empty($areasByUser)) {
            $entity->area_restricted = true;
        }
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $areasByUser = $this->areaByUser;
        // $areasByUser will always be empty for system groups because system groups are linked directly to schools
        if (!$this->_table->AccessControl->isAdmin() && empty($areasByUser)) {
            foreach ($this->_table->fields as $field => $attr) {
                if ($attr['type'] == 'areapicker' && $attr['source_model'] == 'Area.Areas') {
                    $this->_table->fields[$field]['visible'] = false;
                    $targetModel = $attr['source_model'];
                    $areaId = $entity->{$field};
                    $list = $this->getAreaLevelName($targetModel, $areaId);
                    $after = $field;
                    foreach ($list as $key => $area) {
                        if ($this->isCAv4()) {
                            $this->_table->field($field.$key, [
                                'type' => 'readonly',
                                'attr' => ['label' => __($area['level_name']), 'value' => __($area['area_name'])],
                                'after' => $after
                            ]);
                        } else {
                            $this->_table->ControllerAction->field($field.$key, [
                                'type' => 'disabled',
                                'attr' => ['label' => __($area['level_name']), 'value' => $area['area_name']],
                                'after' => $after
                            ]);
                        }
                        $after = $field.$key;
                    }
                }
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        foreach ($this->_table->fields as $field => $attr) {
            if ($attr['type'] == 'areapicker') {
                $this->_table->fields[$field]['type'] = 'hidden';
                $targetModel = $attr['source_model'];
                $areaId = $entity->{$field};
                if (!empty($areaId)) {
                    $list = $this->getAreaLevelName($targetModel, $areaId);
                } else {
                    $list = [];
                }
                $after = $field;
                foreach ($list as $key => $area) {
                    if ($this->isCAv4()) {
                        $this->_table->field($field.$key, [
                            'type' => 'disabled',
                            'attr' => ['label' => __($area['level_name']), 'value' => $area['area_name']],
                            'value' => __($area['area_name']),
                            'after' => $after
                        ]);
                    } else {
                        $this->_table->ControllerAction->field($field.$key, [
                            'type' => 'readonly',
                            'attr' => ['label' => __($area['level_name'])],
                            'value' => __($area['area_name']),
                            'after' => $after
                        ]);
                    }
                    $after = $field.$key;
                }
            }
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // to prevent html injection on area_id
        if ($entity->has('area_restricted') && $entity->area_restricted == true) {
            if (array_key_exists('Institutions', $data)) {
                $data['Institutions']['area_id'] = $entity->area_id;
                $data['Institutions']['isSystemGroup'] = true; // this flag is to be used in ValidationBehavior->checkAuthorisedArea
            }
        }
    }

    public function getAreaLevelName($targetModel, $areaId)
    {
        $targetTable = TableRegistry::get($targetModel);
        $levelAssociation = Inflector::singularize($targetTable->alias()).'Levels';
        $path = $targetTable
            ->find('path', ['for' => $areaId])
            ->contain([$levelAssociation])
            ->select(['level_name' => $levelAssociation.'.name', 'area_name' => $targetTable->aliasField('name')])
            ->bufferResults(false)
            ->toArray();

        if ($targetModel == 'Area.AreaAdministratives') {
            // unset world
            unset($path[0]);
        }
        return $path;
    }
}
