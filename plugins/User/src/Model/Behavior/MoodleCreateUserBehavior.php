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

    public function initialize(array $config): void
    {

    }

    // change in POCOR-8381
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

        if ($isNew) { // For Add action only
            $moodleApi = new MoodleApi();
            if ($moodleApi->enableUserCreation()) {
                try { // POCOR-8532
                    $response = $moodleApi->createUser($entity);
                } catch (\Exception $exception) {

                }
                if (!$response || !$response->getStatusCode() != 200) {  // Use getStatusCode() instead of accessing $code directly
//                    throw new Exception("Network Error"); // POCOR-8532
                    Log::debug('Network Error in Moodle'); // POCOR-8532
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
