<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class ScannedAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Update as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            '*.openemis_no' => 'required|string',
            '*.datetime' => 'required|date'
        ];
    }

    /**
     * Custom error messages for validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            '*.openemis_no.required' => 'The openemis_no field is required.',
            '*.datetime.required' => 'The datetime field is required.'
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        // Remove numeric prefixes from error keys
        $formattedErrors = [];
        foreach ($errors as $key => $error) {
            $cleanKey = preg_replace('/^\d+\./', '', $key); // Remove numeric indices
            $formattedErrors[$cleanKey] = $error;
        }

        throw new HttpResponseException(
            response()->json(
                [
                    'message' => "Unsuccessful.",
                    'errors' => $formattedErrors,
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}
