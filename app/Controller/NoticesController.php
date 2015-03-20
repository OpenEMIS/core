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

App::uses('AppController', 'Controller');

class NoticesController extends AppController {
	public $components = array(
		'ControllerAction' => array('model' => 'Notice')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		
		$this->bodyTitle = 'Notices';
		
		if ($this->action == 'index') {
			$this->Navigation->addCrumb('Notices');
			$this->Notice->fields['created']['labelKey'] = 'Notice';
			$this->Notice->fields['created']['visible'] = true;
			$this->Notice->fields['created']['hyperlink'] = true;
			$this->Notice->fields['created']['displayFormat'] = 'date';
			$order = 1;
			$this->Notice->setFieldOrder('created', $order++);
			$this->Notice->setFieldOrder('message', $order++);
		} else {
			$this->Navigation->addCrumb('Notices', array('controller' => 'Notices', 'action' => 'index', 'plugin' => false));
			$this->Navigation->addCrumb(ucfirst($this->action));
		}
	}

	public function index() {
		$data = $this->Notice->find('all', 
			array(
				'order' => array('Notice.created desc')
			)
		);
		$this->set('data', $data);
		$this->ControllerAction->render();
	}

	public function edit($id) {
		$this->Notice->overrideNltobr = true;
		$this->ControllerAction->edit($id);
		$this->ControllerAction->render();
	}

}
