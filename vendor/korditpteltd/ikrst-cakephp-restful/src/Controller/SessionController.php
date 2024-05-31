<?php
namespace Restful\Controller;

use Cake\Event\Event;
use Cake\Log\Log;
use Restful\Controller\AppController;

class SessionController extends AppController
{
    private $Session = null;
    public $components = ['RequestHandler'];

    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        if (empty($this->request->params['_ext'])) {
            $this->request->params['_ext'] = 'json';
        }
        $this->Session = $this->request->session();
    }

    public function write()
    {
        $data = $this->request->data;
        foreach ($data as $key => $value) {
            $this->Session->write($key, $value);
        }
        $this->set(['data' => $data, '_serialize' => ['data']]);
    }

    public function read($key)
    {
        $data = $this->Session->read($key);
        $this->set(['data' => $data, '_serialize' => ['data']]);
    }

    public function check($key)
    {
        $data = $this->Session->check($key);
        $this->set(['data' => $data, '_serialize' => ['data']]);
    }

    public function delete($key)
    {
    	$this->Session->delete($key);
    	$this->set(['data' => true, '_serialize' => ['data']]);
    }
}
