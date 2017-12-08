<?php
namespace Localization\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;
use Cake\Validation\Validator;

class TranslationsTable extends AppTable {

	// Initialisation
	public function initialize(array $config) {
		parent::initialize($config);
	}

	// Search component
	public function indexBeforeAction(Event $event){
		// By default English has to be there
		$defaultLocale = 'en';

		// Get the localization option from localization component
		$localeOptions = $this->Localization->getOptions();

		if(array_key_exists($defaultLocale, $localeOptions)){
			unset($localeOptions[$defaultLocale]);
		}
		$this->controller->set(compact('localeOptions'));

		$selectedOption = $this->queryString('translations_id', $localeOptions);
		$this->controller->set('selectedOption', $selectedOption);

		$toolbarElements = [
			['name' => 'Localization.controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$selected = 'ar';
		if(array_key_exists($selectedOption, $localeOptions)){
			$selected = $selectedOption;
		}

		$this->ControllerAction->setFieldOrder([
			 $defaultLocale, $selected
		]);
		$this->ControllerAction->setFieldVisible(['index'], [
			$defaultLocale, $selected
		]);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		if ($entity->editable == 0) {
			if ($this->action == 'edit') {
				$this->ControllerAction->field('en', ['type' => 'readonly']);
			}
		}
		$this->ControllerAction->field('editable', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_search'] = false;
		$options['auto_contain'] = false;

		$search = $this->ControllerAction->getSearchKey();

		if (!empty($search)) {
			$query->where([$this->aliasField('en')." LIKE '%" . $search . "%'"]);
		}
	}

	public function onGetEditable(Event $event, Entity $entity) {
		return ($entity->editable == 0)? __('No'): __('Yes');
	}

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if ($entity->editable == 0) {
			// remove the delete button
			unset($buttons['remove']);
		}
    	return $buttons;
    }
}
