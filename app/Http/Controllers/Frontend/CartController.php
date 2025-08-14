<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Throwable;

class CartController extends Controller
{
    public function index()
    {
		// Cart::destroy();
		$items = Cart::content();
		// dd($items);
		$cart = Cart::content()->count();
        $setting = Setting::first();
        view()->share('setting', $setting);
		view()->share('countCart', $cart);
		// dd($items);

		return view('frontend.carts.index', compact('items'));
    }

    private function _getItemQuantity($itemQty)
	{
		$items = Cart::content();
		$itemQuantity = $itemQty;
		if ($items) {
			foreach ($items as $item) {
				$itemQuantity += $item->qty;
			}
		}
		return $itemQuantity;
    }

    public function store(Request $request)
    {
        if (Auth::check()) {
			$params = $request->except('_token');
		// dd($params);

			$product = Product::findOrFail($params['product_id']);
			$slug = $product->slug;
			$productName = $product->name;
			$originalProduct = $product;

			$attributes = [];
			if ($product->configurable()) {
				if (isset($params['attributes']) && is_array($params['attributes'])) {
					foreach ($params['attributes'] as $attributeCode => $data) {
						if (is_array($data) && isset($data['option_id'])) {
							$option = \App\Models\AttributeOption::find($data['option_id']);
							if ($option) {
								$attributes[$attributeCode] = $option->name;
							}
						}
					}
					
					$product = $this->_findProductVariantByOptions($product->id, $params['attributes']);
					if (!$product) {
						$variants = Product::where('parent_id', $originalProduct->id)->get();
						if ($variants->count() === 1) {
							$product = $variants->first();
						} else {
							return response()->json([
								'status' => 'error',
								'message' => 'Product variant not found'
							]);
						}
					}
					$productName = $originalProduct->name;
				} else {
					$variants = Product::where('parent_id', $originalProduct->id)->get();
					if ($variants->count() === 1) {
						$product = $variants->first();
						$productName = $originalProduct->name;
					} else {
						return response()->json([
							'status' => 'error',
							'message' => 'Product variant selection required'
						]);
					}
				}
			}

			$itemQuantity =  $this->_getItemQuantity($params['qty']);

			try {

			} catch (\Throwable $th) {
				// dd($th);

			}
			if ($product->productInventory->qty < $itemQuantity) {
				// throw new \App\Exceptions\OutOfStockException('The product '. $product->sku .' is out of stock');
				return response()->json([
					'status' => 'stok_habis',
				]);
			} else {
				Cart::add(
					$product->id,
					$productName,
					(int)$params['qty'],
					(float)$product->price,
					['options' => $attributes]
				)->associate('App\Models\Product');
				// dd($cart);

				return response()->json([
					'status' => 'success',
				]);
			}
		} else {
			return redirect()->route('login');
		}


	}

	public function update(Request $request)
	{
		$params = $request->except('_token');

		Cart::update($request->productId, $request->qty);

		Session::flash('success', 'The cart has been updated');
		return redirect('carts');
	}

	public function destroy($id)
	{
		Cart::remove($id);

		return redirect()->back()->with([
			'message' => 'Produk berhasil di hapus !',
			'alert-type' => 'danger'
		]);
	}

	private function _findProductVariant($parentProductId, $selectedAttributes)
	{
		// Get all variants of the parent product
		$variants = Product::where('parent_id', $parentProductId)
			->with(['productAttributeValues.attribute_option'])
			->get();

		foreach ($variants as $variant) {
			$variantMatches = true;
			
			// Check if this variant has all the selected attributes
			foreach ($selectedAttributes as $variantId => $optionId) {
				$hasAttribute = $variant->productAttributeValues->contains(function ($value) use ($optionId) {
					return $value->attribute_option_id == $optionId;
				});
				
				if (!$hasAttribute) {
					$variantMatches = false;
					break;
				}
			}
			
			if ($variantMatches) {
				return $variant;
			}
		}

		return null;
	}

	private function _findProductVariantByOptions($parentProductId, $selectedAttributes)
	{
		$variants = Product::where('parent_id', $parentProductId)
			->with(['productAttributeValues.attribute_option'])
			->get();

		foreach ($variants as $variant) {
			$variantMatches = true;
			
			foreach ($selectedAttributes as $attributeCode => $data) {
				if (is_array($data) && isset($data['option_id'])) {
					$hasAttribute = $variant->productAttributeValues->contains(function ($value) use ($data) {
						return $value->attribute_option_id == $data['option_id'];
					});
					
					if (!$hasAttribute) {
						$variantMatches = false;
						break;
					}
				}
			}
			
			if ($variantMatches) {
				return $variant;
			}
		}

		return null;
	}
}
