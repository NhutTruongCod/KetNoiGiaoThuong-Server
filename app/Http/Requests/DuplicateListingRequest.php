<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duplicate_items' => 'required|array|min:2',
            'duplicate_items.*' => 'required|integer|exists:listings,id',
            'detected_by' => 'required|string|in:AI,manual,system',
            'note' => 'nullable|string|max:1000',
            'confidence_score' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'duplicate_items.required' => 'Danh sach bai dang trung lap la bat buoc.',
            'duplicate_items.array' => 'Danh sach bai dang phai la mang.',
            'duplicate_items.min' => 'Phai co it nhat 2 bai dang trung lap.',
            'duplicate_items.*.required' => 'Moi bai dang la bat buoc.',
            'duplicate_items.*.integer' => 'ID bai dang phai la so nguyen.',
            'duplicate_items.*.exists' => 'Bai dang khong ton tai.',
            'detected_by.required' => 'Phuong thuc phat hien la bat buoc.',
            'detected_by.in' => 'Phuong thuc phat hien khong hop le.',
            'note.max' => 'Ghi chu khong duoc vuot qua 1000 ky tu.',
            'confidence_score.min' => 'Do tin cay phai tu 0 den 100.',
            'confidence_score.max' => 'Do tin cay phai tu 0 den 100.',
        ];
    }
}
