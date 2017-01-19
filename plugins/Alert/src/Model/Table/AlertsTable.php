<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class AlertsTable extends ControllerActionTable
{
    private $statusTypes = [
        '0' => 'Stop',
        '1' => 'Running'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.process'] = 'process';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('process_name', ['visible' => false]);
        $this->field('process_id', ['visible' => false]);
        $this->field('status', ['after' => 'name']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $shellName = $entity->process_name;

        if (array_key_exists('edit', $buttons)) {
            if ($this->isShellStopExist($shellName)) {
                    $label = 'Start'; // if have the file, process not running
                } else {
                    $label = 'Stop';
                }

            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'process'
            ];
            $processButtons = $buttons['edit'];
            $processButtons['label'] = __($label);
            $newButtons['processButtons'] = $processButtons;
            $newButtons['processButtons']['url'] = $this->setQueryString($url, [
                'shell_name' => $shellName
            ]);
        }

        return $newButtons;
    }

    public function process(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $params = [];
        if (array_key_exists('queryString', $requestQuery)) {
            $params = $this->paramsDecode($requestQuery['queryString']);
        }

        $this->addRemoveShellStop($params['shell_name']);
        $this->triggerSendingAlertFeatureShell($params['shell_name']);

        // redirect to index page
        // sleep(1); // to let the shell process finished the changes then redirect
        $url = $this->url('index');
        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $shellName = $entity->process_name;
        if ($this->isShellStopExist($shellName)) {
            $status = 0;
        } else {
            $status = 1;
        }

        return $this->statusTypes[$status];
    }

    public function isShellStopExist($shellName)
    {
        // folder to the shellprocesses.
        $dir = new Folder(ROOT . DS . 'webroot' . DS . 'shellprocesses'); // path
        $filesArray = $dir->find($shellName.'.stop');

        if (!empty($filesArray)) {
            $exists = true;
        } else {
            $exists = false;
        }

        return $exists;
    }

    public function addRemoveShellStop($shellName)
    {
        $dir = new Folder(ROOT . DS . 'webroot' . DS . 'shellprocesses'); // path
        // $file = new File($dir->path.'/' . $shellName . '.stop', true);

        if ($this->isShellStopExist($shellName)) {
            // shell stop file exist, remove the shell stop
            $file = new File($dir->path.'/' . $shellName . '.stop', true);
            $file->delete();
        } else {
            // shell stop not exists, adding shell stop
            $file = new File($dir->path.'/' . $shellName . '.stop', true);
        }
    }


    public function triggerSendingAlertFeatureShell($shellName)
    {
        $args = '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
