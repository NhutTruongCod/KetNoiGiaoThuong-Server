<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

class ReportService
{
    // --- Helpers: lấy tên bảng/cột từ config & quote an toàn ---
    private function T(string $key): string {
        // trả về `tên_bảng` đã được quote
        $name = config("reports.tables.$key");
        return $this->q($name);
    }
    private function C(string $group, string $key): string {
        // trả về `tên_cột` đã được quote
        $name = config("reports.columns.$group.$key");
        return $this->q($name);
    }
    private function q(string $identifier): string {
        // quote `table` hoặc `table.column` an toàn với backtick
        // chấp nhận dạng "schema.table" hoặc "table"
        return implode('.', array_map(fn($p) => '`'.str_replace('`','',$p).'`', explode('.', $identifier)));
    }

    // TRAFFIC: timeseries lượt truy cập & unique visitors
    public function traffic(string $from, string $to, string $groupBy='day', ?string $source=null): array
    {
        $eventsT = $this->T('events');
        $evAt    = $this->C('events','created_at');
        $evSid   = $this->C('events','session_id');
        $evUid   = $this->C('events','user_id');
        $evSrc   = $this->C('events','utm_source');

        [$dateExpr, $orderExpr] = $this->dateExpr("$eventsT.$evAt", $groupBy);

        $q = DB::table(DB::raw($eventsT))
            ->selectRaw("$dateExpr AS period")
            ->selectRaw("COUNT(*) AS visits")
            ->selectRaw("COUNT(DISTINCT COALESCE($evSid, CONCAT('u-', $evUid))) AS unique_visitors")
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to]);

        if ($source) $q->where(DB::raw($evSrc), $source);

        $rows = $q->groupBy('period')->orderByRaw($orderExpr)->get();
        return ['rows' => $rows];
    }

    // TRANSACTIONS: đơn & GMV (sum total)
    public function transactions(string $from, string $to, string $groupBy='day', ?string $status='paid'): array
    {
        $ordersT  = $this->T('orders');
        $oAt      = $this->C('orders','created_at');
        $oTotal   = $this->C('orders','total');
        $oStatus  = $this->C('orders','status');

        [$dateExpr, $orderExpr] = $this->dateExpr("$ordersT.$oAt", $groupBy);

        $q = DB::table(DB::raw($ordersT))
            ->selectRaw("$dateExpr AS period")
            ->selectRaw("COUNT(*) AS orders_count")
            ->selectRaw("SUM($oTotal) AS gmv")
            ->whereBetween(DB::raw("$ordersT.$oAt"), [$from, $to]);

        if ($status) $q->where(DB::raw($oStatus), $status);

        $rows = $q->groupBy('period')->orderByRaw($orderExpr)->get();
        foreach ($rows as $r) {
            $r->aov = ($r->orders_count > 0) ? (float)$r->gmv / (int)$r->orders_count : 0.0;
        }
        return ['rows' => $rows];
    }

    // ADS: impressions / clicks / CTR
    public function ads(string $from, string $to, string $groupBy='day', ?int $campaignId=null): array
    {
        $eventsT = $this->T('events');
        $evAt    = $this->C('events','created_at');
        $evType  = $this->C('events','type');
        $evCamp  = $this->C('events','ad_campaign_id');

        [$dateExpr, $orderExpr] = $this->dateExpr("$eventsT.$evAt", $groupBy);

        $q = DB::table(DB::raw($eventsT))
            ->selectRaw("$dateExpr AS period")
            ->selectRaw("SUM(CASE WHEN $evType = 'impression' THEN 1 ELSE 0 END) AS impressions")
            ->selectRaw("SUM(CASE WHEN $evType = 'click' THEN 1 ELSE 0 END) AS clicks")
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to]);

        if ($campaignId) $q->where(DB::raw($evCamp), $campaignId);

        $rows = $q->groupBy('period')->orderByRaw($orderExpr)->get();
        foreach ($rows as $r) $r->ctr = ($r->impressions > 0) ? round($r->clicks / $r->impressions, 4) : 0;
        return ['rows' => $rows];
    }

    // BEHAVIOR: top-pages / top-sources / funnels
    public function behavior(string $from, string $to, string $metric='top-pages'): array
    {
        $eventsT = $this->T('events');
        $evAt    = $this->C('events','created_at');
        $evRoute = $this->C('events','route');
        $evSrc   = $this->C('events','utm_source');
        $evType  = $this->C('events','type');

        $q = DB::table(DB::raw($eventsT))->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to]);

        if ($metric === 'top-pages') {
            $rows = $q->select(DB::raw("$evRoute AS route"), DB::raw('COUNT(*) AS visits'))
                      ->groupBy(DB::raw($evRoute))->orderByDesc('visits')->limit(20)->get();
            return ['rows' => $rows];
        }

        if ($metric === 'top-sources') {
            $rows = $q->select(DB::raw("$evSrc AS utm_source"), DB::raw('COUNT(*) AS visits'))
                      ->groupBy(DB::raw($evSrc))->orderByDesc('visits')->limit(20)->get();
            return ['rows' => $rows];
        }

        if ($metric === 'funnels') {
            $steps = ['view_item','add_to_cart','checkout','purchase'];
            $res = [];
            foreach ($steps as $s) {
                $res[] = [
                    'step'  => $s,
                    'count' => DB::table(DB::raw($eventsT))
                                ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to])
                                ->where(DB::raw($evType), $s)->count(),
                ];
            }
            return ['rows' => $res];
        }

        return ['rows' => []];
    }

    // OVERVIEW: KPI tổng hợp nhanh cho dashboard
    public function overview(string $from, string $to): array
    {
        $eventsT = $this->T('events');
        $evAt    = $this->C('events','created_at');
        $evSid   = $this->C('events','session_id');
        $evUid   = $this->C('events','user_id');
        $evType  = $this->C('events','type');

        $ordersT = $this->T('orders');
        $oAt     = $this->C('orders','created_at');
        $oTotal  = $this->C('orders','total');
        $oStatus = $this->C('orders','status');

        $visits = DB::table(DB::raw($eventsT))
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to])->count();

        $unique = DB::table(DB::raw($eventsT))
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to])
            ->select(DB::raw("COUNT(DISTINCT COALESCE($evSid, CONCAT('u-', $evUid))) AS uv"))
            ->value('uv');

        $orders = DB::table(DB::raw($ordersT))
            ->whereBetween(DB::raw("$ordersT.$oAt"), [$from, $to])
            ->whereIn(DB::raw($oStatus), ['paid','completed'])
            ->selectRaw("COUNT(*) AS cnt, SUM($oTotal) AS gmv")
            ->first();

        $impressions = DB::table(DB::raw($eventsT))
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to])
            ->where(DB::raw($evType), 'impression')->count();

        $clicks = DB::table(DB::raw($eventsT))
            ->whereBetween(DB::raw("$eventsT.$evAt"), [$from, $to])
            ->where(DB::raw($evType), 'click')->count();

        return [
            'visits'           => (int)$visits,
            'unique_visitors'  => (int)$unique,
            'orders'           => (int)($orders->cnt ?? 0),
            'gmv'              => (float)($orders->gmv ?? 0),
            'aov'              => ($orders->cnt ?? 0) > 0 ? round(($orders->gmv ?? 0)/$orders->cnt, 2) : 0,
            'ad_impressions'   => (int)$impressions,
            'ad_clicks'        => (int)$clicks,
            'ctr'              => $impressions > 0 ? round($clicks/$impressions, 4) : 0,
            'conversion_rate'  => $visits > 0 ? round(($orders->cnt ?? 0)/$visits, 4) : 0,
        ];
    }

    // --- build biểu thức thời gian theo groupBy ---
    private function dateExpr(string $fullCol, string $groupBy): array
    {
        return match ($groupBy) {
            'hour'  => ["DATE_FORMAT($fullCol, '%Y-%m-%d %H:00:00')", "1"],
            'day'   => ["DATE($fullCol)", "1"],
            'week'  => ["YEARWEEK($fullCol, 3)", "YEARWEEK($fullCol, 3)"],
            'month' => ["DATE_FORMAT($fullCol, '%Y-%m-01')", "1"],
            default => ["DATE($fullCol)", "1"],
        };
    }
}
