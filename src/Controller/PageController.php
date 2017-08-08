<?php
namespace App\Controller;

use Page\Controller\PageController as BaseController;

class PageController extends BaseController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->config('sequence', 'order');
        $this->Page->config('is_visible', 'visible');
    }
}
