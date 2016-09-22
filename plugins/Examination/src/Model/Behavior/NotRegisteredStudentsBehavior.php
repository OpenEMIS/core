<?php 
namespace Examination\Model\Behavior;

use ArrayObject;
use Cake\ORM\ResultSet;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class NotRegisteredStudentsBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        // hide add button
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('add', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['add']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // End
    }

    public function indexAfterAction(Event $event, ResultSet $resultSet, ArrayObject $extra) {
        $model = $this->_table;
        $session = $model->request->session();

        $sessionKey = $model->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $model->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra) {
        $model = $this->_table;

        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.warning';
        $session->write($sessionKey, $model->aliasField('restrictAdd'));

        $url = $model->url('index');
        $event->stopPropagation();
        return $model->controller->redirect($url);
    }
}
