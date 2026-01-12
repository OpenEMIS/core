<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ExaminationStudentSubjectResultRequest extends FormRequest
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
            'marks' => ['regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'nullable', 'required_if:examination_grading_option_id,null'],
            'examination_grading_option_id' => 'required_if:marks,null',
            'academic_period_id' => 'required',
            'examination_id' => 'required',
            'examination_subject_id' => 'required',
            'examination_centre_id' => 'required',
            'institution_id' => 'required',
            'student_id' => 'required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json(
                [
                    'message' => "Unsuccessful.",
                    'Enter Required fields' => $errors,
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}