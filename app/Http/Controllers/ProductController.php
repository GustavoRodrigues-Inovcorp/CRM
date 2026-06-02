<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('user_id', auth()->id())
            ->withCount('deals')
            ->orderBy('name')
            ->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'unit'        => 'nullable|string|max:50',
            'active'      => 'boolean',
        ]);

        Product::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Produto criado.');
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'unit'        => 'nullable|string|max:50',
            'active'      => 'boolean',
        ]);

        $product->update($validated);

        return back()->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $product)
    {
        abort_if($product->user_id !== auth()->id(), 403);
        $product->delete();
        return back()->with('success', 'Produto eliminado.');
    }
}