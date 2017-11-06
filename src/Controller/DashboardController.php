<?php
namespace App\Controller;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use App\Controller\AppController;

class DashboardController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        // $this->ControllerAction->model('Notices');
        // $this->loadComponent('Paginator');

        $this->ControllerAction->models = [
            'StudentAdmission'  => ['className' => 'Institution.StudentAdmission', 'actions' => ['edit']],
            'StudentWithdraw'   => ['className' => 'Institution.StudentWithdraw', 'actions' => ['edit']],
        ];
        $this->attachAngularModules();
    }

    // CAv4
    public function TransferApprovals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.TransferApprovals']);
    }
    // end of CAv4

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
        $user = $this->Auth->user();
        if (is_array($user) && array_key_exists('last_login', $user) && is_null($user['last_login'])) {
            $userInfo = TableRegistry::get('User.Users')->get($user['id']);
            if ($userInfo->password) {
                $this->Alert->warning('security.login.changePassword');
                $lastLogin = $userInfo->last_login;
                $this->request->session()->write('Auth.User.last_login', $lastLogin);
                $this->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Accounts', 'edit', $this->ControllerAction->paramsEncode(['id' => $user['id']])]);
            }

        }
        $header = __('Home Page');
        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        // set header
        $header = $model->getHeader($model->alias);

        // breadcrumb
        $alias = $model->alias;
        $models = [
            'TransferApprovals' => __('Transfer Approvals')
        ];


        if (array_key_exists($alias, $models)) {
            if ($model->action == 'associated') {
                $this->Navigation->addCrumb($models[$model->alias], [
                    'plugin' => false,
                    'controller' => 'Dashboard',
                    'action' => 'TransferApprovals',
                    'edit',
                    $model->paramsPass(0)
                ]);
                $this->Navigation->addCrumb(Inflector::humanize($model->action));
            }

            $header = $model->getHeader() . ' - ' . Inflector::humanize($model->action);
        }

        $this->set('contentHeader', $header);
    }

    public function index()
    {
        $this->set('ngController', 'DashboardCtrl as DashboardController');
        $this->set('noBreadcrumb', true);
    }

    private function attachAngularModules()
    {
        $action = $this->request->action;

        switch ($action) {
            case 'index':
                $this->Angular->addModules([
                    'alert.svc',
                    'dashboard.ctrl',
                    'dashboard.svc'
                ]);
                break;
        }
    }
}
