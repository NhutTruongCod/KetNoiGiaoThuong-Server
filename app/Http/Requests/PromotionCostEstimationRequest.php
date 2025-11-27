<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionCostEstimationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $estimationId = $this->route('cost_estimation') ? $this->route('cost_estimation')->id : null;

        $rules = [
            'store_id' => 'required|integer|exists:stores,id',
            'listing_id' => 'nullable|integer|exists:listings,id',
            'promotion_type' => 'required|in:banner,video_ads,social_media,search_ads,email_marketing,in_app_ads',
            'duration_days' => 'required|integer|min:1|max:365',
            'budget' => 'required|numeric|min:10000',
            'currency' => 'sometimes|string|size:3',
            'status' => 'sometimes|in:pending,approved,rejected,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['store_id'] = 'sometimes|integer|exists:stores,id';
            $rules['listing_id'] = 'nullable|integer|exists:listings,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Cua hang la bat buoc.',
            'store_id.exists' => 'Cua hang khong ton tai.',
            'listing_id.exists' => 'Bai dang khong ton tai.',
            'promotion_type.required' => 'Loai quang cao la bat buoc.',
            'promotion_type.in' => 'Loai quang cao khong hop le.',
            'duration_days.required' => 'Thoi luong quang cao la bat buoc.',
            'duration_days.min' => 'Thoi luong toi thieu la 1 ngay.',
            'duration_days.max' => 'Thoi luong toi da la 365 ngay.',
            'budget.required' => 'Ngan sach la bat buoc.',
            'budget.min' => 'Ngan sach toi thieu la 10000 VND.',
            'currency.size' => 'Ma tien te phai co 3 ky tu.',
            'status.in' => 'Trang thai khong hop le.',
            'notes.max' => 'Ghi chu khong duoc vuot qua 1000 ky tu.',
        ];
    }
}
