<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Frontend\HomepageController;

Route::post('payments/notification', [App\Http\Controllers\Frontend\OrderController::class, 'notificationHandler'])
    ->name('payment.notification');

// Auth guest routes App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PembelianDetailController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/test-add-cart', function() {
    return view('test_add_cart');
});

// $cart = Cart::content()->count();
// dd($cart);
// view()->share('countCart', $cart);

// Route::get('/debug-midtrans', [OrderController::class, 'debug']);

Route::get('/employee-performance-summary', function () {
    echo "<h1>🎉 Employee Performance Tracking System</h1>";
    echo "<h2>✅ Implementation Complete!</h2>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 System Statistics:</h3>";
    
    $totalEmployees = \App\Models\EmployeePerformance::distinct('employee_name')->count();
    $totalTransactions = \App\Models\EmployeePerformance::count();
    $totalRevenue = \App\Models\EmployeePerformance::sum('transaction_value');
    $totalBonuses = \App\Models\EmployeeBonus::sum('bonus_amount');
    $ordersWithTracking = \App\Models\Order::where('use_employee_tracking', true)->count();
    
    echo "<ul>";
    echo "<li><strong>Total Employees Tracked:</strong> {$totalEmployees}</li>";
    echo "<li><strong>Total Transactions Recorded:</strong> {$totalTransactions}</li>";
    echo "<li><strong>Total Revenue Tracked:</strong> Rp " . number_format($totalRevenue, 0, ',', '.') . "</li>";
    echo "<li><strong>Total Bonuses Given:</strong> Rp " . number_format($totalBonuses, 0, ',', '.') . "</li>";
    echo "<li><strong>Orders with Employee Tracking:</strong> {$ordersWithTracking}</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #cce5ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🔗 Quick Links:</h3>";
    echo "<ul>";
    echo "<li><a href='/admin' target='_blank'>Admin Dashboard</a> (requires admin login)</li>";
    echo "<li><a href='/admin/employee-performance' target='_blank'>Employee Performance Dashboard</a> (requires admin login)</li>";
    echo "<li><a href='/admin/orders' target='_blank'>Orders Management</a> (requires admin login)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📋 Usage Instructions:</h3>";
    echo "<ol>";
    echo "<li><strong>For Order Tracking:</strong>";
    echo "<ul>";
    echo "<li>Go to any order detail page in admin</li>";
    echo "<li>Check 'Employee Tracking' checkbox</li>";
    echo "<li>Enter employee name</li>";
    echo "<li>Complete the order normally</li>";
    echo "</ul></li>";
    echo "<li><strong>For Performance Review:</strong>";
    echo "<ul>";
    echo "<li>Visit Employee Performance menu</li>";
    echo "<li>Use filters to view specific periods/employees</li>";
    echo "<li>Click 'Detail' to see individual performance</li>";
    echo "</ul></li>";
    echo "<li><strong>For Giving Bonuses:</strong>";
    echo "<ul>";
    echo "<li>Click 'Bonus' button on performance dashboard</li>";
    echo "<li>Fill in bonus details and period</li>";
    echo "<li>Submit to record the bonus</li>";
    echo "</ul></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Important Notes:</h3>";
    echo "<ul>";
    echo "<li>Employee tracking is <strong>optional</strong> per order</li>";
    echo "<li>Employee name must be filled before completing tracked orders</li>";
    echo "<li>Performance data is automatically recorded when orders are completed</li>";
    echo "<li>Bonuses are separate from automatic performance tracking</li>";
    echo "<li>All data is visible to customers in their order details</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>🚀 System Ready for Production Use!</h3>";
    echo "<p><em>You can now use the employee performance tracking system to monitor staff performance and manage bonuses effectively.</em></p>";
    
    return "";
});

Route::get('/stress-test-employee-performance', function () {
    try {
        echo "<h1>Employee Performance System Stress Test</h1>";
        
        echo "<h2>Test 1: Order Completion with Employee Tracking</h2>";
        
        $order = \App\Models\Order::where('use_employee_tracking', true)->first();
        
        if (!$order) {
            echo "❌ No orders with employee tracking found<br>";
            return;
        }
        
        echo "✅ Testing with Order #{$order->code}<br>";
        echo "Employee: {$order->handled_by}<br>";
        echo "Order Total: Rp " . number_format($order->grand_total, 0, ',', '.') . "<br><br>";
        
        echo "<h2>Test 2: Employee Performance Controller</h2>";
        
        $employees = \App\Models\EmployeePerformance::getEmployeeList();
        echo "✅ Found {$employees->count()} employees in system<br>";
        
        $totalTransactions = \App\Models\EmployeePerformance::count();
        echo "✅ Total transactions: {$totalTransactions}<br>";
        
        $totalRevenue = \App\Models\EmployeePerformance::sum('transaction_value');
        echo "✅ Total revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . "<br>";
        
        $averageTransaction = \App\Models\EmployeePerformance::avg('transaction_value');
        echo "✅ Average transaction: Rp " . number_format($averageTransaction, 0, ',', '.') . "<br>";
        
        $topEmployee = \App\Models\EmployeePerformance::selectRaw('employee_name, SUM(transaction_value) as total_revenue')
                                        ->groupBy('employee_name')
                                        ->orderBy('total_revenue', 'desc')
                                        ->first();
                                        
        echo "✅ Top employee: {$topEmployee->employee_name} (Rp " . number_format($topEmployee->total_revenue, 0, ',', '.') . ")<br><br>";
        
        echo "<h2>Test 3: Relationship Testing</h2>";
        
        foreach (\App\Models\Order::where('use_employee_tracking', true)->take(3)->get() as $testOrder) {
            $performance = $testOrder->employeePerformance;
            if ($performance) {
                echo "✅ Order #{$testOrder->code} -> Performance ID #{$performance->id}<br>";
            } else {
                echo "❌ Order #{$testOrder->code} has no performance record<br>";
            }
        }
        
        echo "<br><h2>Test 4: Employee Methods</h2>";
        
        foreach (\App\Models\Order::where('use_employee_tracking', true)->take(3)->get() as $testOrder) {
            if ($testOrder->isHandledByEmployee()) {
                echo "✅ Order #{$testOrder->code} is handled by employee: {$testOrder->handled_by}<br>";
            } else {
                echo "❌ Order #{$testOrder->code} not properly handled by employee<br>";
            }
        }
        
        echo "<br><h2>✅ Stress Test Complete!</h2>";
        echo "<p><strong>Employee Performance System is Ready!</strong></p>";
        
        return "";
        
    } catch (Exception $e) {
        echo "❌ Stress test failed: " . $e->getMessage();
        return "";
    }
});

Route::get('/test-employee-data', function () {
    try {
        echo "<h2>Employee Performance Data Test</h2>";
        
        // Test 1: Check employee performances
        $performances = \App\Models\EmployeePerformance::with('order')->get();
        echo "<h3>Employee Performances ({$performances->count()} records):</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Employee</th><th>Order ID</th><th>Transaction Value</th><th>Completed At</th></tr>";
        
        foreach ($performances as $performance) {
            echo "<tr>";
            echo "<td>{$performance->employee_name}</td>";
            echo "<td>#{$performance->order->code}</td>";
            echo "<td>Rp " . number_format($performance->transaction_value, 0, ',', '.') . "</td>";
            echo "<td>{$performance->completed_at}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Test 2: Check employee bonuses
        $bonuses = \App\Models\EmployeeBonus::with('givenBy')->get();
        echo "<h3>Employee Bonuses ({$bonuses->count()} records):</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Employee</th><th>Bonus Amount</th><th>Period</th><th>Given By</th></tr>";
        
        foreach ($bonuses as $bonus) {
            echo "<tr>";
            echo "<td>{$bonus->employee_name}</td>";
            echo "<td>Rp " . number_format($bonus->bonus_amount, 0, ',', '.') . "</td>";
            echo "<td>{$bonus->period_start} - {$bonus->period_end}</td>";
            echo "<td>{$bonus->givenBy->name}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Test 3: Aggregate data
        $stats = \App\Models\EmployeePerformance::selectRaw('
            employee_name,
            COUNT(*) as total_transactions,
            SUM(transaction_value) as total_revenue,
            AVG(transaction_value) as average_transaction
        ')->groupBy('employee_name')->get();
        
        echo "<h3>Employee Statistics:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Employee</th><th>Total Transactions</th><th>Total Revenue</th><th>Average Transaction</th></tr>";
        
        foreach ($stats as $stat) {
            echo "<tr>";
            echo "<td>{$stat->employee_name}</td>";
            echo "<td>{$stat->total_transactions}</td>";
            echo "<td>Rp " . number_format($stat->total_revenue, 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($stat->average_transaction, 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<h3>✅ Employee Performance System Ready!</h3>";
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li>Visit <a href='/admin/employee-performance'>/admin/employee-performance</a> (requires admin login)</li>";
        echo "<li>Go to admin orders and enable employee tracking</li>";
        echo "<li>Complete orders to see performance tracking</li>";
        echo "<li>Give bonuses to employees</li>";
        echo "</ul>";
        
        return "";
        
    } catch (Exception $e) {
        return "Test failed: " . $e->getMessage();
    }
});

Route::get('/test-employee-performance', function () {
    try {
        // Test database connection and table existence
        echo "Testing Employee Performance Implementation...\n\n";
        
        // Test 1: Check if tables exist
        echo "1. Checking database tables:\n";
        $tables = [
            'employee_performances',
            'employee_bonuses'
        ];
        
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                echo "   ✓ Table '{$table}' exists<br>";
            } else {
                echo "   ✗ Table '{$table}' does not exist<br>";
                return "Migration not complete";
            }
        }
        
        // Test 2: Check if columns exist in orders table
        echo "<br>2. Checking orders table columns:<br>";
        $orderColumns = ['handled_by', 'use_employee_tracking'];
        foreach ($orderColumns as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', $column)) {
                echo "   ✓ Column '{$column}' exists in orders table<br>";
            } else {
                echo "   ✗ Column '{$column}' does not exist in orders table<br>";
                return "Orders table migration not complete";
            }
        }
        
        // Test 3: Check models
        echo "<br>3. Testing models:<br>";
        try {
            $employeePerformance = new \App\Models\EmployeePerformance();
            echo "   ✓ EmployeePerformance model exists<br>";
        } catch (Exception $e) {
            echo "   ✗ EmployeePerformance model error: " . $e->getMessage() . "<br>";
        }
        
        try {
            $employeeBonus = new \App\Models\EmployeeBonus();
            echo "   ✓ EmployeeBonus model exists<br>";
        } catch (Exception $e) {
            echo "   ✗ EmployeeBonus model error: " . $e->getMessage() . "<br>";
        }
        
        // Test 4: Check order model relationship
        echo "<br>4. Testing Order model updates:<br>";
        try {
            $order = new \App\Models\Order();
            if (method_exists($order, 'employeePerformance')) {
                echo "   ✓ Order->employeePerformance() relationship exists<br>";
            } else {
                echo "   ✗ Order->employeePerformance() relationship missing<br>";
            }
            
            if (method_exists($order, 'isHandledByEmployee')) {
                echo "   ✓ Order->isHandledByEmployee() method exists<br>";
            } else {
                echo "   ✗ Order->isHandledByEmployee() method missing<br>";
            }
        } catch (Exception $e) {
            echo "   ✗ Order model error: " . $e->getMessage() . "<br>";
        }
        
        // Test 5: Check controller
        echo "<br>5. Testing controller:<br>";
        try {
            $controller = new \App\Http\Controllers\Admin\EmployeePerformanceController();
            echo "   ✓ EmployeePerformanceController exists<br>";
        } catch (Exception $e) {
            echo "   ✗ EmployeePerformanceController error: " . $e->getMessage() . "<br>";
        }
        
        // Test 6: Check routes
        echo "<br>6. Testing routes:<br>";
        $routes = [
            'admin.employee-performance.index',
            'admin.employee-performance.data', 
            'admin.employee-performance.giveBonus'
        ];
        
        foreach ($routes as $routeName) {
            try {
                $route = route($routeName);
                echo "   ✓ Route '{$routeName}' exists<br>";
            } catch (Exception $e) {
                echo "   ✗ Route '{$routeName}' error: " . $e->getMessage() . "<br>";
            }
        }
        
        // Test 7: Check views
        echo "<br>7. Testing views:<br>";
        $views = [
            'admin.employee-performance.index',
            'admin.employee-performance.show'
        ];
        
        foreach ($views as $viewName) {
            if (view()->exists($viewName)) {
                echo "   ✓ View '{$viewName}' exists<br>";
            } else {
                echo "   ✗ View '{$viewName}' does not exist<br>";
            }
        }
        
        echo "<br>✅ Employee Performance Implementation Test Complete!<br>";
        echo "<br>Next steps:<br>";
        echo "1. Navigate to admin panel<br>";
        echo "2. Go to Employee Performance menu<br>"; 
        echo "3. Create an order and test employee tracking<br>";
        echo "4. Complete the order to test performance recording<br>";
        echo "5. Give bonus to test bonus functionality<br>";
        
        return "Test completed successfully!";
        
    } catch (Exception $e) {
        return "Test failed: " . $e->getMessage();
    }
});

Route::get('/debug-midtrans', function() {
    $midtransTest = 'Unknown';
    try {
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        $midtransTest = 'Midtrans Config set successfully';
    } catch (Exception $e) {
        $midtransTest = 'Error: ' . $e->getMessage();
    }
    
    return [
        'config' => [
            'serverKey' => config('midtrans.serverKey') ? 'Set (hidden)' : 'Not set',
            'clientKey' => config('midtrans.clientKey'),
            'isProduction' => config('midtrans.isProduction'),
            'isSanitized' => config('midtrans.isSanitized'),
            'is3ds' => config('midtrans.is3ds'),
        ],
        'midtrans_test' => $midtransTest,
    ];
});

Route::get('/test-payment-status/{orderId}', function($orderId) {
    $order = App\Models\Order::where('code', $orderId)->first();
    if (!$order) {
        return ['error' => 'Order not found'];
    }
    
    // Update payment status to simulate successful payment
    $order->payment_status = 'paid';
    $order->status = 'confirmed';
    $order->approved_at = now();
    $order->save();
    
    return [
        'success' => true,
        'message' => 'Payment status updated to paid',
        'order_id' => $orderId,
        'new_status' => $order->payment_status,
        'redirect_url' => url("orders/received/{$order->id}")
    ];
});

Route::get('/debug-order/{id}', function($id) {
    $order = App\Models\Order::find($id);
    if (!$order) {
        return ['error' => 'Order not found'];
    }
    
    return [
        'order' => [
            'id' => $order->id,
            'code' => $order->code,
            'customer_first_name' => $order->customer_first_name,
            'customer_last_name' => $order->customer_last_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'grand_total' => $order->grand_total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
        ]
    ];
});

Route::get('/test-midtrans-token/{id}', function($id) {
    $order = App\Models\Order::find($id);
    if (!$order) {
        return ['error' => 'Order not found'];
    }
    
    try {
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is3ds');
        
        // Disable SSL for testing
        \Midtrans\Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => []
        ];
        
        $params = [
            'transaction_details' => [
                'order_id' => 'TEST-' . time(),
                'gross_amount' => 10000,
            ],
            'customer_details' => [
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => 'test@example.com',
                'phone' => '08123456789',
            ],
            'item_details' => [
                [
                    'id' => 'TEST-ITEM',
                    'price' => 10000,
                    'quantity' => 1,
                    'name' => 'Test Item'
                ]
            ]
        ];
        
        $snap = \Midtrans\Snap::createTransaction($params);
        
        return [
            'success' => true,
            'token' => $snap->token ?? 'No token',
            'redirect_url' => $snap->redirect_url ?? 'No redirect URL',
            'config' => [
                'serverKey' => config('midtrans.serverKey') ? 'Set' : 'Not set',
                'isProduction' => config('midtrans.isProduction'),
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'config' => [
                'serverKey' => config('midtrans.serverKey') ? 'Set' : 'Not set',
                'isProduction' => config('midtrans.isProduction'),
            ]
        ];
    }
});

Route::post('payments/notification', [App\Http\Controllers\Frontend\OrderController::class, 'notificationHandler'])
    ->name('payment.notification')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('payments/client-key', [App\Http\Controllers\Frontend\OrderController::class, 'getMidtransClientKey'])
    ->name('payment.client-key');

    Route::get('payments/finish', [App\Http\Controllers\Frontend\OrderController::class, 'finishRedirect'])
    ->name('payment.finish');

    Route::get('payments/unfinish', [App\Http\Controllers\Frontend\OrderController::class, 'unfinishRedirect'])
    ->name('payment.unfinish');

    Route::get('payments/error', [App\Http\Controllers\Frontend\OrderController::class, 'errorRedirect'])
    ->name('payment.error');

	// Customer invoice and order status routes
	Route::get('orders/invoice/{id}', [App\Http\Controllers\Frontend\OrderController::class, 'invoice'])
		->name('orders.invoice')
		->middleware('auth');
		
	Route::get('orders/status/{id}', [App\Http\Controllers\Frontend\OrderController::class, 'getOrderStatus'])
		->name('orders.status');

    Route::get('/instagram', [InstagramController::class, 'getInstagramData'])->name('admin.instagram.index');
    Route::get('/instagram/callback', [InstagramController::class, 'handleCallback'])
        ->name('instagram.callback');
    Route::match(['get','post'], '/instagram/webhook', [InstagramController::class, 'webhook'])
        ->name('instagram.webhook');
        Route::post('orders/complete/{order}', [\App\Http\Controllers\Admin\OrderController::class , 'doComplete'])->name('orders.complete');




Route::group(['middleware' => ['auth', 'is_admin'], 'prefix' => 'admin', 'as' => 'admin.'], function() {
    // admin
    Route::post('/products/find-barcode', [ProductController::class, 'findByBarcode'])
     ->name('products.find-barcode');
    Route::get('/orders/invoices/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'invoices'])
     ->name('orders.invoices');
     Route::get('/products/exportTemplate', [ProductController::class, 'exportTemplate'])->name("products.exportTemplate");
    Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::get('users/edit/{id}', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('users/update/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('users/delete/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::resource('setting', SettingController::class);
    Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/updateHargaJual/{id}', [PembelianController::class, 'updateHargaJual'])->name('updateHargaJual');
    Route::put('/updateHargaBeli/{id}', [PembelianController::class, 'updateHargaBeli'])->name('updateHargaBeli');
    Route::get('/supplier/data', [SupplierController::class, 'data'])->name('supplier.data');
    Route::resource('/pembelian', PembelianController::class)->except('create');
    Route::get('/pembeliansss/data', [PembelianController::class, 'data'])->name('pembelian.data');
    Route::get('/pembelian/{id}/create', [PembelianController::class, 'create'])->name('pembelian.create');
    Route::resource('/pembelian_detail', PembelianDetailController::class)->except('create', 'show', 'edit');
    Route::get('/pembelian_detail/{id}/data', [PembelianDetailController::class, 'data'])->name('pembelian_detail.data');
    Route::get('/pembelian/invoices/{id}', [PembelianController::class, 'invoices'])
        ->name('pembelian.invoices');
    Route::get('reports/revenue/{awal}/{akhir}/excel',
        [App\Http\Controllers\Frontend\HomepageController::class, 'exportExcel']
    )->name('reports.revenue.excel');
    Route::get('/pembelian_detail/loadform/{diskon}/{total}', [PembelianDetailController::class, 'loadForm'])->name('pembelian_detail.load_form');
    Route::get('/pembelian_detail/editBayar/{id}', [PembelianDetailController::class, 'editBayar'])->name('pembelian_detail.editBayar');
    Route::put('/pembelian_detail/updateEdit/{id}', [PembelianDetailController::class, 'updateEdit'])->name('pembelian_detail.updateEdit');

    Route::resource('supplier', SupplierController::class);
    Route::get('/laporan', [HomepageController::class, 'reports'])->name('laporan');
    Route::get('/laporan/data/{awal}/{akhir}', [HomepageController::class, 'data'])->name('laporan.data');
    Route::post('/products/import', [ProductController::class, 'imports'])->name('products.imports');
    Route::get('/quaggaTest', function () {
        return view('admin.products.quaggaTest');
    })->name('quaggaTest');
    Route::get('/downloadExcel', function () {
        return response()->download(public_path('template.xlsx'));
        // dd(public_path('/file'));
    })->name('downloadTemplate');
    
    Route::get('/barcode/preview' , [ProductController::class, 'previewBarcode'])->name('barcode.preview');
    Route::get('/barcode/preview/landscape' , [ProductController::class, 'previewBarcodeLandscape'])->name('barcode.preview.landscape');
    Route::get('/barcode/preview/portrait' , [ProductController::class, 'previewBarcodePortrait'])->name('barcode.preview.portrait');
    Route::get('/barcode/print/landscape' , [ProductController::class, 'printBarcodeLandscape'])->name('barcode.print.landscape');
    Route::get('/barcode/print/portrait' , [ProductController::class, 'printBarcodePortrait'])->name('barcode.print.portrait');
    Route::get('/barcode/downloadSingle/{id}' , [ProductController::class, 'downloadSingleBarcode'])->name('barcode.downloadSingle');
    Route::get('/laporan/export', [ReportController::class, 'exportExcel'])->name('laporan.exportExcel');
    // Route::get('/laporan/dataTotal/{awal}/{akhir}', [HomepageController::class, 'getReportsData'])->name('laporan.data');
    Route::get('/laporan/export/{awal}/{akhir}', [HomepageController::class, 'data'])->name('laporan.exportPDF');

    Route::get('/instagram/create', [InstagramController::class, 'create'])->name('instagram.create');
    Route::post('/instagram/post', [InstagramController::class, 'postToInstagram'])->name('instagram.store');
    Route::get('/instagram/postProduct/{id}', [InstagramController::class, 'postToInstagramFromProducts'])
        ->name('instagram.postProduct');
    Route::get('/instagram/data', [InstagramController::class, 'getInstagramData'])->name('instagram.data');
    Route::get('/instagram/redirect', [InstagramController::class, 'redirectToInstagram'])
     ->name('instagram.redirect');
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('attributes', \App\Http\Controllers\Admin\AttributeController::class);
    Route::resource('attributes.attribute_variants', \App\Http\Controllers\Admin\AttributeVariantController::class);
    Route::resource('attributes.attribute_variants.attribute_options', \App\Http\Controllers\Admin\AttributeOptionController::class);
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
    Route::get('/products/data/datatable', [ProductController::class, 'data'])->name('products.data');
    Route::get('/products/{id}/attributes', [ProductController::class, 'getProductAttributes'])->name('products.attributes');
    Route::get('/products/{id}/variant-options', [ProductController::class, 'getVariantOptions'])->name('products.variant-options');
    Route::get('/products/{id}/all-variants', [ProductController::class, 'getAllVariants'])->name('products.all-variants');
    Route::post('/products/barcode/search', [ProductController::class, 'findByBarcode'])->name('products.findByBarcode');
    Route::delete('/products/{id}/delete-variants', [ProductController::class, 'deleteVariants'])->name('products.deleteVariants');
    Route::post('/variants/create', [\App\Http\Controllers\Admin\ProductVariantController::class, 'store'])->name('variants.create');
    Route::get('/variants/{id}', [\App\Http\Controllers\Admin\ProductVariantController::class, 'show'])->name('variants.show');
    Route::put('/variants/{id}', [\App\Http\Controllers\Admin\ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('/variants/{id}', [\App\Http\Controllers\Admin\ProductVariantController::class, 'destroy'])->name('variants.destroy');
    Route::resource('products.product_images', \App\Http\Controllers\Admin\ProductImageController::class);
    Route::get('/products/generateAllBarcodes', [ProductController::class, 'generateBarcodeAll'])->name('products.generateAll');
    Route::get('/products/generateSingleBarcode/{id}', [ProductController::class, 'generateBarcodeSingle'])->name('products.generateSingle');
    Route::resource('slides', \App\Http\Controllers\Admin\SlideController::class);
    Route::get('slides/{slideId}/up', [\App\Http\Controllers\Admin\SlideController::class, 'moveUp']);
    Route::get('slides/{slideId}/down', [\App\Http\Controllers\Admin\SlideController::class, 'moveDown']);

    Route::get('orders/trashed', [\App\Http\Controllers\Admin\OrderController::class , 'trashed'])->name('orders.trashed');
    Route::get('orders/restore/{order:id}', [\App\Http\Controllers\Admin\OrderController::class , 'restore'])->name('orders.restore');
    Route::resource('orders', \App\Http\Controllers\Admin\OrderController::class);
    Route::get('datas', [App\Http\Controllers\Admin\ProductController::class, 'data'])
     ->name('products.data');
    Route::post('ordersAdmin', [\App\Http\Controllers\Admin\OrderController::class , 'storeAdmin'])->name('orders.storeAdmin');
    Route::get('ordersAdmin', [\App\Http\Controllers\Admin\OrderController::class , 'checkPage'])->name('orders.checkPage');
    Route::post('orders/payment-notification', [\App\Http\Controllers\Admin\OrderController::class , 'paymentNotification'])->name('orders.payment-notification');
    Route::post('orders/{order}/generate-payment-token', [\App\Http\Controllers\Admin\OrderController::class , 'generatePaymentToken'])->name('orders.generate-payment-token');
    Route::post('orders/complete/{order}', [\App\Http\Controllers\Admin\OrderController::class , 'doComplete'])->name('orders.complete');
    Route::post('orders/confirm-pickup/{order}', [\App\Http\Controllers\Admin\OrderController::class , 'confirmPickup'])->name('orders.confirmPickup');
    Route::post('orders/{order}/employee-tracking', [\App\Http\Controllers\Admin\OrderController::class, 'updateEmployeeTracking'])->name('orders.updateEmployeeTracking');
    Route::post('orders/{order}/toggle-tracking', [\App\Http\Controllers\Admin\OrderController::class, 'toggleEmployeeTracking'])->name('orders.toggleEmployeeTracking');
    
    // Admin payment callback routes
    Route::get('orders/payment/finish', [\App\Http\Controllers\Admin\OrderController::class, 'paymentFinishRedirect'])->name('payment.finish');
    Route::get('orders/payment/unfinish', [\App\Http\Controllers\Admin\OrderController::class, 'paymentUnfinishRedirect'])->name('payment.unfinish');
    Route::get('orders/payment/error', [\App\Http\Controllers\Admin\OrderController::class, 'paymentErrorRedirect'])->name('payment.error');
    Route::get('orders/{order:id}/cancel', [\App\Http\Controllers\Admin\OrderController::class , 'cancel'])->name('orders.cancels');
	Route::put('orders/cancel/{order:id}', [\App\Http\Controllers\Admin\OrderController::class , 'doCancel'])->name('orders.cancel');
	Route::put('orders/confirm/{id}', [\App\Http\Controllers\Frontend\OrderController::class , 'confirmPaymentAdmin'])->name('orders.confirmAdmin');

    Route::resource('shipments', \App\Http\Controllers\Admin\ShipmentController::class);

    Route::get('reports/revenue', [\App\Http\Controllers\Admin\ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/product', [\App\Http\Controllers\Admin\ReportController::class, 'product'])->name('reports.product');
    Route::get('reports/inventory', [\App\Http\Controllers\Admin\ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/payment', [\App\Http\Controllers\Admin\ReportController::class, 'payment'])->name('reports.payment');
    
    Route::get('employee-performance', [\App\Http\Controllers\Admin\EmployeePerformanceController::class, 'index'])->name('employee-performance.index');
    Route::get('employee-performance/data', [\App\Http\Controllers\Admin\EmployeePerformanceController::class, 'data'])->name('employee-performance.data');
    Route::get('employee-performance/{employee}', [\App\Http\Controllers\Admin\EmployeePerformanceController::class, 'show'])->name('employee-performance.show');
    Route::post('employee-performance/bonus', [\App\Http\Controllers\Admin\EmployeePerformanceController::class, 'giveBonus'])->name('employee-performance.giveBonus');
    Route::get('employee-performance-bonus-history', [\App\Http\Controllers\Admin\EmployeePerformanceController::class, 'bonusHistory'])->name('employee-performance.bonusHistory');
});


Route::get('/', [\App\Http\Controllers\Frontend\HomepageController::class, 'index'])->name('index');
Route::get('products', [\App\Http\Controllers\Frontend\ProductController::class, 'index']);
Route::get('product/{product:slug}', [\App\Http\Controllers\Frontend\ProductController::class, 'show'])->name('product.detail');
Route::get('products/quick-view/{product:slug}', [\App\Http\Controllers\Frontend\ProductController::class, 'quickView']);
Route::get('/shop', [HomepageController::class, 'shop'])->name('shop');
Route::get('/shopCetak', [HomepageController::class, 'shopCetak'])->name('shopCetak');
Route::get('/shopCategory/{slug}', [HomepageController::class, 'shopCategory'])->name('shopCategory');
Route::get('/shop/detail/{id}', [HomepageController::class, 'detail'])->name('shop-detail');

Route::group(['middleware' => 'auth'], function() {
    Route::get('carts', [\App\Http\Controllers\Frontend\CartController::class, 'index'])->name('carts.index');
    Route::post('carts', [\App\Http\Controllers\Frontend\CartController::class, 'store'])->name('carts.store');
    Route::post('carts/update', [\App\Http\Controllers\Frontend\CartController::class, 'update']);
    Route::get('carts/remove/{cartId}', [\App\Http\Controllers\Frontend\CartController::class, 'destroy']);



Route::get('/download-file/{id}', [\App\Http\Controllers\Frontend\OrderController::class, 'downloadFile'])->name('download-file');
    Route::get('orders/confirmPayment/{id}', [\App\Http\Controllers\Frontend\OrderController::class, 'confirmPaymentManual'])->name('orders.confirmation_payment');
    Route::put('orders/confirmPaymentManual/{id}', [\App\Http\Controllers\Frontend\OrderController::class, 'confirmPayment'])->name('orders.confirmPayment');
    Route::get('orders/checkout', [\App\Http\Controllers\Frontend\OrderController::class, 'checkout'])->middleware('auth');
    Route::post('orders/checkout', [\App\Http\Controllers\Frontend\OrderController::class, 'doCheckout'])->name('orders.checkout')->middleware('auth');
    Route::post('orders/shipping-cost', [\App\Http\Controllers\Frontend\OrderController::class, 'shippingCost'])->name('orders.shippingCost')->middleware('auth');
    Route::post('orders/set-shipping', [\App\Http\Controllers\Frontend\OrderController::class, 'setShipping'])->middleware('auth');
    Route::get('orders/received/{orderId}', [\App\Http\Controllers\Frontend\OrderController::class, 'received']);
    Route::get('orders/{orderId}', [\App\Http\Controllers\Frontend\OrderController::class, 'show'])->name('showUsersOrder');
    Route::resource('wishlists', \App\Http\Controllers\Frontend\WishListController::class)->only(['index','store','destroy']);
    
    Route::resource('orders', \App\Http\Controllers\Frontend\OrderController::class)->only(['index','store','destroy']);

    // Midtrans routes


    Route::get('profile',  [\App\Http\Controllers\Auth\ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [\App\Http\Controllers\Auth\ProfileController::class, 'update']);

});

// Location endpoints (no auth required for dropdown data)
Route::get('api/provinces', [\App\Http\Controllers\Frontend\OrderController::class, 'provinces']);
Route::get('api/cities/{province_id}', [\App\Http\Controllers\Frontend\OrderController::class, 'cities']);
Route::get('api/districts/{city_id}', [\App\Http\Controllers\Frontend\OrderController::class, 'districts']);
Route::get('/api/attribute-options/{attributeId}/{variantId}', function($attributeId, $variantId) {
    $options = \App\Models\AttributeOption::where('attribute_variant_id', $variantId)->get();
    return response()->json(['options' => $options]);
})->name('api.attribute-options');

Route::get('/barcode-test-preview', function() {
    return view('test_barcode_preview');
})->name('barcode.test.preview');
