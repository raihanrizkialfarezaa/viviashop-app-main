@extends('layouts.app')

@section('content')

    <!-- Main content -->
    <section class="content pt-4">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Buat Produk</h3>
                <a href="{{ route('admin.products.index')}}" class="btn btn-success shadow-sm float-right"> <i class="fa fa-arrow-left"></i> Kembali</a>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <form method="post" action="{{ route('admin.products.store') }}">
                    @csrf
                    <div class="form-group row border-bottom pb-4">
                        <label for="type" class="col-sm-2 col-form-label">Tipe Kategori</label>
                        <div class="col-sm-10">
                            <select class="form-control product-type" name="type" id="type">
                                @foreach($types as $value => $type)
                                    <option {{ old('type') == $value ? 'selected' : '' }} value="{{ $value }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row border-bottom pb-4">
                        <label for="sku" class="col-sm-2 col-form-label">SKU</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" placeholder="Dicetak di produk, tercantum di kemasan, dan terbaca oleh POS" name="sku" value="{{ old('sku') }}" id="sku">
                        </div>
                    </div>
                    <div class="form-group row border-bottom pb-4">
                        <label for="name" class="col-sm-2 col-form-label">Nama Produk</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="name" value="{{ old('name') }}" id="name">
                        </div>
                    </div>
                    <div class="form-group row border-bottom pb-4">
                        <label for="category_id" class="col-sm-2 col-form-label">Kategori Produk</label>
                        <div class="col-sm-10">
                        <!-- sampai sini -->
                          <select class="form-control select-multiple"  multiple="multiple" name="category_id[]" id="category_id">
                            @foreach($categories as $category)
                              <option {{ old('category_id') == $category->id ? 'selected' : null }} value="{{ $category->id }}"> {{ $category->name }}</option>
                            @endforeach
                          </select>
                        </div>
                    </div>
                    <div class="form-group row border-bottom pb-4">
                        <label for="weight" class="col-sm-2 col-form-label">Berat (kg)</label>
                        <div class="col-sm-10">
                          <input type="number" step="0.01" class="form-control" name="weight" value="{{ old('weight') }}" id="weight" placeholder="Contoh: 1.5 (untuk 1.5 kg)">
                        </div>
                    </div>
                    <div class="configurable-attributes">
                      @if(count($configurable_attributes) > 0)
                        <p class="text-primary mt-4">Konfigurasi Attribute Produk</p>
                        <hr/>
                        @foreach($configurable_attributes as $configurable_attribute)
                          <div class="form-group row border-bottom pb-4">
                              <label for="{{ $configurable_attribute->code }}" class="col-sm-2 col-form-label">{{ $configurable_attribute->name }}</label>
                              <div class="col-sm-10">
                                @foreach($configurable_attribute->attribute_variants as $variant)
                                  <div class="variant-section mb-3">
                                    <h6 class="text-info">{{ $variant->name }}</h6>
                                    <select class="form-control select-multiple" multiple="multiple" name="{{ $configurable_attribute->code }}[]" id="{{ $configurable_attribute->code }}_{{ $variant->id }}">
                                      @foreach($variant->attribute_options as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                @endforeach
                              </div>
                          </div>
                        @endforeach
                      @endif
                    </div>
                    <button type="submit" class="btn btn-success">Save</button>
                </form>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection

@push('style-alt')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('script-alt')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
      $('.select-multiple').select2();
      function showHideConfigurableAttributes() {
			var productType = $(".product-type").val();
            console.log(productType);

			if (productType == 'configurable') {
				$(".configurable-attributes").show();
			} else {
				$(".configurable-attributes").hide();
			}
		}
		$(function(){
			showHideConfigurableAttributes();
			$(".product-type").change(function() {
				showHideConfigurableAttributes();
			});
		});
</script>
@endpush
