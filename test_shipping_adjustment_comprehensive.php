<?php

/**
 * Comprehensive Test for Shipping Cost Adjustment Feature
 * 
 * This test simulates:
 * 1. Customer creates an order with courier delivery
 * 2. Admin adjusts shipping cost due to API vs field rate discrepancy 
 * 3. Customer views updated shipping information
 * 
 * Created: <?= date('Y-m-d H:i:s') ?>
 */

require_once 'vendor/autoload.php';

use App\Models\Order;
use App\Models\User;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShippingAdjustmentTest
{
    private $testOrderId;
    private $adminUserId;
    private $customerUserId;

    public function __construct()
    {
        echo "🚀 Starting Shipping Cost Adjustment Feature Test\n";
        echo "=" . str_repeat("=", 60) . "\n";
    }

    /**
     * Run complete test flow
     */
    public function runTest()
    {
        try {
            // Step 1: Setup test data
            $this->setupTestData();
            
            // Step 2: Test order creation
            $this->testOrderCreation();
            
            // Step 3: Test admin shipping adjustment
            $this->testAdminShippingAdjustment();
            
            // Step 4: Test customer view updates
            $this->testCustomerViewUpdates();
            
            // Step 5: Test edge cases
            $this->testEdgeCases();
            
            echo "\n✅ ALL TESTS PASSED SUCCESSFULLY!\n";
            echo "=" . str_repeat("=", 60) . "\n";
            
        } catch (Exception $e) {
            echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            echo "=" . str_repeat("=", 60) . "\n";
            throw $e;
        }
    }

    /**
     * Setup test data
     */
    private function setupTestData()
    {
        echo "\n📋 Step 1: Setting up test data...\n";
        
        // Find or create admin user
        $this->adminUserId = User::where('email', 'admin@test.com')->first()->id ?? 1;
        echo "   ✓ Admin user ID: {$this->adminUserId}\n";
        
        // Find or create customer user
        $this->customerUserId = User::where('email', 'customer@test.com')->first()->id ?? 2;
        echo "   ✓ Customer user ID: {$this->customerUserId}\n";
    }

    /**
     * Test order creation with courier delivery
     */
    private function testOrderCreation()
    {
        echo "\n📦 Step 2: Testing order creation with courier delivery...\n";
        
        // Create test order with shipping
        $orderData = [
            'user_id' => $this->customerUserId,
            'code' => 'TEST-' . time(),
            'status' => Order::CONFIRMED,
            'order_date' => now(),
            'payment_due' => now()->addDays(1),
            'payment_status' => Order::UNPAID,
            'payment_method' => 'manual',
            'base_total_price' => 100000,
            'tax_amount' => 10000,
            'shipping_cost' => 7000, // API rate from RajaOngkir
            'shipping_courier' => 'JNE',
            'shipping_service_name' => 'JNE REG',
            'grand_total' => 117000,
            'customer_full_name' => 'Test Customer',
            'customer_first_name' => 'Test',
            'customer_last_name' => 'Customer',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'customer_address1' => 'Test Address 1',
            'customer_address2' => 'Test Address 2',
            'customer_postcode' => '12345',
        ];
        
        $order = Order::create($orderData);
        $this->testOrderId = $order->id;
        
        echo "   ✓ Order created with ID: {$this->testOrderId}\n";
        echo "   ✓ Original shipping cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
        echo "   ✓ Original courier: {$order->shipping_courier}\n";
        echo "   ✓ Original service: {$order->shipping_service_name}\n";
        
        // Verify order has no adjustment initially
        $this->assertFalse($order->isShippingCostAdjusted(), "Order should not be adjusted initially");
        echo "   ✓ Confirmed: Order has no shipping adjustment initially\n";
    }

    /**
     * Test admin shipping adjustment functionality
     */
    private function testAdminShippingAdjustment()
    {
        echo "\n⚙️ Step 3: Testing admin shipping adjustment...\n";
        
        $order = Order::find($this->testOrderId);
        
        // Test the adjustment (simulating real field rates vs API rates)
        $originalShippingCost = $order->shipping_cost; // 7000 (API rate)
        $newShippingCost = 10000; // Real field rate
        $newCourier = 'JNE Express';
        $newService = 'JNE YES';
        $adjustmentNote = 'API rate Rp7,000 vs field rate Rp10,000 - adjusted to actual courier charges';
        
        echo "   📊 Adjusting shipping:\n";
        echo "      • From: Rp" . number_format($originalShippingCost, 0, ",", ".") . " ({$order->shipping_courier})\n";
        echo "      • To: Rp" . number_format($newShippingCost, 0, ",", ".") . " ({$newCourier})\n";
        echo "      • Reason: {$adjustmentNote}\n";
        
        // Perform adjustment using model method
        $adjustmentResult = $order->adjustShippingCost(
            $newShippingCost,
            $newCourier,
            $newService,
            $adjustmentNote,
            $this->adminUserId
        );
        
        $this->assertTrue($adjustmentResult, "Shipping adjustment should succeed");
        
        // Reload order to get updated data
        $order->refresh();
        
        // Verify adjustment was applied correctly
        $this->assertEquals($newShippingCost, $order->shipping_cost, "Shipping cost should be updated");
        $this->assertEquals($newCourier, $order->shipping_courier, "Shipping courier should be updated");
        $this->assertEquals($newService, $order->shipping_service_name, "Shipping service should be updated");
        $this->assertEquals($originalShippingCost, $order->original_shipping_cost, "Original shipping cost should be preserved");
        $this->assertEquals($adjustmentNote, $order->shipping_adjustment_note, "Adjustment note should be saved");
        $this->assertTrue($order->shipping_cost_adjusted, "Adjustment flag should be true");
        $this->assertEquals($this->adminUserId, $order->shipping_adjusted_by, "Adjustment should be attributed to admin");
        $this->assertNotNull($order->shipping_adjusted_at, "Adjustment timestamp should be set");
        
        echo "   ✅ Shipping adjustment applied successfully:\n";
        echo "      • Current shipping cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
        echo "      • Original shipping cost: Rp" . number_format($order->original_shipping_cost, 0, ",", ".") . "\n";
        echo "      • Current courier: {$order->shipping_courier}\n";
        echo "      • Original courier: {$order->original_shipping_courier}\n";
        echo "      • Adjustment note: {$order->shipping_adjustment_note}\n";
        echo "      • Adjusted by user ID: {$order->shipping_adjusted_by}\n";
        echo "      • Adjusted at: {$order->shipping_adjusted_at}\n";
        
        // Verify grand total was recalculated
        $expectedGrandTotal = $order->base_total_price + $order->tax_amount + $order->shipping_cost;
        $this->assertEquals($expectedGrandTotal, $order->grand_total, "Grand total should be recalculated");
        echo "   ✓ Grand total recalculated: Rp" . number_format($order->grand_total, 0, ",", ".") . "\n";
        
        // Test helper methods
        $this->assertTrue($order->isShippingCostAdjusted(), "isShippingCostAdjusted should return true");
        $this->assertTrue($order->hasOriginalShippingData(), "hasOriginalShippingData should return true");
        $this->assertEquals(3000, $order->getShippingCostDifference(), "Shipping cost difference should be 3000");
        echo "   ✓ Helper methods working correctly\n";
    }

    /**
     * Test customer view updates
     */
    private function testCustomerViewUpdates()
    {
        echo "\n👁️ Step 4: Testing customer view updates...\n";
        
        $order = Order::find($this->testOrderId);
        
        // Simulate customer viewing order details
        echo "   📱 Simulating customer order detail view...\n";
        
        // Test order details display
        if ($order->isShippingCostAdjusted()) {
            echo "   ✅ Customer sees adjusted shipping information:\n";
            echo "      • Shipping Cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
            echo "      • Original Cost: Rp" . number_format($order->original_shipping_cost, 0, ",", ".") . "\n";
            echo "      • Adjustment Note: {$order->shipping_adjustment_note}\n";
        } else {
            echo "   ❌ Customer does not see adjustment information\n";
        }
        
        // Test order summary display
        echo "   💰 Order Summary Display:\n";
        echo "      • Subtotal: Rp" . number_format($order->base_total_price, 0, ",", ".") . "\n";
        echo "      • Tax (10%): Rp" . number_format($order->tax_amount, 0, ",", ".") . "\n";
        
        if ($order->isShippingCostAdjusted()) {
            echo "      • Shipping Cost (Adjusted): Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
            echo "      • Original shipping cost: Rp" . number_format($order->original_shipping_cost, 0, ",", ".") . "\n";
        } else {
            echo "      • Shipping Cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
        }
        
        echo "      • Grand Total: Rp" . number_format($order->grand_total, 0, ",", ".") . "\n";
        
        $this->assertTrue($order->isShippingCostAdjusted(), "Customer should see adjustment indicators");
        echo "   ✓ Customer view displays adjustment information correctly\n";
    }

    /**
     * Test edge cases and validation
     */
    private function testEdgeCases()
    {
        echo "\n🧪 Step 5: Testing edge cases...\n";
        
        $order = Order::find($this->testOrderId);
        
        // Test 1: Try to adjust cancelled order (should fail)
        echo "   🚫 Testing adjustment on cancelled order...\n";
        $order->update(['status' => Order::CANCELLED]);
        
        $result = $order->adjustShippingCost(15000, 'Test Courier', 'Test Service', 'Test note', $this->adminUserId);
        $this->assertFalse($result, "Should not allow adjustment on cancelled order");
        echo "   ✓ Correctly prevents adjustment on cancelled orders\n";
        
        // Reset order status
        $order->update(['status' => Order::CONFIRMED]);
        
        // Test 2: Negative shipping cost (should fail)
        echo "   💸 Testing negative shipping cost...\n";
        $result = $order->adjustShippingCost(-1000, 'Test Courier', 'Test Service', 'Test note', $this->adminUserId);
        $this->assertFalse($result, "Should not allow negative shipping cost");
        echo "   ✓ Correctly prevents negative shipping costs\n";
        
        // Test 3: Multiple adjustments
        echo "   🔄 Testing multiple adjustments...\n";
        $result1 = $order->adjustShippingCost(12000, 'Updated Courier', 'Updated Service', 'Second adjustment', $this->adminUserId);
        $this->assertTrue($result1, "Should allow multiple adjustments");
        
        $order->refresh();
        $this->assertEquals(12000, $order->shipping_cost, "Should update to latest shipping cost");
        echo "   ✓ Multiple adjustments work correctly\n";
        
        // Test 4: Check adjustment relationship
        echo "   👤 Testing adjustment relationship...\n";
        $adjustedBy = $order->shippingAdjustedBy;
        $this->assertNotNull($adjustedBy, "Should have adjustment relationship");
        $this->assertEquals($this->adminUserId, $adjustedBy->id, "Should link to correct admin user");
        echo "   ✓ Adjustment relationship works correctly\n";
    }

    /**
     * Helper assertion methods
     */
    private function assertTrue($condition, $message)
    {
        if (!$condition) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    private function assertFalse($condition, $message)
    {
        if ($condition) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: {$message}. Expected: {$expected}, Actual: {$actual}");
        }
    }

    private function assertNotNull($value, $message)
    {
        if ($value === null) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    /**
     * Cleanup test data
     */
    public function cleanup()
    {
        if ($this->testOrderId) {
            echo "\n🧹 Cleaning up test data...\n";
            
            // Delete test order
            Order::where('id', $this->testOrderId)->delete();
            echo "   ✓ Test order deleted\n";
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    echo "🔧 SHIPPING COST ADJUSTMENT - COMPREHENSIVE TEST\n";
    echo "=" . str_repeat("=", 60) . "\n";
    echo "Testing API rate vs field rate adjustment feature\n";
    echo "Scenario: RajaOngkir API shows Rp7,000 but field rate is Rp10,000\n\n";
    
    $test = new ShippingAdjustmentTest();
    
    try {
        $test->runTest();
        echo "\n🎉 COMPREHENSIVE TEST COMPLETED SUCCESSFULLY!\n";
        echo "The shipping cost adjustment feature is working correctly.\n";
        echo "Admin can now adjust shipping costs when API rates differ from field rates.\n";
        
    } catch (Exception $e) {
        echo "\n💥 COMPREHENSIVE TEST FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Please check the implementation and try again.\n";
        
    } finally {
        $test->cleanup();
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
}