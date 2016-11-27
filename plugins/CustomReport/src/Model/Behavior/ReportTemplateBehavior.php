<?php
namespace CustomReport\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Network\Request;
use Cake\Event\Event;

class ReportTemplateBehavior extends Behavior
{
    private $moduleMapping = [
        'Institution.AssessmentResults' => 'Institution -> Assessment Results'
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
            'type' => 'hidden',
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $model->field('file_type', [
            'type' => 'hidden',
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $model->field('file_content', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true]
        ]);

        $model->setFieldOrder(['model', 'file_name', 'file_type', 'file_content']);
    }

    public function onGetModel(Event $event, Entity $entity)
    {
        $value = array_key_exists($entity->model, $this->moduleMapping) ? $this->moduleMapping[$entity->model] : $entity->model;

        return $value;
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
    	$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
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

    public function onUpdateFieldModel(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->model;
            $attr['attr']['value'] = array_key_exists($entity->model, $this->moduleMapping) ? $this->moduleMapping[$entity->model] : $entity->model;
        }

        return $attr;
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;

        $model->field('model', ['entity' => $entity]);
        $model->field('file_content');

        $model->setFieldOrder(['model' , 'file_content']);
    }

    public function checkIfHasTemplate($registryAlias=null)
    {
        $hasTemplate = false;

        $model = $this->_table;
        $entity = $model->find()->where([$model->aliasField('model') => $registryAlias])->first();
        $hasTemplate = !empty($entity->file_content) ? true : false;

        return $hasTemplate;
    }
}
