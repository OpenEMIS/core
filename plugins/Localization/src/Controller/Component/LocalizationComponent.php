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
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Core\App;
use Cake\I18n\Time;

class LocalizationComponent extends Component {
	private $defaultLocale = 'en';
	private $controller;
	public $Session;
	public $showLanguage = true;
	public $language = 'en';
	private $languages = [
		'ar' => ['name' => 'العربية', 'direction' => 'rtl', 'locale' => 'ar_SA'],
		'zh' => ['name' => '中文', 'direction' => 'ltr', 'locale' => 'zh_CN'],
		'en' => ['name' => 'English', 'direction' => 'ltr', 'locale' => 'en_US'],
		'fr' => ['name' => 'Français', 'direction' => 'ltr', 'locale' => 'fr_FR'],
		'ru' => ['name' => 'русский', 'direction' => 'ltr', 'locale' => 'ru_RU'],
		'es' => ['name' => 'español', 'direction' => 'ltr', 'locale' => 'es_ES']
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
		// $locale = isset($this->languages[$lang]) ? $this->languages[$lang]['locale'] : 'en_US';
		// I18n::locale($locale);
		I18n::locale($lang);

		$this->language = $lang;
		$this->Session = $session;
		if ($this->controller->name != 'Translations') {
			$this->updateLocaleFile($lang);
		}
	}

	private function updateLocaleFile($lang) {
		if ($this->defaultLocale != $lang) {
			if ($this->isChange($lang)) {
				$this->convertPO($lang);
			}	
		}
	}

	private function isChange($locale) {
		$change = false;
		$localeDir = App::path('Locale');
		$localeDir = $localeDir[0];
		$fileLocation = $localeDir . $locale . DS . 'default.po';
		$file = fopen($fileLocation, "r");
		while (!feof($file)) {
		   $line = fgets($file);
		   if (strpos($line, 'PO-Revision-Date: ')) {
		   		$line = str_replace('"PO-Revision-Date: ', '', $line);
		   		$line = str_replace('\n"', '', $line);
		   		try {
			   		$dateTime = new Time($line);
			   		$TranslationsTable = TableRegistry::get('Localization.Translations');
			   		$selectedColumns = [
						'modified' => '(
							CASE 
								WHEN '.$TranslationsTable->aliasField('modified').' > '.$TranslationsTable->aliasField('created').' 
								THEN '.$TranslationsTable->aliasField('modified').' 
								ELSE '.$TranslationsTable->aliasField('created').' 
								END
							)'
					];
			   		$lastModified = $TranslationsTable
						->find()
						->select($selectedColumns)
						->order(['modified' => 'DESC'])
						->extract('modified')
						->first();
					
					if ($lastModified->gt($dateTime)) {
						$change = true;
					}
				} catch (\Exception $e) {
					$change = true;
				}
		   		break;
		   }
		}

		fclose($file);
		return $change;
	}

	private function convertPO($locale){
		$str = "";
		$localeDir = App::path('Locale');
		$localeDir = $localeDir[0];
		$fileLocation = $localeDir . $locale . DS . 'default.po';
		$TranslationsTable = TableRegistry::get('Localization.Translations');
		$data = $TranslationsTable
			->find('list' ,[
				'keyField' => $this->defaultLocale, 
				'valueField' => $locale
			])
			->toArray();

		// clear persistent cache that is used for Translations
		Cache::clear(false, '_cake_core_');

		// Header of the PO file
		$str .= 'msgid ""'."\n";
		$str .= 'msgstr ""'."\n";
		$str .= '"Project-Id-Version: OpenEMIS Project\n"'."\n";
		$str .= '"POT-Creation-Date: 2013-01-17 02:33+0000\n"'."\n";
		$str .= '"PO-Revision-Date: '.date('Y-m-d H:i:sP').'\n"'."\n";
		$str .= '"Last-Translator: \n"'."\n";
		$str .= '"Language-Team: \n"'."\n";
		$str .= '"MIME-Version: 1.0\n"'."\n";
		$str .= '"Content-Type: text/plain; charset=UTF-8\n"'."\n";
		$str .= '"Content-Transfer-Encoding: 8bit\n"'."\n";
		$str .= '"Language: '.$locale.'\n"'."\n";
		
		//Replace the whole file
		if(file_put_contents($fileLocation, $str, LOCK_EX)){
			// For populating the translation list
			foreach ($data as $key => $value) {
				$msgid = $key;
				$msgstr = $value;
				$str = "\n";
				$str .= 'msgid "'.$msgid.'"'."\n";
				$str .= 'msgstr "'.$msgstr.'"'."\n";
				//Append to current file
				file_put_contents($fileLocation, $str, FILE_APPEND | LOCK_EX);
			}
			return true;
		}else{
			return false;
		}
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
