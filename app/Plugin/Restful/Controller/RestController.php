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

class RestController extends RestfulAppController {
	public $uses = array(
        'Surveys.SurveyTemplate',
        'Surveys.SurveyQuestion',
        'Surveys.SurveyResponse'
	);

	public $components = array(
		'Paginator',
        'Restful.RestSurvey' => array(
            'models' => array(
                'Module' => 'Surveys.SurveyModule',
                'Group' => 'Surveys.SurveyTemplate',
                'Field' => 'Surveys.SurveyQuestion',
                'FieldOption' => 'Surveys.SurveyQuestionChoice',
                'TableColumn' => 'Surveys.SurveyTableColumn',
                'TableRow' => 'Surveys.SurveyTableRow'
            )
        )
	);

	public $paginate = array(
        'limit' => 20,
        'contain' => array()
    );

	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->allow();
	}

	public function survey() {
        $this->autoRender = false;
        $pass = $this->params->pass;
        $action = 'index';
        if (!empty($pass)) {
            $action = array_shift($pass);
        }

        if (method_exists($this->RestSurvey, $action)) {
            return call_user_func_array(array($this->RestSurvey, $action), $pass);
        } else {
            return false;
        }
    }
}
