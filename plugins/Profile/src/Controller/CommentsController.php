<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Controller\PageController;

class CommentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Security.Users');
        $this->loadModel('User.UserComments');
        $this->Page->loadElementsFromTable($this->UserComments);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->exclude(['security_user_id']);
        $page->get('comment_date')->setLabel('Date');
    }

    public function index()
    {
        $page = $this->Page;

        // set field order
        $page->move('comment_date')->first();
        $page->move('comment_type_id')->after('comment_date');

        // set default ordering
        $page->setQueryOption('order', [$this->UserComments->aliasField('comment_date') => 'DESC']);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;

        // set field order
        $page->move('comment_type_id')->first();
        $page->move('comment_date')->after('comment');

        parent::view($id);
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        // for Institution Staff and Institution Students
        if ($plugin == 'Institution') {
            $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
            $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;
            $institutionName = array_key_exists('institutionName', $options) ? $options['institutionName'] : '';

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $pluralUserRole = Inflector::pluralize($userRole);

            $page->addCrumb('Institutions', [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Institutions',
                'index'
            ]);
            $page->addCrumb($institutionName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                'institutionId' => $encodedInstitutionId,
                $encodedInstitutionId
            ]);
            $page->addCrumb($pluralUserRole, [
                'plugin' => $this->plugin,
                'controller' => 'Institutions',
                'action' => $pluralUserRole,
                'institutionId' => $encodedInstitutionId
            ]);
            $page->addCrumb($userName, [
                'plugin' => $this->plugin,
                'controller' => 'Institutions',
                'action' => $userRole.'User',
                'view',
                $encodedUserId
            ]);
            $page->addCrumb('Comments');

        // for Directories and Profiles
        } else if ($plugin == 'Profile' || $plugin == 'Directory') {
            $pluralPlugin = Inflector::pluralize($plugin);

            $page->addCrumb($plugin, [
                'plugin' => $plugin,
                'controller' => $pluralPlugin,
                'action' => $pluralPlugin,
                'view',
                $encodedUserId
            ]);
            $page->addCrumb($userName);
            $page->addCrumb('Comments');
        }
    }

    // for Directories and Profiles
    public function setupTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';

        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserId = $this->paramsEncode(['id' => $userId]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Overview' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' =>['text' =>  __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];

        foreach ($tabElements as $action => $obj) {
            $url = ['plugin' => $plugin, 'controller' => $pluralPlugin, 'action' => $action, $subaction, $encodedUserId];

            // exceptions
            if ($action == 'Overview') {
                $url['action'] = $pluralPlugin;
                $url[0] = 'view';

            } else if ($action == 'Accounts') {
                $url[0] = 'view';

            } else if ($action == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $plugin.'Comments',
                    'action' => 'index',
                    $encodedUserId
                ];

            } else if ($action == 'UserNationalities') {
                $url['action'] = 'Nationalities';
                $url[1] = $encodedUserAndNationalityId;
            }

            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($url);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }

    // for Institution Staff and Institution Students
    public function setupInstitutionTabElements($options)
    {
        $page = $this->Page;

        $plugin = $this->plugin;
        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $userRole = array_key_exists('userRole', $options) ? $options['userRole'] : '';
        $institutionId = array_key_exists('institutionId', $options) ? $options['institutionId'] : 0;

        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserId = $this->paramsEncode(['id' => $userId]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $pluralUserRole = Inflector::pluralize($userRole);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Overview' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Special Needs' =>['text' =>  __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];

        foreach ($tabElements as $action => $obj) {
            $url = ['plugin' => $userRole, 'controller' => $pluralUserRole, 'action' => $action, 'index', $encodedUserId];

            // exceptions
            if (in_array($action, ['Overview', 'Accounts'])) {
                $action = $action == 'Overview' ? $userRole.'User' : $action;
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $encodedUserId
                ];

            } else if ($action == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'institutionId' => $encodedInstitutionId,
                    'controller' => $userRole.'Comments',
                    'action' => 'index',
                    $encodedUserId
                ];

            } else if ($action == 'UserNationalities') {
                $url['action'] = 'Nationalities';
                $url[0] = $encodedUserAndNationalityId;
            }

            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($url);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }
}
