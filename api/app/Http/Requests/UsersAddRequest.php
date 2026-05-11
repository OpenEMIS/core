<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsersAddRequest extends FormRequest
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
     * POCOR-9697: silently strip super_admin from the request and log the
     * attempt server-side. We deliberately do NOT reject the request — a
     * 422 that names the field would fingerprint the API for any attacker
     * probing escalation vectors. The other defence layers (model $fillable,
     * controller permission gate, UserRepository::setUserData) ensure the
     * field is never persisted; this step is here only so ops gets a log
     * line whenever someone tries.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('super_admin')) {
            return;
        }

        $callerId = optional(JWTAuth::user())->id ?? 'unauthenticated';
        $payloadValue = $this->input('super_admin');
        Log::warning(
            'POCOR-9697: super_admin field detected in request body — silently stripped',
            [
                'endpoint'  => $this->path(),
                'method'    => $this->method(),
                'caller_id' => $callerId,
                'ip'        => $this->ip(),
                'value'     => $payloadValue,
            ]
        );

        // Remove from both the parsed input and the underlying request bag
        // so downstream consumers (e.g. $request->all() in UserService /
        // UserRepository) never see it.
        $this->request->remove('super_admin');
        $this->merge(['super_admin' => null]);
        $this->offsetUnset('super_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required_without:id',
            'last_name' => 'required_without:id',
            'gender_id' => 'required_without:id',
            'date_of_birth' => 'required_without:id',
        ];
    }

    /**
     * @param Validator $validator
     */
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
