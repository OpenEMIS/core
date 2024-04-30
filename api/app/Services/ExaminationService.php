<?php

namespace App\Services;

use App\Repositories\ExaminationRepository;

class ExaminationService
{
    protected ExaminationRepository $examinationRepository;

    public function __construct(ExaminationRepository $examinationRepository) {
        $this->examinationRepository = $examinationRepository;
    }

    public function getExaminationDetails($id)
    {
        $result = $this->examinationRepository->getExaminationDetails($id);

        return $result;
    }

    public function getCenterExaminationDetails($examinationId, $centerId)
    {
        $result = $this->examinationRepository->getCenterExaminationDetails($examinationId, $centerId);

        $data = [];

        if (!$result) {
            return null;
        }

        $data['id'] = $result->examination_centre_id;
        $data['name'] = $result->ExaminationCentre->name;
        $data['code'] = $result->ExaminationCentre->code;
        $data['address'] = $result->ExaminationCentre->address;
        $data['postal_code'] = $result->ExaminationCentre->postal_code;
        $data['contact_person'] = $result->ExaminationCentre->contact_person;
        $data['telephone'] = $result->ExaminationCentre->telephone;
        $data['fax'] = $result->ExaminationCentre->fax;
        $data['email'] = $result->ExaminationCentre->email;
        $data['website'] = $result->ExaminationCentre->website;
        $data['institution_id'] = $result->ExaminationCentre->institution_id;
        $data['area_id'] = $result->ExaminationCentre->area_id;
        $data['academic_period_id'] = $result->ExaminationCentre->academic_period_id;
        $data['examination_id'] = $result->examination->id;
        $data['total_registered'] = $result->total_registered;
        $data['modified_user_id'] = $result->ExaminationCentre->modified_user_id;
        $data['modified'] = $result->ExaminationCentre->modified;
        $data['created_user_id'] = $result->ExaminationCentre->created_user_id;
        $data['created'] = $result->ExaminationCentre->created;
        $data['examination'] = $result->examination;

        return $data;
    }

    public function getCenterExaminationStudentDetails($examinationId, $centerId, $studentId)
    {
        $result =  $this->examinationRepository->getCenterExaminationStudentDetails($examinationId, $centerId, $studentId);

        if ($result->isEmpty()) {
            return null;
        }

        return $result;
    }

    public function examinationCenterExaminationSubjects($examinationId, $centerId)
    {
        $subjects = $this->examinationRepository->examinationCenterExaminationSubjects($examinationId, $centerId);
        $data = [];

        if (!$subjects) {
            return null;
        }
        foreach($subjects as $key => $subject) {
            $data[$key]['id'] = $subject->id;
            $data[$key]['created'] = $subject->created;
            $data[$key]['education_subject_id'] = $subject->education_subject_id;
            $data[$key]['examination_centre_id'] = $subject->examination_centre_id;
            $data[$key]['examination_id'] = $subject->examination_id;
            $data[$key]['examination_subject_id'] = $subject->examination_subject_id;
            $data[$key]['education_subject'] = $subject->educationSubject;
            $data[$key]['examination_subject'] = $subject->examinationSubject;
        }

        return $data;
    }

    public function examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId)
    {
        $students = $this->examinationRepository->examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId);
        $data = [];

        if (!$students) {
            return null;
        }

        foreach($students as $key => $student) {
            $data[$key]['student_id'] = $student->student_id;
            $data[$key]['registration_number'] = $student->registration_number;
            $data[$key]['institution_id'] = $student->institution_id;
            $data[$key]['academic_period_id'] = $student->academic_period_id;
            $data[$key]['total_mark'] = $student->total_mark;
            $data[$key]['result_marks'] = $student->result_marks;
            $data[$key]['result_id'] = $student->result_id;
            $data[$key]['examination_grading_option_id'] = $student->examination_grading_option_id;
            $data[$key]['first_name'] = $student->first_name;
            $data[$key]['last_name'] = $student->last_name;
            $data[$key]['third_name'] = $student->third_name;
            $data[$key]['middle_name'] = $student->middle_name;
            $data[$key]['openemis_no'] = $student->openemis_no;
        }

        return $data;
    }

    public function examStudentSubjectResult($data)
    {
        return $this->examinationRepository->examStudentSubjectResult($data);
        
    }

}