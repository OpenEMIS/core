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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $shellName = $entity->process_name;
        if ($this->isShellStopExist($shellName)) {
            $icon = '<i class="fa fa-play"></i>';
            $label = 'Start'; // if have the file, process not running
        } else {
            $icon = '<i class="fa fa-stop"></i>';
            $label = 'Stop';
        }

        // process Toolbar buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('edit', $toolbarButtonsArray)) {
            if ($this->AccessControl->check(['Alerts', 'Alerts', 'edit'])) { // to check edit permission
                $url = [
                    'plugin' => 'Alert',
                    'controller' => 'Alerts',
                    'action' => 'Alerts',
                    'process'
                ];
                $toolbarButtonsArray['edit']['label'] = $icon;
                $toolbarButtonsArray['edit']['attr']['title'] = __($label);
                $toolbarButtonsArray['edit']['url'] = $this->setQueryString($url, [
                    'shell_name' => $shellName,
                    'action' => 'view'
                ]);
            }
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end process toolbar buttons
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $shellName = $entity->process_name;

        if (array_key_exists('view', $buttons)) {
            $newButtons['view'] = $buttons['view'];
        }

        if (array_key_exists('edit', $buttons)) {
            if ($this->AccessControl->check(['Alerts', 'Alerts', 'process'])) { // to check execute permission
                if ($this->isShellStopExist($shellName)) {
                    $icon = '<i class="fa fa-play"></i>';
                    $label = 'Start'; // if have the file, process not running
                } else {
                    $icon = '<i class="fa fa-stop"></i>';
                    $label = 'Stop';
                }

                $url = [
                    'plugin' => 'Alert',
                    'controller' => 'Alerts',
                    'action' => 'Alerts',
                    'process'
                ];
                $processButtons = $buttons['edit'];
                $processButtons['label'] = $icon.__($label);
                $newButtons['processButtons'] = $processButtons;
                $newButtons['processButtons']['url'] = $this->setQueryString($url, [
                    'shell_name' => $shellName,
                    'action' => 'index'
                ]);
            }
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

        $this->addRemoveShellStop($params['shell_name']); // create and remove the shell stop of the shell
        $this->triggerAlertFeatureShell($params['shell_name']); // trigger the feature shell

        // redirect to respective page from params['action']
        $url = $this->url($params['action']);
        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $today = Time::now();
        $shellName = $entity->process_name;
        if ($this->isShellStopExist($shellName)) {
            $status = 0;

            // update the pid and time
            $this->query()
                ->update()
                ->where(['process_name' => $shellName])
                ->set([
                    'process_id' => null,
                    'modified' => $today
                ])
                ->execute();
        } else {
            $status = 1;

            // update the pid and time
            $shellCmd = "ps -ef | grep " . $shellName . " | grep apache | awk '{print $2}'";
            $pid = exec($shellCmd);

            $this->query()
                ->update()
                ->where(['process_name' => $shellName])
                ->set([
                    'process_id' => $pid,
                    'modified' => $today
                ])
                ->execute();
        }

        return $this->statusTypes[$status];
    }

    public function isShellStopExist($shellName)
    {
        // folder to the shellprocesses.
        $dir = new Folder(ROOT . DS . 'tmp'); // path
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
        $dir = new Folder(ROOT . DS . 'tmp'); // path

        if ($this->isShellStopExist($shellName)) {
            // shell stop file exist, remove the shell stop
            $file = new File($dir->path.'/' . $shellName . '.stop', true);
            $file->delete();
        } else {
            // shell stop not exists, adding shell stop
            $file = new File($dir->path.'/' . $shellName . '.stop', true);
        }
    }

    public function triggerAlertFeatureShell($shellName)
    {
        $args = '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
