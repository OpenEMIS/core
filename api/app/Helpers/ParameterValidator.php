<?php

namespace App\Helpers;

use App\Models\AcademicPeriod;
use App\Models\EducationGrades;
use App\Models\EducationSubjects;
use App\Models\Examination;
use App\Models\ExaminationCentre;
use App\Models\ExaminationGradingOption;
use App\Models\InstitutionClasses;
use App\Models\Institutions;
use App\Models\InstitutionSubjects;

class ParameterValidator
{
    public static function validateInstitution($id)
    {
        return  Institutions::where('id', $id)->first();
    }

    public static function validateAcademicPeriod($id)
    {
        return  AcademicPeriod::where('id', $id)->first();
    }

    public static function validateClass($id)
    {
        return  InstitutionClasses::where('id', $id)->first();
    }

    public static function validateInstitutionSubject($id)
    {
        return  InstitutionSubjects::where('id', $id)->first();
    }

    public static function validateEducationSubject($id)
    {
        return  EducationSubjects::where('id', $id)->first();
    }

    public static function validateExaminationSubject($id)
    {
        return  EducationSubjects::where('id', $id)->first();
    }

    public static function validateEducationGrade($id)
    {
        return  EducationGrades::where('id', $id)->first();
    }

    public static function validateExaminationGradingOption($id)
    {
        return  ExaminationGradingOption::where('id', $id)->first();
    }

    public static function validateExamination($id)
    {
        return  Examination::where('id', $id)->first();
    }

    public static function validateExaminationCentre($id)
    {
        return  ExaminationCentre::where('id', $id)->first();
    }
}