<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class AlertsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $statusTypes = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->statusTypes = $this->getSelectOptions('Alert.status_types');

        $this->toggle('add', false);
        $this->toggle('edit', false);
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
        $this->field('name', ['sort' => false]);
        $this->field('process_name', ['visible' => false]);
        $this->field('process_id', ['visible' => false]);
        $this->field('status', ['after' => 'name']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->order('name');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // for shell process the modified_user_id unable to get the auth user id.
        $this->field('modified_user_id',['visible' => false]);

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

        $url = [
            'plugin' => 'Alert',
            'controller' => 'Alerts',
            'action' => 'Alerts',
            'process'
        ];

        $toolbarButtonsArray['process'] = $this->getButtonTemplate();
        $toolbarButtonsArray['process']['label'] = $icon;
        $toolbarButtonsArray['process']['attr']['title'] = __($label);
        $toolbarButtonsArray['process']['url'] = $this->setQueryString($url, [
            'shell_name' => $shellName,
            'action' => 'view'
        ]);

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end process toolbar buttons
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $shellName = $entity->process_name;

        if (array_key_exists('view', $buttons)) {
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

                $buttons['process'] = $buttons['view'];
                $buttons['process']['label'] = $icon . __($label);
                $buttons['process']['url'] = $this->setQueryString($url, [
                    'shell_name' => $shellName,
                    'action' => 'index'
                ]);
            }
        }

        return $buttons;
    }

    public function process(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $params = [];
        if (array_key_exists('queryString', $requestQuery)) {
            $params = $this->paramsDecode($requestQuery['queryString']);
        }

        $this->stopShell($params['shell_name']); // create and remove the shell stop of the shell
        $this->triggerAlertFeatureShell($params['shell_name']); // trigger the feature shell

        // redirect to respective page from params['action']
        $url = $this->url($params['action']);
        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->name));
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        $shellName = $entity->process_name;
        if ($this->isShellStopExist($shellName)) {
            $status = 0; // Stopped
        } else {
            $status = 1; // Running
        }

        return $this->statusTypes[$status];
    }

    public function isShellStopExist($shellName)
    {
        // folder to the shellprocesses.
        $dir = new Folder(ROOT . DS . 'tmp'); // path
        $filesArray = $dir->find($shellName.'.stop');

        return !empty($filesArray);
    }

    public function stopShell($shellName)
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
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
