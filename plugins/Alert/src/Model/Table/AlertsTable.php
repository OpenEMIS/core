<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;
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
        $this->toggle('edit', true); //POCOR-7558 
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
        $this->field('frequency',['sort'=>false,'after'=>'process_name']); //POCOR-7558 
        $this->field('last_run_date'); //POCOR-7558 
        // // $this->field('status', ['after' => 'name']); //POCOR-7558 
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Alerts','Communications');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
         //POCOR-7558 start
        $systemProcess=TableRegistry::get('system_processes');
        $query->select([
            $this->aliasField('id'),
            $this->aliasField('name'),
            $this->aliasField('process_name'),
            $this->aliasField('process_id'),
            $this->aliasField('frequency'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'),
            $this->aliasField('created_user_id'),
            $this->aliasField('created'),
            "last_run_date"=>$systemProcess->aliasField('end_date'),]) 
        ->leftJoin(
            [ $systemProcess->alias() => $systemProcess->table()],
            [
                $systemProcess->aliasField('name = ') . $this->aliasField('name'),
            ])
        ->distinct(  $this->aliasField('id'))
        ->order($this->aliasField('name'));
         //POCOR-7558 start
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
         //POCOR-7558 start
        // if (array_key_exists('view', $buttons)) {
        //     if ($this->AccessControl->check(['Alerts', 'Alerts', 'process'])) { // to check execute permission
        //         // if ($this->isShellStopExist($shellName)) {
        //         //     $icon = '<i class="fa fa-play"></i>';
        //         //     $label = 'Start'; // if have the file, process not running
        //         // } else {
        //         //     $icon = '<i class="fa fa-stop"></i>';
        //         //     $label = 'Stop';
        //         // }

        //         $url = [
        //             'plugin' => 'Alert',
        //             'controller' => 'Alerts',
        //             'action' => 'Alerts',
        //             'process'
        //         ];

        //         $buttons['process'] = $buttons['view'];
        //         $buttons['process']['label'] = $icon . __($label);
        //         $buttons['process']['url'] = $this->setQueryString($url, [
        //             'shell_name' => $shellName,
        //             'action' => 'index'
        //         ]);
        //     }
        // }
         //POCOR-7558 end
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
    
   //POCOR-7558 start
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'last_run_date':
                return __('Last Run');
       default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    public function onUpdateFieldFrequency(Event $event, array $attr, $action)
    {
        $freqOptions=["Daily"=>"Daily",
                         "Weekly"=>"Weekly",
                         "Monthly"=>"Monthly",
                         "Yearly"=>"Yearly"];
        $attr['type'] = 'select';
        $attr['attr']['options'] = $freqOptions;
	    $attr['onChangeReload'] = true;
        return $attr;
    }
    public function editBeforeAction(Event $event)
    {   
        $this->field('name',['type' => 'readonly']);
        $this->field('frequency',['after' => 'name']);
        $this->field('last_run_date', ['visible' => false]);
    }
     //POCOR-7558 end
}