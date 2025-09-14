@extends('layouts.app')

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kartu Stok - {{ $product->name }}</h3>
                        <div class="float-right">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Informasi Produk:</strong><br>
                                <strong>Nama:</strong> {{ $product->name }}<br>
                                <strong>SKU:</strong> {{ $product->sku }}<br>
                                <strong>Tipe:</strong> {{ $product->type }}<br>
                                <strong>Harga Jual:</strong> Rp {{ number_format($product->price) }}<br>
                                <strong>Harga Beli:</strong> Rp {{ number_format($product->harga_beli) }}
                            </div>
                            <div class="col-md-6">
                                <strong>Ringkasan Stok:</strong><br>
                                @if($variants->count() > 0)
                                    @foreach($variants as $variant)
                                        <div class="mb-2 p-2 border rounded">
                                            <strong>Variant:</strong> 
                                            @if($variant->variantAttributes->count() > 0)
                                                {{ $variant->variantAttributes->pluck('attribute_value')->implode(', ') }}
                                            @else
                                                Default
                                            @endif
                                            <br>
                                            <strong>Stok Saat Ini:</strong> {{ $variant->stock }}
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">Tidak ada variant</span>
                                @endif
                            </div>
                        </div>

                        <h5>Riwayat Pergerakan Stok</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Variant</th>
                                        <th>Tipe</th>
                                        <th>Jumlah</th>
                                        <th>Stok Lama</th>
                                        <th>Stok Baru</th>
                                        <th>Keterangan</th>
                                        <th>Referensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('d/m/Y H:i:s') }}</td>
                                            <td>
                                                @if($movement->variant && $movement->variant->variantAttributes->count() > 0)
                                                    {{ $movement->variant->variantAttributes->pluck('attribute_value')->implode(', ') }}
                                                @else
                                                    Default
                                                @endif
                                            </td>
                                            <td>
                                                @if($movement->movement_type == 'in')
                                                    <span class="badge badge-success">Masuk</span>
                                                @else
                                                    <span class="badge badge-danger">Keluar</span>
                                                @endif
                                            </td>
                                            <td>{{ $movement->quantity }}</td>
                                            <td>{{ $movement->old_stock }}</td>
                                            <td>{{ $movement->new_stock }}</td>
                                            <td>{{ $movement->reason }}</td>
                                            <td>
                                                @if($movement->reference_type && $movement->reference_id)
                                                    {{ ucfirst($movement->reference_type) }} #{{ $movement->reference_id }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">Belum ada pergerakan stok</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection