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

class StudentAttendanceType extends StudentsAppModel {

    public function findOptions($options = array()) {
        $conditions = array('StudentAttendanceType.order >' => 0);
        if (isset($options['conditions'])) {
            $options['conditions'] = array_merge($options['conditions'], $conditions);
        }
        $list = parent::findOptions($options);
        return $list;
    }

    // Used by SetupController
    public function getLookupVariables() {
        $lookup = array('Attendance Type' => array('model' => 'Students.StudentAttendanceType'));
        return $lookup;
    }

    public function getOptions() {
        $data = $this->find('all', array('recursive' => -1, 'conditions' => array('StudentAttendanceType.visible' => 1), 'order' => array('StudentAttendanceType.order')));
        $list = array();
        foreach ($data as $obj) {
            $list[$obj['StudentAttendanceType']['id']] = $obj['StudentAttendanceType']['name'];
        }

        return $list;
    }

    public function getAttendanceTypes() {
        $data = $this->find('all', array(
            'recursive' => -1,
            'fields' => array('StudentAttendanceType.id', 'StudentAttendanceType.name', 'StudentAttendanceType.international_code', 'StudentAttendanceType.national_code'),
            'conditions' => array('StudentAttendanceType.visible' => 1),
            'order' => array('StudentAttendanceType.order')
        ));

        return $data;
    }

}