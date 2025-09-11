<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ViVia Print Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: white;
            font-weight: bold;
        }
        .step.active {
            background: #28a745;
        }
        .step.completed {
            background: #007bff;
        }
        .upload-area {
            border: 3px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0056b3;
            background: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background: #e8f5e8;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .btn-print {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 25px;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }
        .price-display {
            background: #e9ecef;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .session-info {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="session-info">
            <h5><i class="fas fa-clock"></i> Session Active</h5>
            <small>Session expires: {{ $session->expires_at->format('d/m/Y H:i') }}</small>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">
                    <i class="fas fa-print text-primary"></i>
                    ViVia Print Service
                </h2>

                <div class="step-indicator">
                    <div class="step active" id="step-1">1</div>
                    <div class="step" id="step-2">2</div>
                    <div class="step" id="step-3">3</div>
                    <div class="step" id="step-4">4</div>
                </div>

                <!-- Step 1: File Upload -->
                <div id="upload-section" class="step-content">
                    <h4 class="mb-3"><i class="fas fa-upload"></i> Upload Files</h4>
                    <div class="upload-area" id="upload-area">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <h5>Drop files here or click to browse</h5>
                        <p class="text-muted">Supports: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, TXT, CSV, LOG, RTF, ODT, ODS</p>
                        <p class="text-muted">Max size: 50MB per file, Max files: 10</p>
                        <input type="file" id="file-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt,.csv,.log,.rtf,.odt,.ods" style="display: none;">
                    </div>
                    
                    <div id="file-list" class="mt-3"></div>
                    
                    <button class="btn btn-primary mt-3" id="upload-btn" disabled>
                        <i class="fas fa-arrow-right"></i> Next Step
                    </button>
                </div>

                <!-- Step 2: Paper Selection -->
                <div id="selection-section" class="step-content" style="display: none;">
                    <h4 class="mb-3"><i class="fas fa-paper-plane"></i> Select Paper & Print Type</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Paper Size</label>
                            <select class="form-select" id="paper-size">
                                <option value="">Select paper size</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Print Type</label>
                            <select class="form-select" id="print-type">
                                <option value="">Select print type</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity (sets)</label>
                            <input type="number" class="form-control" id="quantity" value="1" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Pages</label>
                            <input type="number" class="form-control" id="total-pages" readonly>
                        </div>
                    </div>

                    <div class="price-display" id="price-display" style="display: none;">
                        <h5>Price Calculation</h5>
                        <div class="row">
                            <div class="col-6">Unit Price:</div>
                            <div class="col-6 text-end" id="unit-price">-</div>
                        </div>
                        <div class="row">
                            <div class="col-6">Total Pages:</div>
                            <div class="col-6 text-end" id="display-total-pages">-</div>
                        </div>
                        <div class="row">
                            <div class="col-6">Quantity:</div>
                            <div class="col-6 text-end" id="display-quantity">-</div>
                        </div>
                        <hr>
                        <div class="row fw-bold">
                            <div class="col-6">Total Price:</div>
                            <div class="col-6 text-end text-primary" id="total-price">-</div>
                        </div>
                    </div>

                    <button class="btn btn-secondary me-2" id="back-to-upload">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-primary" id="selection-next" disabled>
                        <i class="fas fa-arrow-right"></i> Next Step
                    </button>
                </div>

                <!-- Step 3: Customer Info & Payment -->
                <div id="payment-section" class="step-content" style="display: none;">
                    <h4 class="mb-3"><i class="fas fa-user"></i> Customer Information</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" id="customer-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="customer-phone" required>
                        </div>
                    </div>

                    <h5 class="mt-4"><i class="fas fa-credit-card"></i> Payment Method</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card payment-option" data-method="toko">
                                <div class="card-body text-center">
                                    <i class="fas fa-store fa-2x mb-2"></i>
                                    <h6>Pay at Store</h6>
                                    <small>Pay when you pick up</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card payment-option" data-method="manual">
                                <div class="card-body text-center">
                                    <i class="fas fa-university fa-2x mb-2"></i>
                                    <h6>Bank Transfer</h6>
                                    <small>Upload payment proof</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card payment-option" data-method="automatic">
                                <div class="card-body text-center">
                                    <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                    <h6>Online Payment</h6>
                                    <small>Pay with e-wallet/card</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="payment-proof-section" style="display: none;" class="mt-3">
                        <label class="form-label">Upload Payment Proof</label>
                        <input type="file" class="form-control" id="payment-proof" accept="image/*">
                    </div>

                    <button class="btn btn-secondary me-2" id="back-to-selection">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-print" id="checkout-btn" disabled>
                        <i class="fas fa-check"></i> Complete Order
                    </button>
                </div>

                <!-- Step 4: Status -->
                <div id="status-section" class="step-content" style="display: none;">
                    <div class="text-center">
                        <div id="status-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let sessionToken = '{{ $session->session_token }}';
        let uploadedFiles = [];
        let selectedVariant = null;
        let paymentMethod = null;
        let currentCalculation = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            setupEventListeners();
        });

        function setupEventListeners() {
            // File upload
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('file-input');

            uploadArea.addEventListener('click', () => fileInput.click());
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);

            // Step navigation
            document.getElementById('upload-btn').addEventListener('click', goToSelection);
            document.getElementById('back-to-upload').addEventListener('click', goToUpload);
            document.getElementById('selection-next').addEventListener('click', goToPayment);
            document.getElementById('back-to-selection').addEventListener('click', goToSelection);
            document.getElementById('checkout-btn').addEventListener('click', doCheckout);

            // Selection changes
            document.getElementById('paper-size').addEventListener('change', updatePrintTypes);
            document.getElementById('print-type').addEventListener('change', calculatePrice);
            document.getElementById('quantity').addEventListener('input', calculatePrice);

            // Payment method selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.addEventListener('click', function() {
                    selectPaymentMethod(this.dataset.method);
                });
            });
        }

        async function loadProducts() {
            try {
                const response = await fetch('/print-service/products');
                const data = await response.json();
                
                if (data.success) {
                    populateProductOptions(data.products);
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        function populateProductOptions(products) {
            const paperSizeSelect = document.getElementById('paper-size');
            const paperSizes = [...new Set(products[0].variants.map(v => v.paper_size))];
            
            paperSizes.forEach(size => {
                paperSizeSelect.innerHTML += `<option value="${size}">${size}</option>`;
            });
        }

        function updatePrintTypes() {
            const paperSize = document.getElementById('paper-size').value;
            const printTypeSelect = document.getElementById('print-type');
            
            printTypeSelect.innerHTML = '<option value="">Select print type</option>';
            
            if (paperSize) {
                // This would be populated based on available variants for the selected paper size
                printTypeSelect.innerHTML += '<option value="bw">Black & White</option>';
                printTypeSelect.innerHTML += '<option value="color">Color</option>';
            }
        }

        function handleDragOver(e) {
            e.preventDefault();
            document.getElementById('upload-area').classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            document.getElementById('upload-area').classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            document.getElementById('upload-area').classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        }

        function handleFileSelect(e) {
            handleFiles(e.target.files);
        }

        async function handleFiles(files) {
            if (files.length > 10) {
                alert('Maximum 10 files allowed');
                return;
            }

            const formData = new FormData();
            formData.append('session_token', sessionToken);
            
            for (let file of files) {
                if (file.size > 50 * 1024 * 1024) {
                    alert(`File ${file.name} is too large (max 50MB)`);
                    return;
                }
                formData.append('files[]', file);
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch('/print-service/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    uploadedFiles = data.files;
                    displayUploadedFiles();
                    document.getElementById('total-pages').value = data.total_pages;
                    document.getElementById('upload-btn').disabled = false;
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            }
        }

        function displayUploadedFiles() {
            const fileList = document.getElementById('file-list');
            fileList.innerHTML = '';
            
            uploadedFiles.forEach(file => {
                fileList.innerHTML += `
                    <div class="file-item">
                        <i class="fas fa-file me-2"></i>
                        <span>${file.name}</span>
                        <span class="ms-auto">${file.pages_count} pages</span>
                    </div>
                `;
            });
        }

        async function calculatePrice() {
            const paperSize = document.getElementById('paper-size').value;
            const printType = document.getElementById('print-type').value;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const totalPages = parseInt(document.getElementById('total-pages').value) || 0;

            if (!paperSize || !printType || !totalPages) return;

            // Find matching variant
            try {
                const response = await fetch('/print-service/products');
                const data = await response.json();
                
                if (data.success) {
                    const variant = data.products[0].variants.find(v => 
                        v.paper_size === paperSize && v.print_type === printType
                    );
                    
                    if (variant) {
                        selectedVariant = variant;
                        
                        const calcResponse = await fetch('/print-service/calculate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                variant_id: variant.id,
                                total_pages: totalPages,
                                quantity: quantity
                            })
                        });

                        const calcData = await calcResponse.json();
                        
                        if (calcData.success) {
                            currentCalculation = calcData.calculation;
                            displayPrice(calcData.calculation);
                            document.getElementById('selection-next').disabled = false;
                        }
                    }
                }
            } catch (error) {
                console.error('Price calculation error:', error);
            }
        }

        function displayPrice(calculation) {
            document.getElementById('unit-price').textContent = `Rp ${calculation.unit_price.toLocaleString()}`;
            document.getElementById('display-total-pages').textContent = calculation.total_pages;
            document.getElementById('display-quantity').textContent = calculation.quantity;
            document.getElementById('total-price').textContent = `Rp ${calculation.total_price.toLocaleString()}`;
            document.getElementById('price-display').style.display = 'block';
        }

        function selectPaymentMethod(method) {
            paymentMethod = method;
            
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary');
            });
            
            document.querySelector(`[data-method="${method}"]`).classList.add('border-primary');
            
            if (method === 'manual') {
                document.getElementById('payment-proof-section').style.display = 'block';
            } else {
                document.getElementById('payment-proof-section').style.display = 'none';
            }
            
            updateCheckoutButton();
        }

        function updateCheckoutButton() {
            const name = document.getElementById('customer-name').value;
            const phone = document.getElementById('customer-phone').value;
            
            document.getElementById('checkout-btn').disabled = !(name && phone && paymentMethod);
        }

        // Add event listeners for customer info
        document.getElementById('customer-name').addEventListener('input', updateCheckoutButton);
        document.getElementById('customer-phone').addEventListener('input', updateCheckoutButton);

        async function doCheckout() {
            const formData = new FormData();
            formData.append('session_token', sessionToken);
            formData.append('customer_name', document.getElementById('customer-name').value);
            formData.append('customer_phone', document.getElementById('customer-phone').value);
            formData.append('variant_id', selectedVariant.id);
            formData.append('payment_method', paymentMethod);
            formData.append('total_pages', document.getElementById('total-pages').value);
            formData.append('quantity', document.getElementById('quantity').value);
            formData.append('files', JSON.stringify(uploadedFiles));

            if (paymentMethod === 'manual') {
                const proofFile = document.getElementById('payment-proof').files[0];
                if (proofFile) {
                    formData.append('payment_proof', proofFile);
                }
            }

            try {
                const response = await fetch('/print-service/checkout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    if (data.payment.status === 'redirect') {
                        window.location.href = data.payment.redirect_url;
                    } else {
                        showOrderStatus(data.order, data.payment);
                    }
                } else {
                    alert('Checkout failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Checkout failed');
            }
        }

        function showOrderStatus(order, payment) {
            goToStatus();
            
            let statusContent = `
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>Order Created Successfully!</h4>
                <p>Order Code: <strong>${order.order_code}</strong></p>
                <p>Total: <strong>Rp ${order.total_price.toLocaleString()}</strong></p>
            `;

            if (payment.status === 'pending') {
                statusContent += `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        ${payment.message}
                    </div>
                `;
            }

            statusContent += `
                <button class="btn btn-primary" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> New Order
                </button>
            `;

            document.getElementById('status-content').innerHTML = statusContent;
        }

        // Step navigation functions
        function goToUpload() {
            document.querySelector('#step-1').classList.add('active');
            document.querySelector('#step-2').classList.remove('active');
            document.getElementById('upload-section').style.display = 'block';
            document.getElementById('selection-section').style.display = 'none';
        }

        function goToSelection() {
            document.querySelector('#step-1').classList.remove('active');
            document.querySelector('#step-1').classList.add('completed');
            document.querySelector('#step-2').classList.add('active');
            document.getElementById('upload-section').style.display = 'none';
            document.getElementById('selection-section').style.display = 'block';
        }

        function goToPayment() {
            document.querySelector('#step-2').classList.remove('active');
            document.querySelector('#step-2').classList.add('completed');
            document.querySelector('#step-3').classList.add('active');
            document.getElementById('selection-section').style.display = 'none';
            document.getElementById('payment-section').style.display = 'block';
        }

        function goToStatus() {
            document.querySelector('#step-3').classList.remove('active');
            document.querySelector('#step-3').classList.add('completed');
            document.querySelector('#step-4').classList.add('active');
            document.getElementById('payment-section').style.display = 'none';
            document.getElementById('status-section').style.display = 'block';
        }
    </script>

    <style>
        .payment-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .payment-option.border-primary {
            border-color: #007bff !important;
            border-width: 2px !important;
        }
    </style>
</body>
</html>
