@extends('layouts.app')

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kartu Stok Global</h3>
                        <div class="float-right">
                            <a href="{{ route('admin.stock.movements') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-list"></i> Lihat Semua Pergerakan
                            </a>
                            <a href="{{ route('admin.stock.report') }}" class="btn btn-success btn-sm">
                                <i class="fa fa-chart-bar"></i> Laporan Stok
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="stock-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Produk</th>
                                        <th>SKU</th>
                                        <th>Tipe</th>
                                        <th>Total Variants</th>
                                        <th>Total Stok</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->sku }}</td>
                                            <td>
                                                @if($product->type == 'simple')
                                                    <span class="badge badge-primary">Simple</span>
                                                @else
                                                    <span class="badge badge-info">Configurable</span>
                                                @endif
                                            </td>
                                            <td>{{ $product->productVariants->count() }}</td>
                                            <td>
                                                @if($product->productVariants->count() > 0)
                                                    {{ $product->productVariants->sum('stock') }}
                                                @elseif($product->productInventory)
                                                    {{ $product->productInventory->qty }}
                                                @else
                                                    0
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.stock.product', $product->id) }}" class="btn btn-sm btn-info" title="Lihat Kartu Stok">
                                                        <i class="fa fa-chart-line"></i> Kartu Stok
                                                    </a>
                                                    @if($product->productVariants->count() > 0)
                                                        @foreach($product->productVariants as $variant)
                                                            <a href="{{ route('admin.stock.show', $variant->id) }}" class="btn btn-sm btn-secondary" title="Detail Variant">
                                                                <i class="fa fa-eye"></i> V{{ $loop->iteration }}
                                                            </a>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data produk</td>
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

@push('style-alt')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
@endpush

@push('script-alt')
<script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $("#stock-table").DataTable({
        "order": [[ 5, "desc" ]],
        "pageLength": 25
    });
});
</script>
@endpush