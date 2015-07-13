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

		$options = $this->Localization->getOptions();
		// Getting the elements for the toolbar
		// need to make a controls.ctp
		$toolbarElements = [
			['name' => 'Translation.controls', 'data' => [], 'options' => []]
		];

		//$this->controller->set(compact('toolbarElements'));

		//pr($this->Localization->getOptions());
		// $this->request
		//pr($this->schema());
		//$this->fields;

		// 'ar' => ['name' => 'العربية', 'direction' => 'rtl'],
		// 'zh' => ['name' => '中文', 'direction' => 'ltr'],
		// 'en' => ['name' => 'English', 'direction' => 'ltr'],
		// 'fr' => ['name' => 'Français', 'direction' => 'ltr'],
		// 'ru' => ['name' => 'русский', 'direction' => 'ltr'],
		// 'es' => ['name' => 'español', 'direction' => 'ltr']

		$defaultLocale = I18n::locale();

		$this->ControllerAction->setFieldOrder([
			 
		]);
	}

}

?>