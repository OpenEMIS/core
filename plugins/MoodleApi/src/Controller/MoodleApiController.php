<?php
namespace MoodleApi\Controller;

use App\Controller\AppController as BaseController;

class MoodleApiController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    //Example on how to use the component. Goto <base_url>/MoodleApi
    public function index() 
    {
        $this->loadComponent('MoodleApi.MoodleApi');

        $response = $this->MoodleApi->test_create_user();

        if (count($response->error)) {
            dd($response->error);
        } else {
            dd($response->json);
        }
    }
}
