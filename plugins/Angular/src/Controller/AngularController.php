<?php

namespace Angular\Controller;

use Angular\Controller\AppController;

class AngularController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->Angular->resetConfig = false;
	}

	public function app() {
		$this->getView()->layout(false);
	}
}
