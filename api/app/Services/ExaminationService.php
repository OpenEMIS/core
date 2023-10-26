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
}