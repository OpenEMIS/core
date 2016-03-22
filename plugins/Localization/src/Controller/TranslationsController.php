<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\Core\App;
use Cake\Cache\Cache;

class TranslationsController extends AppController {
	private $defaultLocale = 'en';

	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('Localization.Translations');
		$this->Localization->autoCompile(false);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$header = "Translations";

		// Setting a bread crumb
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);

		// Setting a header
		$this->set('contentHeader', __($header));
	}
}
