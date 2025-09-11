<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintSession;
use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrintServiceController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    public function index()
    {
        $activeSessions = PrintSession::active()->count();
        $pendingOrders = PrintOrder::where('payment_status', PrintOrder::PAYMENT_WAITING)->count();
        $printQueue = PrintOrder::printQueue()->count();
        $todayOrders = PrintOrder::whereDate('created_at', today())->count();
        
        $recentOrders = PrintOrder::with(['paperProduct', 'paperVariant'])
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get();

        return view('admin.print-service.index', compact(
            'activeSessions', 
            'pendingOrders', 
            'printQueue', 
            'todayOrders',
            'recentOrders'
        ));
    }

    public function queue()
    {
        $printQueue = $this->printService->getPrintQueue();
        
        return view('admin.print-service.queue', compact('printQueue'));
    }

    public function sessions()
    {
        $sessions = PrintSession::with('printOrders')
                               ->orderBy('created_at', 'desc')
                               ->paginate(20);
        
        return view('admin.print-service.sessions', compact('sessions'));
    }

    public function orders()
    {
        $orders = PrintOrder::with(['paperProduct', 'paperVariant', 'session'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(20);
        
        return view('admin.print-service.orders', compact('orders'));
    }

    public function generateSession(Request $request)
    {
        try {
            $session = $this->printService->generateSession();
            
            return response()->json([
                'success' => true,
                'session' => $session,
                'qr_code_url' => $session->getQrCodeUrl()
            ]);
        } catch (\Exception $e) {
            Log::error('Generate session error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate session'], 500);
        }
    }

    public function confirmPayment(Request $request, $id)
    {
        try {
            $printOrder = PrintOrder::findOrFail($id);
            
            if ($printOrder->payment_method !== 'toko' && $printOrder->payment_method !== 'manual') {
                return response()->json(['error' => 'Invalid payment method for manual confirmation'], 400);
            }

            $printOrder->markAsPaymentConfirmed();
            $this->printService->markReadyToPrint($printOrder);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Confirm payment error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function printOrder(Request $request, $id)
    {
        try {
            $printOrder = PrintOrder::findOrFail($id);

            $this->printService->printDocument($printOrder);

            return response()->json([
                'success' => true,
                'message' => 'Document sent to printer successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Print order error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function printFiles(Request $request, $id)
    {
        try {
            $printOrder = PrintOrder::with(['files'])->findOrFail($id);
            
            $files = [];
            foreach ($printOrder->files as $file) {
                $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
                if (file_exists($fullPath)) {
                    $files[] = [
                        'id' => $file->id,
                        'name' => basename($fullPath),
                        'path' => $fullPath,
                        'view_url' => route('admin.print-service.view-file', $file->id)
                    ];
                }
            }

            if (empty($files)) {
                return response()->json(['error' => 'No files found for printing'], 400);
            }

            return response()->json([
                'success' => true,
                'files' => $files,
                'order_code' => $printOrder->order_code,
                'customer_name' => $printOrder->customer_name,
                'print_data' => [
                    'paper_size' => $printOrder->paperVariant->paper_size ?? 'A4',
                    'print_type' => $printOrder->print_type,
                    'quantity' => $printOrder->quantity,
                    'total_pages' => $printOrder->total_pages
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Print files error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function viewFile($fileId)
    {
        try {
            $printFile = PrintFile::findOrFail($fileId);
            $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $printFile->file_path));
            
            if (!file_exists($fullPath)) {
                abort(404, 'File not found');
            }
            
            $mimeType = mime_content_type($fullPath);
            
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"'
            ]);
            
        } catch (\Exception $e) {
            abort(404, 'File not found: ' . $e->getMessage());
        }
    }

    public function completeOrder(Request $request, $id)
    {
        try {
            $printOrder = PrintOrder::findOrFail($id);

            if ($printOrder->status !== PrintOrder::STATUS_PRINTING) {
                return response()->json(['error' => 'Order is not in printing status'], 400);
            }

            $printOrder->markAsPrinted();
            $this->printService->completePrintOrder($printOrder);

            return response()->json([
                'success' => true,
                'message' => 'Order completed and files deleted for privacy'
            ]);
        } catch (\Exception $e) {
            Log::error('Complete order error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cancelOrder(Request $request, $id)
    {
        try {
            $printOrder = PrintOrder::findOrFail($id);
            
            $printOrder->update(['status' => PrintOrder::STATUS_CANCELLED]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cancel order error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reports(Request $request)
    {
        $startDate = $request->get('start_date', today()->format('Y-m-d'));
        $endDate = $request->get('end_date', today()->format('Y-m-d'));

        $orders = PrintOrder::whereBetween('created_at', [$startDate, $endDate])
                           ->with(['paperProduct', 'paperVariant'])
                           ->get();

        $totalRevenue = $orders->where('payment_status', PrintOrder::PAYMENT_PAID)->sum('total_price');
        $totalOrders = $orders->count();
        $completedOrders = $orders->where('status', PrintOrder::STATUS_COMPLETED)->count();
        $totalPages = $orders->sum('total_pages');

        $stats = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'total_pages' => $totalPages,
            'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
        ];

        return view('admin.print-service.reports', compact('orders', 'stats', 'startDate', 'endDate'));
    }

    public function downloadPaymentProof($id)
    {
        try {
            $printOrder = PrintOrder::findOrFail($id);
            
            if (!$printOrder->payment_proof) {
                return abort(404, 'Payment proof not found');
            }

            return response()->download(storage_path('app/' . $printOrder->payment_proof));
        } catch (\Exception $e) {
            Log::error('Download payment proof error: ' . $e->getMessage());
            return abort(500, 'File download failed');
        }
    }
}
