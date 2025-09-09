<?php

namespace App\Http\Controllers\Frontend;

use App\Exports\ReportRevenue;
use App\Models\Slide;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\ProductCategory;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Http\Controllers\InstagramController; // Import the InstagramController
use Maatwebsite\Excel\Facades\Excel;

class HomepageController extends Controller
{
    public function index()
    {
        $productActive = Product::where(function($query) {
            $query->where('type', 'simple')
                  ->whereNull('parent_id')
                  ->orWhere('type', 'configurable');
        })->get()->pluck('id');
        $productActives = array($productActive);
        $products = ProductCategory::with('categories', 'products')->limit(8)->whereIn('product_id', $productActives[0])->get();
        $categories = ProductCategory::with('products', 'categories')->whereIn('product_id', $productActives[0])->get();
        $categoriesCount = ProductCategory::with('products', 'categories')->whereIn('product_id', $productActives[0])->pluck('category_id');
        $categoriesName = Category::whereIn('id', $categoriesCount)->get();
        $popular = Product::where(function($query) {
            $query->where('type', 'simple')
                  ->whereNull('parent_id')
                  ->orWhere('type', 'configurable');
        })->active()->limit(6)->get();
        $totalProduct = Product::where(function($query) {
            $query->where('type', 'simple')
                  ->whereNull('parent_id')
                  ->orWhere('type', 'configurable');
        })->count();
        $totalOrder = Order::where('payment_status', 'paid')->count();
        $slides = Slide::active()->orderBy('position', 'ASC')->get();
        $cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
        view()->share('countCart', $cart);
        return view('frontend.homepage', compact('products', 'totalOrder', 'totalProduct', 'categories', 'categoriesName', 'popular', 'slides'));
    }

    public function detail($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            abort(404);
        }
        
        if ($product->parent_id) {
            return redirect()->route('shop-detail', $product->parent_id);
        }
        
        $parentProduct = Product::with(['productInventory', 'productVariants.variantAttributes'])->find($id);
        
        if (!$parentProduct) {
            abort(404);
        }
        
        $productCategory = ProductCategory::where('product_id', $parentProduct->id)->with('categories')->first();
        
        $configurable_attributes = collect();
        $variants = collect();
        $variantOptions = [];
        
        // Handle edge case: simple products that have variants (data inconsistency)
        // For simple products with variants, treat them as configurable
        $hasVariants = $parentProduct->activeVariants()->count() > 0;
        $isConfigurable = $parentProduct->type == 'configurable' || 
                         ($parentProduct->type == 'simple' && $hasVariants);
        
        if ($isConfigurable) {
            $configurable_attributes = \App\Models\Attribute::where('is_configurable', true)
                ->with(['attribute_variants.attribute_options'])
                ->get();
            $variants = $parentProduct->activeVariants()->with(['variantAttributes'])->get();
            
            if ($variants->count() > 0) {
                try {
                    $variantOptions = $parentProduct->getVariantOptions();
                    
                    $minPrice = $variants->min('price');
                    $maxPrice = $variants->max('price');
                    $priceRange = [
                        'min' => $minPrice,
                        'max' => $maxPrice,
                        'same' => $minPrice == $maxPrice
                    ];
                } catch (Exception $e) {
                    $variantOptions = [];
                    $priceRange = null;
                }
            } else {
                $priceRange = null;
            }
        } else {
            $priceRange = null;
        }
        
        $cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
        view()->share('countCart', $cart);
        return view('frontend.shop.detail', compact('parentProduct', 'productCategory', 'configurable_attributes', 'variants', 'variantOptions', 'priceRange'));
    }

    public function reports(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('admin.reports.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function exportExcel($awal, $akhir)
    {
        $fileName = "Laporan-Revenue-{$awal}_{$akhir}.xlsx";

        return Excel::download(
            new ReportRevenue($awal, $akhir),
            $fileName
        );
    }

    public function getReportsData($awal, $akhir)
    {
        $no = 1;
        $data = array();
        $pendapatan = 0;
        $total_pendapatan = 0;
        $total_keuntungan = 0;
        $total_shipping = 0;
        $total_pembelian_seluruh = 0;
        $total_penjualan_seluruh = 0;
        $total_pengeluaran_seluruh = 0;

        $currentDate = $awal;
        while (strtotime($currentDate) <= strtotime($akhir)) {
            $tanggal = $currentDate;
            $currentDate = date('Y-m-d', strtotime("+1 day", strtotime($currentDate)));

            $orders = Order::where('payment_status', 'paid')
                ->where('grand_total', '>', 0)
                ->whereDate('created_at', $tanggal)
                ->with('orderItems.product')
                ->get();

            $validOrders = $orders->filter(function($order) {
                if (!$order->orderItems || count($order->orderItems) == 0) {
                    return false;
                }
                
                $order_net_sales = $order->grand_total - $order->shipping_cost;
                if ($order_net_sales <= 0) {
                    return false;
                }
                
                $order_total_cost = 0;
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product && $orderItem->product->harga_beli) {
                        $order_total_cost += ($orderItem->product->harga_beli * $orderItem->qty);
                    }
                }
                
                return $order_net_sales >= ($order_total_cost * 0.1);
            });
            
            $total_penjualan = $validOrders->sum('grand_total');

            $total_pembelian = Pembelian::whereDate('created_at', $tanggal)
                ->sum('total_harga');

            $total_pengeluaran = Pengeluaran::whereDate('created_at', $tanggal)
                ->sum('nominal');

            $total_shipping = $validOrders->sum('shipping_cost');

            $total_cost_of_goods = 0;
            $total_profit_margin = 0;

            foreach ($validOrders as $order) {
                if ($order->orderItems && count($order->orderItems) > 0) {
                    $order_total_base_price = $order->orderItems->sum(function($item) {
                        return $item->base_price * $item->qty;
                    });
                    
                    $order_net_sales = $order->grand_total - $order->shipping_cost;
                    
                    if ($order_total_base_price <= 0 || $order_net_sales <= 0) {
                        continue;
                    }
                    
                    $order_total_cost = 0;
                    foreach ($order->orderItems as $orderItem) {
                        if ($orderItem->product && $orderItem->product->harga_beli) {
                            $order_total_cost += ($orderItem->product->harga_beli * $orderItem->qty);
                        }
                    }
                    
                    if ($order_net_sales < ($order_total_cost * 0.1)) {
                        continue;
                    }
                    
                    foreach ($order->orderItems as $orderItem) {
                        if ($orderItem->product && $orderItem->product->harga_beli) {
                            $cost_price = $orderItem->product->harga_beli;
                            $base_price = $orderItem->base_price;
                            $qty = $orderItem->qty;
                            
                            $item_proportion = ($base_price * $qty) / $order_total_base_price;
                            $actual_selling_price = ($order_net_sales * $item_proportion) / $qty;
                            
                            $total_cost_of_goods += ($cost_price * $qty);
                            $profit_per_item = $actual_selling_price - $cost_price;
                            $total_profit_margin += ($profit_per_item * $qty);
                        }
                    }
                }
            }

            $net_sales = $total_penjualan - $total_shipping;
            $keuntungan = $total_profit_margin - $total_pengeluaran;

            $total_keuntungan += $keuntungan;
            $pendapatan = $total_penjualan;
            $total_pendapatan += $pendapatan;
            $total_pembelian_seluruh += $total_pembelian;
            $total_penjualan_seluruh += $total_penjualan;
            $total_pengeluaran_seluruh += $total_pengeluaran;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['tanggal'] = $tanggal;
            $row['penjualan'] = $total_penjualan;
            $row['pembelian'] = $total_pembelian;
            $row['pengeluaran'] = $total_pengeluaran;
            $row['pendapatan'] = $pendapatan;
            $row['keuntungan'] = $keuntungan;
            $row['cost_of_goods'] = $total_cost_of_goods;
            $row['net_sales'] = $net_sales;

            $data[] = $row;
        }

        $data[] = [
            'DT_RowIndex' => '',
            'tanggal' => '',
            'penjualan' => $total_penjualan_seluruh,
            'pembelian' => $total_pembelian_seluruh,
            'pengeluaran' => $total_pengeluaran_seluruh,
            'pendapatan' => $total_pendapatan,
            'keuntungan' => $total_keuntungan,
            'cost_of_goods' => 0,
            'net_sales' => 0,
        ];

        return $data;
    }

    public function data($awal, $akhir)
    {
        $rawData = $this->getReportsData($awal, $akhir);
        
        $formattedData = collect($rawData)->map(function($item) {
            if (empty($item['tanggal'])) {
                return [
                    'DT_RowIndex' => '<strong>TOTAL</strong>',
                    'tanggal' => '<strong>TOTAL</strong>',
                    'penjualan' => '<strong>' . format_uang($item['penjualan']) . '</strong>',
                    'pembelian' => '<strong>' . format_uang($item['pembelian']) . '</strong>',
                    'pengeluaran' => '<strong>' . format_uang($item['pengeluaran']) . '</strong>',
                    'pendapatan' => '<strong>' . format_uang($item['pendapatan']) . '</strong>',
                    'keuntungan' => '<strong>' . format_uang($item['keuntungan']) . '</strong>',
                ];
            }
            
            $profitMargin = $item['penjualan'] > 0 ? (($item['keuntungan'] / $item['penjualan']) * 100) : 0;
            $profitStatus = $item['keuntungan'] > 0 ? 'profit' : ($item['keuntungan'] < 0 ? 'loss' : 'break-even');
            
            return [
                'DT_RowIndex' => $item['DT_RowIndex'],
                'tanggal' => tanggal_indonesia($item['tanggal'], false),
                'penjualan' => format_uang($item['penjualan']),
                'pembelian' => format_uang($item['pembelian']),
                'pengeluaran' => format_uang($item['pengeluaran']),
                'pendapatan' => format_uang($item['pendapatan']),
                'keuntungan' => '<span class="badge badge-' . 
                               ($profitStatus == 'profit' ? 'success' : 
                                ($profitStatus == 'loss' ? 'danger' : 'warning')) . '">' . 
                               format_uang($item['keuntungan']) . 
                               ' (' . number_format($profitMargin, 1) . '%)</span>',
                'detail' => [
                    'gross_sales' => format_uang($item['penjualan']),
                    'shipping_cost' => format_uang($item['penjualan'] - $item['net_sales']),
                    'net_sales' => format_uang($item['net_sales']),
                    'cost_of_goods' => format_uang($item['cost_of_goods']),
                    'expenses' => format_uang($item['pengeluaran']),
                    'profit_margin' => format_uang($item['keuntungan']),
                    'profit_percentage' => number_format($profitMargin, 2) . '%',
                    'breakdown' => "Penjualan Kotor: " . format_uang($item['penjualan']) . 
                                 " - Ongkir: " . format_uang($item['penjualan'] - $item['net_sales']) .
                                 " = Penjualan Bersih: " . format_uang($item['net_sales']) .
                                 " - HPP: " . format_uang($item['cost_of_goods']) .
                                 " - Pengeluaran: " . format_uang($item['pengeluaran']) .
                                 " = Keuntungan: " . format_uang($item['keuntungan'])
                ]
            ];
        });

        return datatables()
            ->of($formattedData)
            ->rawColumns(['DT_RowIndex', 'tanggal', 'penjualan', 'pembelian', 'pengeluaran', 'pendapatan', 'keuntungan'])
            ->make(true);
    }

    public function exportPDF($awal, $akhir)
    {
        $data = $this->getReportsData($awal, $akhir);
        $pdf  = Pdf::loadView('admin.reports.pdf', compact('awal', 'akhir', 'data'));
        $pdf->setPaper('a4', 'potrait');

        return $pdf->stream('Laporan-pendapatan-'. date('Y-m-d-his') .'.pdf');
    }

    public function shop(Request $request)
    {
        $produk = Product::where(function($query) {
            $query->where('type', 'simple')
                  ->whereNull('parent_id')
                  ->orWhere('type', 'configurable');
        })->get()->pluck('id');
        $produkss = array($produk);
        $products = ProductCategory::with(['products', 'categories'])->whereIn('product_id', $produkss[0])->get();
        $producteds = ProductCategory::with(['products', 'categories'])->whereIn('product_id', $produkss[0])->get();
        $cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
        view()->share('countCart', $cart);
        $categories = Category::all();

        if ($request->has('search')) {
            $searchTerm = $request->get('search', '');
            $filteredProducts = collect();
            
            foreach ($products as $row) {
                if ($row->products && stripos($row->products->name, $searchTerm) !== false) {
                    if ($row->products->parent_id) {
                        $parentProduct = Product::find($row->products->parent_id);
                        if ($parentProduct) {
                            $existingProduct = $filteredProducts->first(function($item) use ($parentProduct) {
                                return $item->products && $item->products->id === $parentProduct->id;
                            });
                            
                            if (!$existingProduct) {
                                $parentProductCategory = ProductCategory::where('product_id', $parentProduct->id)->first();
                                if ($parentProductCategory) {
                                    $filteredProducts->push($parentProductCategory);
                                }
                            }
                        }
                    } else {
                        $filteredProducts->push($row);
                    }
                }
            }
            
            $producted = $filteredProducts;
        } else {
            $producted = $producteds;
        }

        return view('frontend.shop.index', [
            'products' => $producted,
            'categories' => $categories,
        ]);
    }

    public function shopCetak(Request $request)
    {
        $cat = Category::where('slug', 'like', '%' . 'cetak' . '%')->get()->pluck('id');
        $cats = array($cat);
        $products = ProductCategory::with(['products', 'categories'])->whereIn('category_id', $cats[0])->get();
        $producteds = ProductCategory::with(['products', 'categories'])->whereIn('category_id', $cats[0])->get();
        $cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
        view()->share('countCart', $cart);
        $categories = Category::all();
        return view('frontend.shop.index', compact('products', 'categories', 'producteds'));
    }
    public function shopCategory(Request $request, $slug)
    {
        $cat = Category::where('slug', 'like', '%' . $slug . '%')->get()->pluck('id');
        $cats = array($cat);
        $products = ProductCategory::with(['products', 'categories'])->whereIn('category_id', $cats[0])->get();
        $producteds = ProductCategory::with(['products', 'categories'])->whereIn('category_id', $cats[0])->get();
        $cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
        view()->share('countCart', $cart);
        $categories = Category::all();
        return view('frontend.shop.index', compact('products', 'categories', 'producteds'));
    }
}
