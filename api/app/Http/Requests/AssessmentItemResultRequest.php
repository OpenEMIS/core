<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AssessmentItemResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            
            'student_id' => 'required',
            'assessment_id' => 'required',
            'education_subject_id' => 'required',
            'education_grade_id' => 'required',
            'academic_period_id' => 'required',
            'assessment_period_id' => 'required',
            'institution_id' => 'required',
            'institution_classes_id' => 'required',
            // 'marks' => 'required',
            'assessment_grading_option_id' => 'required',
        ];
    }


   //* @param Validator $validator
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json([
                'message' => "Unsuccessful.",
                'Enter Required Fileds' => $errors,
            ],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        )
        );
    }
}
