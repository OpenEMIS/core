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

class AreaShell extends AppShell {
    public $uses = array('Area', 'AreaAdministrative');
    
    public function main() {}
	
    public function _welcome() {}
	
    public function run() {
		echo "Start recover Area\n";
		if(sizeof($this->args) == 1) {
			$i = $this->args[0];
			if (isset($this->uses[$i])) {
				$model = $this->uses[$i];
				$this->{$model}->recover('parent', -1);
			}
		}
		echo "End recover Area\n";
    }
}

?>
