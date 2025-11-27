<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $promotionId = $this->route('promotion') ? $this->route('promotion')->id : null;

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'required|url|max:500',
            'status' => 'required|in:active,inactive,expired,upcoming',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_usage' => 'nullable|integer|min:1',
            'promo_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('promotions', 'promo_code')->ignore($promotionId)
            ],
            'is_featured' => 'boolean',
        ];

        // Neu update, cho phep start_date o qua khu
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['start_date'] = 'required|date';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tieu de khuyen mai la bat buoc.',
            'title.max' => 'Tieu de khong duoc vuot qua 255 ky tu.',
            'image_url.required' => 'URL hinh anh la bat buoc.',
            'image_url.url' => 'URL hinh anh khong hop le.',
            'status.required' => 'Trang thai la bat buoc.',
            'status.in' => 'Trang thai khong hop le.',
            'start_date.required' => 'Ngay bat dau la bat buoc.',
            'start_date.after_or_equal' => 'Ngay bat dau phai tu hom nay tro di.',
            'end_date.required' => 'Ngay ket thuc la bat buoc.',
            'end_date.after' => 'Ngay ket thuc phai sau ngay bat dau.',
            'discount_percentage.min' => 'Phan tram giam gia phai lon hon hoac bang 0.',
            'discount_percentage.max' => 'Phan tram giam gia khong duoc vuot qua 100.',
            'min_order_amount.min' => 'Gia tri don hang toi thieu phai lon hon hoac bang 0.',
            'max_usage.min' => 'So lan su dung toi da phai lon hon 0.',
            'promo_code.unique' => 'Ma khuyen mai da ton tai.',
        ];
    }

    protected function prepareForValidation()
    {
        // Tu dong dat status theo ngay neu khong co
        if (!$this->has('status') && $this->has('start_date') && $this->has('end_date')) {
            $today = Carbon::today();
            $startDate = Carbon::parse($this->start_date);
            $endDate = Carbon::parse($this->end_date);

            if ($today->gt($endDate)) {
                $this->merge(['status' => 'expired']);
            } elseif ($today->between($startDate, $endDate)) {
                $this->merge(['status' => 'active']);
            } elseif ($today->lt($startDate)) {
                $this->merge(['status' => 'upcoming']);
            }
        }
    }
}
