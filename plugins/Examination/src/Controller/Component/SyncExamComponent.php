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
 * 
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
    public $components = ['CurlRequest', 'ControllerAction.Alert'];

    /**
     * API endpoint for authentication
     * 
     * @var string
     */
    private $loginEndPoint = '/api/v2/login';

    /**
     * API endpoint for fetching exam results
     * 
     * @var string
     */
    private $resultEndPoint = '/api/v2/results';

    /**
     * Authentication token for API requests
     * 
     * @var string
     */
    private $token = "";

    /**
     * Initialize component
     * 
     * @param array $config Configuration settings
     * @return void
     */
    public function initialize(array $config): void {}

    /**
     * Connect to OpenEMIS Exam Project API and get authentication token
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
            "accept: application/json, text/plain, */*",
            "content-type: application/json",
        ];

        $loginUrl = $url . $this->loginEndPoint;

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
     * @param array $params Parameters containing academic_period_code and examination_code
     * @return void
     */
    public function getResultFromExam(array $params): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '9600');
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
                    $this->getExamResult($params, $config);
                } else {
                    Log::write('debug', 'Invalid parameters: academic period code or examination code is missing.');
                    $this->Alert->error('Invalid parameters: academic period code or examination code is missing.', ['type' => 'string', 'reset' => true]);
                }
            } else {
                Log::write('debug', 'Connection failed: ' . $response['message']);
                $this->Alert->error('Connection failed: ' . h($response['message']), ['type' => 'string', 'reset' => true]);
            }
        } else {
            Log::write('debug', 'OpenEMIS Exam Configuration not found.');
            $this->Alert->error('OpenEMIS Exam Configuration not found.', ['type' => 'string', 'reset' => true]);
        }

        Log::write('debug', '=================== END RESULT SYNC ===================');
    }

    /**
     * Fetch exam results from the API
     * 
     * @param array $params Parameters for the API request
     * @param array $config Configuration settings
     * @return void
     */
    private function getExamResult(array $params, array $config): void
    {
        Log::write('debug', '=================== BEGIN FETCH RESULTS ===================');

        // Prepare request headers with authentication token
        $headers = [
            "accept: application/json, text/plain, */*",
            "Authorization: Bearer " . $this->token,
            "content-type: application/json",
        ];

        // Override params for testing - this should be removed in production
        $params = [
            "academic_period_code" => '24',
            'examination_code' => 'FO202411',
        ];

        Log::write('debug', 'Using params for result fetch: ' . json_encode($params));

        // Build API request URL
        $queryString = http_build_query($params);
        $resultUrl = $config['url'] . $this->resultEndPoint . "?" . $queryString;
        Log::write('debug', 'Sending results request to: ' . $resultUrl);
        Log::write('debug', 'Request headers: ' . json_encode($headers));

        $responseData = [];

        // Make the API request
        $response = $this->CurlRequest->makeCurlRequests($resultUrl, 'GET', $headers);

        // Process response
        if ($response['data']) {
            $responseData = json_decode($response['data'], true);
            $params = json_encode($params);
            Log::write('debug', 'Response status code: ' . $response['statusCode']);
            Log::write('debug', 'Response data received: ' . (isset($responseData['data']) ? 'Yes' : 'No'));
        }

        // Check if results were successfully fetched
        if ($response['statusCode'] == 200 && isset($responseData['data'])) {
            Log::write('debug', 'Results fetched successfully. Creating temporary file and launching sync shell.');

            // Create temporary file to store results data
            $tempFile = TMP . 'exam_data_' . time() . '.json';
            file_put_contents($tempFile, json_encode($responseData));
            Log::write('debug', 'Temporary file created: ' . $tempFile);

            // Prepare shell command to process results in background
            $cmd = ROOT . DS . 'bin' . DS . 'cake SyncExamResult ' . escapeshellarg($tempFile) . ' ' . escapeshellarg($params);
            $logs = ROOT . DS . 'logs' . DS . 'SyncExamResult.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;

            // Log shell command details
            Log::write('debug', 'About to execute shell command: ' . $shellCmd);
            $this->Alert->success(__('Sync process started in background'), ['type' => 'string', 'reset' => true]);
            try {
                // Execute shell command
                $pid = shell_exec($shellCmd) ?? 1;
                Log::write('debug', 'Shell command executing with PID: ' . $pid);
            } catch (\Exception $ex) {
                Log::write('error', __METHOD__ . ' exception syncing exam result: ' . $ex->getMessage());
                Log::write('error', 'Exception trace: ' . $ex->getTraceAsString());
                $this->Alert->success(__('Error starting sync process: {0}', $ex->getMessage()), ['type' => 'string', 'reset' => true]);
            }
        } else {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            Log::write('debug', 'Sync Request Failed: ' . $errorMessage);
            Log::write('debug', 'Full response: ' . json_encode($response));
            $this->Alert->error('Unable to fetch data : ' . h($errorMessage), ['type' => 'string', 'reset' => true]);
        }

        Log::write('debug', '=================== END FETCH RESULTS ===================');
    }

    /**
     * Decrypt sensitive data using provided secret key
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
        foreach ($studentData as $student) {
            foreach ($response as $syncResult) {
                if ($syncResult['openemis_no'] === $student['openemis_no']) {
                    Log::write('debug', 'Updating student: ' . $student['openemis_no'] . ' with sync status: ' . ($syncResult['sync_status'] ? '1' : '-1'));

                    $studentsTable->updateAll(
                        [
                            'sync_status' => $syncResult['sync_status'] ? 1 : -1,
                            'last_synced' => date('Y-m-d H:i:s'),
                        ],
                        ['student_id' => $student['student_id']]
                    );

                    $updateCount++;
                    break;
                }
            }
        }

        Log::write('debug', 'Updated sync status for ' . $updateCount . ' students');
        Log::write('debug', '=================== END STATUS UPDATE ===================');
    }
}
