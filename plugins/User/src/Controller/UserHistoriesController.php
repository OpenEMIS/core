<?php
namespace User\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;

use App\Controller\PageController;

class UserHistoriesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.Institutions');
        $this->loadModel('Security.Users');
        $this->loadModel('User.UserHistories');
        $this->Page->loadElementsFromTable($this->UserHistories);
        $this->Page->disable(['add', 'edit', 'view', 'delete']);
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $userId = $this->paramsDecode($this->request->query['queryString'])['security_user_id'];
        $userName = $this->Users->get($userId)->name;
        $userType = $this->paramsDecode($this->request->query['queryString'])['user_type'];

        parent::beforeFilter($event);

        // set Breadcrumb
        $this->setBreadCrumb([
            'institution_id' => $institutionId,
            'institution_name' => $institutionName,
            'user_id' => $userId,
            'user_name' => $userName,
            'user_type' => $userType,
        ]);

        $page = $this->Page;

        // set header
        $header = $page->getHeader();
        $page->setHeader($userName . ' - ' . __('History'));

        // set queryString
        $page->setQueryString('security_user_id', $userId);

        // set field
        $page->exclude(['model_reference', 'field_type', 'operation', 'security_user_id']);

        // set field order
        $page->move('model')->first();
        $page->move('field')->after('model');
        $page->move('old_value')->after('field');
        $page->move('new_value')->after('old_value');
    }

    public function index()
    {
        $page = $this->Page;

        // modified_by
        $page->addNew('modified_by');
        $page->get('modified_by')->setDisplayFrom('created_user.name');

        // modified_on
        $page->addNew('modified_on');
        $page->get('modified_on')->setDisplayFrom('created');

        parent::index();
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        $userId = array_key_exists('user_id', $options) ? $options['user_id'] : 0;
        $userName = array_key_exists('user_name', $options) ? $options['user_name'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        if ($plugin == 'Institution') { // for student and staff
            $institutionId = array_key_exists('institution_id', $options) ? $options['institution_id'] : 0;
            $institutionName = array_key_exists('institution_name', $options) ? $options['institution_name'] : '';
            $userType = array_key_exists('user_type', $options) ? $options['user_type'] : '';
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            $pluralUserType = Inflector::pluralize($userType);

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
            $page->addCrumb($userType, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $pluralUserType,
                'institutionId' => $encodedInstitutionId
            ]);
            $page->addCrumb($userName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $userType.'User',
                'view',
                $encodedUserId
            ]);
            $page->addCrumb(__('History'));
        } else if ($plugin == 'Directory') { // for directory
            $page->addCrumb('Directory', [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'Directories',
                'index'
            ]);
            $page->addCrumb($userName, [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'Directories',
                'view',
                $encodedUserId
            ]);
            $page->addCrumb(__('History'));
        }
    }
}
