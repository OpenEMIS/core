<?php
namespace User\Model\Behavior;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use App\MoodleApi\MoodleApi;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class MoodleCreateUserBehavior extends Behavior
{

    public function initialize(array $config)
    {

    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity instanceof \Institution\Model\Entity\Student) {
            $entity = $this->convertStudentToUser($entity);
        }

        if ($entity->isNew()) { //For Add action only
            $moodleApi = new MoodleApi();
            if ($moodleApi->enableUserCreation()) {
                $response = $moodleApi->createUser($entity);
            }
        }
    }

    private function convertStudentToUser($entity)
    {
        $Users = TableRegistry::get('Security.Users');
        return $Users->find()->where(['id' => $entity->student_id])->first();
    }
}
