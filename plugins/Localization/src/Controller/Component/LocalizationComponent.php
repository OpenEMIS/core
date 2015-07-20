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

class LocalizationComponent extends Component {
	private $controller;

	public $Session;
	public $showLanguage = true;
	public $language = 'en';
	private $languages = [
		'ar' => ['name' => 'العربية', 'direction' => 'rtl'],
		'zh' => ['name' => '中文', 'direction' => 'ltr'],
		'en' => ['name' => 'English', 'direction' => 'ltr'],
		'fr' => ['name' => 'Français', 'direction' => 'ltr'],
		'ru' => ['name' => 'русский', 'direction' => 'ltr'],
		'es' => ['name' => 'español', 'direction' => 'ltr']
	];
	public $components = ['Cookie'];


	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {
		$session = $this->request->session();
		$this->controller = $this->_registry->getController();
		
		$this->Cookie->name = str_replace(' ', '_', $this->controller->_productName) . '_COOKIE';
		$this->Cookie->time = 3600 * 24 * 30; // expires after one month
		$lang = $this->language;
		$params = $this->controller->params;

		if (!empty($params->query['lang'])) {
			$lang = $params->query['lang'];

		} else if ($this->Cookie->check('System.language')) {
			$lang = $this->Cookie->read('System.language');

		} else if ($session->check('System.language')) {
			$lang = $session->read('System.language');

		}
		I18n::locale($lang);
		$this->language = $lang;
		$this->Session = $session;
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {
		$controller = $this->controller;
		$htmlLang = $this->language;
		$languages = $this->languages;
		
		if ($this->request->is('post') && array_key_exists('System', $this->request->data)) {
			if (isset($this->request->data['System']['language'])) {
				$htmlLang = $this->request->data['System']['language'];
				$this->Cookie->write('System.language', $htmlLang);
			}
		}

		$this->Session->write('System.language', $htmlLang);

		$htmlLangDir = $languages[$htmlLang]['direction'];
		$controller->set('showLanguage', $this->showLanguage);
		$controller->set('languageOptions', $this->getOptions());
		$controller->set(compact('htmlLang', 'htmlLangDir'));
	}

	public function getOptions() {
		$languages = $this->languages;
		$options = [];

		foreach ($languages as $key => $lang) {
			$options[$key] = $lang['name'];
		}
		return $options;
	}

	public function getLanguages() {
		return $this->languages;
	}
}
