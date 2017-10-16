<?php
namespace Transport\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class BusesController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Buses);
    }

	public function beforeFilter(Event $event)
    {
    	parent::beforeFilter($event);
		$page = $this->Page;

        // set Breadcrumb
    	$page->addCrumb('Buses', ['plugin' => $this->plugin, 'controller' => $this->name]);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment']);

        // Transport Providers
        $transportProviders = $this->Buses->TransportProviders
            ->getList()
            ->toArray();

        $transportProviderOptions = [null => __('All Transport Providers')] + $transportProviders;
        $page->addFilter('transport_provider_id')
            ->setOptions($transportProviderOptions);
        // end Transport Providers

        // Bus Types
        $busTypes = $this->Buses->BusTypes
            ->getList()
            ->toArray();

        $busTypeOptions = [null => __('All Bus Types')] + $busTypes;
        $page->addFilter('bus_type_id')
            ->setOptions($busTypeOptions);
        // end Bus Types

        // Transport Statuses
        $transportStatuses = $this->Buses->TransportStatuses
            ->getList()
            ->toArray();

        $transportStatusOptions = [null => __('All Transport Statuses')] + $transportStatuses;
        $page->addFilter('transport_status_id')
            ->setOptions($transportStatusOptions);
        // end Transport Statuses

        // reorder fields
        $page->move('transport_provider_id')->after('plate_number');
        $page->move('bus_type_id')->after('transport_provider_id');
        $page->move('capacity')->after('bus_type_id');
        $page->move('transport_status_id')->after('capacity');
        // end reorder fields

        parent::index();
    }

	public function add()
    {
        $this->addEdit();
        parent::add();
        $this->reorderFields();
    }

    public function edit($id)
    {
        $this->addEdit();
        parent::edit($id);
        $this->reorderFields();
    }

	private function addEdit()
    {
        $page = $this->Page;

        $page->get('transport_provider_id')
            ->setControlType('select');

        $page->get('bus_type_id')
            ->setControlType('select');

        $page->get('transport_status_id')
            ->setControlType('select');

        $transportFeatureOptions = $this->Buses->TransportFeatures
            ->getList()
            ->toArray();

        $page->addNew('transport_features')
            ->setControlType('select')
            ->setAttributes('multiple', true)
            ->setAttributes('placeholder', __('Select Transport Features'))
            ->setOptions($transportFeatureOptions, false);
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('transport_provider_id')->after('plate_number');
        $page->move('bus_type_id')->after('transport_provider_id');
        $page->move('capacity')->after('bus_type_id');
        $page->move('transport_status_id')->after('capacity');
        $page->move('transport_features')->after('transport_status_id');
        $page->move('comment')->after('transport_features');
    }
}
