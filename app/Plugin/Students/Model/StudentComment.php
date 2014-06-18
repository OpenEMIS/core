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

class StudentComment extends StudentsAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('comment_date'));
	public $belongsTo = array(
		'Students.Student',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Title'
			)
		),
		'comment' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Comment'
			)
		),
	);

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'comment_date', 'type' => 'datepicker'),
				array('field' => 'title'),
				array('field' => 'comment', 'type' => 'textarea'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function comments($controller, $params) {
		$controller->Navigation->addCrumb('Comments');
		$this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStudentId($controller->Session->read('Student.id'),  array(), array('StudentComment.comment_date' => 'asc'));

		$controller->set('data', $data);
	}
	
	public function commentsAdd($controller, $params) {
		$controller->Navigation->addCrumb(__('Add Comment'));
		$header = __('Add Comment');
		if ($controller->request->is('post')) {
			$data = $controller->request->data[$this->alias];
			$data['student_id'] = $controller->Session->read('Student.id');
			$this->create();

			if ($this->save($data)) {
				$id = $this->getLastInsertId();
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'comments'));
			}
		}
		$controller->set(compact('header'));
	}

	public function commentsView($controller, $params) {
		$controller->Navigation->addCrumb('Comment Details');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$data = $this->findById($id);

		if (!empty($data)) {
			$controller->Session->write($this->alias . '.id', $id);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'comments'));
		}
		$fields = $this->getDisplayFields($controller);
		$header = __('Comment Details');
		$controller->set(compact('data', 'fields', 'header'));
	}

	public function commentsEdit($controller, $params) {
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$controller->Navigation->addCrumb('Edit Comment');
		$header = __('Edit Comment');
		if ($controller->request->is('get')) {
			$obj = $this->findById($id);

			if (!empty($obj)) {
				$controller->request->data = $obj;
			} else {
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'comments'));
			}
		} else {
			$commentData = $controller->request->data[$this->alias];
			$commentData['student_id'] = $controller->Session->read('Student.id');

			if ($this->save($commentData)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'commentsView', $commentData['id']));
			}
		}
		$controller->set(compact('id', 'header'));
	}

	public function commentsDelete($controller, $params) {
		return $this->remove($controller, 'comments');
	}

}
