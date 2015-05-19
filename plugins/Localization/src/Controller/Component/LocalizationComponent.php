<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace Localization\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\I18n\I18n;
//App::uses('L10n', 'I18n');

class LocalizationComponent extends Component {
	private $controller;

	public $Session;
	public $showLanguage = true;
	public $language = 'eng';
	public $languageOptions = [

	];
	public $components = ['Cookie'];


	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$Session = $this->request->session();
		$this->controller = $this->_registry->getController();
		/*
		$this->Cookie->name = str_replace(' ', '_', $this->controller->_productName) . '_COOKIE';
		$this->Cookie->time = 3600 * 24 * 30; // expires after one month
		$lang = 'eng';
		$params = $this->controller->params;

		$session = $this->request->session();

		if (!empty($params->query['lang'])) {
			$lang = $params->query['lang'];

		} else if ($this->Cookie->check('System.language')) {
			$lang = $this->Cookie->read('System.language');

		} else if ($session->check('System.language')) {
			$lang = $session->read('System.language');

		}
		$this->language = $lang;
		*/
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		/*
		$lang = $this->language;
		$languageOptions = array(
			'ara' => 'العربية',
			'chi' => '中文',
			'eng' => 'English',
			'fre' => 'Français',
			'rus' => 'русский',
			'spa' => 'español'
		);

		$l10n = new L10n();

		$controller->set('showLanguage', $this->showLanguage);

		if ($controller->request->is('post') && array_key_exists('System', $controller->request->data)) {
			if (isset($controller->request->data['System']['language'])) {
				$lang = $controller->request->data['System']['language'];
				$this->Cookie->write('System.language', $lang);
			}
		}
		
		$locale = $l10n->map($lang);
		if ($locale == false) {
			$lang = 'eng';
			$locale = $l10n->map($lang);
		}

		$catalog = $l10n->catalog($locale);
		$controller->set('lang_locale', $locale);
		$controller->set('lang_dir', $catalog['direction']);
		$controller->set('lang', $lang);
		$controller->set('languageOptions', $languageOptions);

		$this->Session->write('System.language', $lang);
		Configure::write('Config.language', $lang);
		*/

		$htmlLang = 'en';
		$htmlLangDir = 'ltr';
		$this->controller->set(compact('htmlLang', 'htmlLangDir'));
	}
}
