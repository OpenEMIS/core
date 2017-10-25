<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;

use Restful\Service\DataService;
use Firebase\JWT\JWT;

class DataServiceController extends AppController
{

    private function generateToken($userId) {
        return JWT::encode([
                'sub' => $userId,
                'exp' =>  time() + 3600
            ], Configure::read('Application.private.key'), 'RS256');
    }

    public function index()
    {
        $this->autoRender = false;

        $config = [
            'base' => 'http://localhost/assessments/api',
            'className' => null,
            'version' => 'v2',
            'action' => 'index',
            'db' => 'default',
            'header' => [
                'Authorization' => 'Bearer '.$this->generateToken(1),
                'ControllerAction' => 'School'
            ]
        ];
        $dataService = new DataService($config);


        //mobile api
        $packagesService = $dataService->init('Packages');
        $PAService = $dataService->init('PackagesAssessments');
        $downloadService = $dataService->init('PackageDownloads');
        // //filter by status
        // $response = $packagesService->where(['status' => 1])->all();

        // //filter by grade
        // $response = $packagesService->where(['grade_id' => 3])->all();

        // //filter by level
        // $response = $packagesService->innerJoinWith(['Levels'])->where(['PackagesLevels.level_id' => 1])->all();

        // //- API for searching package
        // $response = $packagesService->search('t')->all();

        // //retrieve package details
        // $response = $packagesService->where(['id' => 1])->all();

        //retrieving package details including all assessments
        //$response = $packagesService->find('PackagesAssessmentByFirstQn')->all();

        //download counts
        // $response = $downloadService->find('PackageDownloadCounts')->all();


        //web api
        $statusService = $dataService->init('PackageStatuses');
        $assessmentTypeService = $dataService->init('AssessmentTypes');
        $gradesService = $dataService->init('Grades');

        //search for all types of statuses and filter by status
        // $response = $statusService->find('OptionList')->all();
        // $response = $packagesService->where(['status' => 2])->all();

        //search for all types of assessment type and filter by assessment type
        // $response = $assessmentTypeService->find('OptionList')->all();
        // $response = $packagesService->where(['assessment_type_id' => 2])->all();

        //search for all types of grades and filter by grades
        // $response = $gradesService->find('OptionList')->all();
        // $response = $packagesService->where(['grade_id' => 5])->all();


        pr($response);
    }
}
