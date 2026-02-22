<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        // Returns the list of products in the user's wishlist
        return response()->json($request->user()->wishlist);
    }

    public function store(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $user = $request->user();
        $productId = $request->product_id;

        // toggle() is a built-in Eloquent method for many-to-many relationships
        // It attaches if missing, and detaches if existing.
        $status = $user->wishlist()->toggle($productId);

        $attached = count($status['attached']) > 0;

        return response()->json([
            'message' => $attached ? 'Added to wishlist' : 'Removed from wishlist',
            'is_wishlisted' => $attached
        ]);
    }
}
