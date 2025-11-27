<?php
// config/reports.php
return [
    // TÊN BẢNG theo .sql 
    'tables' => [
        'orders'  => 'orders',           // ví dụ: 'don_hang'
        'events'  => 'analytics_events', // ví dụ: 'su_kien_phan_tich'
    ],

    // TÊN CỘT theo .sql của em
    'columns' => [
        'orders' => [
            'total'      => 'total_amount',   // ví dụ: 'tong_tien'
            'status'     => 'status',         // ví dụ: 'trang_thai'
            'created_at' => 'created_at',     // ví dụ: 'ngay_tao'
        ],
        'events' => [
            'created_at'    => 'created_at',  // ví dụ: 'thoi_diem'
            'session_id'    => 'session_id',
            'user_id'       => 'user_id',
            'type'          => 'type',        // visit/impression/click/...
            'route'         => 'route',
            'ad_campaign_id'=> 'ad_campaign_id',
            'utm_source'    => 'utm_source',
            'utm_medium'    => 'utm_medium',
            'utm_campaign'  => 'utm_campaign',
            'value'         => 'value',       // doanh thu nếu có
        ],
    ],
];
