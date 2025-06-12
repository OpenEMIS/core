<?php

/**
 * MoodleCreateCourse - Handles Moodle's core_course_create_courses logic
 * @category  API
 * @package   MoodleApi
 * @author    Megha Gupta <barkha@madvit.com>
 */

namespace App\MoodleApi\MoodleFunction;

class MoodleCreateCourse extends MoodleFunction
{
    // Define the Moodle API function for course creation
    protected static $functionParam = "core_course_create_courses";
    protected static $mandatoryParams
    = [
        'fullname',
        'shortname',
        'categoryid'
    ];
    protected static $allowedParams
    = [];
    protected $data = [];
    protected function convertDataToParam()
    {
        $this->data = [0 => $this->data];
        $this->data = ["courses" => $this->data];
    }

    /**
     * Convert the entity data into an array.
     *
     * @param array $data - Course data
     * @return void
     */
    protected function convertEntityToData($entity)
    {
        if (!$entity instanceof \Institution\Model\Entity\InstitutionSubject) {
            $this->setError("Entity Datatype is not  \Institution\Model\Entity\InstitutionSubject");
        }
        $this->data = [
            'fullname' => $this->createMoodleCourseName($entity),
            'shortname' => $entity['name'],
            'categoryid' => 1,
        ];
    }

    /**
     * 
     * Convert the entity data into an array.
     *
     * @param array $data - Course data
     * @return void
     */
    private function createMoodleCourseName($data)
    {
        // Extract field names from the provided data
        $fieldNames = $data['fieldNames'] ?? [];
        // Assign field values with default fallback
        $academicPeriodName = $fieldNames['academic_period_name'] ?? '';
        $institutionCode = $fieldNames['institution_code'] ?? '';
        $educationGradeCode = $fieldNames['education_grade_code'] ?? '';
        $educationGradeName = $fieldNames['education_grade_name'] ?? '';
        $className = $fieldNames['class_name'] ?? '';
        $subjectCode = $fieldNames['subject_code'] ?? '';
        $subjectName = $data['name'] ?? '';
        // Construct the course name dynamically
        $courseName = implode(' | ', array_filter([
            $academicPeriodName,
            $institutionCode,
            $educationGradeCode,
            $educationGradeName,
            $className,
            $subjectCode,
            $subjectName,
        ]));
        // Return the generated course name
        return $courseName;
    }

    /**
     * Check that all mandatory parameters are provided.
     *
     * @return void
     */
    protected function checkData()
    {
        foreach ($this->mandatoryParams as $param) {
            if (empty($this->data[$param])) {
                $this->setError("The $param is mandatory.");
            }
        }
    }
}
