<?php
namespace Institution\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class InstitutionBusesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->InstitutionBuses);
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

        // set institution_id
        $page->get('institution_id')->setControlType('hidden')->setValue($institutionId);
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['comment', 'institution_id']);

        // Transport Providers
        $transportProviders = $this->InstitutionBuses->InstitutionTransportProviders
            ->getList()
            ->toArray();

        $transportProviderOptions = [null => __('All Transport Providers')] + $transportProviders;
        $page->addFilter('institution_transport_provider_id')
            ->setOptions($transportProviderOptions);
        // end Transport Providers

        // Bus Types
        $busTypes = $this->InstitutionBuses->BusTypes
            ->getList()
            ->toArray();

        $busTypeOptions = [null => __('All Bus Types')] + $busTypes;
        $page->addFilter('bus_type_id')
            ->setOptions($busTypeOptions);
        // end Bus Types

        // Transport Statuses
        $transportStatuses = $this->InstitutionBuses->TransportStatuses
            ->getList()
            ->toArray();

        $transportStatusOptions = [null => __('All Transport Statuses')] + $transportStatuses;
        $page->addFilter('transport_status_id')
            ->setOptions($transportStatusOptions);
        // end Transport Statuses

        // reorder fields
        $page->move('institution_transport_provider_id')->after('plate_number');
        $page->move('bus_type_id')->after('institution_transport_provider_id');
        $page->move('capacity')->after('bus_type_id');
        $page->move('transport_status_id')->after('capacity');
        // end reorder fields
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

        $page->get('institution_transport_provider_id')
            ->setControlType('select');

        $page->get('bus_type_id')
            ->setControlType('select');

        $page->get('transport_status_id')
            ->setControlType('select');

        $transportFeatureOptions = $this->InstitutionBuses->TransportFeatures
            ->getList()
            ->toArray();

        $page->addNew('transport_features')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Transport Features'))
            ->setOptions($transportFeatureOptions, false);

        $this->reorderFields();
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('institution_transport_provider_id')->after('plate_number');
        $page->move('bus_type_id')->after('institution_transport_provider_id');
        $page->move('capacity')->after('bus_type_id');
        $page->move('transport_status_id')->after('capacity');
        $page->move('transport_features')->after('transport_status_id');
        $page->move('comment')->after('transport_features');
    }
}
