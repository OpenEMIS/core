<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

// App::uses('StudentsAppModel', 'Model');

class StudentAssessment extends StudentsAppModel {
    public $useTable = 'assessment_item_results';

    public function getYears($studentId){
        $years = array();
        $result = $this->find('all', array(
            'fields' => array("DISTINCT SchoolYear.id", "SchoolYear.name"),
            'joins' => array(
                array(
                    'table' => 'school_years',
                    'alias' => 'SchoolYear',
                    'type' => 'INNER',
                    'conditions' => array(
                        "SchoolYear.id = {$this->name}.school_year_id"
                    ),
                    'order' => array("{$this->name}.school_year_id DESC")
                )
            ),
            'conditions' => array($this->name.'.student_id' => $studentId)
        ));

        foreach($result as $row){
            $years[$row['SchoolYear']['id']] = $row['SchoolYear']['name'];
        }
        return $years;
    }

    public function getProgrammeGrades($studentId, $yearId=0){
        $programmeGrades = array();
        $conditions = array("{$this->name}.student_id" => $studentId);
        if($yearId > 0){
            $conditions["{$this->name}.school_year_id"] = $yearId;
        }

        $result = $this->find('all', array(
            'fields' => array("DISTINCT EducationGrade.id, EducationGrade.name, EducationProgramme.name"),
            'joins' => array(
                array(
                    'table' => 'assessment_items',
                    'alias' => 'AssessmentItem',
                    'type' => 'INNER',
                    'conditions' => array(
                        "AssessmentItem.id = {$this->name}.assessment_item_id"
                    )
                ),
                array(
                    'table' => 'education_grades_subjects',
                    'alias' => 'EducationGradeSubject',
                    'type' => 'INNER',
                    'conditions' => array(
                        "EducationGradeSubject.id = AssessmentItem.education_grade_subject_id"
                    )
                ),
                array(
                    'table' => 'education_grades',
                    'alias' => 'EducationGrade',
                    'type' => 'INNER',
                    'conditions' => array(
                        "EducationGrade.id = EducationGradeSubject.education_grade_id"
                    )
                ),
                array(
                    'table' => 'education_programmes',
                    'alias' => 'EducationProgramme',
                    'type' => 'INNER',
                    'conditions' => array(
                        "EducationProgramme.id = EducationGrade.education_programme_id"
                    )
                )
            ),
            'conditions' => $conditions,
            'order' => array("EducationGrade.id ASC")
        ));

        foreach($result as $row){
            $programmeGrades[$row['EducationGrade']['id']] = "{$row['EducationProgramme']['name']} - {$row['EducationGrade']['name']}";

        }

        return $programmeGrades;
    }

    public function getData($studentId, $yearId, $gradeId){


        $result = $this->find('all', array(
            'fields' => array(
                "{$this->name}.marks",
                "AssessmentItem.min",
                "AssessmentResultType.name",
                "EducationSubject.name",
                "EducationSubject.code",
                "AssessmentItemType.name",
                "AssessmentItemType.code",
//                "EducationGrade.id",
//                "EducationGrade.name",
//                "EducationProgramme.name",
                "InstitutionSite.name"
            ),
            'joins' => array(
                array(
                    'table' => 'assessment_result_types',
                    'alias' => 'AssessmentResultType',
                    'type' => 'INNER',
                    'conditions' => array(
                        "AssessmentResultType.id = {$this->name}.assessment_result_type_id"
                    )
                ),
                array(
                    'table' => 'assessment_items',
                    'alias' => 'AssessmentItem',
                    'type' => 'INNER',
                    'conditions' => array(
                        "AssessmentItem.id = {$this->name}.assessment_item_id"
                    )
                ),
                array(
                    'table' => 'assessment_item_types',
                    'alias' => 'AssessmentItemType',
                    'type' => 'INNER',
                    'conditions' => array(
                        "AssessmentItemType.id = AssessmentItem.assessment_item_type_id"
                    )
                ),
                array(
                    'table' => 'education_grades_subjects',
                    'alias' => 'EducationGradeSubject',
                    'type' => 'INNER',
                    'conditions' => array(
                        "EducationGradeSubject.id = AssessmentItem.education_grade_subject_id"
                    )
                ),
                array(
                    'table' => 'education_subjects',
                    'alias' => 'EducationSubject',
                    'type' => 'INNER',
                    'conditions' => array(
                        "EducationSubject.id = EducationGradeSubject.education_subject_id"
                    )
                ),
//                array(
//                    'table' => 'education_grades',
//                    'alias' => 'EducationGrade',
//                    'type' => 'INNER',
//                    'conditions' => array(
//                        "EducationGrade.id = EducationGradeSubject.education_grade_id"
//                    )
//                ),
//                array(
//                    'table' => 'education_programmes',
//                    'alias' => 'EducationProgramme',
//                    'type' => 'INNER',
//                    'conditions' => array(
//                        "EducationProgramme.id = EducationGrade.education_programme_id"
//                    )
//                ),
                array(
                    'table' => 'institution_sites',
                    'alias' => 'InstitutionSite',
                    'type' => 'INNER',
                    'conditions' => array(
                        "InstitutionSite.id = {$this->name}.institution_site_id"
                    )
                )
            ),
            'conditions' => array(
                "{$this->name}.student_id" => $studentId,
                "{$this->name}.school_year_id" => intval($yearId),
                "EducationGradeSubject.education_grade_id" => intval($gradeId),
            ),
//            'group' => array("InstitutionSite.name"),
//            'order' => array("EducationGrade.id ASC")
        ));

        $result = $this->formatData($result);

        return $result;
    }

    private function formatData($data){
        $formattedData = array();
        foreach($data as $row){
            $tempArray = array();
            $tempArray['marks']['value'] = $row['StudentAssessment']['marks'];
            $tempArray['marks']['min'] = $row['AssessmentItem']['min'];
            $tempArray['grading']['name'] = $row['AssessmentResultType']['name'];
            $tempArray['subject'] = array('name'=> $row['EducationSubject']['name'], 'code' => $row['EducationSubject']['code']);
            $tempArray['assessment'] = array('name'=> $row['AssessmentItemType']['name'], 'code' => $row['AssessmentItemType']['code']);
//            $tempArray['grade'] = array('name'=> $row['EducationGrade']['name'], 'id' => $row['EducationGrade']['id']);
//            $tempArray['programme']['name'] = $row['EducationProgramme']['name'];
            $tempArray['site']['name'] = $row['InstitutionSite']['name'];
            $formattedData[] = $tempArray;
        }

        $formattedData = $this->formatDataGroupBySiteProgrammeGrade($formattedData);

        return $formattedData;
    }

    private function formatDataGroupBySiteProgrammeGrade($unformattedArray){
        $formattedArray = array();
        foreach($unformattedArray as $unformattedData){
            $key = $unformattedData['site']['name'];
            if(!array_key_exists($key, $formattedArray)){
                $formattedArray[$key] = array();
            }

            unset($unformattedData['site']);

            $formattedArray[$key][] = $unformattedData;
        }
        $formattedArray = $this->formatDataGroupBySubject($formattedArray);
        return $formattedArray;
    }

    private function formatDataGroupBySubject($unformattedArray){
        $formattedArray = array();
        foreach($unformattedArray as $key => $row){
            $tempArray = array();
            foreach($row as $unformattedData){
                $innerKey = trim("{$unformattedData['subject']['name']} - {$unformattedData['subject']['code']}");


                if(!array_key_exists($innerKey, $tempArray)){
                    $tempArray[$innerKey] = array();
                }
                unset($unformattedData['subject']);
                $tempArray[$innerKey][] = $unformattedData;
            }
            if(!array_key_exists($innerKey, $formattedArray)){
                $formattedArray[$key] = array();
            }

            $formattedArray[$key] = $tempArray;
        }
        return $formattedArray;
    }

    private function print_r_html($data){
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }

}
?>
