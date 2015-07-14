<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\I18n;
use Cake\Core\App;

class TranslationsController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('Localization.Translations');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$header = "Translations";

		// Setting a bread crumb
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);

		// Setting a header
		$this->set('contentHeader', __($header));
	}

	private $defaultLocale = 'en';

	public function convert(){
		$this->autoRender = false;
		$locale = $this->request->query('locale');
		$this->convertPO($locale);
	}

	private function convertPO($locale){
		$this->autoRender = false;
		// /rootURL/openemis-phpoe/src/locales/
		$localeDir = App::path('locales');
		$localeDir = $localeDir[0];
		$fileLocation = $localeDir . $locale . DS . 'default.po';
		$data = $this->Translations->find('all', [$this->defaultLocale, $locale]);
		
		// Check if the translation file exist
		// if (!file_exists($fileLocation)) {
		// 	// Open the file for write
		// 	fopen($fileLocation, 'w');
		// 	fclose($opFile);
		// }
		//pr($data);
	}

	// public function onInitialize(Event $event, $model) {
		
	// }
}
?>