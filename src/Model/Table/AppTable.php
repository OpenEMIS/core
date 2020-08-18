<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ControllerAction\Model\Traits\UtilityTrait;
use ControllerAction\Model\Traits\ControllerActionTrait;
use Page\Traits\OptionListTrait;

class AppTable extends Table
{
    use ControllerActionTrait;
    use UtilityTrait;
    use LogTrait;
    use OptionListTrait;
    const OpenEMIS = 'OpenEMIS ID';
    public function initialize(array $config)
    {
        Time::$defaultLocale = 'en_US';
        Date::$defaultLocale = 'en_US';

        $_config = [
            'Modified' => true,
            'Created' => true
        ];
        $_config = array_merge($_config, $config);
        parent::initialize($config);

        $schema = $this->schema();
        $columns = $schema->columns();

        if (in_array('modified', $columns) || in_array('created', $columns)) {
            $this->addBehavior('Timestamp', [
                'events' => [
                    'Model.beforeSave' => [
                        'created' => 'new',
                        'modified' => 'existing'
                    ]
                ]
            ]);
        }

        if (in_array('modified_user_id', $columns) && $_config['Modified']) {
            $this->belongsTo('ModifiedUser', ['className' => 'User.Users', 'foreignKey' => 'modified_user_id']);
        }

        if (in_array('created_user_id', $columns) && $_config['Created']) {
            $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);
        }

        if (in_array('visible', $columns)) {
            $this->addBehavior('Visible');
        }

        if (in_array('order', $columns)) {
            $this->addBehavior('Reorder');
        }

        $dateFields = [];
        $timeFields = [];
        foreach ($columns as $column) {
            if ($schema->columnType($column) == 'date') {
                $dateFields[] = $column;
            } elseif ($schema->columnType($column) == 'time') {
                $timeFields[] = $column;
            }
        }
        if (!empty($dateFields)) {
            $this->addBehavior('ControllerAction.DatePicker', $dateFields);
        }
        if (!empty($timeFields)) {
            $this->addBehavior('ControllerAction.TimePicker', $timeFields);
        }
        $this->addBehavior('Validation');
        $this->addBehavior('Modification');

        $this->addBehavior('TrackAdd');
        $this->addBehavior('TrackDelete');
        $this->addBehavior('ControllerAction.Security');

        $this->_controllerActionEvents['Restful.Model.onRenderDatetime'] = 'onRestfulRenderDatetime';
        $this->_controllerActionEvents['Restful.Model.onRenderDate'] = 'onRestfulRenderDate';
        $this->_controllerActionEvents['Restful.Model.onRenderTime'] = 'onRestfulRenderTime';
    }

    public function validationDefault(Validator $validator)
    {
        $schema = $this->schema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            if ($schema->columnType($column) == 'date') {
                $attr = $schema->column($column);
                // check if is nullable
                if (array_key_exists('null', $attr) && $attr['null'] === true) {
                    $validator->allowEmpty($column);
                }
            }
        }

        return $validator;
    }

    // Function to get the entity property from the entity. If data validation occur,
    // the invalid value has to be extracted from invalid array
    // For use in Cake 3.2 and above
    public function getEntityProperty($entity, $propertyName)
    {
        if ($entity->has($propertyName)) {
            return $entity->get($propertyName);
        } elseif (array_key_exists($propertyName, $entity->invalid())) {
            return $entity->invalid($propertyName);
        } else {
            return null;
        }
    }

    // Event: 'ControllerAction.Model.onPopulateSelectOptions'
    public function onPopulateSelectOptions(Event $event, Query $query)
    {
        return $this->getList($query);
    }

    public function getList($query = null)
    {
        $schema = $this->schema();
        $columns = $schema->columns();
        $table = $schema->name();

        if (is_null($query)) {
            if ($table == 'area_levels') {
                $query = $this
                    ->find('list', [
                        'keyField' => 'level',
                        'valueField' => 'name'
                    ]);
            } else {
                $query = $this->find('list');
            }
        }

        if (in_array('order', $columns)) {
            $query->find('order');
        }

        if (in_array('visible', $columns)) {
            $query->find('visible');
        }
        
        return $query;
    }

    // Event: 'Model.excel.onFormatDate' ExcelBehavior
    public function onExcelRenderDate(Event $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};
        if (!empty($field)) {
            if ($field instanceof Time || $field instanceof Date) {
                return $this->formatDate($field);
            } else {
                if ($field != '0000-00-00') {
                    $date = new Date($field);
                    return $this->formatDate($date);
                } else {
                    return '';
                }
            }
        } else {
            return $field;
        }
    }

    public function onExcelRenderDateTime(Event $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};
        if (!empty($field)) {
            if ($field instanceof Time || $field instanceof Date) {
                return $this->formatDate($field);
            } else {
                $date = new Time($field);
                return $this->formatDate($date);
            }
        } else {
            return $field;
        }
    }

    // Event: 'ControllerAction.Model.onFormatDate'
    public function onFormatDate(Event $event, $dateObject)
    {
        return $this->formatDate($dateObject);
    }

    /**
     * For calling from view files
     * @param  Time   $dateObject [description]
     * @return [type]             [description]
     */
    public function formatDate($dateObject)
    {
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format');
        $value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
        return $value;
    }

    // Event: 'ControllerAction.Model.onFormatTime'
    public function onFormatTime(Event $event, $timeObject)
    {
        return $this->formatTime($timeObject);
    }

    /**
     * For calling from view files
     * @param  Time   $dateObject [description]
     * @return [type]             [description]
     */
    public function formatTime($timeObject)
    {
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('time_format');
        $value = '';
        if (is_object($timeObject)) {
            $value = $timeObject->format($format);
        }
        return $value;
    }

    // Event: 'ControllerAction.Model.onFormatDateTime'
    public function onFormatDateTime(Event $event, $timeObject)
    {
        return $this->formatDateTime($timeObject);
    }

    /**
     * For calling from view files
     * @param  Time   $dateObject [description]
     * @return [type]             [description]
     */
    public function formatDateTime($dateObject)
    {
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format') . ' - ' . $ConfigItem->value('time_format');
        $value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
        return $value;
    }

    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderDatetime(Event $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatDateTime($dateTimeObj);
    }

    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderDate(Event $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatDate($dateTimeObj);
    }

    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderTime(Event $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatTime($dateTimeObj);
    }

    // Event: 'ControllerAction.Model.onGetFieldLabel'
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        $Labels     = TableRegistry::get('Labels');
        $fieldLabel = $Labels->find()
                ->select(['name'])
                ->where(['module' => $event->data['module'],'field'=>'openemis_no'])
                ->first();
       
        if ($field == 'openemis_no' && !empty($fieldLabel['name'])) {
             return $fieldLabel['name'];
             
        } else if ($field == 'openemis_no') {
            return self::OpenEMIS;
            
		} else if ($field == 'fax' && !empty($fieldLabel['name'])) {
		    return $fieldLabel['name'];
        }
        
        return $this->getFieldLabel($module, $field, $language, $autoHumanize);
    }

    public function getFieldLabel($module, $field, $language, $autoHumanize = true)
    {
        $Labels = TableRegistry::get('Labels');
        $label = $Labels->getLabel($module, $field, $language);
        
        if ($label === false && $autoHumanize) {
            $label = Inflector::humanize($field);
            if ($this->endsWith($field, '_id') && $this->endsWith($label, ' Id')) {
                $label = str_replace(' Id', '', $label);
            }
            $label = __($label);
        }
        
        if (substr($label, -1) == ')') {
            $label = $label.' ';
        }
        
        return $label;
    }

    // Event: 'Model.excel.onExcelGetLabel'
    public function onExcelGetLabel(Event $event, $module, $col, $language)
    {
       return __($this->getFieldLabel($module, $col, $language));
    }

    public function getButtonAttr()
    {
        return [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
    }

    // Event: 'ControllerAction.Model.onInitializeButtons'
    public function onInitializeButtons(Event $event, ArrayObject $buttons, $action, $isFromModel, ArrayObject $extra)
    {
        // needs clean up
        $controller = $event->subject()->_registry->getController();
        $access = $controller->AccessControl;

        $toolbarButtons = new ArrayObject([]);
        $indexButtons = new ArrayObject([]);

        $toolbarAttr = $this->getButtonAttr();
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

        // Set for roles belonging to the controller
        $roles = [];
        $event = $controller->dispatchEvent('Controller.Buttons.onUpdateRoles', null, $this);
        if ($event->result) {
            $roles = $event->result;
        }
        if ($action != 'index') {
            $toolbarButtons['back'] = $buttons['back'];
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $toolbarAttr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
            if ($action == 'remove' && ($buttons['remove']['strategy'] == 'transfer' || $buttons['remove']['strategy'] == 'restrict')) {
                $toolbarButtons['list'] = $buttons['index'];
                $toolbarButtons['list']['type'] = 'button';
                $toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
                $toolbarButtons['list']['attr'] = $toolbarAttr;
                $toolbarButtons['list']['attr']['title'] = __('List');
            }
        }
        if ($action == 'index') {
            if ($buttons->offsetExists('add') && $access->check($buttons['add']['url'], $roles)) {
                $toolbarButtons['add'] = $buttons['add'];
                $toolbarButtons['add']['type'] = 'button';
                $toolbarButtons['add']['label'] = '<i class="fa kd-add"></i>';
                $toolbarButtons['add']['attr'] = $toolbarAttr;
                $toolbarButtons['add']['attr']['title'] = __('Add');
            }
            if ($buttons->offsetExists('search')) {
                $toolbarButtons['search'] = [
                    'type' => 'element',
                    'element' => 'OpenEmis.search',
                    'data' => ['url' => $buttons['index']['url']],
                    'options' => []
                ];
            }
        } elseif ($action == 'add' || $action == 'edit') {
            if ($action == 'edit' && $buttons->offsetExists('index')) {
                $toolbarButtons['list'] = $buttons['index'];
                $toolbarButtons['list']['type'] = 'button';
                $toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
                $toolbarButtons['list']['attr'] = $toolbarAttr;
                $toolbarButtons['list']['attr']['title'] = __('List');
            }
        } elseif ($action == 'view') {
            // edit button
            if ($buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
                $toolbarButtons['edit'] = $buttons['edit'];
                $toolbarButtons['edit']['type'] = 'button';
                $toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
                $toolbarButtons['edit']['attr'] = $toolbarAttr;
                $toolbarButtons['edit']['attr']['title'] = __('Edit');
            }

            // delete button
            // disabled for now until better solution
            if ($buttons->offsetExists('remove') && $buttons['remove']['strategy'] != 'transfer' && $access->check($buttons['remove']['url'], $roles)) {
                $toolbarButtons['remove'] = $buttons['remove'];
                $toolbarButtons['remove']['type'] = 'button';
                $toolbarButtons['remove']['label'] = '<i class="fa fa-trash"></i>';
                $toolbarButtons['remove']['attr'] = $toolbarAttr;
                $toolbarButtons['remove']['attr']['title'] = __('Delete');

                if ($buttons['remove']['strategy'] != 'restrict') {
                    $toolbarButtons['remove']['attr']['data-toggle'] = 'modal';
                    $toolbarButtons['remove']['attr']['data-target'] = '#delete-modal';
                    $toolbarButtons['remove']['attr']['field-target'] = '#recordId';
                    $toolbarButtons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
                    if ($extra->offsetExists('primaryKeyValue')) {
                        $toolbarButtons['remove']['attr']['field-value'] = $extra['primaryKeyValue'];
                    }
                }
            }
        }

        if ($buttons->offsetExists('view') && $access->check($buttons['view']['url'], $roles)) {
            $indexButtons['view'] = $buttons['view'];
            $indexButtons['view']['label'] = '<i class="fa fa-eye"></i>' . __('View');
            $indexButtons['view']['attr'] = $indexAttr;
        }

        if ($buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
            $indexButtons['edit'] = $buttons['edit'];
            $indexButtons['edit']['label'] = '<i class="fa fa-pencil"></i>' . __('Edit');
            $indexButtons['edit']['attr'] = $indexAttr;
        }

        if ($buttons->offsetExists('remove') && $access->check($buttons['remove']['url'], $roles)) {
            $indexButtons['remove'] = $buttons['remove'];
            $indexButtons['remove']['label'] = '<i class="fa fa-trash"></i>' . __('Delete');
            $indexButtons['remove']['attr'] = $indexAttr;
        }

        if ($buttons->offsetExists('reorder') && $buttons->offsetExists('edit') && $access->check($buttons['edit']['url'], $roles)) {
            // if ($buttons->offsetExists('reorder') && $access->check($buttons['edit']['url'])) {
            $controller->set('reorder', true);
        }

        $event = new Event('Model.custom.onUpdateToolbarButtons', $this, [$buttons, $toolbarButtons, $toolbarAttr, $action, $isFromModel]);
        $this->eventManager()->dispatch($event);

        if ($toolbarButtons->offsetExists('back')) {
            $controller->set('backButton', $toolbarButtons['back']);
        }
        $controller->set(compact('toolbarButtons', 'indexButtons'));
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $id = $this->getEncodedKeys($entity);

        if (array_key_exists('view', $buttons)) {
            $buttons['view']['url'][] = $id;
        }
        if (array_key_exists('edit', $buttons)) {
            $buttons['edit']['url'][] = $id;
        }
        if (array_key_exists('remove', $buttons)) {
            if (in_array($buttons['remove']['strategy'], ['cascade'])) {
                $buttons['remove']['attr']['data-toggle'] = 'modal';
                $buttons['remove']['attr']['data-target'] = '#delete-modal';
                $buttons['remove']['attr']['field-target'] = '#recordId';
                $buttons['remove']['attr']['field-value'] = $id;
                $buttons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
            } else {
                $buttons['remove']['url'][] = $id;
            }
        }
        return $buttons;
    }

    public function findVisible(Query $query, array $options)
    {
        return $query->where([$this->aliasField('visible') => 1]);
    }

    public function findActive(Query $query, array $options)
    {
        return $query->where([$this->aliasField('active') => 1]);
    }

    public function findOrder(Query $query, array $options)
    {
        return $query->order([$this->aliasField('order') => 'ASC']);
    }

    public function postString($key)
    {
        $request = $this->request;
        $selectedId = null;
        if ($request->data($this->aliasField($key))) {
            $selectedId = $request->data($this->aliasField($key));
        }
        return $selectedId;
    }

    public function isForeignKey($field, $table = null)
    {
        if (is_null($table)) {
            $table = $this;
        }
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedTable($field, $table = null)
    {
        if (is_null($table)) {
            $table = $this;
        }
        $relatedModel = null;

        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    public function getAssociatedKey($field, $table = null)
    {
        if (is_null($table)) {
            $table = $this;
        }
        $tableObj = $this->getAssociatedTable($field, $table);
        $key = null;
        if (is_object($tableObj)) {
            $key = Inflector::underscore(Inflector::singularize($tableObj->alias()));
        }
        return $key;
    }

    public function getEncodedKeys(Entity $entity)
    {
        $primaryKey = $this->primaryKey();
        $primaryKeyValue = [];
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key) {
                $primaryKeyValue[$key] = $entity->getOriginal($key);
            }
        } else {
            $primaryKeyValue[$primaryKey] = $entity->getOriginal($primaryKey);
        }

        $encodedKeys = $this->paramsEncode($primaryKeyValue);

        return $encodedKeys;
    }

    public function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public function dispatchEventToModels($eventKey, $params, $subject, $listeners)
    {
        foreach ($listeners as $listener) {
            $listener->dispatchEvent($eventKey, $params, $subject);
        }
    }
}
