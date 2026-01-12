<?php

/**
 * MoodleApiComponent - Uses Cake's HTTP to do webservice call to moodle.
 * Moodle does not complies to restful. Only GET and POST is sufficient.
 * For moodle specifc function logic, please create a class under MoodleFunction.
 * SEE MoodleFunction\MoodleCreateUser for example.
 *
 * Use $reponse->error to check error details.
 *
 * PHP version 7.2
 *
 * @category  API
 * @package   MoodleApi
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */

namespace App\MoodleApi;

use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use App\MoodleApi\MoodleFunction\MoodleCreateUser;
use MoodleApi\Model\Table\MoodleApiLogTable;
use App\MoodleApi\MoodleFunction\MoodleCreateCourse; //POCOR-8706

class MoodleApi
{
    private $_token;
    private $_baseURL;
    private $_enableUserCreation; //POCOR-8706
    private $_status; //POCOR-8706
    const WEB_SERVICE_URL = "/webservice/rest/server.php";
    const TOKEN_PARAM = "wstoken";
    const FUNCTION_PARAM = "wsfunction";
    const JSON_MODE_PARAM = "moodlewsrestformat=json";
    const TEACHER_ROLE_ID = 3;
    const STUDENT_ROLE_ID = 5;

    public function __construct()
    {
        $this->_loadConfig();
    }

    /**
     * To call moodle api GET functions.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function get($function = "core_webservice_get_site_info", $params = [])
    {
        $url = $this->getUrl($function);
        if (!empty($params)) {
            $url = $url . '&' . http_build_query($params);
        }
        $http = new Client();


        $response = $http->get($url);
        return $this->_checkError($response);
    }

    /**
     * To call moodle api POST functions.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @param array $params - look at moodle api for the structure.
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function post($function = null, $params = null)
    {
        if (!$function || !$params) {
            Log::write('debug', "MoodleApiComponent @post Exception - function or params are null");
            $errorObject = $this->_createErrorObject();
            $errorObject->error["param_invalid_exception"] = "Please check your data parameters.";
            return $errorObject;
        }

        $url = $this->getUrl($function);
        $http = new Client();

        $response = $http->post($url, $params);

        return $this->_checkError($response);
    }

    /**
     * Creates user on moodle. Return false if fails.
     *
     * @param string $data - params data for user. Check class MoodleCreateUser
     *                       for available fields.
     *
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function createUser($data)
    {
        if ($this->enableUserCreation() && $this->getStatus()) { //POCOR-8706
            $moodleUser = new MoodleCreateUser($data);

            $response = $this->post(MoodleCreateUser::getFunctionParam(), $moodleUser->getData());

            $this->_apiLog(MoodleCreateUser::getFunctionParam(), $moodleUser->getData(), $response, __METHOD__, $data);

            if ($response->isOk()) {
                $data = $response->getJson();
                $data = $data[0];
                $moodleUser->linkMoodletoOpenEmis($data['id'], $data['username']);
                //POCOR-5677 starts
                //$moodleUser->linkMoodletoOpenEmis($data->id, $data->username);
                //POCOR-5677 ends
            }
            return $response;
        } else {
            return null;
        }
    }

    /**
     * Creates a course on Moodle and returns the response if successful.
     *
     * This method sends a request to the Moodle API to create a new course using
     * the provided data. It utilizes the `MoodleCreateCourse` class to structure
     * the request data and handles the API response.
     *
     * @param array $data An associative array containing the parameters for the course creation.
     *                    Refer to the `MoodleCreateCourse` class for the list of available fields.
     *
     * @return \Psr\Http\Message\ResponseInterface|null The response object if the course creation is successful.
     *                                                  Returns `null` if the API request fails.
     *
     * @author Megha Gupta <barkha@madvit.com>
     * @since 2024-12-20
     * @task  POCOR-8706 
     */

    public function createCourse($data)
    {
        if ($this->getStatus()) {
            $moodleCourse = new MoodleCreateCourse($data);
            $moodleData = $moodleCourse->getData();
            $moodleCourseListResponse = $this->get(MoodleCreateCourse::getListFunctionParam());
            $moodleCourseList = [];
            if ($moodleCourseListResponse && $moodleCourseListResponse->isOk()) {
                $moodleCourseList = $moodleCourseListResponse->getJson();
            } else {
                Log::debug("Failed to fetch Moodle course list.", ['scope' => 'moodle']);
            }
            $moodleCourseList = $moodleCourseListResponse->getJson();
            $existingCourseId = MoodleCreateCourse::courseAlreadyExist($moodleData, $moodleCourseList);

            $response = "";
            if ($existingCourseId) {
                $moodleUpdatedData = $moodleCourse->getUpdateData($moodleData, $existingCourseId);
                $response = $this->post(MoodleCreateCourse::getUpdateFunctionParam(), $moodleUpdatedData);
                $this->_apiLog(MoodleCreateCourse::getListFunctionParam(), $moodleUpdatedData, $response, __METHOD__, $data);
            } else {
                $response = $this->post(MoodleCreateCourse::getFunctionParam(), $moodleData);
                $this->_apiLog(MoodleCreateCourse::getFunctionParam(), $moodleData, $response, __METHOD__, $data);
            }
            if ($response->isOk()) {
                if ($existingCourseId) {
                    $this->saveStaffToMoodleCourse($data['subject_staff'] ?? [], $existingCourseId);
                    $this->saveStudentToMoodleCourse($data['subjectStudent'] ?? [], $existingCourseId);
                }
                Log::info('Moodle course creation successful.', [
                    'response' => $response->getJson()
                ]);

                $responseData = $response->getJson();
                $data = $responseData[0] ?? null;
            } else {
                Log::warning('Moodle course creation failed.', [
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $response->getBody()->getContents()
                ]);
            }
            return $response;
        } else {
            return null;
        }
    }

    /**
     * To construct Moodle API URL based on the function name you are calling.
     *
     * @param string $function - moodle api function name.
     *                           Example: core_webservice_get_site_info
     *
     * @return string - the url to do query for API without params
     */
    public function getUrl($function)
    {
        return $this->_baseURL
            . self::WEB_SERVICE_URL
            . "?"
            . $this->_constructBasicParams($function)
            . "&" . self::JSON_MODE_PARAM;
    }

    public function enableUserCreation()
    {
        return isset($this->_enableUserCreation) && $this->_enableUserCreation;
    }
    //POCOR-8706 start
    // It simply return status whether moodle is enabled or not
    public function getStatus()
    {
        return isset($this->_status) && $this->_status;
    }
    //POCOR-8706 end

    private function _apiLog($action, $param, $response, $callback, $callbackData)
    {
        $apiLogTable = TableRegistry::getTableLocator()->get("MoodleApi.MoodleApiLog");
        // Pass an empty array to newEntity() if you don't have initial data to populate
        $apiInstance = $apiLogTable->newEntity([]);

        if ($response->isOk()) {
            $status = MoodleApiLogTable::STATUS_SUCCESS;
        } else {
            $status = MoodleApiLogTable::STATUS_FAILED;
        }

        $apiInstance->action = $action;
        $apiInstance->params = json_encode($param);
        $apiInstance->response = json_encode($response);
        $apiInstance->status = $status;
        $apiInstance->callback = $callback;
        $apiInstance->callback_param = serialize($callbackData);

        $apiLogTable->save($apiInstance);
    }

    private function _loadConfig()
    {
        //POCOR-8386 new changes
        $ConfigItemsTable = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $ConfigItems = $ConfigItemsTable->find()->where(['external_data_source_type' => 'External Data Source - LMS'])->toArray();
        //POCOR-8706 start
        $StatusTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $status = $StatusTable->find()
            ->select(['value'])
            ->where([
                'code' => 'external_source_status',
                'type' => 'External Data Source - LMS',
            ])
            ->first();
        $statusValue = $status->value ?? null;

        $this->_token = null;
        $this->_baseURL = null;
        $this->_enableUserCreation = null;
        $this->_status = $statusValue;
        //POCOR-8706 end
        foreach ($ConfigItems as $configItem) {
            if ($configItem->attribute_field == 'api_token') {
                $this->_token = $configItem->value;
            } elseif ($configItem->attribute_field == 'base_url') {
                $this->_baseURL = $configItem->value;
            } elseif ($configItem->attribute_field == 'enable_user_creation') {
                $this->_enableUserCreation = $configItem->value;
            }
        }
    }

    private function _constructBasicParams($function)
    {
        return self::TOKEN_PARAM . "=" . $this->_token
            . "&" .
            self::FUNCTION_PARAM . "=" . $function;
    }

    private function _checkError(&$response)
    {
        $this->_initResponseError($response);
        if ($response->isOk()) {
            $responseBody = $response->getJson();
            if (isset($responseBody["exception"])) {
                Log::write('debug', "MoodleApi Exception - " . $responseBody["exception"]);
                Log::write('debug', "MoodleApi Exception Message - " . $responseBody["message"]);
                $response->error["api_exception"] = $responseBody;
            }
        } else {
            Log::write('debug', "MoodleApi Exception - response error");
            Log::write('debug', "MoodleApi Exception response - ");
            Log::write('debug', $response);
            $response->error["http_exception"] = $response->code;
        }
        return $response;
    }

    private function _createErrorObject()
    {
        $errorObject = new \stdClass();
        $this->_initResponseError($errorObject);
        return $errorObject;
    }

    private function _initResponseError(&$response)
    {
        if (!isset($response->error)) {
            $response->error = [];
        }

        return $response;
    }

    /**
     * To be deleted. This function is to test that create users works.
     * @return object - the response data. Use $response->json to get the json data.
     */
    public function test_create_user()
    {
        $users = array();
        $users["username"] = "ervinz" . time();
        $users["password"] = "Password12#$";
        // $users["createpassword"]= 0;
        $users["firstname"] = "Ervin";
        $users["lastname"] = "Kwan";
        $users["email"] = "ekwanzs" . time() . "@kordit.com";
        // $users["auth"]= "manual";
        // $users["idnumber"]= "";
        // $users["lang"]= "en";
        // $users["calendartype"]= "gregorian";
        // $users["theme"]= "";
        // $users["timezone"]= "Asia/Singapore";
        // $users["mailformat"]= "";
        // $users["description"]= "";
        // $users["city"]= "";
        // $users["country"]= "";
        // $users["firstnamephonetic"]= "";
        // $users["lastnamephonetic"]= "";
        // $users["middlename"]= "";
        // $users["alternatename"]= "";

        return $this->createUser($users);
    }

    /**
     * Assigns a list of staff members to a specified Moodle course.
     *
     * This function iterates through the provided staff list, retrieves each staff member's Moodle user ID 
     * based on their username, and assigns them a predefined role in the given Moodle course.
     * 
     * - Retrieves user information from the local database.
     * - Calls the Moodle API to fetch the corresponding user details.
     * - If a match is found, assigns the staff member to the course.
     * - Skips any staff members not found in the database or Moodle.
     * 
     * @param array $staffList List of staff members to be assigned.
     * @param int $courseId The Moodle course ID.
     * 
     * @return void
     */

    public function saveStaffToMoodleCourse(array $staffList = [], int $courseId): void
    {
        $Users = TableRegistry::getTableLocator()->get('Security.Users');

        $moodleEnrolledUsersResponse = $this->get('core_enrol_get_enrolled_users', ['courseid' => $courseId]);

        if (!$moodleEnrolledUsersResponse || !$moodleEnrolledUsersResponse->isOk()) {
            Log::warning('Failed to fetch currently enrolled users from Moodle.', ['courseId' => $courseId]);
            return;
        }

        $moodleEnrolledUsers = $moodleEnrolledUsersResponse->getJson();
        $unenrolments = [];


        foreach ($moodleEnrolledUsers as $enrolledUser) {
            foreach ($enrolledUser['roles'] as $role) {
                if ($role['roleid'] == self::TEACHER_ROLE_ID) {
                    $unenrolments[] = [
                        'userid' => $enrolledUser['id'],
                        'courseid' => $courseId,
                        'roleid' => self::TEACHER_ROLE_ID,
                    ];
                    break;
                }
            }
        }

        if (!empty($unenrolments)) {
            $this->post('enrol_manual_unenrol_users', ['enrolments' => $unenrolments]);
            Log::info('Unenrolled existing staff from Moodle course.', ['unenrolled' => $unenrolments]);
        }

        if (empty($staffList)) {
            Log::info('Staff list is empty. No new staff assigned.', ['courseId' => $courseId]);
            return;
        }


        foreach ($staffList as $staff) {
            $userEntity = $Users->find()->where(['id' => $staff->staff_id])->first();
            if (!$userEntity) {
                continue;
            }

            $param = ['criteria' => [['key' => 'username', 'value' => $userEntity->username]]];
            $moodleUserListResponse = $this->get(MoodleCreateUser::getListFunctionParam(), $param);

            if (!$moodleUserListResponse || !$moodleUserListResponse->isOk()) {
                continue;
            }

            $existingStaff = $moodleUserListResponse->getJson();
            if (empty($existingStaff['users'][0]['id'])) {
                continue;
            }

            $staffId = $existingStaff['users'][0]['id'];
            $this->createCourseUser($staffId, self::TEACHER_ROLE_ID, $courseId);
        }
    }


    /**
     * Assigns a user to a Moodle course with a specific role.
     *
     * This method sends a request to the Moodle API to assign an existing user to an existing course using 
     * the provided user ID, course ID, and role ID.
     *
     * - Constructs the required API request parameters.
     * - Sends the enrollment request to Moodle.
     * - Logs success and failure responses.
     *
     * @param int $userId The Moodle user ID.
     * @param int $roleid The role ID to be assigned.
     * @param int $courseId The Moodle course ID.
     * 
     * @return void
     */
    public function createCourseUser($userId, $roleid, $courseId)
    {
        $enrollments = array();
        $enrollments["roleid"] = $roleid;
        $enrollments["courseid"] = $courseId;
        $enrollments["userid"] = $userId;
        $enrollments = [0 => $enrollments];
        $enrolmentData = ["enrolments" => $enrollments];

        $response = $this->post(MoodleCreateCourse::getAssignroleFunctionParam(), $enrolmentData);
        $this->_apiLog(MoodleCreateCourse::getAssignroleFunctionParam(), $enrolmentData, $response, __METHOD__, $enrolmentData);

        if ($response->isOk()) {
            Log::info('Moodle role assigned successfully.', [
                'response' => $response->getJson()
            ]);
        } else {
            Log::warning('Moodle course role assignment failed.', [
                'response_status' => $response->getStatusCode(),
                'response_body' => $response->getBody()->getContents()
            ]);
        }
    }

    /**
     * Assigns a list of students members to a specified Moodle course.
     *
     * This function iterates through the provided student list, retrieves each student member's Moodle user ID 
     * based on their username, and assigns them a predefined role in the given Moodle course.
     * 
     * - Retrieves user information from the local database.
     * - Calls the Moodle API to fetch the corresponding user details.
     * - If a match is found, assigns the student  member to the course.
     * - Skips any student members not found in the database or Moodle.
     * 
     * @param array $staffList List of studrnt members to be assigned.
     * @param int $courseId The Moodle course ID.
     * 
     * @return void
     */

    public function saveStudentToMoodleCourse(array $studentList = [], int $courseId): void
    {
        $Users = TableRegistry::getTableLocator()->get('Security.Users');

        $moodleEnrolledUsersResponse = $this->get('core_enrol_get_enrolled_users', ['courseid' => $courseId]);

        if (!$moodleEnrolledUsersResponse || !$moodleEnrolledUsersResponse->isOk()) {
            Log::warning('Failed to fetch currently enrolled users from Moodle.', ['courseId' => $courseId]);
            return;
        }

        $moodleEnrolledUsers = $moodleEnrolledUsersResponse->getJson();
        $unenrolments = [];


        foreach ($moodleEnrolledUsers as $enrolledUser) {
            foreach ($enrolledUser['roles'] as $role) {
                if ($role['roleid'] == self::STUDENT_ROLE_ID) {
                    $unenrolments[] = [
                        'userid' => $enrolledUser['id'],
                        'courseid' => $courseId,
                        'roleid' => self::STUDENT_ROLE_ID,
                    ];
                    break;
                }
            }
        }


        if (!empty($unenrolments)) {
            $this->post('enrol_manual_unenrol_users', ['enrolments' => $unenrolments]);
            Log::info('Unenrolled existing students from Moodle course.', ['unenrolled' => $unenrolments]);
        }


        if (empty($studentList)) {
            Log::info('Student list is empty. No new enrolments added.', ['courseId' => $courseId]);
            return;
        }

        $studentList = array_map(function ($encoded) {
            return json_decode(base64_decode($encoded), true);
        }, $studentList);

        foreach ($studentList as $student) {
            $userEntity = $Users->find()->where(['id' => $student['student_id']])->first();
            if (!$userEntity) {
                continue;
            }

            $param = ['criteria' => [['key' => 'username', 'value' => $userEntity->username]]];
            $moodleUserListResponse = $this->get(MoodleCreateUser::getListFunctionParam(), $param);

            if (!$moodleUserListResponse || !$moodleUserListResponse->isOk()) {
                continue;
            }

            $existingStudent = $moodleUserListResponse->getJson();
            if (empty($existingStudent['users'][0]['id'])) {
                continue;
            }

            $studentId = $existingStudent['users'][0]['id'];
            $this->createCourseUser($studentId, self::STUDENT_ROLE_ID, $courseId);
        }
    }
    //POCOR-9068 start
    /**
     * Checks if a single user exists in Moodle by username.
     *
     * @param string $username The username to check in Moodle.
     * @return bool True if the user exists, false otherwise.
     */
    public function userExists(string $username): bool
    {
        if (empty($username)) {
            return false;
        }

        $criteria = [
            'criteria' => [
                [
                    'key' => 'username',
                    'value' => $username
                ]
            ]
        ];

        $response = $this->get('core_user_get_users', $criteria);

        if ($response && $response->isOk()) {
            $data = $response->getJson();
            if (!empty($data['users']) && !empty($data['users'][0]['id'])) {
                return true;
            }
        }

        return false;
    }
    //POCOR-9068 end
}
