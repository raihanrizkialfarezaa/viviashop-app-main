<?php

use Illuminate\Support\Facades\Route;

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
            if (Schema::hasTable($table)) {
                echo "   ✓ Table '{$table}' exists\n";
            } else {
                echo "   ✗ Table '{$table}' does not exist\n";
                return "Migration not complete";
            }
        }
        
        // Test 2: Check if columns exist in orders table
        echo "\n2. Checking orders table columns:\n";
        $orderColumns = ['handled_by', 'use_employee_tracking'];
        foreach ($orderColumns as $column) {
            if (Schema::hasColumn('orders', $column)) {
                echo "   ✓ Column '{$column}' exists in orders table\n";
            } else {
                echo "   ✗ Column '{$column}' does not exist in orders table\n";
                return "Orders table migration not complete";
            }
        }
        
        // Test 3: Check models
        echo "\n3. Testing models:\n";
        try {
            $employeePerformance = new \App\Models\EmployeePerformance();
            echo "   ✓ EmployeePerformance model exists\n";
        } catch (Exception $e) {
            echo "   ✗ EmployeePerformance model error: " . $e->getMessage() . "\n";
        }
        
        try {
            $employeeBonus = new \App\Models\EmployeeBonus();
            echo "   ✓ EmployeeBonus model exists\n";
        } catch (Exception $e) {
            echo "   ✗ EmployeeBonus model error: " . $e->getMessage() . "\n";
        }
        
        // Test 4: Check order model relationship
        echo "\n4. Testing Order model updates:\n";
        try {
            $order = new \App\Models\Order();
            if (method_exists($order, 'employeePerformance')) {
                echo "   ✓ Order->employeePerformance() relationship exists\n";
            } else {
                echo "   ✗ Order->employeePerformance() relationship missing\n";
            }
            
            if (method_exists($order, 'isHandledByEmployee')) {
                echo "   ✓ Order->isHandledByEmployee() method exists\n";
            } else {
                echo "   ✗ Order->isHandledByEmployee() method missing\n";
            }
        } catch (Exception $e) {
            echo "   ✗ Order model error: " . $e->getMessage() . "\n";
        }
        
        // Test 5: Check controller
        echo "\n5. Testing controller:\n";
        try {
            $controller = new \App\Http\Controllers\Admin\EmployeePerformanceController();
            echo "   ✓ EmployeePerformanceController exists\n";
        } catch (Exception $e) {
            echo "   ✗ EmployeePerformanceController error: " . $e->getMessage() . "\n";
        }
        
        // Test 6: Check routes
        echo "\n6. Testing routes:\n";
        $routes = [
            'admin.employee-performance.index',
            'admin.employee-performance.data',
            'admin.employee-performance.show',
            'admin.employee-performance.giveBonus',
            'admin.orders.updateEmployeeTracking',
            'admin.orders.toggleEmployeeTracking'
        ];
        
        foreach ($routes as $routeName) {
            try {
                $route = route($routeName, ['employee' => 'test', 'order' => 1]);
                echo "   ✓ Route '{$routeName}' exists\n";
            } catch (Exception $e) {
                echo "   ✗ Route '{$routeName}' error: " . $e->getMessage() . "\n";
            }
        }
        
        // Test 7: Check views
        echo "\n7. Testing views:\n";
        $views = [
            'admin.employee-performance.index',
            'admin.employee-performance.show'
        ];
        
        foreach ($views as $viewName) {
            if (view()->exists($viewName)) {
                echo "   ✓ View '{$viewName}' exists\n";
            } else {
                echo "   ✗ View '{$viewName}' does not exist\n";
            }
        }
        
        echo "\n✅ Employee Performance Implementation Test Complete!\n";
        echo "\nNext steps:\n";
        echo "1. Navigate to admin panel\n";
        echo "2. Go to Employee Performance menu\n";
        echo "3. Create an order and test employee tracking\n";
        echo "4. Complete the order to test performance recording\n";
        echo "5. Give bonus to test bonus functionality\n";
        
        return "Test completed successfully!";
        
    } catch (Exception $e) {
        return "Test failed: " . $e->getMessage();
    }
});
