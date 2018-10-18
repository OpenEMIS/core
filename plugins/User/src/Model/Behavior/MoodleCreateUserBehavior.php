<?php
namespace User\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use App\MoodleApi\MoodleApi;

class MoodleCreateUserBehavior extends Behavior
{

    public function initialize(array $config)
    {

    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) { //For Add action only
            $moodleApi = new MoodleApi();
            $response = $moodleApi->createUser($entity);
            // dd($response);
            // Log::write('debug', "response from directory");
            // Log::write('debug', $response);
        }
    }
}
