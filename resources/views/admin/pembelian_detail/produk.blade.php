<div class="modal fade" id="modal-produk" tabindex="-1" role="dialog" aria-labelledby="modal-produk">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pilih Produk</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered table-produk">
                    <thead>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Type</th>
                        <th>Harga Beli</th>
                        <th>Stok Sekarang</th>
                        <th><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>
                        @foreach ($produk as $key => $item)
                            <tr>
                                <td width="5%">{{ $key+1 }}</td>
                                <td><span class="label label-success">{{ $item->id }}</span></td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    @if($item->type == 'configurable')
                                        <span class="label label-info">Configurable</span>
                                    @else
                                        <span class="label label-primary">Simple</span>
                                    @endif
                                </td>
                                <td>{{ number_format($item->harga_beli ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    @if($item->type == 'configurable')
                                        {{ $item->productVariants->sum('stock') }}
                                    @else
                                        {{ $item->productInventory->qty ?? 0 }}
                                    @endif
                                </td>
                                <td>
                                    @if($item->type == 'configurable')
                                        <button type="button" class="btn btn-info btn-xs btn-flat"
                                            onclick="showVariants('{{ $item->id }}')">
                                            <i class="fa fa-list"></i>
                                            Pilih Variant
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-xs btn-flat"
                                            onclick="pilihProduk('{{ $item->id }}', null)">
                                            <i class="fa fa-check-circle"></i>
                                            Pilih
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-variant" tabindex="-1" role="dialog" aria-labelledby="modal-variant">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pilih Variant</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="variant-content">
                    <p class="text-center">Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>
