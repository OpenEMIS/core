<?php
namespace Scholarship\Controller;

use ArrayObject;
use Cake\Event\EventInterface;
use App\Controller\AppController;
// POCOR-9435 upgraded to cakephp4
class UsersDirectoryController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->UsersDirectory = $this->fetchTable('Scholarship.UsersDirectory');
        $this->loadComponent('User.User');
        $this->loadComponent('Page.Page');
        // $this->Page->loadElementsFromTable($this->UsersDirectory);
    }

    public function implementedEvents(): array
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.getEntityRowActions'] = 'getEntityRowActions';

        return $event;
    }

    // Added to satisfy event registration; row actions are handled in the table layer.
    public function getEntityRowActions(EventInterface $event, $entity, ArrayObject $rowActions)
    {
        return $rowActions;
    }

    public function beforeFilter(EventInterface $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        // $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        // $page->addCrumb('Users Directory');

        // $page->setHeader(__('Scholarships') . ' - ' . __('Users Directory'));

        // $page->disable(['add', 'edit', 'delete']);
    }

    // public function index()
    // {
    //     $page = $this->Page;
    //     parent::index();

    //     $page->addToolbar('Back', [
    //         'type' => 'element',
    //         'element' => 'Page.button',
    //         'data' => [
    //             'title' => __('Back'),
    //             'url' => [
    //                 'plugin' => 'Scholarship',
    //                 'controller' => 'Scholarships',
    //                 'action' => 'Applications',
    //                 'index'
    //             ],
    //             'iconClass' => 'fa kd-back',
    //             'linkOptions' => ['title' => __('Back')]
    //         ],
    //         'options' => []
    //     ]);

    //     $page->addNew('name')->setDisplayFrom('name');
    //     $page->move('name')->after('openemis_no');
    //     $page->move('date_of_birth')->after('name');
    // }

    // public function view($id)
    // {
    //     $page = $this->Page;
    //     parent::view($id);

    //     $applicantId = $page->decode($id)['id'];
    //     $queryString = $this->paramsEncode(['applicant_id' => $applicantId]); // v4 Encode

    //     $page->addToolbar('back', []); // to fix the order of the buttons

    //     if ($this->AccessControl->check(['Scholarships', 'Applications', 'add'])) {
    //         $page->addToolbar('Apply', [
    //             'type' => 'element',
    //             'element' => 'Page.button',
    //             'data' => [
    //                 'title' => __('Apply'),
    //                 'url' => [
    //                     'plugin' => 'Scholarship',
    //                     'controller' => 'Scholarships',
    //                     'action' => 'Applications',
    //                     'add',
    //                     'queryString' => $queryString
    //                 ],
    //                 'iconClass' => 'fa kd-add',
    //                 'linkOptions' => ['title' => __('Apply')]
    //             ],
    //             'options' => []
    //         ]);
    //     }
    // }

    // public function getEntityRowActions(Event $event, $entity, ArrayObject $rowActions)
    // {
    //     $applicantId = $entity->id;
    //     $queryString = $this->paramsEncode(['applicant_id' => $applicantId]);

    //     $rowActionsArray = $rowActions->getArrayCopy();
    //     $rowActionsArray['apply'] = [
    //         'url' => [
    //             'plugin' => 'Scholarship',
    //             'controller' => 'Scholarships',
    //             'action' => 'Applications',
    //             'add',
    //             'queryString' => $queryString
    //         ],
    //         'icon' => 'fa kd-add',
    //         'title' => __('Apply')
    //     ];

    //     $rowActions->exchangeArray($rowActionsArray);
    // }
    public function UsersDirectory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.UsersDirectory']);
    }

    // Explicit index hook to ensure ControllerAction sets up the model (avoids null model errors on /UsersDirectory/index).
    public function index()
    {
        $this->ControllerAction->process(['alias' => 'UsersDirectory', 'className' => 'Scholarship.UsersDirectory']);
    }
}
