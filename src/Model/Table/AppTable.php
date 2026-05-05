<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use ControllerAction\Model\Traits\UtilityTrait;
use ControllerAction\Model\Traits\ControllerActionTrait;
use Page\Traits\OptionListTrait;
use Cake\I18n\I18n;
use Cake\Database\Schema\TableSchema;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\I18n\FrozenTime;
use App\Utility\ApplicationTimezone; //POCOR-9565

class AppTable extends Table
{
    use ControllerActionTrait;
    use UtilityTrait;
    use LogTrait;
    use OptionListTrait;
    const OpenEMIS = 'OpenEMIS ID';
    public function initialize(array $config): void
    {
        //Time::$defaultLocale = 'en_US';
        //Date::$defaultLocale = 'en_US';

        $defaultLocale = Time::getDefaultLocale();
        Time::setDefaultLocale('en_US');

        $_config = [
            'Modified' => true,
            'Created' => true
        ];
        $_config = array_merge($_config, $config);
        parent::initialize($config);

        $schema = $this->getSchema();
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
            if ($schema->getColumnType($column) == 'date') {
                $dateFields[] = $column;
            } elseif ($schema->getColumnType($column) == 'time') {
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

    public function validationDefault(Validator $validator): Validator
    {
        $schema = $this->getSchema();
        $columns = $schema->columns();

        foreach ($columns as $column) {
            if ($schema->getColumnType($column) == 'date') {
                $attr = $schema->getColumn($column);
                // check if is nullable
                if (isset($attr['null']) && $attr['null'] === true) {
                    $validator->allowEmptyString($column);
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
            return $entity->invalid()[$propertyName];
        } else {
            return null;
        }
    }


    // Event: 'ControllerAction.Model.onPopulateSelectOptions'
    public function onPopulateSelectOptions(EventInterface $event, Query $query)
    {
        return $this->getList($query);
    }

    public function getList($query = null)
    {
        $schema = $this->getSchema();
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


    public function onExcelRenderDateTime(EventInterface $event, Entity $entity, $attr)
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
    public function onFormatDate(EventInterface $event, $dateObject)
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
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format');
        $value = '';
        if (is_object($dateObject)) {
            $value = $dateObject->format($format);
        }
        return $value;
    }

    // Event: 'ControllerAction.Model.onFormatTime'
    public function onFormatTime(EventInterface $event, $timeObject)
    {
        return $this->formatTime($timeObject);
    }

    /**
     * For calling from view files
     * @param  Time   $dateObject [description]
     * @return [type]             [description]
     * POCOR-9415 more error-save
     */
    public function formatTime($timeInput): string
    {
//        Log::debug(print_r(['timeInput' => $timeInput], true));

        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $format = $ConfigItem->value('time_format') ?: 'H:i:s'; // default fallback
        $value = '';

        try {
            // Normalize to FrozenTime
            if (is_string($timeInput)) {
                $time = new FrozenTime($timeInput);
            } elseif ($timeInput instanceof \DateTimeInterface) {
                $time = FrozenTime::instance($timeInput);
            } else {
                throw new \InvalidArgumentException('Invalid time input format');
            }

            $value = $time->format($format);
        } catch (\Exception $e) {
            Log::error('formatTime error: ' . $e->getMessage());
            $value = ''; // fallback value for safety
        }

        return $value;
    }

    // Event: 'ControllerAction.Model.onFormatDateTime'
    public function onFormatDateTime(EventInterface $event, $timeObject): string
    {
        return $this->formatDateTime($timeObject);
    }

    /**
     * For calling from view files
     * @param  Time   $dateObject [description]
     * @return [type]             [description]
     * POCOR-9415, POCOR-9510 more error-save
     */
    public function formatDateTime($dateInput): string //POCOR-9509: public — called from child tables and view files
    {
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        $dateFormat = $ConfigItem->value('date_format') ?: 'Y-m-d';
        $timeFormat = $ConfigItem->value('time_format') ?: 'H:i:s';
        $displayTimezone = new \DateTimeZone(ApplicationTimezone::getDisplayTimezone());
        $utcTimezone = new \DateTimeZone('UTC');

        $displayFormat = $dateFormat . ' - ' . $timeFormat;
        $inputFormat   = $displayFormat;

        try {
            if ($dateInput instanceof \DateTimeInterface) {
                //POCOR-9565[START]
                return FrozenTime::createFromTimestamp($dateInput->getTimestamp(), $utcTimezone)
                    ->setTimezone($displayTimezone)
                    ->format($displayFormat);
                //POCOR-9565[END]
            }

            if (is_string($dateInput) && trim($dateInput) !== '') {

                // Try parsing EXACT expected format first
                $date = FrozenTime::createFromFormat(
                    $inputFormat,
                    $dateInput,//POCOR-9565
                    $utcTimezone//POCOR-9565
                );

                if ($date !== false) {
                    return $date->setTimezone($displayTimezone)->format($displayFormat);//POCOR-9565
                }

                // Fallback: try ISO / DB formats
                //POCOR-9565
                return (new FrozenTime($dateInput, $utcTimezone))
                    ->setTimezone($displayTimezone)
                    ->format($displayFormat);
                //POCOR-9565
            }
        } catch (\Throwable $e) {
            //POCOR-9509: parsing failed — return a simple readable fallback rather than empty string
            if ($dateInput instanceof \DateTimeInterface) {
                return $dateInput->format('d M Y H:i:s');
            }
            return is_string($dateInput) ? $dateInput : '';
        }

        return '';
    }


    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderDatetime(EventInterface $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatDateTime($dateTimeObj);
    }

    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderDate(EventInterface $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatDate($dateTimeObj);
    }

    // Not using $extra parameter to be backward compatible with restfulv1
    public function onRestfulRenderTime(EventInterface $event, $entity, $property)
    {
        $dateTimeObj = $entity[$property];
        return $this->formatTime($dateTimeObj);
    }

    // Event: 'ControllerAction.Model.onGetFieldLabel'
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {

        $Labels     = TableRegistry::getTableLocator()->get('Labels');
        if($event->getData()['module'] !=null){
            $fieldLabel = $Labels->find()
                    ->select(['name'=>'name'])
                    ->where(['module' => $event->getData()['module'],'field'=>'openemis_no'])
                    ->first();
            if ($field == 'openemis_no' && !empty($fieldLabel['name'])) {
                 return $fieldLabel['name'];

            } else if ($field == 'openemis_no') {
                return self::OpenEMIS;

    		} else if ($field == 'fax' && !empty($fieldLabel['name'])) {
    		    return $fieldLabel['name'];
            }
        }
        return $this->getFieldLabel($module, $field, $language, $autoHumanize);
    }

    public function getFieldLabel($module, $field, $language, $autoHumanize = true)
    {
        $Labels = TableRegistry::getTableLocator()->get('Labels');
        $label = $Labels->getLabel($module, $field, $language);

        if (!$label || $label == "" || $label == false ) { //POCOR-8074-6
            if($field != null){
                $label = Inflector::humanize($field);
            }
            //$label = Inflector::humanize($field);
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
    public function onExcelGetLabel(EventInterface $event, $module, $col, $language)
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
    public function onInitializeButtons(EventInterface $event, ArrayObject $buttons, $action, $isFromModel, ArrayObject $extra)
    {

//         needs clean up
        $controller = $event->getSubject()->_registry->getController();
        $access = $controller->AccessControl;

        $toolbarButtons = new ArrayObject([]);
        $indexButtons = new ArrayObject([]);

        $toolbarAttr = $this->getButtonAttr();
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

        // Set for roles belonging to the controller
        $roles = [];
        $event = $controller->dispatchEvent('Controller.Buttons.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roles = $event->getResult();
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
            //POCOR-6922 Starts add else condition and in `if condition` add url check condition
            if (($this->request->url != 'Securities/Users') && $buttons->offsetExists('search')) {
                $toolbarButtons['search'] = [
                    'type' => 'element',
                    'element' => 'OpenEmis.search',
                    'data' => ['url' => $buttons['index']['url']],
                    'options' => []
                ];
            }else if(($this->request->getParam('plugin') == 'Security' && $this->request->getParam('controller') == 'Securities' && $this->request->url == 'Securities/Users')){
                $toolbarButtons['advance_search'] = [
                    'type' => 'button',
                    'attr' => [
                        'class' => 'btn btn-default btn-xs',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'title' => __('Advanced Search'),
                        'id' => 'search-toggle',
                        'escape' => false,
                        'ng-click'=> 'toggleAdvancedSearch()'
                    ],
                    'url' => '#',
                    'label' => '<i class="fa fa-search-plus"></i>',
                ];
            }//POCOR-6922 Ends
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

        // Start POCOR-5188
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Directory')){
            $is_manual_exist = $this->getManualUrl('Reports','Directory');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Institutions')){
            $is_manual_exist = $this->getManualUrl('Reports','Institution');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Students')){
            $is_manual_exist = $this->getManualUrl('Reports','Students');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Staff')){
            $is_manual_exist = $this->getManualUrl('Reports','Staff');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Textbooks')){
            $is_manual_exist = $this->getManualUrl('Reports','Textbooks');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Performance')){
            $is_manual_exist = $this->getManualUrl('Reports','Performance');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Examinations')){
            $is_manual_exist = $this->getManualUrl('Reports','Examinations');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Trainings')){
            $is_manual_exist = $this->getManualUrl('Reports','Trainings');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Scholarships')){
            $is_manual_exist = $this->getManualUrl('Reports','Scholarships');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Surveys')){
            $is_manual_exist = $this->getManualUrl('Reports','Surveys');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/InstitutionRubrics')){
            $is_manual_exist = $this->getManualUrl('Reports','Rubrics');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/DataQuality')){
            $is_manual_exist = $this->getManualUrl('Reports','Data Quality');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Audits')){
            $is_manual_exist = $this->getManualUrl('Reports','Audits');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/Workflows')){
            $is_manual_exist = $this->getManualUrl('Reports','Workflows');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/UisStatistics')){
            $is_manual_exist = $this->getManualUrl('Reports','UIS Statistics');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->getRequestTarget() == 'Map')){
            $is_manual_exist = $this->getManualUrl('Reports','Map');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Report' && $this->request->getParam('controller') == 'Reports' && $this->request->url == 'Reports/CustomReports')){
            $is_manual_exist = $this->getManualUrl('Reports','Custom');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        $parsedURL = explode('/',$this->request->url);
        if(($this->request->getParam('plugin') == 'Profile' && $this->request->getParam('controller') == 'Profiles' && !empty($parsedURL) && $parsedURL[2] == 'Accounts')){
            $is_manual_exist = $this->getManualUrl('Personal','Accounts');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Student' && $this->request->getParam('controller') == 'Students' && !empty($parsedURL) && $parsedURL[1] == 'Behaviours')){
            $is_manual_exist = $this->getManualUrl('Guardian','Behaviours');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        if(($this->request->getParam('plugin') == 'Profile' && $this->request->getParam('controller') == 'Profiles' && !empty($parsedURL) && $parsedURL[2] == 'History')){
            $is_manual_exist = $this->getManualUrl('Personal','History');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        // echo $this->request->getParam('plugin');
        // echo '---';
        // echo $this->request->getParam('controller');
        // echo '<pre>';
        // print_r( $parsedURL ); die;

        if(($this->request->getParam('plugin') == 'Directory' && $this->request->getParam('controller') == 'Directories' && !empty($parsedURL) && $parsedURL[2] == 'Accounts')){
            $is_manual_exist = $this->getManualUrl('Directory','Accounts','General');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }

        if(($this->request->getParam('plugin') == 'Configuration' && $this->request->getParam('controller') == 'Configurations' && !empty($parsedURL) && $parsedURL[0] == 'Configurations')){
            $is_manual_exist = $this->getManualUrl('Administration','Configurations','System Configurations');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }


        if(($this->request->getParam('plugin') == 'Security' && $this->request->getParam('controller') == 'Securities' && !empty($parsedURL) && $parsedURL[1] == 'Users')){
            $is_manual_exist = $this->getManualUrl('Administration','Users','Security');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }


        if(($this->request->getParam('plugin') == 'Workflow' && $this->request->getParam('controller') == 'Workflows' && !empty($parsedURL) && $parsedURL[1] == 'Statuses')){
            $is_manual_exist = $this->getManualUrl('Administration','Statuses','Workflows');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }

        if(($this->request->getParam('plugin') == 'Workflow' && $this->request->getParam('controller') == 'Workflows' && !empty($parsedURL) && $parsedURL[1] == 'Workflows')){
            $is_manual_exist = $this->getManualUrl('Administration','Workflows','Workflows');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }

        if(($this->request->getParam('plugin') == 'Workflow' && $this->request->getParam('controller') == 'Workflows' && !empty($parsedURL) && $parsedURL[1] == 'Steps')){
            $is_manual_exist = $this->getManualUrl('Administration','Steps','Workflows');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }

        if(($this->request->getParam('plugin') == 'Workflow' && $this->request->getParam('controller') == 'Workflows' && !empty($parsedURL) && $parsedURL[1] == 'Actions')){
            $is_manual_exist = $this->getManualUrl('Administration','Actions','Workflows');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
                $toolbarButtons['help']['url'] = $is_manual_exist['url'];
                $toolbarButtons['help']['type'] = 'button';
                $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
                $toolbarButtons['help']['attr'] = $btnAttr;
                $toolbarButtons['help']['attr']['title'] = __('Help');
            }
        }
        // End POCOR-5188

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

        $this->dispatchEvent('Model.custom.onUpdateToolbarButtons', [$buttons, $toolbarButtons, $toolbarAttr, $action, $isFromModel]);

        if ($toolbarButtons->offsetExists('back')) {
            $controller->set('backButton', $toolbarButtons['back']);
        }
        $controller->set(compact('toolbarButtons', 'indexButtons'));
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        //Log::debug('[TEMP-LOG] AppTable::onUpdateActionButtons START (table: ' . $this->getAlias() . ')');
        //Log::debug('[TEMP-LOG] Incoming buttons: ' . print_r($buttons, true));

        $id = $this->getEncodedKeys($entity);
        //Log::debug('[TEMP-LOG] Encoded key: ' . $id);

        if (isset($buttons['view'])) {
            //Log::debug('[TEMP-LOG] View button URL before append: ' . print_r($buttons['view']['url'], true));
            $buttons['view']['url'][] = $id;
            //Log::debug('[TEMP-LOG] View button URL after append: ' . print_r($buttons['view']['url'], true));
        }
        if (isset($buttons['edit'])) {
            $buttons['edit']['url'][] = $id;
        }
        if (isset($buttons['remove'])) {
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

        //Log::debug('[TEMP-LOG] AppTable final buttons: ' . print_r($buttons, true));
        //Log::debug('[TEMP-LOG] AppTable::onUpdateActionButtons END');

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
        if ($request->getData($this->aliasField($key))) {
            $selectedId = $request->getData($this->aliasField($key));

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
                if ($field === $assoc->getForeignKey()) {
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
                if ($field === $assoc->getForeignKey()) {
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
            $key = Inflector::underscore(Inflector::singularize($tableObj->getAlias()));
        }
        return $key;
    }

    public function getEncodedKeys(Entity $entity)
    {
        $primaryKey = $this->getPrimaryKey();
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

    // Start POCOR-5188
	public function getManualUrl($module, $function, $category='')
    {
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
        if ($category == ''){
            $ManualContent =   $manualTable->find()->select(['url'])->where([
                    $manualTable->aliasField('function') => $function,
                    $manualTable->aliasField('module') => $module
                    ])->first();
        }else{
            $ManualContent =   $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => $function,
                $manualTable->aliasField('module') => $module,
                $manualTable->aliasField('category') => $category,
                ])->first();
        }
        if (!empty($ManualContent['url'])) {
            return ['status'=>'success', 'url'=>$ManualContent['url']];
        }
        return [];
    }
	// End POCOR-5188

    //POCOR-8080-2 start
    /**
     * @param string $param
     * @param string $value
     */
    public function addQueryParam(string $param, string $value): void
    {
        // Get the current request object
        $request = $this->request;

        // Get the existing query parameters
        $queryParams = $request->getQueryParams();

        // Add or modify your parameter
        $queryParams[$param] = $value;

        // Create a new request object with the updated parameters
        $newRequest = $request->withQueryParams($queryParams);

        // Update the request object in the controller
        $this->request = $newRequest;
    }
    // End POCOR-5188
}
