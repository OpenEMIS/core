<?php

namespace User\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Inflector;

use App\Controller\PageController;

class UserHistoriesController extends PageController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Institutions = $this->fetchTable('Institution.Institutions');
        $this->Users = $this->fetchTable('Security.Users');
        $this->UserHistories = $this->fetchTable('User.UserHistories');
        $this->Page->loadElementsFromTable($this->UserHistories);
        $this->Page->disable(['add', 'edit', 'view', 'delete']);
    }

    public function beforeFilter(EventInterface $event)
    {
        $session = $this->request->getSession();
        //$institutionId = $this->getInstitutionID();
        //$institutionName = $session->read('Institution.Institutions.name');
        $institutionId = $this->getQueryString('institution_id');
        $activeInstitution = $this->Institutions->get($institutionId);
        $institutionName = $activeInstitution->name;
        $userId = $this->paramsDecode($this->request->getQuery('queryString'))['security_user_id'];
        $userName = $this->Users->get($userId)->name;
        $userType = $this->paramsDecode($this->request->getQuery('queryString'))['user_type'];

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
        $plugin = $this->getPlugin();

        $userId = isset($options['user_id']) ? $options['user_id'] : 0;
        $userName = isset($options['user_name']) ? $options['user_name'] : '';
        $encodedUserId = $this->paramsEncode(['id' => $userId]);

        if ($plugin == 'Institution') { // for student and staff
            $institutionId = isset($options['institution_id']) ? $options['institution_id'] : 0;
            $paramsPass = $this->request->getAttribute('params')['institutionId'];
            $institutionId = $this->paramsDecode($paramsPass)['id'];

            $institutionName = isset($options['institution_name']) ? $options['institution_name'] : '';
            $userType = isset($options['user_type']) ? $options['user_type'] : '';
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
                'institutionId' => $encodedInstitutionId,
                'action' => $userType . 'User',
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

    private function getInstitutionID()
    {
        $session = $this->request->getSession();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->params['institutionId']) ?
            $this->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}
