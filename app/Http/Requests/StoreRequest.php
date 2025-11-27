<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->route('store') ? $this->route('store')->id : null;

        return [
            'name' => 'required|string|max:255|unique:stores,name,' . $storeId,
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:stores,email,' . $storeId,
            'phone' => 'required|string|size:10|regex:/^[0-9]+$/',
            'address' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ten cua hang la bat buoc.',
            'name.unique' => 'Ten cua hang da ton tai.',
            'owner_name.required' => 'Ten chu cua hang la bat buoc.',
            'email.required' => 'Email la bat buoc.',
            'email.email' => 'Email khong dung dinh dang.',
            'email.unique' => 'Email da ton tai.',
            'phone.required' => 'So dien thoai la bat buoc.',
            'phone.size' => 'So dien thoai phai co 10 ky tu.',
            'phone.regex' => 'So dien thoai chi duoc chua ky tu so.',
            'address.required' => 'Dia chi la bat buoc.',
        ];
    }
}
