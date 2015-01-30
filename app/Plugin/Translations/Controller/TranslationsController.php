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

App::uses('Converter', 'Translations.Lib');
class TranslationsController extends AppController {
	public $components = array('Paginator');

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('plugin' => false, 'controller' => 'Areas'));
		$this->Navigation->addCrumb('Translations', array('controller' => 'Translations', 'action' => 'index'));
	}

	public function index() {
		$this->Navigation->addCrumb('List of Translations');
		$header = __('List of Translations');
		$model = 'Translation';

		$languageOptions = array(
			'ara' => 'العربية',
			'chi' => '中文',
			'fre' => 'Français',
			'rus' => 'русский',
			'spa' => 'español'
		);

		if ($this->request->is('post')) {
			$selectedLang = $this->request->data[$model]['language'];
			$this->Session->write("$model.language", $selectedLang);
		} else {
			if ($this->Session->check("$model.language")) {
				$selectedLang = $this->Session->read("$model.language");
			} else {
				$selectedLang = key($languageOptions);
			}
		}
		$this->request->data[$model]['language'] = $selectedLang;
		$order = empty($this->params->named['sort']) ? array('Translation.eng' => 'asc') : array();
		$data = $this->Search->search($this->Translation, array(), $order);
		
		if (empty($data)){
			$this->Message->alert('general.notExists');
		}
		$model = 'Translation';
		$this->set(compact('header', 'data', 'model', 'languageOptions', 'selectedLang'));
	}

	public function view() {
		if (empty($this->params['pass'][0])) {
			return $this->redirect(array('action' => 'index'));
		}
		$id = $this->params['pass'][0];

		$this->Navigation->addCrumb('Translation Details');
		$header = __('Translation Details');

		$data = $this->Translation->findById($id);

		$fields = $this->Translation->getFields();
		$this->Session->write('Translation.id', $id);

		$this->set(compact('header', 'data', 'fields', 'id'));
	}

	public function add() {
		$this->Navigation->addCrumb('Add Translation');
		$header = __('Add Translation');
		$this->set(compact('header'));
		$this->setupAddEditForm('add');
	}

	public function edit() {
		$this->Navigation->addCrumb('Edit Translation');
		$header = __('Edit Translation');
		$this->set(compact('header'));
		$this->setupAddEditForm('edit');
		$this->render('add');
	}

	private function setupAddEditForm($type) {
		$id = empty($this->params['pass'][0]) ? 0 : $this->params['pass'][0];
		$languages = array('eng', 'ara', 'spa', 'chi', 'rus', 'fre');
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			/*
			$data['Translation']['code'] = empty($data['Translation']['code'])? NULL: nl2br($data['Translation']['code']);
			
			foreach ($languages as $lang) {
				$data['Translation'][$lang] = empty($data['Translation'][$lang])? NULL : nl2br($data['Translation'][$lang]);
			}
			*/
			
			if ($this->Translation->save($data)) {
				$this->Message->alert('general.' . $type . '.success');
				$action = array('action' => 'index');
				if ($type == 'edit') {
					$action['action'] = 'view';
					$action[] = $id;
				}
				return $this->redirect($action);
			}
		} else {
			$this->recursive = -1;
			$data = $this->Translation->findById($id);
			if (!empty($data)) {
				$this->request->data = $data;
			}
		}
	}

	public function delete() {
		if ($this->Session->check('Translation.id')) {
			$id = $this->Session->read('Translation.id');
			if ($this->Translation->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->Message->alert('general.delete.failed');
			}

			$this->Session->delete('Translation.id');
			return $this->redirect(array('action' => 'index'));
		}
	}

	public function compile() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$lang = $this->request->data['lang'];

			$this->generatePO($lang);
			$this->generateMO($lang);
			$this->Message->alert('general.translation.success');
		}
	}

	public function importPOFile($lang = 'ara') {
		$this->autoRender = false;
		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$localeImportFile = $localDir . $lang . DS . 'LC_MESSAGES' . DS . 'update.po';
		if (is_file($localeImportFile)) {
			//echo '<meta content="text/html; charset=utf-8" http-equiv="Content-Type">';
			$translations = I18n::loadPo($localeImportFile);

			$saveData = array();
			$counter = 0;
			foreach ($translations as $tKey => $tValue) {
				if (!empty($tKey)) {
					$saveData[$counter]['eng'] = $tKey;
					$saveData[$counter][$lang] = $tValue;
					$counter ++;
				}
			}

			$data = $this->Translation->find('all');

			if (!empty($data)) {
				foreach ($saveData as $tKey => $tValue) {

					$conditions = array(
						'Translation.eng' => $tValue['eng'],
					);
					if ($this->Translation->hasAny($conditions)) {
						$this->Translation->recursive = -1;
						$transData = $this->Translation->findByEng($tValue['eng'], array('id'));
						$saveData[$tKey]['id'] = $transData['Translation']['id'];
					}
				}
			}

			if ($this->Translation->saveAll($saveData)) {
				return $this->redirect(array('action' => 'index', $lang));
			}
		}
	}

	public function generatePO($lang = 'ara') {
		$this->autoRender = false;

		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$localeImportFile = $localDir . $lang . DS . 'LC_MESSAGES' . DS . 'default.po';

		$data = $this->Translation->find('all', array('fields' => array('eng', $lang)));
		
		if (!file_exists($localeImportFile)) {
			$opFile = fopen($localeImportFile, 'w');
			fclose($opFile);
		}
		
		chmod($localeImportFile, 0666);
		
		if (is_writable($localeImportFile)) {
			$opFile = fopen($localeImportFile, 'w');
			fwrite($opFile, "msgid \"\"\n");
			fwrite($opFile, "msgstr \"\"\n");
	
			$format = '"%s\n"';
			fprintf($opFile, $format, 'Project-Id-Version: Openemis Version 2.0');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'POT-Creation-Date: 2013-01-17 02:33+0000');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'PO-Revision-Date: ' . date('Y-m-d H:i:sP'));
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Last-Translator: ');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Language-Team: ');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'MIME-Version: 1.0');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Content-Type: text/plain; charset=UTF-8');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Content-Transfer-Encoding: 8bit');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Language: ' . $lang);
			fwrite($opFile, "\n");
	
	
			foreach ($data as $translateWord) {
				$key = $translateWord['Translation']['eng'];
				$value = $translateWord['Translation'][$lang];
				fwrite($opFile, "\n");
				fwrite($opFile, "msgid \"$key\"\n");
				fwrite($opFile, "msgstr \"$value\"\n");
			}
			fclose($opFile);
		}
	}

	public function generateMO($lang = 'ara') {
		$this->autoRender = false;

		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$source = $localDir . $lang . DS . 'LC_MESSAGES' . DS . 'default.po';
		$destination = $localDir . $lang . DS . 'LC_MESSAGES' . DS . 'default.mo';

		Converter::convertToMo($source, $destination);
	}

}
