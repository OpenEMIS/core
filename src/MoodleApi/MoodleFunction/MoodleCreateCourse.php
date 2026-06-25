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
    protected static $listfunctionParam = "core_course_get_courses";
    protected static $updateFunctionParam = "core_course_update_courses";
    protected static $assignRolefunctionParam = "enrol_manual_enrol_users";

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
    /**
     * Get the function parameter for listing Moodle courses.
     *
     * @return string The parameter name for listing courses.
     */
    public static function getListFunctionParam()
    {
        return self::$listfunctionParam;
    }
    /**
     * Get the function parameter for updating Moodle courses.
     *
     * @return string The parameter name for updating courses.
     */
    public static function getUpdateFunctionParam()
    {
        return self::$updateFunctionParam;
    }
    /**
     * Check if a course already exists in the provided list by comparing course data.
     *
     * @param array $data The new course data.
     * @param array $list The list of existing courses in Moodle.
     * @return int|null The ID of the existing course if found, otherwise null.
     */
    public static function courseAlreadyExist($data, $list)
    {

        $currentCourseData = self::parseCourseFullname($data['courses'][0]['fullname']);
        $courseId = null;
        if (!empty($list)) {
            foreach ($list as $course) {

                $courseData = self::parseCourseFullname($course['fullname']);
                $filteredCourseData = $courseData;
                unset($filteredCourseData['subject_name']);

                $filteredCurrentData = $currentCourseData;
                unset($filteredCurrentData['subject_name']);

                if ($filteredCourseData == $filteredCurrentData) {
                    $courseId = $course['id'];
                    break;
                }
            }
        }
        return $courseId;
    }
    /**
     * Parse a course's fullname into structured data fields.
     *
     * @param string $fullname The full name of the course (separated by '|').
     * @return array An associative array containing structured course details.
     */


    private static function parseCourseFullname($fullname)
    {

        $parts = array_map('trim', explode('|', $fullname));
        $keys = [
            'academic_period_name',
            'institution_code',
            'education_grade_code',
            'education_grade_name',
            'class_name',
            'subject_code',
            'subject_name'
        ];

        return array_combine($keys, array_pad($parts, count($keys), ''));
    }
    /**
     * Prepare update data by adding the existing course ID.
     *
     * @param array $data The course data to be updated.
     * @param int $id The ID of the existing course.
     * @return array The modified data including the course ID.
     */
    public static function getUpdateData($data, $id)
    {
        $data['courses'][0]['id'] = $id;
        return $data;
    }

     /**
     * Get the function parameter for assigning role to Moodle courses.
     *
     * @return string The parameter name for assigning role to courses.
     */

    public static function getAssignroleFunctionParam(){
        return self::$assignRolefunctionParam;
    }
}
