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

class SyncExamComponent extends Component
{
    use LocatorAwareTrait;

    public $components = ['CurlRequest'];

    private $loginEndPoint = '/login';

    private $resultEndPoint = '/results';

    private $token="";

    public function initialize(array $config): void {}

    // Connect to OpenEMIS Exam Project
    private function getConnectionResponse(string $url, string $username, string $password): array
    {
        $postFields = [
            'username' => $username,
            'password' => $password
        ];

        $headers = [
            "accept: application/json, text/plain, */*",
            "content-type: application/json",
        ];

        $loginUrl = $url . $this->loginEndPoint;

        $response = $this->CurlRequest->makeCurlRequests($loginUrl, 'POST', $headers, $postFields);

        $responseData = json_decode($response['data'], true);

        if ($response['statusCode'] === 200 && isset($responseData['data']['token'])) {
            return [
                'status' => 1,
                'token' => $responseData['data']['token'],
                'message' => $responseData['message'] ?? 'Connected successfully',
            ];
        }

        return [
            'status' => 0,
            'token' => null,
            'message' => $responseData['message'] ?? 'Error connecting to the server',
        ];
    }


    // Main function to sync students to exams
    public function getResultFromExam(array $params): void
    {
        $config = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')
            ->getOpenemisExamConfiguration();

        if (!empty($config)) {
            $response = $this->getConnectionResponse($config['url'], $config['username'], $config['password']);
            if ($response['status']) {
                if (!empty($params['academic_period_code'] && !empty($params['examination_code']))) {
                    $this->token = $response['token'];
                    $this->getExamResult($params,$config);

                } else {
                    Log::write('debug', '');
                }
            } else {
                Log::write('debug', 'Connection failed: ' . $response['message']);
            }
        } else {
            Log::write('debug', 'OpenEMIS Exam Configuration not found.');
        }
    }

    // Get data for registering students to the exam API
    private function getExamResult(array $params,array $config): array
    {
        $headers = [
            "accept: application/json, text/plain, */*",
            "Authorization: Bearer " . $this->token,  // Attach the token dynamically
            "content-type: application/json",  // Optional depending on the server requirements
        ];
        // Convert the parameters array to a query string
        $queryString = http_build_query($params);
        $resultUrl = $config['url'] . $this->resultEndPoint. "?".$queryString;

        $response = $this->CurlRequest->makeCurlRequests($resultUrl, 'GET', $headers);

        echo "<pre>";
        print_r($response);
        exit;
        // $responseData = json_decode($response['data'], true);

        // if ($response['statusCode'] === 200 && isset($responseData['data']['token'])) {
        //     return [
        //         'status' => 1,
        //         'token' => $responseData['data']['token'],
        //         'message' => $responseData['message'] ?? 'Connected successfully',
        //     ];
        // }
        // $studentsTable = $this->getTableLocator()->get('Institution.StudentUser');
        // $examinationsTable = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsStudents');
        // $subjectsTable = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsSubjectsStudents');

        // $studentData = $studentsTable
        //     ->find()
        //     ->contain(['MainNationalities'])
        //     ->where(['openemis_no' => $params['openemis_no']])
        //     ->first();

        // $conditions = [
        //     'academic_period_id' => $params['academic_period_id'],
        //     'examination_id' => $params['examination_id'],
        // ];

        // if (!empty($params['institution_id'])) {
        //     $conditions['institution_id'] = $params['institution_id'];
        // }

        // if (!empty($params['examination_centre_id'])) {
        //     $conditions['examination_centre_id'] = $params['examination_centre_id'];
        // }

        // if (!empty($params['student_id']) && $params['student_id'] !== -1) {
        //     $conditions['student_id'] = $params['student_id'];
        // }

        // $examinationData = $examinationsTable
        //     ->find()
        //     ->contain([
        //         'Examinations',
        //         'ExaminationCentres',
        //         'AcademicPeriods',
        //         'Institutions',
        //         'Users',
        //         'Users.Genders',
        //     ])
        //     ->where($conditions)
        //     ->toArray();

        // foreach ($examinationData as &$data) {
        //     $data['subjects'] = $subjectsTable
        //         ->find()
        //         ->contain(['ExaminationSubjects'])
        //         ->where([
        //             'examination_centre_id' => $data['examination_centre_id'],
        //             'student_id' => $data['student_id'],
        //         ])
        //         ->toArray();
        // }

        return [];
        // return [$examinationData, $studentData];
    }

    // Decrypt sensitive data
    private function decrypt(string $encryptedString, string $secretKey): ?string
    {
        $iv = substr($secretKey, 0, 16);
        $data = base64_decode($encryptedString);
        return openssl_decrypt($data, 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
    }

    // Update sync status
    private function updateSyncStatus(array $response, array $params): void
    {
        $studentsTable = $this->getTableLocator()->get('Examination.ExaminationCentresExaminationsStudents');
        [$studentData] = $this->getRegisterStudentData($params);

        foreach ($studentData as $student) {
            foreach ($response as $syncResult) {
                if ($syncResult['openemis_no'] === $student['openemis_no']) {
                    $studentsTable->updateAll(
                        [
                            'sync_status' => $syncResult['sync_status'] ? 1 : -1,
                            'last_synced' => date('Y-m-d H:i:s'),
                        ],
                        ['student_id' => $student['student_id']]
                    );
                    break;
                }
            }
        }
    }
}
