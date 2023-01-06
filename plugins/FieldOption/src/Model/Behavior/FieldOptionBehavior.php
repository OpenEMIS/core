<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class FieldOptionBehavior extends Behavior {
    public function initialize(array $config)
    {
        $this->_table->setDeleteStrategy('restrict');
    }

    public function getDefaultValue()
    {
        $value = '';
        $primaryKey = $this->_table->primaryKey();
        $entity = $this->getDefaultEntity();
        return $entity->{$primaryKey};
    }

    public function getDefaultEntity()
    {
        $query = $this->_table->find();
        $entity = $query
            ->where([$this->_table->aliasField('default') => 1])
            ->first();

        if (is_null($entity)) {
            $query = $this->_table->find();
            $entity = $query->first();
        }

        return $entity;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'editAfterAction'];
        $events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
        $events['ControllerAction.Model.addEdit.beforePatch'] = ['callable' => 'addEditBeforePatch'];
        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 5];
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!$data->offsetExists('default')) {
            $data['default'] = '0';
        }
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        $addUniqueName = false;
        if ($validator->hasField('name')) {
            $set = $validator->field('name');
            if (!$set->rule('ruleUnique')) {
                $addUniqueName = true;
            }
        } else {
            $addUniqueName = true;
        }

        if ($addUniqueName) {
            $validator
                ->add('name', [
                    'ruleUnique' => [
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                        'message' => __('This field has to be unique')
                    ]
                ]);
        }
        //POCOR-5668 add external validation starts
        if(isset($this->_table->alias) && $this->_table->alias == 'Nationalities'){
            $validator
                ->requirePresence('external_validation')
                ->add('external_validation', [
                    'externalVal' => [
                        'rule' => 'check_external_validation',
                        //'provider' => 'table',
                        'message' => __('Please configure External Data Source in System Configurations to enable External Validation.')
                    ]
                ]); 
        }
        //POCOR-5668 add external validation ends
        $validator
            ->requirePresence('visible')
            ->requirePresence('default')            
            ;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
        // only perform for v4
        if ($this->_table->hasBehavior('ControllerAction')) {
            if ($entity->has('default') && $entity->default == 1) {
                $this->_table->updateAll(['default' => 0], [$this->_table->primaryKey().' != ' => $entity->{$this->_table->primaryKey()}]);
            }
        }
    }

    private function buildFieldOptions() {
        $data = $this->_table->FieldOption->getFieldOptions();
        $fieldOptions = [];
        foreach ($data as $key => $obj) {
            $parent = __($obj['parent']);
            if (!array_key_exists($parent, $fieldOptions)) {
                $fieldOptions[$parent] = [];
            }

            if (isset($obj['title'])) {
                $keyName = $obj['title'];
            } else {
                $keyName = Inflector::humanize(Inflector::underscore($key));
            }
            $fieldOptions[$parent][$key] = __($keyName);
        }
        return $fieldOptions;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {

    }

    private function addFieldOptionControl(ArrayObject $extra, $data = []) {
        $extra['elements']['controls'] = ['name' => 'FieldOption.controls', 'data' => $data, 'order' => 2];
    }

    // for CA v4
    public function onGetEditable(Event $event, Entity $entity) {
        return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetDefault(Event $event, Entity $entity)
    {
        return $entity->default == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $fieldOptions = $this->buildFieldOptions();
        $selectedOption = $model->alias;
        $this->addFieldOptionControl($extra, ['fieldOptions' => $fieldOptions, 'selectedOption' => $selectedOption]);

        $model->field('default', ['options' => $model->getSelectOptions('general.yesno'), 'after' => 'visible']);
        //POCOR-5668 add external validation starts
        if($model->alias == 'Nationalities'){
            $model->field('external_validation', ['options' => $model->getSelectOptions('general.enabledisable'), 'after' => 'default', 'default'=>0]);
        }
        //POCOR-5668 add external validation ends
        $model->field('editable', ['options' => $model->getSelectOptions('general.yesno'), 'visible' => ['index' => true], 'after' => 'default']);

        $extra['config']['selectedLink'] = ['controller' => 'FieldOptions', 'action' => 'index'];
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        if ($entity->has('editable') && $entity->editable == false) {
            $model->fields['name']['type'] = 'disabled';
            $model->fields['visible']['type'] = 'disabled';
            $model->fields['international_code']['type'] = 'disabled';
            $model->fields['national_code']['type'] = 'disabled';
            //POCOR-5668 starts
            $model->fields['external_validation']['type'] = 'disabled';
            //POCOR-5668 ends
        }
    }

    public function indexBeforeAction(Event $event)
    {
        $model = $this->_table;
        $model->field('name', ['after' => 'editable']);
        $fields = ['visible', 'default', 'editable', 'name', 'international_code', 'national_code','external_validation'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $model->fields)) {
                if (is_array($model->fields[$field]['visible'])) {
                    $model->fields[$field]['visible']['index'] = true;
                } else {
                    if ($model->fields[$field]['visible']) {
                        $model->fields[$field]['visible'] = [
                            'view' => true,
                            'edit' => true,
                            'index' => true
                        ];
                    } else {
                        $model->fields[$field]['visible'] = ['index' => true];
                    }
                }
            }
        }
    }
}
