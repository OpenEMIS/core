<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Controller\PageController;

class InstitutionTransportProvidersController extends PageController
{
    public function initialize()
    {
        parent::initialize();

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
        $page->addCrumb('Providers');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Providers'));

        // to filter by institution_id
        $page->setQueryString('institution_id', $institutionId);

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
    }

    public function view($id)
    {
        parent::view($id);

        $page = $this->Page;
        $entity = $page->getData();

        $buses = $this->getBuses($entity);
        $page->addNew('buses')
            ->setControlType('table')
            ->setAttributes('column', [
                ['label' => __('Plate Number'), 'key' => 'plate_number'],
                ['label' => __('Capacity'), 'key' => 'capacity'],
                ['label' => __('Status'), 'key' => 'status']
            ])
            ->setAttributes('row', $buses);

        $page->move('buses')->after('comment');
    }

    private function getBuses(Entity $entity)
    {
        $rows = [];

        if ($entity->has('institution_buses')) {
            foreach ($entity->institution_buses as $obj) {
                $rows[] = [
                    'plate_number' => $obj->plate_number,
                    'capacity' => $obj->capacity,
                    'status' => $obj->transport_status->name
                ];
            }
        }

        return $rows;
    }
}
