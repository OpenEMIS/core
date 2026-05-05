<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
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

    // POCOR-9509: Alert types without Laravel command implementations
    // These alerts cannot be sent, so they should only have "Never" frequency
    // Values are the 'name' field from alerts table (feature names), NOT process_name
    //POCOR-9509: start - updated as new Laravel commands are implemented
    public const NON_IMPLEMENTED_ALERTS = [
        'StaffAttendance', // POCOR-9509: No shell exists, no Laravel command
    ];
    //POCOR-9509: end

    private $statusTypes = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany('AlertRules', [
            'className' => 'Alert.AlertRules',
            'foreignKey' => 'feature',     // in AlertRules
            'bindingKey' => 'name',        // in Alerts
        ]);
        $this->statusTypes = $this->getSelectOptions('Alert.status_types');

        $this->toggle('add', false);
        $this->toggle('edit', true); //POCOR-7558
        $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.process'] = 'process';
        return $events;
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function setupFields(EventInterface $event, Entity $entity)
    {
        $this->field('name', ['sort' => false]);
        $this->field('process_name', ['visible' => false]);
        $this->field('process_id', ['visible' => false]);
        $this->field('frequency',['sort'=>false,'after'=>'name', 'entity' => $entity ]); //POCOR-7558
        $this->field('last_run_date', ['visible' => true]); //POCOR-7558
        // // $this->field('status', ['after' => 'name']); //POCOR-7558

    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if(empty($params)){
            $extra['options']['direction'] = 'asc';
            $extra['options']['limit'] = 20;
            $extra['options']['sort'] = 'name';
        }
         //POCOR-7558 start
        $systemProcess=TableRegistry::getTableLocator()->get('SystemProcesses');
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
            [ $systemProcess->getAlias() => $systemProcess->getTable()],
            [
                $systemProcess->aliasField('name = ') . $this->aliasField('name'),
            ])
        ->distinct(  $this->aliasField('id'))
        ->order($this->aliasField('name'));
         //POCOR-7558 start
        $this->field('name');
        $this->field('process_name', ['visible' => false]);
        $this->field('process_id', ['visible' => false]);
        $this->field('frequency',['sort'=>true,'after'=>'name']); //POCOR-7558
        $this->field('last_run_date', ['visible' => true]); //POCOR-7558
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $shellName = $entity->process_name;
         //POCOR-7558 start
        // if (isset($buttons['view'])) {
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

    public function process(EventInterface $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->getQuery();
        $params = [];
        if (isset($requestQuery['queryString'])) {
            $params = $this->paramsDecode($requestQuery['queryString']);
        }

        $this->stopShell($params['shell_name']); // create and remove the shell stop of the shell
        $this->triggerAlertFeatureShell($params['shell_name']); // trigger the feature shell

        // redirect to respective page from params['action']
        $url = $this->url($params['action']);
        $event->stopPropagation();
        return $this->controller->redirect($url);
    }

    public function onGetName(EventInterface $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->name));
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
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

    /**
     * Triggers a CakePHP shell command with no parameters to send alert messages.
     *
     * Used to execute legacy alert shell scripts.
     *
     * @param string $shellName Name of the shell command (in Cake format).
     * @return void
     *
     * @deprecated Use {@see triggerAlertCommand()} instead. This method will be removed in future versions.
     */
    public function triggerAlertFeatureShell($shellName)
    {
        $args = '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    /**
     * Triggers a CakePHP command-line script with optional named parameters.
     *
     * This is the recommended method for executing alert-related commands.
     * Parameters are passed as named CLI arguments (e.g. --version=3.2.1).
     *
     * @param string $command Name of the command to run (snake_case, no 'Shell' suffix).
     * @param array<string, string|int|null> $params Associative array of command-line options.
     *        Example: ['version' => '3.2.1', 'user_id' => 5, 'roles' => '1,2', 'schools' => '4,5']
     * @return void
     * @throws \RuntimeException If the command fails to execute.
     */
    public function triggerAlertCommand(string $command, array $params = []): void
    {
        $args = '';

        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $args .= ' --' . $key . '=' . escapeshellarg($value);
            }
        }

        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $command . $args;
        $logFile = ROOT . DS . 'logs' . DS . $command . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logFile;

        exec($shellCmd);
        Log::write('debug', '[triggerAlertCommand] ' . $shellCmd);
    }

    /**
     * Triggers a CakePHP shell command with raw parameter string (legacy method).
     *
     * Executes a shell with appended arguments for triggering system update alerts.
     *
     * @param string $shellName Name of the shell command (e.g. 'alert_system_updates').
     * @param string|null $params Raw string of CLI arguments (e.g. '--version=3.2.1').
     * @return void
     *
     * @deprecated Use {@see triggerAlertCommand()} with an associative array instead.
     */
    public function triggerSystemUpdateAlertFeatureShell($shellName, $params)
    {
        $args = '';
        $args .= !is_null($params) ? ' '.$params : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

        //POCOR-8869[START]
        //POCOR-9100 small changes
        public function triggerStudentAdmissionFeatureShell($shellName, $school_name, $student_name, $academic_year, $grade_name, $recipient_id)
        {
            $args = !is_null($school_name) ? ' "' . $school_name . '"' : '';
            $args .= !is_null($student_name) ? ' "'.$student_name.'"' : '';
            $args .= !is_null($academic_year) ? ' "'.$academic_year.'"' : '';
            $args .= !is_null($grade_name) ? ' "'.$grade_name.'"' : '';
            //POCOR-9100 start
            // Ensure $recipient_id is properly formatted
            if (!empty($recipient_id)) {
                if (is_array($recipient_id)) {
                    $args .= " '".json_encode($recipient_id)."'";
                } else {
                    $args .= " '".$recipient_id."'";
                }
            }
            //POCOR-9100 end

            $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
            $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            exec($shellCmd);
            Log::write('debug', $shellCmd);
        }
        //POCOR-8869[START]


   //POCOR-7558 start
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'frequency':
                return __('Frequency');
            case 'name':
                return __('Name');
            case 'last_run_date':
                return __('Last Run');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created On');
            case 'modified':
                return __('Modified By');
            case 'modified_user_id':
                return __('Modified On');
        default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldFrequency(EventInterface $event, array $attr, $action)
    {
        // POCOR-8286 start
        $entity = $attr['entity'];
        // POCOR-9509: Event-based alerts fire on afterSave — daily schedule makes no sense for them
        $oneTimeProcesses = [
            'AlertAttendance',
            'AlertStudentAbsence',      // POCOR-9391
            'AlertStudentAdmission',
            'AlertStudentEnrolment',
            'AlertStudentStatus',       // POCOR-9509: event-based (afterSave)
            'AlertStaffType',           // POCOR-9509: event-based (afterSave)
        ];

        // POCOR-9509: Non-implemented alerts can only be "Never"
        if (in_array($entity->process_name, self::NON_IMPLEMENTED_ALERTS, true)) {
            $freqOptions = [
                "Never" => __("Never")
            ];
        } elseif (in_array($entity->process_name, $oneTimeProcesses, true)) {
            $freqOptions = [
                "Never" => __("Never"), // POCOR-8286
                "Once" => __("Once")
            ];
        } else {
            $freqOptions = [
                "Never" => __("Never"), // POCOR-8533
                "Daily" => __("Daily"),
                "Weekly" => __("Weekly"),
                "Monthly" => __("Monthly"),
                "Yearly" => __("Yearly"),
//                "Once" => __("Once")
            ];
        }
        // POCOR-8286 end
        $attr['type'] = 'select';
        $attr['attr']['options'] = $freqOptions;
        $attr['onChangeReload'] = true;

        return $attr;
    }


    public function editBeforeAction(EventInterface $event)
    {
        $this->field('name',['type' => 'readonly']);
//        $this->field('frequency',['after' => 'name']);
        $this->field('last_run_date', ['visible' => false]);
    }
     //POCOR-7558 end
}
