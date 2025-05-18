<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketItem;
use App\Models\Establishment;
use App\Models\Product;
use App\Models\ItemProductMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Ticket::with('establishment');
        
        // Filter by establishment if provided
        if ($request->has('establishment_id') && $request->establishment_id) {
            $query->where('establishment_id', $request->establishment_id);
        }
        
        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);
        $establishments = Establishment::orderBy('name')->get();
        
        return view('tickets.index', compact('tickets', 'establishments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not used - tickets are only created via the processing script
        return redirect()->route('tickets.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Not used - tickets are only created via the processing script
        return redirect()->route('tickets.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ticket = Ticket::with(['establishment', 'items.product'])->findOrFail($id);
        $imageData = null;
        
        // Try to get the image data
        $imagePath = $ticket->original_path;
        if (file_exists($imagePath)) {
            $imageData = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($imagePath));
        } else {
            // Check in the processed directories
            $ticketDirs = ['/tickets/OK', '/tickets/KO', '/tickets/REVIEW'];
            foreach ($ticketDirs as $dir) {
                $possiblePath = $dir . '/' . $ticket->filename;
                if (file_exists($possiblePath)) {
                    $imageData = 'data:image/' . pathinfo($possiblePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($possiblePath));
                    break;
                }
            }
        }
        
        return view('tickets.show', compact('ticket', 'imageData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $ticket = Ticket::with(['establishment', 'items.product'])->findOrFail($id);
        $imageData = null;
        
        // Try to get the image data
        $imagePath = $ticket->original_path;
        if (file_exists($imagePath)) {
            $imageData = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($imagePath));
        } else {
            // Check in the processed directories
            $ticketDirs = ['/tickets/OK', '/tickets/KO', '/tickets/REVIEW'];
            foreach ($ticketDirs as $dir) {
                $possiblePath = $dir . '/' . $ticket->filename;
                if (file_exists($possiblePath)) {
                    $imageData = 'data:image/' . pathinfo($possiblePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($possiblePath));
                    break;
                }
            }
        }
        
        $establishments = Establishment::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('tickets.edit', compact('ticket', 'imageData', 'establishments', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        $validated = $request->validate([
            'establishment_id' => 'required|exists:establishments,id',
            'total_amount' => 'nullable|numeric',
            'ticket_date' => 'nullable|date',
        ]);
        
        // Update ticket
        $ticket->establishment_id = $validated['establishment_id'];
        $ticket->total_amount = $validated['total_amount'];
        $ticket->ticket_date = $validated['ticket_date'];
        $ticket->manually_reviewed = true;
        
        if ($ticket->status == Ticket::STATUS_REVIEW) {
            $ticket->status = Ticket::STATUS_PROCESSED;
        }
        
        $ticket->save();
        
        // Learn from this manual review
        $this->learnFromTicket($ticket);
        
        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully.');
    }
    
    /**
     * Update ticket items.
     */
    public function updateItems(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:ticket_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|numeric',
            'items.*.product_id' => 'nullable|exists:products,id',
        ]);
        
        foreach ($validated['items'] as $itemData) {
            if (isset($itemData['id'])) {
                // Update existing item
                $item = TicketItem::find($itemData['id']);
                if ($item) {
                    $item->name = $itemData['name'];
                    $item->price = $itemData['price'];
                    $item->quantity = $itemData['quantity'];
                    $item->total = $itemData['price'] * $itemData['quantity'];
                    
                    if ($item->product_id != $itemData['product_id']) {
                        $item->product_id = $itemData['product_id'];
                        
                        // Learn from this mapping
                        if ($itemData['product_id']) {
                            $this->createOrUpdateMapping($ticket->establishment_id, $item->name, $itemData['product_id'], true);
                        }
                    }
                    
                    $item->manually_verified = true;
                    $item->save();
                }
            } else {
                // Create new item
                $item = new TicketItem();
                $item->ticket_id = $ticket->id;
                $item->name = $itemData['name'];
                $item->price = $itemData['price'];
                $item->quantity = $itemData['quantity'];
                $item->total = $itemData['price'] * $itemData['quantity'];
                $item->product_id = $itemData['product_id'];
                $item->manually_verified = true;
                $item->save();
                
                // Learn from this new item
                if ($itemData['product_id']) {
                    $this->createOrUpdateMapping($ticket->establishment_id, $item->name, $itemData['product_id'], true);
                }
            }
        }
        
        // Recalculate total
        $ticket->total_amount = $ticket->calculateTotal();
        $ticket->save();
        
        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket items updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        // Delete all related items
        $ticket->items()->delete();
        
        // Delete the ticket
        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }
    
    /**
     * Delete a ticket item.
     */
    public function deleteItem(string $id)
    {
        $item = TicketItem::findOrFail($id);
        $ticketId = $item->ticket_id;
        
        $item->delete();
        
        // Recalculate total
        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $ticket->total_amount = $ticket->calculateTotal();
            $ticket->save();
        }
        
        return redirect()->route('tickets.show', $ticketId)
            ->with('success', 'Item deleted successfully.');
    }
    
    /**
     * Display the original ticket image.
     */
    public function showImage(string $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        // Try to get the image
        $imagePath = $ticket->original_path;
        if (!file_exists($imagePath)) {
            // Check in the processed directories
            $ticketDirs = ['/tickets/OK', '/tickets/KO', '/tickets/REVIEW'];
            foreach ($ticketDirs as $dir) {
                $possiblePath = $dir . '/' . $ticket->filename;
                if (file_exists($possiblePath)) {
                    $imagePath = $possiblePath;
                    break;
                }
            }
        }
        
        if (!file_exists($imagePath)) {
            abort(404, 'Image not found');
        }
        
        $file = File::get($imagePath);
        $type = File::mimeType($imagePath);
        
        return Response::make($file, 200, ['Content-Type' => $type]);
    }
    
    /**
     * Learn from a manually reviewed ticket.
     */
    private function learnFromTicket(Ticket $ticket)
    {
        // If establishment is set, learn patterns for future tickets
        if ($ticket->establishment_id) {
            $establishment = Establishment::find($ticket->establishment_id);
            
            if ($establishment) {
                $templateData = $establishment->template_data ?: [];
                
                // Store patterns if they don't already exist
                if (!isset($templateData['patterns'])) {
                    $templateData['patterns'] = [];
                }
                
                // Extract key phrases from the OCR text that might identify this establishment
                if ($ticket->ocr_text) {
                    $lines = preg_split('/\\r\\n|\\r|\\n/', $ticket->ocr_text);
                    foreach ($lines as $line) {
                        // Look for lines that might contain the establishment name or identification
                        if (strpos(strtolower($line), strtolower($establishment->name)) !== false) {
                            if (!in_array($line, $templateData['patterns'])) {
                                $templateData['patterns'][] = $line;
                            }
                        }
                    }
                }
                
                $establishment->template_data = $templateData;
                $establishment->save();
            }
        }
    }
    
    /**
     * Create or update a product mapping.
     */
    private function createOrUpdateMapping($establishmentId, $itemName, $productId, $manuallyVerified = false)
    {
        // Check if mapping already exists
        $mapping = ItemProductMapping::where('establishment_id', $establishmentId)
            ->where('item_name', $itemName)
            ->where('product_id', $productId)
            ->first();
            
        if ($mapping) {
            // Update existing mapping
            $mapping->confidence = 1.0; // High confidence for manual verification
            $mapping->manually_verified = $manuallyVerified;
            $mapping->save();
        } else {
            // Create new mapping
            ItemProductMapping::create([
                'establishment_id' => $establishmentId,
                'item_name' => $itemName,
                'product_id' => $productId,
                'confidence' => 1.0,
                'manually_verified' => $manuallyVerified
            ]);
        }
    }
}
