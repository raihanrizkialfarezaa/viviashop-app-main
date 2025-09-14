<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RekamanStok;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianDetailController extends Controller
{
    public function index()
    {
        $id_pembelian = session('id_pembelian');
        $produk = Product::with('productVariants')
                    ->orderBy('name')
                    ->where(function($query) {
                        $query->where('type', 'simple')
                              ->orWhere('type', 'configurable');
                    })
                    ->get();
        $supplier = Supplier::find(session('id_supplier'));
        $diskon = Pembelian::find($id_pembelian)->diskon ?? 0;

        if (! $supplier) {
            abort(404);
        }

        return view('admin.pembelian_detail.index', compact('id_pembelian', 'produk', 'supplier', 'diskon'));
    }

    public function editBayar($id)
    {
        $id_pembelian = $id;
        $produk = Product::orderBy('name')->get();
        $produk_supplier = Pembelian::where('id', $id)->first();
        $detail_pembelian = PembelianDetail::where('id_pembelian', $id)->get();
        $supplier = Supplier::find($produk_supplier->id_supplier);
        $diskon = Pembelian::find($id_pembelian)->diskon ?? 0;
        $tanggal = Pembelian::where('id', $id_pembelian)->first();

        if (! $supplier) {
            abort(404);
        }

        return view('admin.pembelian_detail.editBayar', compact('id_pembelian', 'tanggal', 'detail_pembelian', 'produk', 'supplier', 'diskon'));
    }

    public function data($id)
    {
        $detail = PembelianDetail::with(['products', 'variant'])
            ->where('id_pembelian', $id)
            ->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($item) {
                return '<span class="label label-success">'. $item->products['id'] .'</span>';
            })
            ->addColumn('nama_produk', function ($item) {
                $name = $item->products['name'];
                if ($item->variant) {
                    $variantAttr = $item->variant->variantAttributes->pluck('attribute_value')->implode(', ');
                    $name .= ' (' . $variantAttr . ')';
                }
                return $name;
            })
            ->addColumn('harga_jual', function ($item) {
                $price = $item->variant ? $item->variant->price : $item->products['price'];
                return '<input type="number" class="form-control input-sm price" data-id="'. $item->products['id'] .'" value="'. $price .'">';
            })
            ->addColumn('harga_beli', function ($item) {
                return '<input type="number" class="form-control input-sm harga_beli" data-id="'. $item->products['id'] .'" data-uid="'. $item->id .'" value="'. $item->harga_beli .'">';
            })
            ->addColumn('jumlah', function ($item) {
                return '<input type="number" class="form-control input-sm quantity" data-id="'. $item->id .'" value="'. $item->jumlah .'">';
            })
            ->addColumn('subtotal', function ($item) {
                return 'Rp. '. format_uang($item->subtotal);
            })
            ->addColumn('aksi', function ($item) {
                return '<div class="btn-group">
                            <button onclick="deleteData(`'. route('admin.pembelian_detail.destroy', $item->id) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                        </div>';
            })
            ->with([
                'total' => $detail->sum('subtotal'),
                'total_item' => $detail->sum('jumlah')
            ])
            ->rawColumns(['aksi', 'kode_produk', 'nama_produk', 'jumlah', 'harga_beli', 'harga_jual'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $produk = Product::where('id', $request->id_produk)->first();
            if (! $produk) {
                return response()->json('Produk tidak ditemukan', 400);
            }

            $existingDetail = PembelianDetail::where('id_pembelian', $request->id_pembelian)
                                            ->where('id_produk', $request->id_produk)
                                            ->where('variant_id', $request->variant_id)
                                            ->first();

            if ($existingDetail) {
                return response()->json('Produk sudah ada dalam pembelian ini', 400);
            }

            $detail = new PembelianDetail();
            $detail->id_pembelian = $request->id_pembelian;
            $detail->id_produk = $produk->id;
            $detail->variant_id = $request->variant_id;
            
            if ($request->variant_id) {
                $variant = ProductVariant::find($request->variant_id);
                $detail->harga_beli = $variant ? $variant->harga_beli : ($produk->harga_beli ?? 0);
            } else {
                $detail->harga_beli = $produk->harga_beli ?? 0;
            }

            $detail->jumlah = 1;
            $detail->subtotal = $detail->harga_beli * $detail->jumlah;
            $detail->save();

            DB::commit();
            return response()->json('Data berhasil disimpan', 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json('Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $detail = PembelianDetail::find($id);
        $detail->jumlah = $request->jumlah;
        $detail->subtotal = $detail->harga_beli * $request->jumlah;
        $detail->update();
        // dd($detail);
        if($detail) {
            $id = $detail->id_produk;
            $produk = Product::where('id', $id)->first();
            $stok = $produk->productInventory->qty;
            RekamanStok::create([
                'product_id' => $detail->id_produk,
                'waktu' => Carbon::now(),
                'stok_masuk' => $request->jumlah,
                'stok_awal' => $stok,
                'stok_sisa' => $produk->productInventory->qty += $request->jumlah,
            ]);
        }
    }

    public function updateEdit(Request $request, $id)
    {
        $detail = PembelianDetail::where('id', $id)->first();
        // dd($detail->id);
        // dd($detail->id_pembelian);
        // dd($request->jumlah);
        $rekaman_stok = RekamanStok::where('id_pembelian', $detail->id_pembelian)->where('id_produk', $detail->id_produk)->first();
        $cari = RekamanStok::where('id_pembelian', $detail->id_pembelian)->where('id_produk', $detail->id_produk)->first();
        if (!empty($rekaman_stok->id_produk) == $detail->id_produk) {
            $sum = $request->jumlah - $detail->jumlah;
            // dd($sum);
            if ($sum < 0 && $sum != 0) {
                $positive = $sum * -1;
                $id = $detail->id_produk;
                $produk = Product::where('id', $id)->first();
                $stok = $produk->productInventory->qty;
                $rekaman_stok->update([
                    'product_id' => $detail->id_produk,
                    'waktu' => Carbon::now(),
                    'stok_masuk' => $rekaman_stok->stok_masuk -= $positive,
                    'stok_awal' => $stok,
                    'stok_sisa' => $rekaman_stok->stok_sisa -= $positive,
                ]);
                $produk = Product::find($detail->id_produk);
                $produk->productInventory->qty -= $positive;
                $produk->update();
            } elseif($sum >= 1 && $sum != 0) {
                $id = $detail->id_produk;
                $produk = Product::where('id', $id)->first();
                $stok = $produk->productInventory->qty;
                $rekaman_stok->update([
                    'product_id' => $detail->id_produk,
                    'waktu' => Carbon::now(),
                    'stok_masuk' => $rekaman_stok->stok_masuk += $sum,
                    'stok_sisa' => $rekaman_stok->stok_sisa += $sum,
                    'stok_awal' => $stok,
                ]);
                $positive = $sum * -1;
                $produk = Product::find($detail->id_produk);
                $produk->productInventory->qty += $sum;
                $produk->update();
            }

        } else {
            $produk = Product::find($detail->id_produk);
            $sum = $request->jumlah;
            $stok = $produk->productInventory->qty;
            RekamanStok::create([
                'product_id' => $detail->id_produk,
                'waktu' => Carbon::now(),
                'stok_masuk' => $sum,
                'stok_awal' => $produk->productInventory->qty,
                'stok_sisa' => $stok += $sum,
            ]);
            $produk->productInventory->qty += $sum;
            $produk->update();
        }

        $detail->jumlah = $request->jumlah;
        $detail->subtotal = $detail->harga_beli * $request->jumlah;
        $detail->update();
    }

    public function destroy($id)
    {
        $detail = PembelianDetail::find($id);
        $detail->delete();

        return response(null, 204);
    }

    public function getVariants($productId)
    {
        $product = Product::with(['productVariants.variantAttributes'])->find($productId);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $variants = $product->productVariants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'price' => $variant->price,
                'harga_beli' => $variant->harga_beli ?? 0,
                'stock' => $variant->stock,
                'attributes' => $variant->variantAttributes->pluck('attribute_value')->implode(', '),
                'full_name' => $variant->variantAttributes->pluck('attribute_value')->implode(', ')
            ];
        });

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type
            ],
            'variants' => $variants
        ]);
    }

    public function loadForm($diskon, $total)
    {
        $bayar = $total - ($diskon / 100 * $total);
        $data  = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => format_uang($bayar),
            'terbilang' => ucwords(terbilang($bayar). ' Rupiah')
        ];

        return response()->json($data);
    }
}
