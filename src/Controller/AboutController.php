<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

use PDO;

class AboutController extends AppController
{

    public function initialize()
    {
        parent::initialize();
        $tabElements = [
            'contact' => [
                'url' => ['controller' => $this->name, 'action' => 'contact'],
                'text' => __('Contact')
            ],
            // 'system' => [
            // 	'url' => ['controller' => $this->name, 'action' => 'system'],
            // 	'text' => __('System Information')
            // ],
            'license' => [
                'url' => ['controller' => $this->name, 'action' => 'license'],
                'text' => __('License')
            ],
            'partners' => [
                'url' => ['controller' => $this->name, 'action' => 'partners'],
                'text' => __('Partners')
            ],
        ];

        $this->Navigation->addCrumb($this->name);
        if ($this->request->action != 'index') {
            $this->Navigation->addCrumb($tabElements[$this->request->action]['text']);
        }
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set('contentHeader', __($this->name));
    }

    public function index()
    {
        $this->redirect(['action' => 'contact']);
    }

    public function contact()
    {
    }

    // public function system() {
    // 	$dbo = ConnectionManager::get('default');
    // 	$this->set('databaseInfo', $dbo->config()['driver']);
    // }

    public function license()
    {
    }


    public function partners()
    {
        $ConfigAttachments = TableRegistry::get('ConfigAttachments');

        $configAttachmentsQuery = $ConfigAttachments->find()
            ->where([$ConfigAttachments->aliasField('active') => 1, $ConfigAttachments->aliasField('type') => 'partner', ])
            ->order($ConfigAttachments->aliasField('order'))
            ;

        $this->set('data', $configAttachmentsQuery);
    }
}
