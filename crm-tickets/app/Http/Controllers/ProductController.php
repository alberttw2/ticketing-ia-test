<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ItemProductMapping;
use App\Models\Establishment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::withCount('ticketItems')->orderBy('name')->get();
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        
        // Get all establishments where this product appears
        $establishments = Establishment::whereHas('itemProductMappings', function($query) use ($id) {
            $query->where('product_id', $id);
        })->get();
        
        // Get all ticket items for this product
        $ticketItems = DB::table('ticket_items')
            ->join('tickets', 'tickets.id', '=', 'ticket_items.ticket_id')
            ->join('establishments', 'establishments.id', '=', 'tickets.establishment_id')
            ->select(
                'ticket_items.*',
                'tickets.filename',
                'tickets.ticket_date',
                'establishments.name as establishment_name',
                'establishments.id as establishment_id'
            )
            ->where('ticket_items.product_id', $id)
            ->orderBy('tickets.ticket_date', 'desc')
            ->paginate(15);
        
        // Get all mappings for this product
        $mappings = ItemProductMapping::where('product_id', $id)
            ->with('establishment')
            ->get();
        
        return view('products.show', compact('product', 'establishments', 'ticketItems', 'mappings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        
        // Check if there are related ticket items
        if ($product->ticketItems()->count() > 0) {
            return redirect()->route('products.index')
                ->with('error', 'Cannot delete product with associated ticket items.');
        }
        
        // Remove all mappings first
        ItemProductMapping::where('product_id', $id)->delete();
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
    
    /**
     * Export product data to Excel.
     */
    public function export(string $id)
    {
        $product = Product::findOrFail($id);
        
        // In a real implementation, we would generate an Excel file here
        // For this demo, we'll just return a view with the data
        
        $establishments = Establishment::whereHas('itemProductMappings', function($query) use ($id) {
            $query->where('product_id', $id);
        })->get();
        
        $ticketItems = DB::table('ticket_items')
            ->join('tickets', 'tickets.id', '=', 'ticket_items.ticket_id')
            ->join('establishments', 'establishments.id', '=', 'tickets.establishment_id')
            ->select(
                'ticket_items.*',
                'tickets.filename',
                'tickets.ticket_date',
                'establishments.name as establishment_name'
            )
            ->where('ticket_items.product_id', $id)
            ->orderBy('tickets.ticket_date', 'desc')
            ->get();
        
        return view('products.export', compact('product', 'establishments', 'ticketItems'));
    }
}
