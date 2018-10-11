<?php
namespace App\Controller;

use App\Controller\PageController;
use Cake\Event\Event;
use App\MoodleApi\MoodleApi;

class MoodleApiController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $moodleApi = new MoodleApi();
        $response = $moodleApi->test_create_user();

        if (count($response->error)) {
            dd($response->error);
        } else {
            dd($response->json);
        }
    }


}