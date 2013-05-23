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

class UserSessionComponent extends Component {
    public $components = array('Session','Utility');

	public function writeStatusSession($type, $msg, $action) {
		$this->Session->write('Status.type', $type);
        $this->Session->write('Status.msg', $msg);
        $this->Session->write('Status.action', $action);
	}

	public function readStatusSession($action, $dismissOnClick = true) {
		if($this->Session->check('Status.type') && $this->Session->check('Status.action') == $action) {
            $type = $this->Session->read('Status.type');
            $msg = $this->Session->read('Status.msg');
            $settings = array('type' => $type);
            if (!$dismissOnClick) { $settings['dismissOnClick']; }

            $this->Utility->alert($msg, $settings);
            $this->Session->delete('Status.action');
            $this->Session->delete('Status.type');
            $this->Session->delete('Status.msg');
            
        }
	}

}
