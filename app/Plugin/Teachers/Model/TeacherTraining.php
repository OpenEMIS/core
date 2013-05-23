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

App::uses('UtilityComponent', 'Component');

class TeacherTraining extends TeachersAppModel {
	public $useTable = "teacher_training";
	
	public function getData($id) {

        $utility = new UtilityComponent(new ComponentCollection);
		$options['joins'] = array(
            array('table' => 'teacher_training_categories',
            	'alias' => 'TeacherTrainingCategories',
                'type' => 'LEFT',
                'conditions' => array(
                    'TeacherTrainingCategories.id = TeacherTraining.teacher_training_category_id'
                )
            )
        );

        $options['fields'] = array(
        	'TeacherTraining.id',
            'TeacherTraining.teacher_id',
            'TeacherTraining.teacher_training_category_id',
        	'TeacherTrainingCategories.name',
        	'TeacherTraining.completed_date'
        );

        $options['conditions'] = array('TeacherTraining.teacher_id' => $id);

        $options['order'] = array('TeacherTraining.completed_date DESC');

		$list = $this->find('all', $options);
		$list = $utility->formatResult($list);

		return $list;
	}
}
