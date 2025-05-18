<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use App\Models\ItemProductMapping;
use App\Models\Ticket;
use Illuminate\Http\Request;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $establishments = Establishment::withCount('tickets')->orderBy('name')->get();
        return view('establishments.index', compact('establishments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('establishments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Establishment::create($validated);

        return redirect()->route('establishments.index')
            ->with('success', 'Establishment created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $establishment = Establishment::findOrFail($id);
        $tickets = $establishment->tickets()->orderBy('created_at', 'desc')->paginate(10);
        $mappings = ItemProductMapping::where('establishment_id', $id)
            ->with('product')
            ->orderBy('item_name')
            ->get();

        return view('establishments.show', compact('establishment', 'tickets', 'mappings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $establishment = Establishment::findOrFail($id);
        return view('establishments.edit', compact('establishment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $establishment = Establishment::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $establishment->update($validated);

        return redirect()->route('establishments.index')
            ->with('success', 'Establishment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $establishment = Establishment::findOrFail($id);
        
        // Check if there are related tickets
        if ($establishment->tickets()->count() > 0) {
            return redirect()->route('establishments.index')
                ->with('error', 'Cannot delete establishment with associated tickets.');
        }
        
        $establishment->delete();

        return redirect()->route('establishments.index')
            ->with('success', 'Establishment deleted successfully.');
    }
    
    /**
     * View template data for the establishment.
     */
    public function viewTemplate(string $id)
    {
        $establishment = Establishment::findOrFail($id);
        return view('establishments.template', compact('establishment'));
    }
    
    /**
     * Update template data for the establishment.
     */
    public function updateTemplate(Request $request, string $id)
    {
        $establishment = Establishment::findOrFail($id);
        
        $validated = $request->validate([
            'patterns' => 'nullable|array',
            'item_patterns' => 'nullable|array',
        ]);
        
        $templateData = [
            'patterns' => $validated['patterns'] ?? [],
            'item_patterns' => $validated['item_patterns'] ?? [],
        ];
        
        $establishment->template_data = $templateData;
        $establishment->save();
        
        return redirect()->route('establishments.show', $establishment->id)
            ->with('success', 'Template data updated successfully.');
    }
}
