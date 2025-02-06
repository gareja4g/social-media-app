<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => 'nullable|string|max:500',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'profile_visibility' => 'required|in:public,private,friends-only',
            'post_visibility' => 'required|in:public,private,friends-only',
        ];
    }
}
