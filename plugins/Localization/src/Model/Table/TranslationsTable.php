<?php
namespace Localization\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\I18n;

class TranslationsTable extends AppTable {

	// Initialisation
	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);
		$this->belongsTo('ModifiedUser', ['className' => 'Security.Users', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'Security.Users', 'foreignKey' => 'created_user_id']);
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
}

?>