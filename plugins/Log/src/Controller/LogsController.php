<?php
namespace Log\Controller;

use Log\Controller\AppController;
use Cake\Event\Event;

class LogsController extends AppController {
	public function initialize() {
		parent::initialize();
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Auth->allow(['index', 'download']);
	}

	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	public function index() {
		$files = scandir(LOGS);
		foreach ($files as $i => $file) {
			if (!$this->endsWith($file, '.log')) {
				unset($files[$i]);
			}
		}
		$this->set('files', $files);
	}

	public function download($file) {
		$this->autoRender = false;
		$path = LOGS.$file;

		header("Pragma: public", true);
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Type: text/plain');
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=".$file);
		header("Content-Length: ".filesize($path));
		echo file_get_contents($path);
		die;
	}
}
