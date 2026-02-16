<?php
namespace AcademicPeriod\Model\Table;

use ArrayObject;

use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class AcademicPeriodLevelsTable extends ControllerActionTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function beforeAction(EventInterface $event, ArrayObject $extra) {
		$this->field('level');
		$this->setFieldOrder(['level', 'name']);

		// Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Academic Period Levels','Academic Periods');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
	}

	public function addEditBeforeAction(EventInterface $event, ArrayObject $extra) {
		$this->fields['level']['type'] = 'hidden';
	}

	public function afterAction(EventInterface $event, ArrayObject $extra) {
		$this->field('editable', ['visible' => false]);
	}

	public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra) {
		if (!$entity->editable) {
			$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
			unset($toolbarButtonsArray['edit']);
			unset($toolbarButtonsArray['remove']);
			$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
		}
	}

	public function onUpdateFieldLevel(EventInterface $event, array $attr, $action, ServerRequest $request) {
		if ($action == 'add') {
			$query = $this->find();
			$results = $query
				->select(['level' => $query->func()->max('level')])
				->all();

			$maxLevel = 0;
			if (!$results->isEmpty()) {
				$data = $results->first();
				$maxLevel = $data->level;
			}

			$attr['attr']['value'] = ++$maxLevel;
		}

		return $attr;
	}

	public function editAfterAction(EventInterface $event, Entity $entity) {
		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			$event->stopPropagation();
			return $this->controller->redirect($this->url('index', false));
		}
	}

	public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra) {
		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			$event->stopPropagation();
			$extra['Alert']['message'] = 'general.delete.restrictDelete';
			// this replaces the $extra['result'] leading the conditional to reach Alert->error($extra['Alert']['message']).. probably need to revisit this as this doesnt allow for redirection here
			return false;
		}
	}

	public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($entity->editable == 0) {
			// POCOR-2588 - add logic to AcademicPeriodLevelsTable so that records that are not editable, cannot be deleted or edited
			unset($buttons['edit']);
			unset($buttons['remove']);
		}
    	return $buttons;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'level':
                return __('Level');
            case 'name':
                return __('Name');
            case 'created':
                return __('Created');
            case 'created_user_id':
                    return __('Created By');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');

            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
