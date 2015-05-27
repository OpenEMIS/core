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

ControllerActionComponent - Version 2.0.1
*/

namespace CustomField\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class CustomFieldComponent extends Component {
	// Is called before the controller's beforeFilter method.
	public function initialize(array $config) {}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Event $event) {}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Event $event) {}
}
