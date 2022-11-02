<?php
namespace User\Model\Behavior;

use ArrayObject;
use Exception;

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
        $isNew = $entity->isNew();

        if ($entity instanceof \Institution\Model\Entity\Student) {
            $entity = $this->convertStudentToUser($entity);
        } elseif ($entity instanceof \Institution\Model\Entity\Staff) {
            $entity = $this->convertStaffToUser($entity);
        } elseif (!$entity instanceof \User\Model\Entity\User) {
            return;
        }

        if ($isNew) { //For Add action only
            $moodleApi = new MoodleApi();
            if ($moodleApi->enableUserCreation()) {
                $response = $moodleApi->createUser($entity);
                if ($response->code != 200) {
                    throw new Exception("Network Error");
                }
            }
        }
    }

    private function convertStudentToUser($entity)
    {
        $Users = TableRegistry::get('Security.Users');
        return $Users->find()->where(['id' => $entity->student_id])->first();
    }

    private function convertStaffToUser($entity)
    {
        $Users = TableRegistry::get('Security.Users');
        return $Users->find()->where(['id' => $entity->staff_id])->first();
    }
}
