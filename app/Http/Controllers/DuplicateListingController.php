<?php

namespace App\Http\Controllers;

use App\Models\DuplicateListingGroup;
use App\Models\Listing;
use App\Http\Requests\DuplicateListingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateListingController extends Controller
{
    /**
     * GET – Lấy danh sách các nhóm trùng lặp
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DuplicateListingGroup::query();

            // Lọc theo status
            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            // Sắp xếp theo thời gian phát hiện mới nhất
            $query->orderBy('created_at', 'desc');

            // Phân trang hoặc lấy tất cả
            if ($request->has('limit')) {
                $limit = $request->get('limit', 15);
                $duplicateGroups = $query->paginate($limit);
                
                // Sửa lỗi ở đây: Thay vì getCollection(), sử dụng through() hoặc transform items
                $transformedData = collect($duplicateGroups->items())->map(function ($group) {
                    return $this->transformDuplicateGroup($group);
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $transformedData,
                    'pagination' => [
                        'current_page' => $duplicateGroups->currentPage(),
                        'per_page' => $duplicateGroups->perPage(),
                        'total' => $duplicateGroups->total(),
                        'last_page' => $duplicateGroups->lastPage(),
                    ]
                ]);
            } else {
                $duplicateGroups = $query->get();
                
                $transformedData = $duplicateGroups->map(function ($group) {
                    return $this->transformDuplicateGroup($group);
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $transformedData
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * GET – Lấy chi tiết nhóm trùng lặp theo group_id
     */
    public function show($group_id): JsonResponse
    {
        try {
            $duplicateGroup = DuplicateListingGroup::find($group_id);

            if (!$duplicateGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy nhóm trùng lặp'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformDuplicateGroup($duplicateGroup, true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ'
            ], 500);
        }
    }

    /**
     * POST – Thêm nhóm trùng lặp mới
     */
    public function store(DuplicateListingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Kiểm tra xem các listing đã tồn tại trong nhóm trùng lặp khác chưa
            $existingGroups = $this->checkExistingDuplicateGroups($request->duplicate_items);
            
            if ($existingGroups->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số bài đăng đã tồn tại trong nhóm trùng lặp khác',
                    'existing_groups' => $existingGroups
                ], 409);
            }

            $duplicateGroup = DuplicateListingGroup::create([
                'duplicate_items' => $request->duplicate_items,
                'detected_by' => $request->detected_by,
                'note' => $request->note,
                'confidence_score' => $request->confidence_score,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm nhóm bản ghi trùng lặp mới.',
                'group_id' => $duplicateGroup->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE – Xóa nhóm bản ghi trùng lặp
     */
    public function destroy($group_id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $duplicateGroup = DuplicateListingGroup::find($group_id);

            if (!$duplicateGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy nhóm trùng lặp'
                ], 404);
            }

            $duplicateGroup->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa nhóm bản ghi trùng lặp thành công.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH – Cập nhật trạng thái nhóm trùng lặp
     */
    public function updateStatus($group_id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,resolved,ignored'
            ]);

            $duplicateGroup = DuplicateListingGroup::find($group_id);

            if (!$duplicateGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy nhóm trùng lặp'
                ], 404);
            }

            $duplicateGroup->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => $this->transformDuplicateGroup($duplicateGroup)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST – Tự động phát hiện trùng lặp
     */
    public function autoDetect(): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Logic phát hiện trùng lặp tự động
            $duplicateGroups = $this->findDuplicateListings();

            $createdGroups = [];
            foreach ($duplicateGroups as $group) {
                $duplicateGroup = DuplicateListingGroup::create([
                    'duplicate_items' => $group['listing_ids'],
                    'detected_by' => 'AI',
                    'confidence_score' => $group['confidence_score'],
                    'note' => $group['reason'],
                    'status' => 'pending'
                ]);

                $createdGroups[] = $duplicateGroup->id;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã phát hiện ' . count($createdGroups) . ' nhóm trùng lặp.',
                'created_groups' => $createdGroups
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi phát hiện trùng lặp: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method để transform duplicate group data
     */
    private function transformDuplicateGroup(DuplicateListingGroup $group, $detailed = false)
    {
        $baseData = [
            'group_id' => $group->id,
            'duplicate_items' => $group->duplicate_items_with_details,
            'detected_by' => $group->detected_by,
            'status' => $group->status,
            'detected_at' => $group->created_at->toISOString(),
        ];

        if ($detailed) {
            $baseData['note'] = $group->note;
            $baseData['confidence_score'] = $group->confidence_score;
            $baseData['updated_at'] = $group->updated_at->toISOString();
        }

        return $baseData;
    }

    /**
     * Helper method để kiểm tra listing đã tồn tại trong nhóm khác chưa
     */
    private function checkExistingDuplicateGroups(array $listingIds)
    {
        return DuplicateListingGroup::where('status', 'pending')
            ->where(function ($query) use ($listingIds) {
                foreach ($listingIds as $listingId) {
                    $query->orWhereJsonContains('duplicate_items', $listingId);
                }
            })
            ->get(['id', 'duplicate_items'])
            ->map(function ($group) use ($listingIds) {
                $intersect = array_intersect($listingIds, $group->duplicate_items);
                return [
                    'group_id' => $group->id,
                    'conflicting_listings' => array_values($intersect)
                ];
            })
            ->filter(function ($item) {
                return !empty($item['conflicting_listings']);
            });
    }

    /**
     * Algorithm tự động phát hiện trùng lặp
     */
    private function findDuplicateListings()
    {
        $duplicateGroups = [];
        
        // Lấy tất cả listings active
        $listings = Listing::active()->get(['id', 'title', 'description', 'price', 'store_id']);
        
        // Nhóm theo title tương tự (sử dụng string similarity)
        $groupedByTitle = [];
        
        foreach ($listings as $listing) {
            $normalizedTitle = $this->normalizeText($listing->title);
            $foundGroup = false;
            
            foreach ($groupedByTitle as &$group) {
                $similarity = similar_text($normalizedTitle, $this->normalizeText($group['title']), $percent);
                
                if ($percent > 80) { // 80% similarity
                    $group['listing_ids'][] = $listing->id;
                    $foundGroup = true;
                    break;
                }
            }
            
            if (!$foundGroup) {
                $groupedByTitle[] = [
                    'title' => $listing->title,
                    'listing_ids' => [$listing->id]
                ];
            }
        }
        
        // Tạo duplicate groups từ các nhóm có nhiều hơn 1 listing
        foreach ($groupedByTitle as $group) {
            if (count($group['listing_ids']) > 1) {
                $duplicateGroups[] = [
                    'listing_ids' => $group['listing_ids'],
                    'confidence_score' => 85, // Độ tin cậy dựa trên similarity
                    'reason' => 'Tiêu đề tương tự: ' . $group['title']
                ];
            }
        }
        
        return $duplicateGroups;
    }

    /**
     * Helper để chuẩn hóa text cho comparison
     */
    private function normalizeText($text)
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }
}