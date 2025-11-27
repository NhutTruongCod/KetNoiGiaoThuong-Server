<?php

namespace Database\Seeders;

use App\Models\DuplicateListingGroup;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class DuplicateListingSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy một số listings để tạo dữ liệu trùng lặp mẫu
        $listings = Listing::limit(10)->get();
        
        if ($listings->count() >= 4) {
            $duplicateGroups = [
                [
                    'duplicate_items' => [$listings[0]->id, $listings[1]->id],
                    'detected_by' => 'AI',
                    'note' => 'Các bài đăng có cùng tiêu đề và hình ảnh.',
                    'confidence_score' => 90,
                    'status' => 'pending'
                ],
                [
                    'duplicate_items' => [$listings[2]->id, $listings[3]->id, $listings[4]->id],
                    'detected_by' => 'manual',
                    'note' => 'Phát hiện thủ công bởi quản trị viên.',
                    'confidence_score' => 75,
                    'status' => 'resolved'
                ],
                [
                    'duplicate_items' => [$listings[5]->id, $listings[6]->id],
                    'detected_by' => 'system',
                    'note' => 'Hệ thống tự động phát hiện.',
                    'confidence_score' => 85,
                    'status' => 'ignored'
                ],
            ];

            foreach ($duplicateGroups as $group) {
                DuplicateListingGroup::create($group);
            }
        }
    }
}