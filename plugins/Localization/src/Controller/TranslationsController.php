<?php
namespace Localization\Controller;

use Cake\Event\Event;
use Cake\Core\App;

class TranslationsController extends AppController {
	private $defaultLocale = 'en';

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

	public function compile(){
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$locale = $this->request->data['locale'];
			if($this->convertPO($locale))
				$this->Alert->success('Translations.success');
			else
				$this->Alert->warning('Translations.failed');
		}
	}

	private function convertPO($locale){
		$this->autoRender = false;
		$str = "";
		$localeDir = App::path('Locale');
		$localeDir = $localeDir[0];
		$fileLocation = $localeDir . $locale . DS . 'default.po';
		$data = $this->Translations
			->find('list' ,[
				'keyField' => $this->defaultLocale, 
				'valueField' => $locale
			])
			->toArray();

		// Header of the PO file
		$str .= 'msgid ""'."\n";
		$str .= 'msgstr ""'."\n";
		$str .= '"Project-Id-Version: Openemis Version 3\n"'."\n";
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
}
