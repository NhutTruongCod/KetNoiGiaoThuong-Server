<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'listing_id' => 'required|exists:listings,id',
            'type' => 'required|in:featured,top_search,homepage_banner,category_banner',
            'duration_days' => 'required|integer|min:1|max:90',
            'budget' => 'required|numeric|min:50000',
            'target_audience' => 'nullable|array',
            'target_audience.locations' => 'nullable|array',
            'target_audience.age_range' => 'nullable|array|size:2',
            'target_audience.interests' => 'nullable|array',
            'start_date' => 'nullable|date|after_or_equal:today',
        ];

        if ($isUpdate) {
            $rules['listing_id'] = 'sometimes|exists:listings,id';
            $rules['type'] = 'sometimes|in:featured,top_search,homepage_banner,category_banner';
            $rules['duration_days'] = 'sometimes|integer|min:1|max:90';
            $rules['budget'] = 'sometimes|numeric|min:50000';
            $rules['start_date'] = 'sometimes|date';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'listing_id.required' => 'Listing ID là bắt buộc.',
            'listing_id.exists' => 'Listing không tồn tại.',
            'type.required' => 'Loại quảng cáo là bắt buộc.',
            'type.in' => 'Loại quảng cáo không hợp lệ.',
            'duration_days.required' => 'Số ngày chạy là bắt buộc.',
            'duration_days.min' => 'Số ngày chạy tối thiểu là 1.',
            'duration_days.max' => 'Số ngày chạy tối đa là 90.',
            'budget.required' => 'Ngân sách là bắt buộc.',
            'budget.min' => 'Ngân sách tối thiểu là 50,000 VND.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi.',
        ];
    }

    protected function prepareForValidation()
    {
        // Set default start_date to today if not provided
        if (!$this->has('start_date')) {
            $this->merge(['start_date' => Carbon::today()->toDateString()]);
        }
    }
}
