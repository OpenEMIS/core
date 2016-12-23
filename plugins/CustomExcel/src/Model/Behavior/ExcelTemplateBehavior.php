<?php
namespace CustomExcel\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

class ExcelTemplateBehavior extends Behavior
{
    private $moduleMapping = [
        'CustomExcel.AssessmentResults' => 'Institution -> Assessment Results'
    ];

	public function initialize(array $config)
	{
		parent::initialize($config);

        $model = $this->_table;
        // setting this up to be overridden in viewAfterAction(), this code is required
        $model->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        $events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction'];
        $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'editAfterAction'];
		return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('file_name', [
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $model->field('file_content', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true]
        ]);

        $model->setFieldOrder(['module', 'file_name', 'file_content']);

        $this->initializeExcelTemplateData($extra);
    }

    public function onGetModule(Event $event, Entity $entity)
    {
        $value = $this->getNameByModule($entity->module);
        return $value;
    }

	public function initializeExcelTemplateData(ArrayObject $extra)
    {
        $model = $this->_table;

        if($model->action == 'index') {
            $broadcaster = $model;
            $listeners = [];
            foreach ($this->moduleMapping as $module => $moduleName) {
                $listeners[] = TableRegistry::get($module);
            }

            if (!empty($listeners)) {
                $model->dispatchEventToModels('ExcelTemplates.Model.initializeData', [$extra], $broadcaster, $listeners);
            }
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $action = $model->action;

        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $model->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity, $extra);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldModule(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->module;
            $attr['attr']['value'] = $this->getNameByModule($entity->module);
        }

        return $attr;
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;

        $model->field('module', ['entity' => $entity]);
        $model->field('file_name');
        $model->field('file_content');

        $model->setFieldOrder(['module' , 'file_content']);
    }

    public function getNameByModule($module)
    {
        return array_key_exists($module, $this->moduleMapping) ? $this->moduleMapping[$module] : $module;
    }

    public function checkIfHasTemplate($registryAlias=null)
    {
        $hasTemplate = false;

        $model = $this->_table;
        $entity = $model->find()->where([$model->aliasField('module') => $registryAlias])->first();
        $hasTemplate = !empty($entity->file_content) ? true : false;

        return $hasTemplate;
    }
}
