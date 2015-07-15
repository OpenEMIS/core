<?php
namespace Localization\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Network\Request;

class TranslationsTable extends AppTable {

	// Initialisation
	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);
		$this->belongsTo('ModifiedUser', ['className' => 'Security.Users', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);
	}

	// Has to be implemented before a button can be added
	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('modified', ['visible' => false]);
		$this->ControllerAction->field('modified_user_id', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);
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

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		// Include the js file for the compiling
		$includes['localization'] = ['include' => true, 'js' => 'Localization.translations'];
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$searchField = $request->data['Search']['searchField'];
		// Append the condition to the existing condition in the options
		$options['conditions']['OR'][] = $this->aliasField('en')." LIKE '%".$searchField."%'";
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if($action == "index"){
			$toolbarButtons['download']['type'] = 'button';
			$toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
			$toolbarButtons['download']['attr'] = $attr;
			$toolbarButtons['download']['attr']['title'] = __('Download');
			$toolbarButtons['download']['url'] = "/Localization/js/translations.js";
			$toolbarButtons['download']['onchange'] = "something()";
		}
    }
}

?>