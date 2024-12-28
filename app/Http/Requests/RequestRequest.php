<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'request_id' => 'unique:requests',
            'request_datetime' => 'date_format:Y-m-d H:i:s',
            'theme_id' => 'exists:themes,theme_id',
            'user_id' => 'exists:users,user_id',
            'status_id' => 'exists:request_file_statuses,status_id',
            'upload_files' => 'array|max:10',
            'upload_files.*' => 'file|mimes:mp3,wav|max:1024',
        ];
    }
}
