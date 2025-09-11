<?php

namespace App\Services;

use App\Models\PrintSession;
use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PrintService
{
    public function generateSession()
    {
        PrintSession::cleanup();
        
        return PrintSession::generateNew();
    }

    public function getSession($token)
    {
        return PrintSession::where('session_token', $token)
                          ->where('is_active', true)
                          ->where('expires_at', '>', now())
                          ->first();
    }

    public function getPrintProducts()
    {
        return Product::where('is_print_service', true)
                     ->where('status', Product::ACTIVE)
                     ->with(['activeVariants' => function($query) {
                         $query->orderBy('paper_size', 'asc')
                               ->orderBy('print_type', 'asc');
                     }])
                     ->get();
    }

    public function uploadFiles(array $files, PrintSession $session)
    {
        $uploadedFiles = [];
        $totalPages = 0;

        foreach ($files as $file) {
            if ($this->validateFile($file)) {
                $fileData = $this->storeFile($file, $session);
                
                $printFile = PrintFile::create([
                    'print_session_id' => $session->id,
                    'file_path' => $fileData['path'],
                    'file_name' => $fileData['name'],
                    'file_type' => $fileData['type'],
                    'file_size' => $fileData['size'],
                    'pages_count' => $fileData['pages_count'],
                ]);
                
                $uploadedFiles[] = $printFile;
                $totalPages += $fileData['pages_count'];
            }
        }

        return [
            'success' => true,
            'files' => collect($uploadedFiles)->map(function($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'file_name' => $file->file_name,
                    'file_type' => $file->file_type,
                    'file_size' => $file->file_size,
                    'pages_count' => $file->pages_count,
                    'file_path' => $file->file_path
                ];
            })->toArray(),
            'total_pages' => $totalPages
        ];
    }

    public function deleteFile($fileId, PrintSession $session)
    {
        $file = PrintFile::where('id', $fileId)
                         ->where('print_session_id', $session->id)
                         ->first();
        
        if (!$file) {
            throw new \Exception('File not found or access denied');
        }

        if (Storage::exists($file->file_path)) {
            Storage::delete($file->file_path);
        }

        $file->delete();

        $remainingFiles = $session->printFiles()->get();
        $totalPages = $remainingFiles->sum('pages_count');

        return [
            'success' => true,
            'files' => $remainingFiles->map(function($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'file_name' => $file->file_name,
                    'file_type' => $file->file_type,
                    'file_size' => $file->file_size,
                    'pages_count' => $file->pages_count,
                    'file_path' => $file->file_path
                ];
            })->toArray(),
            'total_pages' => $totalPages
        ];
    }

    public function createPrintOrder(array $data, PrintSession $session)
    {
        $variant = ProductVariant::findOrFail($data['variant_id']);
        $product = $variant->product;

        if (!$product->is_print_service) {
            throw new \Exception('Invalid product for print service');
        }

        $sessionFiles = $session->printFiles;
        if ($sessionFiles->isEmpty()) {
            throw new \Exception('No files uploaded for this session');
        }

        $totalPages = $sessionFiles->sum('pages_count');
        $unitPrice = $variant->price;
        $totalPrice = $unitPrice * $totalPages * ($data['quantity'] ?? 1);

        $printOrder = PrintOrder::create([
            'order_code' => PrintOrder::generateCode(),
            'customer_phone' => $data['customer_phone'],
            'customer_name' => $data['customer_name'],
            'file_data' => json_encode($sessionFiles->map(function($file) {
                return [
                    'name' => $file->file_name,
                    'type' => $file->file_type,
                    'size' => $file->file_size,
                    'pages' => $file->pages_count
                ];
            })),
            'paper_product_id' => $product->id,
            'paper_variant_id' => $variant->id,
            'print_type' => $variant->print_type,
            'quantity' => $data['quantity'] ?? 1,
            'total_pages' => $totalPages,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'payment_method' => $data['payment_method'],
            'session_id' => $session->id,
        ]);

        foreach ($sessionFiles as $file) {
            $file->update([
                'print_order_id' => $printOrder->id,
                'print_session_id' => null
            ]);
        }

        $session->updateStep(PrintSession::STEP_PAYMENT);

        return $printOrder;
    }

    public function calculatePrice($variantId, $totalPages, $quantity = 1)
    {
        $variant = ProductVariant::findOrFail($variantId);
        
        if (!$variant->product->is_print_service) {
            throw new \Exception('Invalid product for print service');
        }

        $unitPrice = $variant->price;
        $totalPrice = $unitPrice * $totalPages * $quantity;

        return [
            'unit_price' => $unitPrice,
            'total_pages' => $totalPages,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'variant' => $variant
        ];
    }

    public function processPayment(PrintOrder $printOrder, $paymentData)
    {
        switch ($printOrder->payment_method) {
            case 'toko':
                return $this->processStorePayment($printOrder, $paymentData);
            case 'manual':
                return $this->processManualPayment($printOrder, $paymentData);
            case 'automatic':
                return $this->processAutomaticPayment($printOrder, $paymentData);
            default:
                throw new \Exception('Invalid payment method');
        }
    }

    public function markReadyToPrint(PrintOrder $printOrder)
    {
        if (!$printOrder->isPaid()) {
            throw new \Exception('Payment not confirmed');
        }

        $printOrder->update(['status' => PrintOrder::STATUS_READY_TO_PRINT]);
    }

    public function printDocument(PrintOrder $printOrder)
    {
        if (!$printOrder->canPrint()) {
            throw new \Exception('Order is not ready for printing');
        }

        $printOrder->markAsPrinting();

        try {
            $this->sendToPrinter($printOrder);
            $printOrder->markAsPrinted();
        } catch (\Exception $e) {
            Log::error('Print failed for order ' . $printOrder->order_code . ': ' . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function completePrintOrder(PrintOrder $printOrder)
    {
        if (!in_array($printOrder->status, [PrintOrder::STATUS_PRINTING, PrintOrder::STATUS_PRINTED])) {
            throw new \Exception('Order is not ready for completion');
        }

        if ($printOrder->status === PrintOrder::STATUS_PRINTING) {
            $printOrder->markAsPrinted();
        }

        $printOrder->markAsCompleted();
        $this->cleanupFiles($printOrder);

        $session = $printOrder->session;
        if ($session) {
            $session->updateStep(PrintSession::STEP_COMPLETE);
            $session->markInactive();
        }

        Log::info('Print order completed and files cleaned: ' . $printOrder->order_code);

        return true;
    }

    public function getPrintQueue()
    {
        return PrintOrder::printQueue()
                         ->with(['paperProduct', 'paperVariant', 'files'])
                         ->orderBy('created_at', 'asc')
                         ->get();
    }

    private function validateFile(UploadedFile $file)
    {
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv', 'log', 'rtf', 'odt', 'ods', 'ppt', 'pptx'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            throw new \Exception('File type not supported: ' . $extension);
        }

        if ($file->getSize() > 50 * 1024 * 1024) { // 50MB limit
            throw new \Exception('File size too large: ' . $file->getClientOriginalName());
        }

        return true;
    }

    private function storeFile(UploadedFile $file, PrintSession $session)
    {
        $date = now()->format('Y-m-d');
        $directory = "print-files/{$date}/{$session->session_token}";
        
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs($directory, $filename, 'local');
        
        $pagesCount = $this->countPages($file);

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'type' => strtolower($file->getClientOriginalExtension()),
            'size' => $file->getSize(),
            'pages_count' => $pagesCount
        ];
    }

    private function countPages(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        switch ($extension) {
            case 'pdf':
                return $this->countPdfPages($file);
            case 'doc':
            case 'docx':
            case 'rtf':
            case 'odt':
                return $this->estimateDocPages($file);
            case 'xls':
            case 'xlsx':
            case 'ods':
                return $this->estimateExcelPages($file);
            case 'ppt':
            case 'pptx':
                return $this->estimatePptPages($file);
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 1;
            case 'txt':
            case 'csv':
            case 'log':
                return $this->estimateTxtPages($file);
            default:
                return 1;
        }
    }

    private function countPdfPages(UploadedFile $file)
    {
        try {
            $content = file_get_contents($file->path());
            $pageCount = preg_match_all("/\/Page\W/", $content);
            return max(1, $pageCount);
        } catch (\Exception $e) {
            Log::warning('Could not count PDF pages: ' . $e->getMessage());
            return 1;
        }
    }

    private function estimateDocPages(UploadedFile $file)
    {
        $sizeInBytes = $file->getSize();
        $estimatedPages = max(1, round($sizeInBytes / 50000));
        return $estimatedPages;
    }

    private function estimateExcelPages(UploadedFile $file)
    {
        $sizeInBytes = $file->getSize();
        $estimatedPages = max(1, round($sizeInBytes / 75000));
        return $estimatedPages;
    }

    private function estimatePptPages(UploadedFile $file)
    {
        $sizeInBytes = $file->getSize();
        $estimatedPages = max(1, round($sizeInBytes / 100000));
        return $estimatedPages;
    }

    private function estimateTxtPages(UploadedFile $file)
    {
        try {
            $content = file_get_contents($file->path());
            $lines = substr_count($content, "\n") + 1;
            $estimatedPages = max(1, ceil($lines / 50));
            return $estimatedPages;
        } catch (\Exception $e) {
            return 1;
        }
    }

    private function processStorePayment(PrintOrder $printOrder, $paymentData)
    {
        $printOrder->update([
            'status' => PrintOrder::STATUS_PAYMENT_PENDING,
            'payment_status' => PrintOrder::PAYMENT_WAITING
        ]);

        return ['status' => 'pending', 'message' => 'Menunggu konfirmasi pembayaran di toko'];
    }

    private function processManualPayment(PrintOrder $printOrder, $paymentData)
    {
        if (!isset($paymentData['payment_proof'])) {
            throw new \Exception('Payment proof is required');
        }

        $proofPath = $this->storePaymentProof($paymentData['payment_proof'], $printOrder);
        
        $printOrder->update([
            'status' => PrintOrder::STATUS_PAYMENT_PENDING,
            'payment_status' => PrintOrder::PAYMENT_WAITING,
            'payment_proof' => $proofPath
        ]);

        return ['status' => 'pending', 'message' => 'Bukti pembayaran berhasil dikirim, menunggu verifikasi'];
    }

    private function processAutomaticPayment(PrintOrder $printOrder, $paymentData)
    {
        $paymentToken = $this->generateMidtransToken($printOrder);
        
        $printOrder->update([
            'status' => PrintOrder::STATUS_PAYMENT_PENDING,
            'payment_status' => PrintOrder::PAYMENT_UNPAID,
            'payment_token' => $paymentToken['token'],
            'payment_url' => $paymentToken['redirect_url']
        ]);

        return [
            'status' => 'redirect',
            'token' => $paymentToken['token'],
            'redirect_url' => $paymentToken['redirect_url']
        ];
    }

    private function storePaymentProof(UploadedFile $file, PrintOrder $printOrder)
    {
        $directory = "print-payments/{$printOrder->order_code}";
        $filename = 'payment_proof_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $filename, 'local');
    }

    private function generateMidtransToken(PrintOrder $printOrder)
    {
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is3ds');

        $params = [
            'transaction_details' => [
                'order_id' => $printOrder->order_code,
                'gross_amount' => (int) $printOrder->total_price,
            ],
            'item_details' => [
                [
                    'id' => $printOrder->paper_variant_id,
                    'price' => $printOrder->unit_price,
                    'quantity' => $printOrder->total_pages * $printOrder->quantity,
                    'name' => $printOrder->paperVariant->name . ' (' . $printOrder->total_pages . ' halaman)',
                ]
            ],
            'customer_details' => [
                'first_name' => $printOrder->customer_name,
                'phone' => $printOrder->customer_phone,
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            return [
                'token' => $snapToken,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans token generation failed: ' . $e->getMessage());
            throw new \Exception('Payment gateway error');
        }
    }

    private function sendToPrinter(PrintOrder $printOrder)
    {
        Log::info('Sending print order to printer: ' . $printOrder->order_code);
        return true;
    }

    private function cleanupFiles(PrintOrder $printOrder)
    {
        foreach ($printOrder->files as $file) {
            try {
                Storage::disk('local')->delete($file->file_path);
                Log::info('Deleted file: ' . $file->file_path);
            } catch (\Exception $e) {
                Log::warning('Could not delete file: ' . $file->file_path . ' - ' . $e->getMessage());
            }
        }

        if ($printOrder->payment_proof) {
            try {
                Storage::disk('local')->delete($printOrder->payment_proof);
                Log::info('Deleted payment proof: ' . $printOrder->payment_proof);
            } catch (\Exception $e) {
                Log::warning('Could not delete payment proof: ' . $printOrder->payment_proof . ' - ' . $e->getMessage());
            }
        }
    }
}
