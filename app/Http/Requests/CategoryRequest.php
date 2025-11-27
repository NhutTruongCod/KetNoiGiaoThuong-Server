<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($categoryId)
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ten danh muc la bat buoc.',
            'name.unique' => 'Ten danh muc da ton tai.',
            'name.max' => 'Ten danh muc khong duoc vuot qua 255 ky tu.',
            'slug.required' => 'Slug la bat buoc.',
            'slug.unique' => 'Slug da ton tai.',
            'slug.regex' => 'Slug chi duoc chua chu cai thuong, so va dau gach ngang.',
            'slug.max' => 'Slug khong duoc vuot qua 255 ky tu.',
            'description.max' => 'Mo ta khong duoc vuot qua 1000 ky tu.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'ten danh muc',
            'slug' => 'slug',
            'description' => 'mo ta',
        ];
    }
}
