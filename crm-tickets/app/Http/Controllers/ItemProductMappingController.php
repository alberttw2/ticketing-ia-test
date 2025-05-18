<?php

namespace App\Http\Controllers;

use App\Models\ItemProductMapping;
use App\Models\Establishment;
use App\Models\Product;
use Illuminate\Http\Request;

class ItemProductMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ItemProductMapping::with(['establishment', 'product']);
        
        // Filter by establishment if provided
        if ($request->has('establishment_id') && $request->establishment_id) {
            $query->where('establishment_id', $request->establishment_id);
        }
        
        // Filter by product if provided
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        
        $mappings = $query->orderBy('establishment_id')->orderBy('item_name')->paginate(20);
        
        $establishments = Establishment::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('mappings.index', compact('mappings', 'establishments', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $establishments = Establishment::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('mappings.create', compact('establishments', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'establishment_id' => 'required|exists:establishments,id',
            'item_name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
        ]);
        
        // Check if mapping already exists
        $existingMapping = ItemProductMapping::where('establishment_id', $validated['establishment_id'])
            ->where('item_name', $validated['item_name'])
            ->where('product_id', $validated['product_id'])
            ->first();
            
        if ($existingMapping) {
            return redirect()->route('mappings.index')
                ->with('error', 'This mapping already exists.');
        }
        
        ItemProductMapping::create([
            'establishment_id' => $validated['establishment_id'],
            'item_name' => $validated['item_name'],
            'product_id' => $validated['product_id'],
            'confidence' => 1.0, // High confidence for manual mapping
            'manually_verified' => true,
        ]);
        
        return redirect()->route('mappings.index')
            ->with('success', 'Mapping created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mapping = ItemProductMapping::with(['establishment', 'product'])->findOrFail($id);
        
        return view('mappings.show', compact('mapping'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $mapping = ItemProductMapping::findOrFail($id);
        $establishments = Establishment::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('mappings.edit', compact('mapping', 'establishments', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $mapping = ItemProductMapping::findOrFail($id);
        
        $validated = $request->validate([
            'establishment_id' => 'required|exists:establishments,id',
            'item_name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
        ]);
        
        // Check if we're creating a duplicate
        $existingMapping = ItemProductMapping::where('establishment_id', $validated['establishment_id'])
            ->where('item_name', $validated['item_name'])
            ->where('product_id', $validated['product_id'])
            ->where('id', '!=', $id)
            ->first();
            
        if ($existingMapping) {
            return redirect()->route('mappings.edit', $id)
                ->with('error', 'This would create a duplicate mapping.');
        }
        
        $mapping->establishment_id = $validated['establishment_id'];
        $mapping->item_name = $validated['item_name'];
        $mapping->product_id = $validated['product_id'];
        $mapping->confidence = 1.0; // High confidence for manual mapping
        $mapping->manually_verified = true;
        $mapping->save();
        
        return redirect()->route('mappings.index')
            ->with('success', 'Mapping updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mapping = ItemProductMapping::findOrFail($id);
        $mapping->delete();
        
        return redirect()->route('mappings.index')
            ->with('success', 'Mapping deleted successfully.');
    }
}
