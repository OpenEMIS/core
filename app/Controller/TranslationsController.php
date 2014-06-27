<?php

App::uses('Sanitize', 'Utility');

class TranslationsController extends AppController {

	public $uses = Array('Translation');
	public $components = array('Paginator');

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Setup', 'action' => 'index'));
		$this->Navigation->addCrumb('Translations', array('controller' => 'Translations', 'action' => 'index'));
	}

	public function index() {
		$selectedLang = empty($this->params['pass'][0]) ? 'ara' : $this->params['pass'][0];
		$this->Navigation->addCrumb('List of Translations');
		$header = __('List of Translations');

		$searchKey = $this->Session->read('Translation.SearchField');
		$languageOptions = array(
			'ara' => 'العربية',
			'chi' => '中文',
			'fre' => 'Français',
			'rus' => 'русский',
			'spa' => 'español'
		);

		if ($this->request->is('post', 'put')) {
			if (isset($this->request->data['Translation']['SearchField'])) {
				$searchKey = $this->request->data['Translation']['SearchField'];

				$this->Session->delete('Translation.SearchField');
				$this->Session->write('Translation.SearchField', $searchKey);
			}
		} 
		
		if (!empty($searchKey)) {
			$searchField = Sanitize::escape(trim($searchKey));
			$options['conditions']['Translation.eng LIKE'] = '%' . $searchField . '%';
		}
		
		$options['order'] = array('Translation.eng' => 'asc');
		//$conditions = array('order' => array('Translation.eng' => 'asc'), 'conditions' => array('Translation.eng LIKE' => '%home%'));
		$this->Paginator->settings = array_merge(array('limit' => 30, 'maxLimit' => 100), $options);

		$data = $this->Paginator->paginate('Translation');
		if (empty($data)){
			$this->Message->alert('general.notExists');
		}
		$this->set(compact('header', 'data', 'languageOptions', 'selectedLang', 'searchKey'));
	}

	public function view() {
		if (empty($this->params['pass'][0])) {
			return $this->redirect(array('action' => 'index'));
		}

		$id = $this->params['pass'][0];

		$this->Navigation->addCrumb('Translation Details');
		$header = __('Translation Details');

		$data = $this->Translation->findById($id);

		$fields = $this->getDisplayFields();
		$this->Session->write('Translation.id', $id);

		$this->set(compact('header', 'data', 'fields', 'id'));
	}

	private function getDisplayFields() {
		$fields = array(
			'model' => 'Translation',
			'fields' => array(
				array('field' => 'code'),
				array('field' => 'eng', 'labelKey' => 'general.language.eng'),
				array('field' => 'ara', 'labelKey' => 'general.language.ara'),
				array('field' => 'spa', 'labelKey' => 'general.language.spa'),
				array('field' => 'chi', 'labelKey' => 'general.language.chi'),
				array('field' => 'rus', 'labelKey' => 'general.language.rus'),
				array('field' => 'fre', 'labelKey' => 'general.language.fre'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
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
		if ($this->request->is('post') || $this->request->is('put')) {
			$this->request->data['Translation']['eng'] = nl2br($this->request->data['Translation']['eng']);
			$this->request->data['Translation']['ara'] = nl2br($this->request->data['Translation']['ara']);
			$this->request->data['Translation']['spa'] = nl2br($this->request->data['Translation']['spa']);
			$this->request->data['Translation']['chi'] = nl2br($this->request->data['Translation']['chi']);
			$this->request->data['Translation']['rus'] = nl2br($this->request->data['Translation']['rus']);
			$this->request->data['Translation']['fre'] = nl2br($this->request->data['Translation']['fre']);
			if ($this->Translation->save($this->request->data)) {
				$this->Message->alert('general.' . $type . '.success');
				return $this->redirect(array('action' => 'index'));
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

		$opFile = fopen($localeImportFile, 'w');
		fwrite($opFile, "msgid \"\"\n");
		fwrite($opFile, "msgstr \"\"\n");

		fprintf($opFile, '"%s\n"', 'Project-Id-Version: Openemis Version 2.0');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'POT-Creation-Date: 2013-01-17 02:33+0000');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'PO-Revision-Date: ' . date('Y-m-d H:i:sP'));
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'Last-Translator: ');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'Language-Team: ');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'MIME-Version: 1.0');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'Content-Type: text/plain; charset=UTF-8');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'Content-Transfer-Encoding: 8bit');
		fwrite($opFile, "\n");
		fprintf($opFile, '"%s\n"', 'Language: ' . $lang);
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

	public function generateMO($lang = 'ara') {
		$this->autoRender = false;

		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$localeImportFile = $localDir . $lang . DS . 'LC_MESSAGES' . DS . 'default.po';

		App::import('Vendor', 'php-mo');
		phpmo_convert($localeImportFile);
	}

}
