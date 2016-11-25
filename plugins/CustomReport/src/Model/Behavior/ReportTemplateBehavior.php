<?php
namespace CustomReport\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class ReportTemplateBehavior extends Behavior
{
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
		return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
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
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $model->setFieldOrder(['model', 'file_name', 'file_type', 'file_content']);
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

        $model->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);
    }
}
