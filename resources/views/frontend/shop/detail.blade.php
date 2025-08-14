@extends('frontend.layouts')
@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Shop Detail</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item active text-white">Shop Detail</li>
        </ol>
    </div>

    <div class="container-fluid py-5 mt-5">
        <div class="container py-5">
            <div class="row g-4 mb-5">
                <div class="col-lg-8 col-xl-9">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded">
                                @if ($product->products->productImages->count() > 0)
                                    @if ($product->products->productImages->count() > 1)
                                        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-indicators">
                                                @foreach ($product->products->productImages as $key)
                                                    {{--  <?php  echo $key; ?>  --}}
                                                    <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="{{ $loop->index }}" class="active"></button>
                                                @endforeach
                                            </div>
                                            <div class="carousel-inner">
                                                @foreach($product->products->productImages as $key => $images)
                                                <div class="carousel-item {{$key == 0 ? 'active' : '' }}">
                                                    <img src="{{ asset('storage/'. $images->path) }}" class="d-block w-100"  alt="...">
                                                </div>
                                                @endforeach
                                            </div>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                        </div>
                                    @else
                                        <img src="{{ asset('storage/'. $product->products->productImages->first()->path) }}" class="img-fluid rounded" alt="Image">
                                    @endif
                                @else
                                <img src="{{ asset('images/placeholder.jpg') }}" class="img-fluid rounded" alt="Image">
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h4 class="fw-bold mb-3">{{ $product->products->name }}</h4>
                            <p class="mb-3">Category: {{ $product->categories->name }}</p>
                            <p class="mb-3">Stock: {{ $product->products->productInventory->qty }}</p>
                            <h5 class="fw-bold mb-3">Rp. {{ number_format($product->products->price) }}</h5>
                            <div class="d-flex mb-4">
                            </div>
                            <b class="mb-4">{{ $product->products->short_description }}</b>
                            @if ($product->products->productInventory != null)
                                <p>Stok : {{ $product->products->productInventory->qty }}</p>
                            @endif
                            
                            @if($product->products->type == 'configurable' && count($configurable_attributes) > 0)
                                <div class="product-attributes mb-4">
                                    <h6>Pilih Varian Produk:</h6>
                                    @foreach($configurable_attributes as $attribute)
                                        <div class="attribute-group mb-3">
                                            <label class="form-label fw-bold">{{ $attribute->name }}:</label>
                                            @foreach($attribute->attribute_variants as $variant)
                                                <div class="variant-group mb-2">
                                                    <h6 class="text-muted">{{ $variant->name }}:</h6>
                                                    <select class="form-select attribute-select" 
                                                            data-attribute-id="{{ $attribute->id }}" 
                                                            data-variant-id="{{ $variant->id }}">
                                                        <option value="">Pilih {{ $variant->name }}</option>
                                                        @foreach($variant->attribute_options as $option)
                                                            <option value="{{ $option->id }}" 
                                                                    data-product-variant-id="{{ $product->products->variants->where('productAttributeValues.attribute_option_id', $option->id)->first()->id ?? '' }}">
                                                                {{ $option->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            <p class="mb-4">{!! $product->products->description !!}</p>
                            <div class="input-group quantity mb-5" style="width: 100px;">
                            </div>
                            <a class="btn border border-secondary rounded-pill px-4 py-2 mb-4 text-primary add-to-card" product-id="{{ $product->products->id }}" product-type="{{ $product->products->type }}" product-slug="{{ $product->products->slug }}">
                                <i class="fa fa-shopping-bag me-2 text-primary"></i>
                                Add to cart
                            </a>
                        </div>
                        <div class="col-lg-12">
                            <nav>
                                <div class="nav nav-tabs mb-3">
                                    <button class="nav-link border-white border-bottom-0" type="button" role="tab"
                                        id="nav-about-tab" data-bs-toggle="tab" data-bs-target="#nav-about"
                                        aria-controls="nav-about" aria-selected="true">Description</button>
                                    <button class="nav-link border-white border-bottom-0" type="button" role="tab"
                                        id="nav-links-tab" data-bs-toggle="tab" data-bs-target="#nav-links"
                                        aria-controls="nav-links" aria-selected="true">Link Product</button>
                                </div>
                            </nav>
                            <div class="tab-content mb-5">
                                <div class="tab-pane active" id="nav-about" role="tabpanel" aria-labelledby="nav-about-tab">
                                    <b>{{ $product->products->short_description }} </b>
                                    @if ($product->products->productInventory != null)
                                        <p>Stok : {{ $product->products->productInventory->qty }}</p>
                                    @endif
                                    <p>{!! $product->products->description !!}</p>
                                    <div class="px-2">
                                        <div class="row g-4">
                                            <div class="col-6">
                                                <div class="row bg-light align-items-center text-center justify-content-center py-2">
                                                    <div class="col-6">
                                                        <p class="mb-0">Weight</p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-0">{{ $product->products->weight }} gram</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="nav-links" role="tabpanel" aria-labelledby="nav-links-tab">
                                    <a href="{{ $product->products->link1 }}"><p>Product Link 1 : {{ $product->products->link1 }} </p></a>
                                    <a href="{{ $product->products->link2 }}"><p>Product Link 2 : {{ $product->products->link2 }}</p></a>
                                    <a href="{{ $product->products->link3 }}"><p>Product Link 3 : {{ $product->products->link3 }}</p></a>
                                </div>
                                <div class="tab-pane" id="nav-mission" role="tabpanel" aria-labelledby="nav-mission-tab">
                                    <div class="d-flex">
                                        <img src="img/avatar.jpg" class="img-fluid rounded-circle p-3" style="width: 100px; height: 100px;" alt="">
                                        <div class="">
                                            <p class="mb-2" style="font-size: 14px;">April 12, 2024</p>
                                            <div class="d-flex justify-content-between">
                                                <h5>Sam Peters</h5>
                                                <div class="d-flex mb-3">
                                                    <i class="fa fa-star text-secondary"></i>
                                                    <i class="fa fa-star text-secondary"></i>
                                                    <i class="fa fa-star text-secondary"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                </div>
                                            </div>
                                            <p class="text-dark">The generated Lorem Ipsum is therefore always free from repetition injected humour, or non-characteristic
                                                words etc. Susp endisse ultricies nisi vel quam suscipit </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="nav-vision" role="tabpanel">
                                    <p class="text-dark">Tempor erat elitr rebum at clita. Diam dolor diam ipsum et tempor sit. Aliqu diam
                                        amet diam et eos labore. 3</p>
                                    <p class="mb-0">Diam dolor diam ipsum et tempor sit. Aliqu diam amet diam et eos labore.
                                        Clita erat ipsum et lorem et sit</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-3">
                    <div class="row g-4 fruite">
                        <div class="col-lg-12">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const attributeSelects = document.querySelectorAll('.attribute-select');
            const addToCartBtn = document.querySelector('.add-to-card');
            
            attributeSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    updateProductVariant();
                });
            });
            
            function updateProductVariant() {
                const selectedAttributes = {};
                let allSelected = true;
                
                attributeSelects.forEach(function(select) {
                    if (select.value) {
                        selectedAttributes[select.dataset.variantId] = select.value;
                    } else {
                        allSelected = false;
                    }
                });
                
                if (allSelected && Object.keys(selectedAttributes).length > 0) {
                    // Here you can update price, stock, or other variant-specific data
                    console.log('Selected attributes:', selectedAttributes);
                    
                    // Update add to cart button to include selected attributes
                    addToCartBtn.setAttribute('data-selected-attributes', JSON.stringify(selectedAttributes));
                    addToCartBtn.classList.remove('disabled');
                } else {
                    addToCartBtn.removeAttribute('data-selected-attributes');
                    if (attributeSelects.length > 0) {
                        addToCartBtn.classList.add('disabled');
                    }
                }
            }
            
            // Initialize the state
            updateProductVariant();
        });
    </script>
@endsection
