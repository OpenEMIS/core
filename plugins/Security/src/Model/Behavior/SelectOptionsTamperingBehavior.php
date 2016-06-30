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

namespace Security\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Controller\Exception\AuthSecurityException;

class SelectOptionsTamperingBehavior extends Behavior {
	const DEFAULT_MESSAGE = 'Options has been tampered';
	
	public function implementedEvents() 
	{
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.index.beforeAction' => 'indexBeforeAction',
			'ControllerAction.Model.view.beforeAction' => 'viewBeforeAction'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function indexBeforeAction(Event $event) 
	{	
		$session = new Session();
		$session->delete('FormTampering');
	}
	public function viewBeforeAction(Event $event) 
	{	
		$session = new Session();
		$session->delete('FormTampering');
	}
}
