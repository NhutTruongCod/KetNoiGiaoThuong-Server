<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeHub - Sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { transition: 0.3s; }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center">Danh sách sản phẩm</h1>
    <div class="row">
        @forelse($products as $p)
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                @if($p->productImages->first())
                    <img src="{{ $p->productImages->first()->url }}" 
                         class="card-img-top" 
                         style="height:200px; object-fit:cover;" 
                         alt="{{ $p->title }}">
                @else
                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                         style="height:200px;">
                        <span>Không có ảnh</span>
                    </div>
                @endif
                <div class="card-body d-flex flex-column">
                   <h6 class="card-title">{{ Str::limit($p->title, 50) }}</h6>
                    <p class="text-danger fw-bold">{{ number_format($p->price) }}₫</p>
                    <small class="text-muted">Cửa hàng: {{ $p->shop->name ?? 'Chưa có' }}</small>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center">
            <p>Chưa có sản phẩm nào.</p>
        </div>
        @endforelse
    </div>
</div>
</body>
</html>