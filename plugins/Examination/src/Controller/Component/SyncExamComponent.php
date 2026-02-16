<?php

declare(strict_types=1);

namespace Examination\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use Cake\Utility\Security;
use Cake\ORM\TableRegistry;
use Exception;

/**
 * SyncExamComponent
 * Ticket: POCOR-7510, POCOR-7509
 * This component handles synchronization of examination data with the OpenEMIS Exam Project
 * through its API endpoints. It manages authentication, fetching exam results, and updating
 * local records.
 */
class SyncExamComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * Components used by this component
     * 
     * @var array
     */
    // Components are defined in the parent class as protected $components = []
    // We set them in initialize() method instead to avoid type declaration conflicts

    /**
     * API endpoint base paths
     */
    private const API_ENDPOINTS = [
        'login' => '/api/v2/login',
        'result' => '/api/v2/results',
        'register' => '/api/v2/examination/registration',
    ];

    /**
     * Authentication token for API requests
     * 
     * @var string
     */
    private $token = "";

    /**
     * Initialize component
     * 
     * Sets up the component configurations and dependencies
     * 
     * @param array $config Configuration settings
     * @return void
     */
    public function initialize(array $config): void
    {
        // Set components to avoid redeclaring the property (which causes type conflicts in CakePHP 5)
        $this->components = ['CurlRequest', 'ControllerAction.Alert'];
        
        // Manually populate _componentMap since we set components after constructor
        // This is needed for __get() to work properly in CakePHP 5
        if ($this->components) {
            $this->_componentMap = $this->_registry->normalizeArray($this->components);
        }
    }

    /**
     * Connect to OpenEMIS Exam Project API and get authentication token
     * 
     * Establishes connection with the exam API using provided credentials and obtains
     * an authentication token for subsequent requests.
     * 
     * @param string $url Base URL for the API
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @return array Response with status, token and message
     */
    private function getConnectionResponse(string $url, string $username, string $password): array
    {
        // Prepare authentication data
        $postFields = [
            'username' => $username,
            'password' => $password
        ];

        $headers = [
            "Accept: application/json, text/plain, */*",
            "Content-Type: application/json",
        ];

        $loginUrl = $url . self::API_ENDPOINTS['login'];

        // Log connection attempt details
        Log::write('debug', '=================== BEGIN AUTH REQUEST ===================');
        Log::write('debug', 'Sending request to: ' . $loginUrl);
        Log::write('debug', 'Request Headers: ' . json_encode($headers));
        Log::write('debug', 'Post Fields: ' . json_encode($postFields));

        // Make the authentication request
        $response = $this->CurlRequest->makeCurlRequests($loginUrl, 'POST', $headers, $postFields);

        // Initialize responseData variable
        $responseData = [];

        // Process response
        if ($response['data']) {
            $responseData = json_decode($response['data'], true);
            Log::write('debug', 'Response Data: ' . json_encode($responseData));
        }

        // Check if authentication was successful
        if ($response['statusCode'] == 200 && isset($responseData['data']['token'])) {
            Log::write('debug', 'Connection successful. Token received.');
            Log::write('debug', '=================== END AUTH REQUEST ===================');
            return [
                'status' => 1,
                'token' => $responseData['data']['token'],
                'message' => $responseData['message'] ?? 'Connected successfully',
            ];
        }

        // Log failure details
        Log::write('debug', 'Connection failed. Status code: ' . $response['statusCode'] . ', Message: ' . ($responseData['message'] ?? 'No message'));
        Log::write('debug', '=================== END AUTH REQUEST ===================');

        return [
            'status' => 0,
            'token' => null,
            'message' => $responseData['message'] ?? 'Error connecting to the server',
        ];
    }

    /**
     * Main function to fetch and process exam results
     * 
     * Retrieves examination results from the Exam API based on provided academic period and
     * examination codes. Sets up the environment, validates configuration, and initiates 
     * the result retrieval process.
     * 
     * @param array $params Parameters containing academic_period_code and examination_code
     * @return array Status array with success/failure information
     */
    public function getResultFromExam(array $params): array
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '900');
        Log::write('debug', '=================== BEGIN RESULT SYNC ===================');
        Log::write('debug', 'Starting exam result sync with params: ' . json_encode($params));

        // Get OpenEMIS Exam configuration
        $config = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')
            ->getOpenemisExamConfiguration();

        if (!empty($config)) {
            Log::write('debug', 'Configuration found, attempting connection');

            // Attempt to connect to the API
            $response = $this->getConnectionResponse($config['url'], $config['username'], $config['password']);

            if ($response['status']) {
                Log::write('debug', 'Connection successful, proceeding to fetch results');

                // Check if required parameters are provided
                if (!empty($params['academic_period_code']) && !empty($params['examination_code'])) {
                    $this->token = $response['token'];
                    $resultStatus = $this->getExamResult($params, $config);
                    Log::write('debug', '=================== END RESULT SYNC ===================');
                    return $resultStatus;
                } else {
                    $errorMsg = 'Invalid parameters: academic period code or examination code is missing.';
                    Log::write('error', $errorMsg);
                    $this->Alert->error(__($errorMsg), ['type' => 'string', 'reset' => true]);
                    Log::write('debug', '=================== END RESULT SYNC ===================');
                    return [
                        'success' => false,
                        'message' => $errorMsg
                    ];
                }
            } else {
                $errorMsg = 'Connection failed: ' . $response['message'];
                Log::write('error', $errorMsg);
                $this->Alert->error(__('Connection failed: {0}', h($response['message'])), ['type' => 'string', 'reset' => true]);
                Log::write('debug', '=================== END RESULT SYNC ===================');
                return [
                    'success' => false,
                    'message' => $errorMsg
                ];
            }
        } else {
            $errorMsg = 'OpenEMIS Exam Configuration not found.';
            Log::write('error', $errorMsg);
            $this->Alert->error(__($errorMsg), ['type' => 'string', 'reset' => true]);
            Log::write('debug', '=================== END RESULT SYNC ===================');
            return [
                'success' => false,
                'message' => $errorMsg
            ];
        }
    }

    /**
     * Fetch exam results from the API
     * 
     * Makes API request to fetch examination results based on the provided parameters.
     * Creates a temporary file with result data and initiates a background shell task
     * for processing the data asynchronously.
     * 
     * @param array $params Parameters for the API request
     * @param array $config Configuration settings
     * @return array Status array with success/failure information  
     */
    private function getExamResult(array $params, array $config): array
    {
        Log::write('debug', '=================== BEGIN FETCH RESULTS ===================');

        // Prepare request headers with authentication token
        $headers = [
            "Accept: application/json, text/plain, */*",
            "Authorization: Bearer " . $this->token,
            "Content-Type: application/json",
        ];

        Log::write('debug', 'Using params for result fetch: ' . json_encode($params));

        // Build API request URL
        $queryString = http_build_query($params);
        $resultUrl = $config['url'] . self::API_ENDPOINTS['result'] . "?" . $queryString;
        Log::write('debug', 'Sending results request to: ' . $resultUrl);
        Log::write('debug', 'Request headers: ' . json_encode($headers));

        $responseData = [];

        // Make the API request
        $response = $this->CurlRequest->makeCurlRequests($resultUrl, 'GET', $headers);

        // Process response
        if ($response['data']) {
            $responseData = json_decode($response['data'], true);
            Log::write('debug', 'Response status code: ' . $response['statusCode']);
            Log::write('debug', 'Response data received: ' . (isset($responseData['data']) ? 'Yes' : 'No'));
            Log::write('debug', 'Full API response: ' . json_encode($responseData));
        }

        // Check if results were successfully fetched
        if ($response['statusCode'] == 200 && isset($responseData['data'])) {
            Log::write('debug', 'Results fetched successfully. Creating temporary file and launching sync shell.');

            // Create temporary file to store results data with unique timestamp
            $timestamp = time();
            $tempFile = TMP . 'exam_data_' . $timestamp . '.json';
            file_put_contents($tempFile, json_encode($responseData));
            Log::write('debug', 'Temporary file created: ' . $tempFile);

            // Prepare shell command to process results in background
            $jsonParams = json_encode($params);
            $cmd = ROOT . DS . 'bin' . DS . 'cake SyncExamResult ' . escapeshellarg($tempFile) . ' ' . escapeshellarg($jsonParams);
            $logs = ROOT . DS . 'logs' . DS . 'SyncExamResult_' . $timestamp . '.log';

            // Ensure the shell runs in background and redirects output properly
            $shellCmd = sprintf('%s > %s 2>&1 & echo $!', $cmd, $logs);

            // Log shell command details
            Log::write('debug', 'About to execute shell command: ' . $shellCmd);

            try {
                // Execute shell command and capture PID
                $pid = shell_exec($shellCmd);
                $pid = trim($pid);

                if (!empty($pid) && is_numeric($pid)) {
                    Log::write('debug', 'Shell command executing with PID: ' . $pid);
                    // Success message to browser
                    $this->Alert->info(__('Sync process started in background (Process ID: {0}). You can view progress in the logs.', $pid), ['type' => 'string', 'reset' => true]);
                    Log::write('debug', '=================== END FETCH RESULTS ===================');
                    return [
                        'success' => true,
                        'message' => 'Sync process started in background (Process ID: ' . $pid . '). You can view progress in the logs.',
                        'process_id' => $pid
                    ];
                } else {
                    Log::write('error', 'Failed to get valid PID: ' . $pid);
                    $this->Alert->error(__('Sync process may have failed to start. Please check the logs.'), ['type' => 'string', 'reset' => true]);
                    Log::write('debug', '=================== END FETCH RESULTS ===================');
                    return [
                        'success' => false,
                        'message' => 'Sync process may have failed to start. Please check the logs.'
                    ];
                }
            } catch (\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception syncing exam result: ' . $ex->getMessage());
                Log::write('error', 'Exception trace: ' . $ex->getTraceAsString());
                $this->Alert->error(__('Error starting sync process: {0}', $ex->getMessage()), ['type' => 'string', 'reset' => true]);
                Log::write('debug', '=================== END FETCH RESULTS ===================');
                return [
                    'success' => false,
                    'message' => 'Error starting sync process: ' . $ex->getMessage()
                ];
            }
        } else {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            Log::write('error', 'Sync Request Failed: ' . $errorMessage);
            Log::write('error', 'Full response: ' . json_encode($response));
            $this->Alert->error(__('Unable to fetch data: {0}', h($errorMessage)), ['type' => 'string', 'reset' => true]);
            Log::write('debug', '=================== END FETCH RESULTS ===================');
            return [
                'success' => false,
                'message' => 'Unable to fetch data: ' . $errorMessage
            ];
        }
    }

    /**
     * Decrypt sensitive data using provided secret key
     * 
     * Uses AES-256-CBC encryption to decrypt sensitive data strings using the
     * provided secret key. The first 16 bytes of the key are used as the IV.
     * 
     * @param string $encryptedString The encrypted data to decrypt
     * @param string $secretKey The secret key for decryption
     * @return string|null Decrypted data or null on failure
     */
    private function decrypt(string $encryptedString, string $secretKey): ?string
    {
        Log::write('debug', 'Decrypting sensitive data');
        $iv = substr($secretKey, 0, 16);
        $data = base64_decode($encryptedString);
        return openssl_decrypt($data, 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Update local sync status based on API response
     * 
     * Updates the sync_status and last_synced fields in the local database
     * for each student based on the response received from the API.
     * 
     * @param array $response API response data
     * @param array $params Request parameters
     * @return void
     */
    private function updateSyncStatus(array $response, array $params): void
    {
        Log::write('debug', '=================== BEGIN STATUS UPDATE ===================');
        Log::write('debug', 'Updating sync status for students');

        $studentsTable = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsStudents');
        [$studentData] = $this->getRegisterStudentData($params);

        Log::write('debug', 'Found ' . count($studentData) . ' students to update');

        $updateCount = 0;
        $failedUpdates = 0;

        foreach ($studentData as $student) {
            $found = false;

            foreach ($response as $syncResult) {
                if ($syncResult['openemis_no'] === $student['openemis_no']) {
                    $found = true;
                    $syncStatus = $syncResult['sync_status'] ? 1 : -1;

                    Log::write('debug', 'Updating student: ' . $student['openemis_no'] . ' with sync status: ' . $syncStatus);

                    try {
                        $result = $studentsTable->updateAll(
                            [
                                'sync_status' => $syncStatus,
                                'last_synced' => date('Y-m-d H:i:s'),
                            ],
                            ['student_id' => $student['student_id']]
                        );

                        if ($result) {
                            $updateCount++;
                        } else {
                            $failedUpdates++;
                            Log::write('error', 'Failed to update sync status for student: ' . $student['openemis_no']);
                        }
                    } catch (\Exception $ex) {
                        $failedUpdates++;
                        Log::write('error', 'Exception updating sync status for student ' . $student['openemis_no'] . ': ' . $ex->getMessage());
                    }

                    break;
                }
            }

            if (!$found) {
                Log::write('warning', 'No matching response data found for student: ' . $student['openemis_no']);
            }
        }

        Log::write('debug', 'Updated sync status for ' . $updateCount . ' students successfully');

        if ($failedUpdates > 0) {
            Log::write('warning', 'Failed to update ' . $failedUpdates . ' students');
        }

        Log::write('debug', '=================== END STATUS UPDATE ===================');
    }

    /**
     * Register students in exams through the API
     * 
     * Handles the process of registering students for examinations by collecting
     * student data and sending it to the Exam API. Updates local sync status
     * based on the registration response.
     * 
     * @param array $params Parameters for registration
     * @return void
     */
    public function registerStudentsInExams(array $params): void
    {
        Log::write('debug', '=================== BEGIN STUDENT REGISTRATION ===================');
        Log::write('debug', 'Starting student registration with params: ' . json_encode($params));

        // Increase the memory limit and execution time for large operations
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '900'); // Increased to 15 minutes for larger operations

        // Get Openemis exam configuration
        $config = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')
            ->getOpenemisExamConfiguration();

        if (empty($config)) {
            Log::write('error', 'OpenEMIS Exam Configuration not found.');
            $this->Alert->error(__('OpenEMIS Exam Configuration not found.'), ['type' => 'string', 'reset' => true]);
            Log::write('debug', '=================== END STUDENT REGISTRATION ===================');
            return;
        }

        // Get the connection response
        $response = $this->getConnectionResponse($config['url'], $config['username'], $config['password']);

        if (!$response['status']) {
            Log::write('error', 'Connection failed: ' . $response['message']);
            $this->Alert->error(__('Connection failed: {0}', h($response['message'])), ['type' => 'string', 'reset' => true]);
            Log::write('debug', '=================== END STUDENT REGISTRATION ===================');
            return;
        }

        Log::write('debug', 'Connection successful, proceeding to register students');

        // Get student data to register
        [$studentData, $rawData] = $this->getRegisterStudentData($params);

        if (empty($studentData)) {
            Log::write('error', 'No student data available for registration');
            $this->Alert->error(__('No student data available for registration'), ['type' => 'string', 'reset' => true]);
            Log::write('debug', '=================== END STUDENT REGISTRATION ===================');
            return;
        }

        Log::write('debug', 'Found ' . count($studentData) . ' students to register');

        // Set up the registration URL
        $url = $config['url'] . self::API_ENDPOINTS['register'];

        // Headers for the HTTP request - make sure to format exactly as Postman does
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $response['token'],
        ];

        Log::write('debug', 'Registration URL: ' . $url);
        Log::write('debug', 'Request headers: ' . json_encode($headers));
        Log::write('debug', 'Student data sample: ' . json_encode(array_slice($studentData, 0, 1)));

        // Make the API request
        Log::write('debug', 'Sending PUT request to register students...');
        $httpResponse = $this->CurlRequest->makeCurlRequests($url, 'PUT', $headers, $studentData);

        Log::write('debug', 'Response status code: ' . $httpResponse['statusCode']);
        Log::write('debug', 'Full HTTP response: ' . json_encode($httpResponse));

        // Process the response
        if ($httpResponse['statusCode'] == 200) {
            // Decode the JSON response data
            $responseData = json_decode($httpResponse['data'], true);

            Log::write('debug', 'Registration response received: ' . json_encode($responseData));

            // Check if errors exist in the response data
            $hasErrors = false;

            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $index => $dataItem) {
                    // Check for errors in the current data item
                    if (isset($dataItem['errors']) && !empty($dataItem['errors'])) {
                        $hasErrors = true;

                        // Log each error
                        foreach ($dataItem['errors'] as $error) {
                            Log::write('error', 'Registration error (Item ' . $index . '): ' . $error);
                            $this->Alert->error(__('Registration error: {0}', h($error)), ['type' => 'string', 'reset' => true]);
                        }
                    }
                }
            }

            if (!$hasErrors) {
                // If no errors, log success and display success message
                Log::write('debug', 'Registration successful: ' . json_encode($responseData));
                $this->Alert->success(__('Students registered successfully'), ['type' => 'string', 'reset' => true]);

                // Update sync status if applicable
                if (isset($responseData['data'])) {
                    $this->updateSyncStatus($responseData['data'], $params);
                }
            }
        } else {
            // Handle non-200 status codes
            Log::write('error', 'HTTP request failed with status code: ' . $httpResponse['statusCode']);

            $responseData = json_decode($httpResponse['data'] ?? '{}', true);
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';

            Log::write('error', 'Registration failed: ' . $errorMessage);
            Log::write('error', 'Full response: ' . json_encode($httpResponse));

            $this->Alert->error(__('Registration failed: {0}', h($errorMessage)), ['type' => 'string', 'reset' => true]);
        }

        Log::write('debug', '=================== END STUDENT REGISTRATION ===================');
    }

    /**
     * Get student data in the format required for registration
     * 
     * Fetches and formats student data from the database according to the
     * structure expected by the Exam API registration endpoint. Includes
     * personal details, academic information, and registered subjects.
     * 
     * @param array $params Parameters to filter student data
     * @return array Array containing formatted student data and raw data
     */
    public function getRegisterStudentData(array $params)
    {
        Log::write('debug', '=================== BEGIN GET STUDENT DATA ===================');
        Log::write('debug', 'Getting student data with params: ' . json_encode($params));

        $data = [];

        // Get the student user data with its related nationality
        if (isset($params['openemis_no']) && !empty($params['openemis_no'])) {
            $SecurityUserTable = $this->getTableLocator()->get('Security.Users');
            $userData = $SecurityUserTable->find()
                ->contain('MainNationalities')  // Contain the necessary related data
                ->where(['openemis_no' => $params['openemis_no']])
                ->first();

            if (!$userData) {
                Log::write('error', "No user found for OpenEmis No: " . $params['openemis_no']);
                Log::write('debug', '=================== END GET STUDENT DATA ===================');
                return [$data, []];
            }
        }

        // Conditions for examination centre and student data
        $conditions = [
            'ExaminationCentresExaminationsStudents.academic_period_id' => $params['academic_period_id'],
            'ExaminationCentresExaminationsStudents.examination_id' => $params['examination_id'],
        ];

        if (!empty($params['institution_id'])) {
            $conditions['ExaminationCentresExaminationsStudents.institution_id'] = $params['institution_id'];
        }

        if (!empty($params['examination_centre_id'])) {
            $conditions['ExaminationCentresExaminationsStudents.examination_centre_id'] = $params['examination_centre_id'];
        }

        if (!empty($params['student_id']) && $params['student_id'] != -1) {
            $conditions['ExaminationCentresExaminationsStudents.student_id'] = $params['student_id'];
        }

        Log::write('debug', 'Query conditions: ' . json_encode($conditions));

        // Fetching examination and student data based on the conditions
        $ExaminationTableData = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsStudents')
            ->find()
            ->select([
                'academic_period_id' => 'AcademicPeriods.id',
                'academic_period_code' => 'AcademicPeriods.code',
                'academic_period_name' => 'AcademicPeriods.name',
                'academic_period_start_year' => 'AcademicPeriods.start_year',
                'academic_period_end_year' => 'AcademicPeriods.end_year',
                'examination_id' => 'Examinations.id',
                'examination_code' => 'Examinations.code',
                'examination_name' => 'Examinations.name',
                'examination_centre_id' => 'ExaminationCentres.id',
                'examination_centre_area_id' => 'Areas.code',
                'examination_centre_code' => 'ExaminationCentres.code',
                'examination_centre_name' => 'ExaminationCentres.name',
                'institution_id' => 'Institutions.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'student_id' => 'Users.id',
                'openemis_no' => 'Users.openemis_no',
                'candidate_number' => 'ExaminationCentresExaminationsStudents.registration_number',
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'gender_id' => 'Genders.id',
                'gender_code' => 'Genders.code',
                'gender_name' => 'Genders.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => 'Users.identity_number',
                'nationality' => 'MainNationalities.name',
                'date_of_birth' => 'Users.date_of_birth',
                'address' => 'Users.address',
                'postal_code' => 'Users.postal_code',
            ])
            ->contain([
                'Examinations',
                'ExaminationCentres',
                'ExaminationCentres.Areas',
                'Institutions',
                'AcademicPeriods',
                'Users',
                'Users.Genders',
                'Users.AddressAreas',
                'Users.BirthplaceAreas',
                'Users.MainNationalities',
                'Users.MainIdentityTypes',
                'Users.SpecialNeeds.SpecialNeedDifficulties',
            ])
            ->where($conditions)
            ->toArray();

        $recordCount = count($ExaminationTableData);
        Log::write('debug', 'Found ' . $recordCount . ' examination records');

        if (empty($ExaminationTableData)) {
            Log::write('warning', 'No examination data found for the provided conditions');
            Log::write('debug', '=================== END GET STUDENT DATA ===================');
            return [$data, []];
        }

        // Fetch the subjects associated with the students
        $ExaminationSubjectData = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsSubjectsStudents');

        // Process each student and associate subject data
        $processedCount = 0;
        foreach ($ExaminationTableData as $studentData) {
            $subjectData = $ExaminationSubjectData->find()
                ->select([
                    'examination_subject_id' => 'ExaminationSubjects.id',
                    'examination_subject_code' => 'ExaminationSubjects.code',
                    'examination_subject_name' => 'ExaminationSubjects.name',
                ])
                ->contain(['ExaminationSubjects'])
                ->where([
                    $ExaminationSubjectData->aliasField('examination_centre_id') => $studentData->examination_centre_id,
                    $ExaminationSubjectData->aliasField('student_id') => $studentData->student_id,
                    $ExaminationSubjectData->aliasField('examination_id') => $studentData->examination_id,
                ])
                ->toArray();

            // Log subjects found for each student
            Log::write('debug', 'Found ' . count($subjectData) . ' subjects for student: ' . $studentData->openemis_no);

            // Assign subjects to the student
            $studentData['subjects'] = $subjectData;
            $processedCount++;

            // Log progress for large datasets
            if ($recordCount > 100 && $processedCount % 50 == 0) {
                Log::write('debug', 'Processing subjects: ' . $processedCount . '/' . $recordCount . ' students completed');
            }
        }

        // Format the student data for the response
        $formattedCount = 0;
        foreach ($ExaminationTableData as $studentDetails) {
            $studentRecord = [
                'openemis_no' => $studentDetails->openemis_no,
                'academic_period_code' => $studentDetails->academic_period_code,
                'academic_period_start_year' => $studentDetails->academic_period_start_year,
                'academic_period_end_year' => $studentDetails->academic_period_end_year,
                'examination_code' => $studentDetails->examination_code,
                'examination_area_code' => $studentDetails->examination_centre_area_id,
                'institution_code' => $studentDetails->institution_code,
                'examination_centre_code' => $studentDetails->examination_centre_code,
                'candidate_number' => $studentDetails->candidate_number,
                'first_name' => $studentDetails->first_name,
                'middle_name' => $studentDetails->middle_name,
                'third_name' => $studentDetails->third_name,
                'last_name' => $studentDetails->last_name,
                'gender_code' => $studentDetails->gender_code,
                'identity_type' => $studentDetails->identity_type,
                'identity_number' => $studentDetails->identity_number,
                'nationality' => $studentDetails->nationality,
                "date_of_birth" => $studentDetails->date_of_birth->format('Y-m-d'),
                "address" => $studentDetails->address,
                "postal_code" => $studentDetails->postal_code,
                "student_id" => $studentDetails->student_id,
            ];

            // Collect subjects codes
            $subjects = [];
            foreach ($studentDetails->subjects as $subject) {
                $subjects[] = $subject->examination_subject_code;
            }

            $studentRecord['subjects'] = $subjects;
            $data[] = $studentRecord;

            $formattedCount++;

            // Log progress for large datasets
            if ($recordCount > 100 && $formattedCount % 50 == 0) {
                Log::write('debug', 'Formatting data: ' . $formattedCount . '/' . $recordCount . ' students completed');
            }
        }

        Log::write('debug', 'Successfully processed ' . count($data) . ' student records');
        Log::write('debug', '=================== END GET STUDENT DATA ===================');

        return [$data, $ExaminationTableData];
    }
}
