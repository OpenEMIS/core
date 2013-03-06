<?php

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