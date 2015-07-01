<?php
namespace App\Controller;

class PreferencesController extends AppController {
	// public function index() {}
	public function index($selectedTab='Account') {
		$this->set('selectedTab', $selectedTab);
		$this->render('' . $selectedTab);
	}
}

