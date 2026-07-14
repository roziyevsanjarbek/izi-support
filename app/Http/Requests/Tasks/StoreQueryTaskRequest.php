<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQueryTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'query_id' => ['required', 'integer'],
        'custom_id' => ['nullable', 'string', 'max:255'],
        'operation_id' => ['required', 'integer'],

        'name' => ['required', 'string', 'max:255'],
        'description' => ['required', 'string'],

        'end_date' => ['required', 'date'],

        'attachments' => ['nullable', 'array', 'max:5'],
        'attachments.*' => ['file', 'max:102400'],
    ];
}
}
