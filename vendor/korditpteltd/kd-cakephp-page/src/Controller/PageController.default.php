<?php
namespace App\Controller;

use Cake\Event\Event;
use Page\Controller\PageController as BaseController;

class PageController extends BaseController
{
    public $helpers = ['Page.Page'];

    public function initialize()
    {
        parent::initialize();

        $labels = [
            'openemis_no' => 'OpenEMIS ID',
            'modified' => 'Modified On',
            'modified_user_id' => 'Modified By',
            'created' => 'Created On',
            'created_user_id' => 'Created By'
        ];

        $this->Page->config('sequence', 'order');
        $this->Page->config('is_visible', 'visible');
        $this->Page->config('labels', $labels);

        $this->loadComponent('Page.RenderLink');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $page = $this->Page;
        $request = $this->request;
        $action = $request->action;
        $ext = $this->request->params['_ext'];

        if ($ext != 'json') {
            if ($request->is(['put', 'post'])) {
                $page->showElements(true);
            }
            $this->set('menuItemSelected', [$this->name]);

            if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
                $viewFile = 'Page.Page/' . $action;
                $this->viewBuilder()->template($viewFile);
            }
        }
    }
}
