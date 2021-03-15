<?php
namespace Institution\Controller;

use Cake\Event\Event;
use App\Controller\PageController;
use Cake\ORM\TableRegistry;

class InstitutionBusesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.InstitutionTransportProviders');
        $this->loadModel('Institution.InstitutionBuses');
        $this->loadModel('Transport.BusTypes');
        $this->loadModel('Transport.TransportStatuses');
        $this->loadModel('Transport.TransportFeatures');

        // to disable actions if institution is not active
        $this->loadComponent('Institution.InstitutionInactive');
    }

	public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

    	parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

		$page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Buses');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Buses'));

        // to filter by institution_id
        $page->setQueryString('institution_id', $institutionId);

        // to rename field label
        $page->get('institution_transport_provider_id')
            ->setLabel('Provider');

        $page->get('transport_status_id')
            ->setLabel('Status');

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['comment', 'institution_id']);

        $institutionId = $page->getQueryString('institution_id');

        // Providers
        $transportProviders = $this->InstitutionTransportProviders
            ->find('optionList', [
                'defaultOption' => false,
                'institution_id' => $institutionId
            ])
            ->toArray();

        $transportProviderOptions = [null => __('All Providers')] + $transportProviders;
        $page->addFilter('institution_transport_provider_id')
            ->setOptions($transportProviderOptions);
        // end Providers

        // Transport Statuses
        $transportStatuses = $this->TransportStatuses
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $transportStatusOptions = [null => __('All Statuses')] + $transportStatuses;
        $page->addFilter('transport_status_id')
            ->setOptions($transportStatusOptions);
        // end Transport Statuses

        // reorder fields
        $Users = TableRegistry::get('labels');
        $result = $Users
        ->find()
        ->where(['module' => 'InstitutionBuses', 'field_name' => 'Capacity'])
        ->toArray();
        if(isset($result[0]['name'])){
            $page->get('capacity')->setSortable(false)->setLabel($result[0]['name']);
            $page->move('capacity')->after('bus_type_id');
        }else{
            $page->move('capacity')->after('bus_type_id');
        }
        $result = $Users
        ->find()
        ->where(['module' => 'InstitutionBuses', 'field_name' => 'Plate Number'])
        ->toArray();
        if(isset($result[0]['name'])){
            $page->get('plate_number')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('plate_number')->first();
        }

        $result = $Users
        ->find()
        ->where(['module' => 'InstitutionBuses', 'field_name' => 'Bus Type'])
        ->toArray();
        if(isset($result[0]['name'])){
            $page->get('bus_type_id')->setSortable(false)->setLabel($result[0]['name']);
        }else{
            $page->move('bus_type_id')->after('institution_transport_provider_id');
        }

        // end reorder fields
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;

        $page->addNew('transport_features')
            ->setLabel('Features')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit();
    }

	private function addEdit()
    {
        $page = $this->Page;

        $institutionId = $page->getQueryString('institution_id');

        $page->get('institution_transport_provider_id')
            ->setControlType('select');

        $page->get('bus_type_id')
            ->setControlType('select');

        $page->get('transport_status_id')
            ->setControlType('select');

        $transportFeatureOptions = $this->TransportFeatures
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $page->addNew('transport_features')
            ->setLabel('Features')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Features'))
            ->setOptions($transportFeatureOptions, false);

        $this->reorderFields();
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('plate_number')->first();
        $page->move('capacity')->after('bus_type_id');
        $page->move('transport_features')->after('transport_status_id');
        $page->move('comment')->after('transport_features');
    }
}
