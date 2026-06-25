<?php

namespace User\Model\Behavior;

use ArrayObject;
use Exception;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use App\MoodleApi\MoodleApi;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router; //POCOR-9277

class MoodleCreateUserBehavior extends Behavior
{

    public function initialize(array $config): void {}

    // change in POCOR-8381
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $isNew = $entity->isNew();
        //POCOR-9277 -- Start
        $model = $this->_table;
        // Get the current request
        $request = Router::getRequest();

        if ($request) {
            $params = $request->getAttribute('params'); // all route params
            $pass = $params['pass'] ?? [];

            if (
                ($params['action'] ?? null) === 'BulkStudentEnrolment' &&
                ($params['controller'] ?? null) === 'Institutions' &&
                ($params['plugin'] ?? null) === 'Institution' &&
                isset($pass[0]) && $pass[0] === 'reconfirm'
            ) {
                return;
            }
        }
        //POCOR-9277 -- End
        //POCOR-9068
        //check if user already exist
        $existingUser = $this->findExistingUser($entity);
        if ($existingUser) {
            return;
        }

        if ($entity instanceof \Institution\Model\Entity\Student) {
            $entity = $this->convertStudentToUser($entity);
        } elseif ($entity instanceof \Institution\Model\Entity\Staff) {
            $entity = $this->convertStaffToUser($entity);
        } elseif ($entity instanceof \Institution\Model\Entity\InstitutionSubject) { // POCOR-8706 | for saving moodle subjects
            $response = $this->addMoodleSubject($entity);
            return;
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
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        return $Users->find()->where(['id' => $entity->student_id])->first();
    }

    private function convertStaffToUser($entity)
    {
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        return $Users->find()->where(['id' => $entity->staff_id])->first();
    }

    /**
     * Stores the created subject as moodle course 
     * 
     * This function attaches additional field names to the provided entity using the 
     * `attachFieldNames` method and then calls the Moodle API to create a new course 
     * in the Moodle system. It also handles and logs any errors encountered during the process.
     * 
     * @param \Cake\ORM\Entity $entity The entity containing the data needed to create the Moodle course.
     * @return bool Returns `false` if the API call fails or if a network error occurs, otherwise no explicit return.
     * 
     * @throws \RuntimeException If there is an unhandled exception during the process.
     * 
     * @author [Megha Gupta]
     * @since 2024-12-20
     * @task POCOR-8706
     */

    private function addMoodleSubject($entity)
    {
        $response = null;
        try {

            $subjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $entity = $subjectsTable->attachFieldNames($entity);

            $moodleApi = new MoodleApi();
            $response = $moodleApi->createCourse($entity);

            if (!$response || $response->getStatusCode() !== 200) {
                Log::debug('Network Error in Moodle');
            }
        } catch (\Exception $exception) {
            Log::error('Error adding Moodle subject: ' . $exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
        return $response;
    }
    //POCOR-9068 start
    /**
     * Checks if a user already exists in Moodle based on the provided entity.
     *
     * This method uses the MoodleApi to determine if a user with the given
     * 'openemis' identifier exists. If user creation is not enabled in the
     * MoodleApi, the method returns true immediately. If the 'openemis'
     * property is set on the entity, it attempts to check for the user's
     * existence and logs any exceptions encountered during the process.
     *
     * @param object $entity The entity containing user information, expected to have an 'openemis' property.
     * @return bool Returns true if the user exists or user creation is disabled, false otherwise.
     */
    private function findExistingUser($entity)
    {
        $moodleApi = new MoodleApi();
        // Only check if user creation is enabled
        if (!$moodleApi->enableUserCreation()) {
            return true;
        }

        if (isset($entity->openemis)) {
            try {
                $existingUser = $moodleApi->userExists($entity->openemis);
                if ($existingUser && !empty($existingUser)) {
                    return true;
                }
            } catch (Exception $e) {
                Log::error('Error checking existing user by username: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);

            }

        }

        return false;
    }
    //POCOR-9068 end
}
