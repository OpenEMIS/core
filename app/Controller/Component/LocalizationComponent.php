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

App::uses('L10n', 'I18n');

class LocalizationComponent extends Component {
	private $controller;

	public $components = array('Session', 'Cookie');

	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->Cookie->time = 3600 * 24 * 30; // expires after one month

		$languageOptions = array(
			'ara' => 'العربية',
			'chi' => '中文',
			'eng' => 'English',
			'fre' => 'Français',
			'rus' => 'русский',
			'spa' => 'español'
		);

		$l10n = new L10n();

		$params = $controller->params;
		$ConfigItem = ClassRegistry::init('ConfigItem');

		$showLanguage = $ConfigItem->getValue('language_menu');
		$controller->set('showLanguage', $showLanguage);

		if (!empty($params->query['lang'])) {
			$lang = $params->query['lang'];

		} else if ($this->Cookie->check('System.language')) {
			$lang = $this->Cookie->read('System.language');

		} else if ($this->Session->check('System.language')) {
			$lang = $this->Session->read('System.language');

		} else {
			$lang = $ConfigItem->getValue('language');

		}

		if ($controller->name == 'Security' 
		&&  $controller->action == 'login' 
		&&  $controller->request->is('post')
		&&  isset($controller->request->data['submit'])
		&&  $controller->request->data['submit'] == 'reload') {
			$lang = $controller->request->data['SecurityUser']['language'];
			$this->Cookie->write('System.language', $lang);
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
	}
}
