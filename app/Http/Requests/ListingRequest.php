<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $listingId = $this->route('listing') ? $this->route('listing')->id : null;

        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'price' => 'required|numeric|min:0',
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id') // Se tao categories table sau
            ],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tieu de bai dang la bat buoc.',
            'title.max' => 'Tieu de khong duoc vuot qua 255 ky tu.',
            'description.required' => 'Mo ta bai dang la bat buoc.',
            'description.max' => 'Mo ta khong duoc vuot qua 2000 ky tu.',
            'price.required' => 'Gia san pham la bat buoc.',
            'price.numeric' => 'Gia san pham phai la so.',
            'price.min' => 'Gia san pham phai lon hon hoac bang 0.',
            'category_id.required' => 'Danh muc la bat buoc.',
            'category_id.exists' => 'Danh muc khong ton tai.',
            'store_id.required' => 'Cua hang la bat buoc.',
            'store_id.exists' => 'Cua hang khong ton tai.',
        ];
    }
}
