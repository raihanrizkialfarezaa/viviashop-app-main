@extends('frontend.layouts')
@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Checkout</h1>
        <ol class="breadcrumb justify-center mb-0">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item active text-white">Checkout</li>
        </ol>
    </div>


    <!-- Checkout Page Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <h1 class="mb-4">Billing details</h1>
            <form action="{{ route('orders.checkout') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row g-5">
                    <div class="col-md-12 col-lg-6 col-xl-7">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <div class="form-item w-100">
                                    <label>Nama <span class="required">*</span></label>
									<input type="text" class="form-control" name="name" value="{{ old('name', auth()->user()->name) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Address <sup>*</sup></label>
							<input type="text" class="form-control" name="address1" value="{{ old('address1', auth()->user()->address1) }}">
                            <br>
							<input type="text" class="form-control" name="address2" value="{{ old('address2', auth()->user()->address2) }}">
                        </div>
                        <div class="form-item">
                            <label>Provinsi<span class="required">*</span></label>
                            <select name="province_id" class="form-control" id="shipping-province">
                                <option value="">-- Pilih Provinsi --</option>
                                @foreach($provinces as $id => $province)
                                    <option {{ auth()->user()->province_id == $id ? 'selected' : null }} value="{{ $id }}">{{ $province }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-item">
                            <label>City<span class="required">*</span></label>
                            <select name="shipping_city_id" class="form-control" id="shipping-city">
                                <option value="">-- Pilih Kota --</option>
                                @if($cities)
                                    @foreach($cities as $id => $city)
                                        <option {{ auth()->user()->city_id == $id ? 'selected' : null }} value="{{ $id }}">{{ $city }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-item">
                            <label>District<span class="required">*</span></label>
                            <select name="shipping_district_id" class="form-control" id="shipping-district">
                                <option value="">-- Pilih Kecamatan --</option>
                                @if($districts)
                                    @foreach($districts as $id => $district)
                                        <option {{ auth()->user()->district_id == $id ? 'selected' : null }} value="{{ $id }}">{{ $district }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-item">
                            <label>Postcode / Zip <span class="required">*</span></label>
							<input type="text" class="form-control" name="postcode" value="{{ old('postcode', auth()->user()->postcode) }}">
                        </div>
                        <div class="form-item">
                            <label>Phone  <span class="required">*</span></label>
							<input type="text" class="form-control" name="phone" value="{{ old('phone', auth()->user()->phone) }}">
                        </div>
                        <div class="form-item">
                            <label>Email Address </label>
                            <input type="text" class="form-control" name="email" value="{{ old('email', auth()->user()->email) }}">
                        </div>
                        <div class="form-item">
                            <label>Order Notes </label>
                            <input type="textarea" class="form-control" name="note" value="{{ old('note') }}">
                        </div>
                        <div class="form-item">
                            <label>Order Attachments (if exists) </label>
                            <input type="file" onchange="" id="image" class="form-control" name="attachments">
                        </div>
                        <div class="form-item mt-4 d-none image-item">
                            <label for="">Preview Image</label>
                            <img src="" class="img-preview img-fluid" alt="">
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-6 col-xl-5">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Products</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $item)
                                    @php
                                        $product = isset($item->model->parent) ? $item->model->parent : $item->model;
                                        $image = !empty($product->productImages->first()) ? asset('storage/'.$product->productImages->first()->path) : asset('themes/ezone/assets/img/cart/3.jpg')
                                    @endphp
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <img src="{{ $image }}" class="img-fluid rounded" style="width: 90px; height: 90px;" alt="">
                                                </div>
                                            </th>
                                            <td class="py-5">{{ $product->name }}</td>
                                            <td class="py-5">Rp. {{ number_format($product->price) }}</td>
                                            <td class="py-5">{{ $item->qty }}</td>
                                            <td class="py-5">Rp. {{ number_format($item->price * $item->qty) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2">The cart is empty! </td>
                                        </tr>
                                    @endforelse
                                    <tr>
                                        <th scope="row">
                                        </th>
                                        <td class="py-5"></td>
                                        <td class="py-5"></td>
                                        <td class="py-5">
                                            <p class="mb-0 text-dark py-3">Subtotal</p>
                                        </td>
                                        <td class="py-5">
                                            <div class="py-3 border-bottom border-top">
                                                <p class="mb-0 text-dark">Rp. {{ Cart::subtotal(0, ",", ".") }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                        </th>
                                        <td class="py-5">
                                            <p class="mb-0 text-dark py-4">Delivery Method</p>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input type="radio" class="form-check-input" id="delivery-self" name="delivery_method" value="self">
                                                <label class="form-check-label" for="delivery-self">
                                                    Self Pickup - Rp. 0 (Same Day)
                                                </label>
                                            </div>
                                            <div class="form-check mt-2">
                                                <input type="radio" class="form-check-input" id="delivery-courier" name="delivery_method" value="courier">
                                                <label class="form-check-label" for="delivery-courier">
                                                    Courier Delivery
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="shipping-row" style="display: none;">
                                        <th scope="row">
                                        </th>
                                        <td class="py-5">
                                            <p class="mb-0 text-dark py-4">Shipping</p>
                                        </td>
                                        <td><select class="form-control" id="shipping-cost-option" name="shipping_service">

										</select></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                        </th>
                                        <td class="py-5">
                                            <p class="mb-0 text-dark py-4">Unique Payment Code</p>
                                        </td>
                                        <td><input type="number" name="unique_code" value="{{ $unique_code }}" class="form-control unique_code" readonly></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                        </th>
                                        <td class="py-5">
                                            <p class="mb-0 text-dark text-uppercase py-3">TOTAL</p>
                                        </td>
                                        <td class="py-5"></td>
                                        <td class="py-5"></td>
                                        <td class="py-5">
                                            <div class="py-3 border-bottom border-top">
                                                <p class="mb-0 text-dark total-amount">{{ number_format((int)Cart::subtotal(0,'','')) }}</p>
                                            </div>
                                            <p>harap tunggu nominal berubah sesuai dengan total sebelum checkout</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input type="checkbox" class="form-check-input checkoption bg-primary border-0" id="Transfer-1" name="payment_method" value="manual">
                                    <label class="form-check-label" for="Transfer-1">Direct Bank Transfer</label>
                                </div>
                                <p class="text-start text-dark">You can pay to us via : <br> 1. BCA : 01401840112(Ahmad Sambudi) <br> 2. BCA : 01401840112(Ahmad Sambudi)</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input type="checkbox" class="form-check-input checkoption bg-primary border-0" id="Automatic-1" name="payment_method" value="automatic">
                                    <label class="form-check-label" for="Automatic-1">Automatic Payment (Midtrans)</label>
                                </div>
                                <p class="text-start text-dark">Pay automatically using Credit Card, E-Wallet, or Bank Transfer via Midtrans</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input type="checkbox" class="form-check-input checkoption bg-primary border-0" id="QRIS-1" name="payment_method" value="qris">
                                    <label class="form-check-label" for="QRIS-1">QRIS Payment</label>
                                </div>
                                <p class="text-start text-dark">Scan QR Code to pay using any e-wallet or banking app</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input type="checkbox" class="form-check-input checkoption bg-primary border-0" id="COD-1" name="payment_method" value="cod">
                                    <label class="form-check-label" for="COD-1">Cash on Delivery (COD)</label>
                                </div>
                                <p class="text-start text-dark">Pay cash when the product is delivered to your location</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input type="checkbox" class="form-check-input checkoption bg-primary border-0" id="Store-1" name="payment_method" value="toko">
                                    <label class="form-check-label" for="Store-1">Bayar Di Toko</label>
                                </div>
                                <p class="text-start text-dark">Datang langsung ke toko untuk melakukan pembayaran</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3" id="payment-slip-section" style="display: none;">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="payment-slip" class="form-label">Upload Payment Slip / Screenshot:</label>
                                    <input type="file" class="form-control" id="payment-slip" name="payment_slip" accept="image/*">
                                    <small class="text-muted">Upload your payment slip or transfer screenshot</small>
                                </div>
                                <div class="mt-3" id="qris-section" style="display: none;">
                                    <img id="images" src="{{ asset('images/qr.jpg') }}" alt="QRIS Code" class="img-fluid" style="max-width: 300px;">
                                    <p class="text-center mt-2">Scan this QR code to pay with any e-wallet or banking app</p>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                            <button type="submit" class="btn border-secondary py-3 px-4 text-uppercase w-100 text-primary">Place Order</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Checkout Page End -->
@endsection
@push('script-alt')
    <script>
        function getShippingCostOptions(district_id) {
            console.log('Getting shipping costs for district_id:', district_id);
            $('#shipping-cost-option').html('<option value="">Loading shipping costs...</option>');
            
            $.ajax({
                url: "{{ route('orders.shippingCost') }}",
                type: 'POST',
                data: {
                    district_id: district_id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log('Shipping API response:', response);
                    var options = '<option value="">-- Select Shipping Service --</option>';
                    
                    if (response.results && response.results.length > 0) {
                        $.each(response.results, function(index, result) {
                            var displayName = result.service + ' - Rp. ' + number_format(result.cost) + ' (' + result.etd + ')';
                            var value = JSON.stringify({
                                service: result.service,
                                cost: result.cost,
                                etd: result.etd,
                                courier: result.courier
                            });
                            options += '<option value="' + value + '">' + displayName + '</option>';
                        });
                    } else {
                        console.warn('No shipping results found in response');
                        options += '<option value="">No shipping options available</option>';
                    }
                    
                    $('#shipping-cost-option').html(options);
                },
                error: function(xhr, status, error) {
                    console.error('Shipping API error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $('#shipping-cost-option').html('<option value="">Error loading shipping costs</option>');
                }
            });
        }

        function number_format(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function updateTotalAmount() {
            var subtotal = parseInt("{{ (int)Cart::subtotal(0,'','') }}");
            var uniqueCode = parseInt($('.unique_code').val()) || 0;
            var shippingCost = 0;
            
            var deliveryMethod = $('input[name="delivery_method"]:checked').val();
            
            if (deliveryMethod === 'self') {
                shippingCost = 0;
            } else if (deliveryMethod === 'courier') {
                var selectedShipping = $('#shipping-cost-option').val();
                if (selectedShipping) {
                    try {
                        var shippingData = JSON.parse(selectedShipping);
                        shippingCost = parseInt(shippingData.cost) || 0;
                    } catch (e) {
                        console.error('Error parsing shipping data:', e);
                    }
                }
            }
            
            var total = subtotal + uniqueCode + shippingCost;
            $('.total-amount').text(number_format(total));
        }

        $(document).ready(function(){
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            $('#shipping-row').hide();
            $('#shipping-cost-option').html('<option value="">-- Select Delivery Method First --</option>');
            
            $('#shipping-province').on('change', function() {
                var province_id = $(this).val();
                console.log('Province changed to:', province_id);
                if (province_id) {
                    var cityUrl = "{{ url('api/cities') }}/" + province_id + '?t=' + Date.now();
                    console.log('Fetching cities from:', cityUrl);
                    $.ajax({
                        url: cityUrl,
                        type: 'GET',
                        success: function(response) {
                            console.log('Cities response:', response);
                            var options = '<option value="">-- Pilih Kota --</option>';
                            if (response && Array.isArray(response)) {
                                $.each(response, function(index, city) {
                                    var selected = city.id == '{{ auth()->user()->city_id }}' ? 'selected' : '';
                                    options += '<option value="' + city.id + '" ' + selected + '>' + city.name + '</option>';
                                });
                            }
                            $('#shipping-city').html(options);
                            $('#shipping-district').html('<option value="">-- Pilih Kecamatan --</option>');
                            
                            var selectedCityId = $('#shipping-city').val();
                            if (selectedCityId) {
                                var districtUrl = "{{ url('api/districts') }}/" + selectedCityId + '?t=' + Date.now();
                                console.log('Auto-loading districts for city:', selectedCityId);
                                $.ajax({
                                    url: districtUrl,
                                    type: 'GET',
                                    success: function(districtResponse) {
                                        var districtOptions = '<option value="">-- Pilih Kecamatan --</option>';
                                        if (districtResponse && Array.isArray(districtResponse)) {
                                            $.each(districtResponse, function(index, district) {
                                                var selected = district.id == '{{ auth()->user()->district_id }}' ? 'selected' : '';
                                                districtOptions += '<option value="' + district.id + '" ' + selected + '>' + district.name + '</option>';
                                            });
                                        }
                                        $('#shipping-district').html(districtOptions);
                                        
                                        var selectedDistrictId = $('#shipping-district').val();
                                        var courierDeliveryChecked = $('input[name="delivery_method"][value="courier"]').is(':checked');
                                        
                                        if (selectedDistrictId && courierDeliveryChecked) {
                                            getShippingCostOptions(selectedDistrictId);
                                        }
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading cities:', error, xhr);
                            $('#shipping-city').html('<option value="">Error loading cities</option>');
                        }
                    });
                } else {
                    $('#shipping-city').html('<option value="">-- Pilih Kota --</option>');
                    $('#shipping-district').html('<option value="">-- Pilih Kecamatan --</option>');
                }
            });
            
            $('#shipping-city').on('change', function() {
                var city_id = $(this).val();
                console.log('City changed to:', city_id);
                if (city_id) {
                    var districtUrl = "{{ url('api/districts') }}/" + city_id + '?t=' + Date.now();
                    console.log('Fetching districts from:', districtUrl);
                    $.ajax({
                        url: districtUrl,
                        type: 'GET',
                        success: function(response) {
                            console.log('Districts response:', response);
                            var options = '<option value="">-- Pilih Kecamatan --</option>';
                            if (response && Array.isArray(response)) {
                                $.each(response, function(index, district) {
                                    var selected = district.id == '{{ auth()->user()->district_id }}' ? 'selected' : '';
                                    options += '<option value="' + district.id + '" ' + selected + '>' + district.name + '</option>';
                                });
                            }
                            $('#shipping-district').html(options);
                            
                            var selectedDistrictId = $('#shipping-district').val();
                            var courierDeliveryChecked = $('input[name="delivery_method"][value="courier"]').is(':checked');
                            
                            if (selectedDistrictId && courierDeliveryChecked) {
                                getShippingCostOptions(selectedDistrictId);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading districts:', error, xhr);
                            $('#shipping-district').html('<option value="">Error loading districts</option>');
                        }
                    });
                } else {
                    $('#shipping-district').html('<option value="">-- Pilih Kecamatan --</option>');
                }
                
                var deliveryMethod = $('input[name="delivery_method"]:checked').val();
                if (deliveryMethod === 'courier') {
                    $('#shipping-cost-option').html('<option value="">-- Select District First --</option>');
                    updateTotalAmount();
                }
            });
            
            $('#shipping-district').on('change', function() {
                var district_id = $(this).val();
                var deliveryMethod = $('input[name="delivery_method"]:checked').val();
                
                if (deliveryMethod === 'courier' && district_id) {
                    getShippingCostOptions(district_id);
                } else if (deliveryMethod === 'courier') {
                    $('#shipping-cost-option').html('<option value="">-- Select District First --</option>');
                    updateTotalAmount();
                }
            });
            
            $('input[name="delivery_method"]').on('change', function() {
                var method = $(this).val();
                
                if (method === 'self') {
                    $('#shipping-row').hide();
                    $('#shipping-cost-option').removeAttr('required');
                    updateTotalAmount();
                } else if (method === 'courier') {
                    $('#shipping-row').show();
                    $('#shipping-cost-option').attr('required', 'required');
                    
                    var district_id = $('#shipping-district').val();
                    if (district_id) {
                        getShippingCostOptions(district_id);
                    } else {
                        $('#shipping-cost-option').html('<option value="">-- Select District First --</option>');
                    }
                }
            });

            $(document).ready(function() {
                var selectedDistrictId = $('#shipping-district').val();
                var courierDeliveryChecked = $('input[name="delivery_method"][value="courier"]').is(':checked');
                
                if (selectedDistrictId && courierDeliveryChecked) {
                    $('#shipping-row').show();
                    getShippingCostOptions(selectedDistrictId);
                }
            });

            $('#shipping-cost-option').on('change', function() {
                updateTotalAmount();
            });

            $('.checkoption').click(function() {
                 $('.checkoption').not(this).prop('checked', false);
            });

            $('input:checkbox').change(function(){
                if ($('.qris').is(':checked')) {
                    $('#images').show();
                } else {
                    $('#images').hide();
                }
            })

       });
    </script>

@endpush
