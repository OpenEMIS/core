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
    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        'Student',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
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

    public function comments($controller, $params) {
        $controller->Navigation->addCrumb('Comments');
        $data = $this->find('all', array('conditions' => array('StudentComment.student_id' => $controller->studentId), 'recursive' => -1, 'order' => 'StudentComment.comment_date'));

        $controller->set('list', $data);
    }

    public function commentsAdd($controller, $params) {
        $controller->Navigation->addCrumb(__('Add Comments'));
        if ($controller->request->is('post')) {
            $addMore = false;
            if (isset($this->data['submit']) && $this->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($this->action);
            } else if (isset($this->data['submit']) && $this->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($this->action);
            } elseif (isset($this->data['submit']) && $this->data['submit'] == __('Add More')) {
                $addMore = true;
            } else {
                $controller->Navigation->validateModel($this->action, 'StudentComment');
            }
            $this->create();
            $controller->request->data['StudentComment']['student_id'] = $controller->studentId;

            $data = $this->data['StudentComment'];

            if ($this->save($data)) {
                $id = $this->getLastInsertId();
                if ($addMore) {
                    $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                }
                $controller->Navigation->updateWizard($this->action, $id, $addMore);
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'comments'));
            }
        }

        $controller->UserSession->readStatusSession($controller->request->action);
    }

    public function commentsView($controller, $params) {
        $commentId = $params['pass'][0];
        $commentObj = $this->find('all', array('conditions' => array('StudentComment.id' => $commentId)));
        if (!empty($commentObj)) {
            $controller->Navigation->addCrumb('Comment Details');

            $controller->Session->write('StudentCommentId', $commentId);
            $controller->set('commentObj', $commentObj);
        }
    }

    public function commentsEdit($controller, $params) {
        $commentId = $params['pass'][0];
        if ($controller->request->is('get')) {
            $commentObj = $this->find('first', array('conditions' => array('StudentComment.id' => $commentId)));

            if (!empty($commentObj)) {
                $controller->Navigation->addCrumb('Edit Comment Details');
                $controller->request->data = $commentObj;
            }
        } else {
            $commentData = $this->data['StudentComment'];

            if (isset($this->data['submit']) && $this->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($this->action);
            } else if (isset($this->data['submit']) && $this->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($this->action);
            }
            $commentData['student_id'] = $controller->studentId;

            if ($this->save($commentData)) {
                $controller->Navigation->updateWizard($this->action, $commentId);
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'commentsView', $commentData['id']));
            }
        }


        $controller->set('id', $commentId);
    }

    public function commentsDelete($id) {
        if ($controller->Session->check('StudentId') && $controller->Session->check('StudentCommentId')) {
            $id = $controller->Session->read('StudentCommentId');
            $studentId = $controller->Session->read('StudentId');
            $name = $this->field('title', array('StudentComment.id' => $id));
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->redirect(array('action' => 'comments', $studentId));
        }
    }

}
