<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionStudentIndexesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.indexes',
        'app.indexes_criterias',
        'app.institution_student_indexes',
        'app.student_indexes_criterias',
        'app.academic_periods',
        'app.academic_period_levels',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.security_users',
        'app.labels',
        'app.behaviour_classifications',
        'app.student_behaviours',
        'app.student_behaviour_categories',
        'app.institutions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.survey_rules',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
    ];

    public function testIndexIndexes()
    {
        $this->setInstitutionSession(1);

        $this->get('/Institutions/Indexes/index');
        $this->assertResponseCode(200);
    }

    public function testIndexInstitutionStudentIndexes()
    {
        $this->setInstitutionSession(1);

        $this->get('/Institutions/InstitutionStudentIndexes?index_id=20&academic_period_id=25');
        $this->assertResponseCode(200);
    }

    public function testViewInstitutionStudentIndexes()
    {
        $this->setInstitutionSession(1);
        $this->setStudentSession(1039);

        $table = TableRegistry::get('Institution.InstitutionStudentIndexes');
        $urlParams = $table->paramsEncode(['id' => 32]);

        $this->get('/Institutions/InstitutionStudentIndexes/view/' . $urlParams . '?index_id=20&academic_period_id=25');
        $this->assertResponseCode(200);
    }

    public function testGenerateInstitutionStudentIndexes()
    {
        $this->setInstitutionSession(1);

        $key = 'Behaviour';
        $model = 'Institution.StudentBehaviours';
        $institutionId = 1;
        $userId = 2;
        $academicPeriodId = 10;

        $Indexes = TableRegistry::get('Indexes.Indexes');
        $InstitutionStudentIndexes = TableRegistry::get('Institution.InstitutionStudentIndexes');

        $url = [
            'plugin' => 'Indexes',
            'controller' => 'Indexes',
            'action' => 'Indexes',
            'generate'
        ];

        $urlGenerate = $Indexes->setQueryString($url, [
            'institution_id' => 1,
            'user_id' => 2,
            'index_id' => 20,
            'academic_period_id' => 25
        ]);

        $this->get($urlGenerate);
        $this->assertResponseCode(200);

        $Indexes->autoUpdateIndexes($key, $model, $institutionId, $userId, $academicPeriodId);

        $results = $InstitutionStudentIndexes->find()
            ->where(['academic_period_id' => $academicPeriodId])
            ->all()->toArray();

        $this->assertNotEmpty($results);
    }
}
