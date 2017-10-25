<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class ReportsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.report_progress',
        'app.institution_quality_rubrics',
        'app.institution_surveys',
        'app.institution_survey_answers',
        'app.institution_survey_table_cells',
        'app.survey_questions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_forms',
        'app.staff_custom_fields',
        'app.staff_custom_field_values',
        'app.staff_custom_table_cells',
        'app.student_custom_fields',
        'app.student_custom_field_values',
        'app.student_custom_table_cells',
        'app.institutions',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_table_cells',
        'app.institution_custom_forms_filters'
    ];

    public function testInstitutionReportIndex()
    {
        $this->get('/Reports/Institutions');
        $this->assertResponseCode(200);
    }

    public function testStudentReportIndex()
    {
        $this->get('/Reports/Students');
        $this->assertResponseCode(200);
    }

    public function testStaffReportIndex()
    {
        $this->get('/Reports/Staff');
        $this->assertResponseCode(200);
    }

    public function testSurveyReportIndex()
    {
        $this->get('/Reports/Surveys');
        $this->assertResponseCode(200);
    }

    public function testQualityReportIndex()
    {
        $this->get('/Reports/InstitutionRubrics');
        $this->assertResponseCode(200);
    }

    public function testDataQualityReportIndex()
    {
        $this->get('/Reports/DataQuality');
        $this->assertResponseCode(200);
    }

    public function testAuditReportIndex()
    {
        $this->get('/Reports/Audit');
        $this->assertResponseCode(200);
    }
}
