<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Listing;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $listingId = $this->route('listing') ? $this->route('listing')->id : null;
        $userId = $this->user()?->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // Kiểm tra trùng title trong cùng user (không cho đăng bài trùng tên)
                function ($attribute, $value, $fail) use ($listingId, $userId) {
                    $query = Listing::where('user_id', $userId)
                        ->where('title', $value);
                    
                    // Nếu đang update, loại trừ listing hiện tại
                    if ($listingId) {
                        $query->where('id', '!=', $listingId);
                    }
                    
                    if ($query->exists()) {
                        $fail('Bạn đã có bài đăng với tiêu đề này. Vui lòng chọn tiêu đề khác.');
                    }
                },
            ],
            'slug' => 'nullable|string|max:255|unique:listings,slug,' . $listingId,
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'type' => 'nullable|in:sell,buy,service',
            'price_cents' => 'required|integer|min:0',
            'currency' => 'nullable|string|max:10',
            'stock_qty' => 'nullable|integer|min:0',
            'shop_id' => [
                'nullable',
                'integer',
                Rule::exists('shops', 'id')
            ],
            'images' => 'nullable|array',
            'images.*' => 'nullable|string|max:512',
            'location_text' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|in:draft,published,archived',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài đăng là bắt buộc.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'price_cents.required' => 'Giá sản phẩm là bắt buộc.',
            'price_cents.integer' => 'Giá sản phẩm phải là số nguyên.',
            'price_cents.min' => 'Giá sản phẩm phải lớn hơn hoặc bằng 0.',
            'type.in' => 'Loại tin đăng không hợp lệ.',
            'stock_qty.integer' => 'Số lượng phải là số nguyên.',
            'stock_qty.min' => 'Số lượng phải lớn hơn hoặc bằng 0.',
            'shop_id.exists' => 'Cửa hàng không tồn tại.',
            'images.array' => 'Hình ảnh phải là mảng.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
