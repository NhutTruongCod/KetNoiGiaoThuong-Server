<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    // 1. GET: Liệt kê tất cả thanh toán
    public function index()
    {
        $payments = Payment::with(['order.buyer', 'order.shop'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    // 2. GET: Lấy theo ID
    public function show($id)
    {
        $payment = Payment::with(['order.buyer', 'order.shop'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Thanh toán không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    // 3. POST: Tạo thanh toán → Chuyển sang MOMO Sandbox
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'method' => 'required|in:momo'
        ]);

        $order = Order::find($request->order_id);
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ thanh toán đơn hàng đang chờ'
            ], 400);
        }

        // Tạo thanh toán
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'momo',
            'status' => 'unpaid',
            'amount' => $order->total_amount
        ]);

        // Gọi MOMO Sandbox
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMO";
        $accessKey = "F8BBA842ECF85";
        $secretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";

        $requestId = (string) time();
        $orderId = "order-" . $payment->id . "-" . $requestId;
        $orderInfo = "Thanh toán đơn hàng #" . $order->id;
        $amount = number_format($order->total_amount, 0, '', ''); // CHUỖI SỐ NGUYÊN
        $redirectUrl = url('/momo/callback');
        $ipnUrl = url('/momo/callback');
        $extraData = "";

        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $ipnUrl .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $partnerCode .
            "&redirectUrl=" . $redirectUrl .
            "&requestId=" . $requestId .
            "&requestType=payWithMethod";

        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "TradeHub",
            'storeId' => "TradeHubStore",
            'requestId' => $requestId,
            'amount' => $amount,           // CHUỖI SỐ NGUYÊN
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => 'payWithMethod',
            'signature' => $signature
        ];

        $response = Http::post($endpoint, $data);
        $jsonResponse = $response->json();

        if ($jsonResponse['resultCode'] == 0) {
            return response()->json([
                'success' => true,
                'message' => 'Chuyển hướng đến MOMO',
                'payment_url' => $jsonResponse['payUrl'],
                'payment_id' => $payment->id
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi MOMO: ' . $jsonResponse['message']
            ], 500);
        }
    }
}