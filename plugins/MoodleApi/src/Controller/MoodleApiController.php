<?php
namespace MoodleApi\Controller;

use App\Controller\AppController as BaseController;

class MoodleApiController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function index() 
    {
        echo "hello";die;
    }
}
