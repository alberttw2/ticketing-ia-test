<?php

namespace App\Console\Commands;

use App\Models\Establishment;
use App\Models\ItemProductMapping;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Intervention\Image\Facades\Image;
use Phpml\Classification\NaiveBayes;
use Phpml\Classification\SVC;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;

class ProcessTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:process {--force : Process all tickets regardless of status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process ticket images from the NEW directory';

    /**
     * Directory paths
     */
    protected $newDir = '/tickets/NEW';
    protected $okDir = '/tickets/OK';
    protected $koDir = '/tickets/KO';
    protected $reviewDir = '/tickets/REVIEW';
    
    /**
     * Allowed image extensions
     */
    protected $allowedExtensions = ['jpg', 'jpeg', 'png'];
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ticket processing...');
        
        // Check if directories exist
        $this->ensureDirectoriesExist();
        
        // Get all files from the NEW directory
        $files = File::files($this->newDir);
        
        $this->info('Found ' . count($files) . ' files to process.');
        
        foreach ($files as $file) {
            $this->processTicket($file);
        }
        
        $this->info('Ticket processing completed!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Ensure all required directories exist
     */
    protected function ensureDirectoriesExist()
    {
        foreach ([$this->newDir, $this->okDir, $this->koDir, $this->reviewDir] as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("Created directory: $dir");
            }
        }
    }
    
    /**
     * Process a single ticket file
     */
    protected function processTicket($file)
    {
        $filename = basename($file);
        $extension = strtolower(File::extension($file));
        
        $this->info("Processing ticket: $filename");
        
        // Check if file is an allowed image type
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->error("Unsupported file type: $extension");
            $this->moveFile($file, $this->koDir);
            return;
        }
        
        try {
            // Create a new ticket record
            $ticket = new Ticket();
            $ticket->filename = $filename;
            $ticket->original_path = (string) $file;
            $ticket->status = Ticket::STATUS_NEW;
            $ticket->save();
            
            // Process the image with OCR
            $ocrResult = $this->performOCR($file);
            
            if (empty($ocrResult)) {
                $this->error("OCR failed for ticket: $filename");
                $this->updateTicketStatus($ticket, Ticket::STATUS_ERROR, ['error' => 'OCR processing failed']);
                $this->moveFile($file, $this->koDir);
                return;
            }
            
            // Update ticket with OCR text
            $ticket->ocr_text = $ocrResult;
            $ticket->save();
            
            // Process the OCR text with AI
            $aiResult = $this->processWithAI($ticket);
            
            if ($aiResult['status'] === 'error') {
                $this->error("AI processing failed for ticket: $filename");
                $this->updateTicketStatus($ticket, Ticket::STATUS_ERROR, $aiResult);
                $this->moveFile($file, $this->koDir);
                return;
            }
            
            if ($aiResult['status'] === 'review') {
                $this->line("Ticket $filename needs review");
                $this->updateTicketStatus($ticket, Ticket::STATUS_REVIEW, $aiResult);
                $this->moveFile($file, $this->reviewDir);
                return;
            }
            
            // Successfully processed
            $this->info("Ticket $filename processed successfully!");
            $this->updateTicketStatus($ticket, Ticket::STATUS_PROCESSED, $aiResult);
            $this->moveFile($file, $this->okDir);
            
            // Create ticket items
            if (!empty($aiResult['items'])) {
                $this->createTicketItems($ticket, $aiResult['items']);
            }
            
        } catch (\Exception $e) {
            $this->error("Error processing ticket $filename: " . $e->getMessage());
            Log::error("Ticket processing error: " . $e->getMessage(), ['file' => $filename, 'exception' => $e]);
            
            // If ticket was created, update its status
            if (isset($ticket) && $ticket->id) {
                $this->updateTicketStatus($ticket, Ticket::STATUS_ERROR, ['error' => $e->getMessage()]);
            }
            
            $this->moveFile($file, $this->koDir);
        }
    }
    
    /**
     * Perform OCR on the ticket image
     */
    protected function performOCR($file)
    {
        // Create a temporary preprocessed image
        $tmpImagePath = tempnam(sys_get_temp_dir(), 'ocr_') . '.jpg';
        
        // Preprocess the image to improve OCR results
        $image = Image::make($file);
        
        // Increase size if too small
        if ($image->width() < 1000) {
            $image->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        // Apply image enhancements for better OCR
        $image->greyscale()
              ->contrast(10)
              ->brightness(5)
              ->sharpen(5);
        
        $image->save($tmpImagePath);
        
        // Perform OCR using Tesseract
        $ocr = new TesseractOCR($tmpImagePath);
        $ocr->lang('eng', 'spa'); // Support English and Spanish
        
        // Get OCR result
        $text = $ocr->run();
        
        // Clean up temporary file
        unlink($tmpImagePath);
        
        return $text;
    }
    
    /**
     * Process the OCR text with AI to extract structured data
     */
    protected function processWithAI(Ticket $ticket)
    {
        $ocrText = $ticket->ocr_text;
        $processingLog = [];
        
        // Step 1: Identify the establishment
        $establishmentResult = $this->identifyEstablishment($ocrText);
        $processingLog['establishment_detection'] = $establishmentResult;
        
        if ($establishmentResult['status'] === 'error') {
            return [
                'status' => 'review',
                'reason' => 'Could not identify establishment',
                'processing_log' => $processingLog
            ];
        }
        
        $establishment = $establishmentResult['establishment'];
        $ticket->establishment_id = $establishment->id;
        $ticket->save();
        
        // Step 2: Extract items and prices
        $itemsResult = $this->extractItems($ocrText, $establishment);
        $processingLog['items_extraction'] = $itemsResult;
        
        if ($itemsResult['status'] === 'error') {
            return [
                'status' => 'review',
                'reason' => 'Could not extract items',
                'establishment' => $establishment->name,
                'processing_log' => $processingLog
            ];
        }
        
        // Step 3: Extract the total amount
        $totalAmount = $this->extractTotalAmount($ocrText);
        $processingLog['total_amount'] = $totalAmount;
        
        // Step 4: Extract the date
        $ticketDate = $this->extractTicketDate($ocrText);
        $processingLog['ticket_date'] = $ticketDate;
        
        if ($ticketDate) {
            $ticket->ticket_date = $ticketDate;
        }
        
        if ($totalAmount) {
            $ticket->total_amount = $totalAmount;
        }
        
        // Step 5: Map items to products
        $itemsWithProducts = $this->mapItemsToProducts($itemsResult['items'], $establishment);
        $processingLog['product_mapping'] = $itemsWithProducts['log'];
        
        // Determine if review is needed
        $needsReview = $establishmentResult['confidence'] < 0.7 || 
                      $itemsResult['confidence'] < 0.7 || 
                      $itemsWithProducts['unmapped_count'] > 0 ||
                      ($totalAmount && abs($totalAmount - array_sum(array_column($itemsResult['items'], 'total'))) > 0.1);
        
        if ($needsReview) {
            return [
                'status' => 'review',
                'reason' => 'Low confidence or unmapped products',
                'establishment' => $establishment->name,
                'establishment_id' => $establishment->id,
                'items' => $itemsWithProducts['items'],
                'total_amount' => $totalAmount,
                'ticket_date' => $ticketDate,
                'processing_log' => $processingLog
            ];
        }
        
        return [
            'status' => 'success',
            'establishment' => $establishment->name,
            'establishment_id' => $establishment->id,
            'items' => $itemsWithProducts['items'],
            'total_amount' => $totalAmount,
            'ticket_date' => $ticketDate,
            'processing_log' => $processingLog
        ];
    }
    
    /**
     * Identify the establishment from the OCR text
     */
    protected function identifyEstablishment($ocrText)
    {
        // Get all establishments
        $establishments = Establishment::all();
        
        if ($establishments->isEmpty()) {
            // If no establishments exist yet, need manual review
            return [
                'status' => 'error',
                'message' => 'No establishments in the database'
            ];
        }
        
        $bestMatch = null;
        $highestConfidence = 0;
        
        foreach ($establishments as $establishment) {
            // Check if the establishment name appears in the OCR text
            if (stripos($ocrText, $establishment->name) !== false) {
                return [
                    'status' => 'success',
                    'establishment' => $establishment,
                    'confidence' => 1.0,
                    'method' => 'exact_match'
                ];
            }
            
            // If template data exists, use it for pattern matching
            if (!empty($establishment->template_data)) {
                $templateData = $establishment->template_data;
                
                if (isset($templateData['patterns'])) {
                    foreach ($templateData['patterns'] as $pattern) {
                        if (preg_match('/' . preg_quote($pattern, '/') . '/i', $ocrText)) {
                            $confidence = 0.9; // High confidence but not exact match
                            
                            if ($confidence > $highestConfidence) {
                                $highestConfidence = $confidence;
                                $bestMatch = $establishment;
                            }
                        }
                    }
                }
            }
            
            // Basic fuzzy matching for establishment names
            $nameSimilarity = $this->calculateTextSimilarity($establishment->name, $ocrText);
            if ($nameSimilarity > $highestConfidence) {
                $highestConfidence = $nameSimilarity;
                $bestMatch = $establishment;
            }
        }
        
        if ($bestMatch && $highestConfidence > 0.3) {
            return [
                'status' => 'success',
                'establishment' => $bestMatch,
                'confidence' => $highestConfidence,
                'method' => 'similarity_match'
            ];
        }
        
        // No good match found
        return [
            'status' => 'error',
            'message' => 'Could not identify establishment with sufficient confidence',
            'highest_confidence' => $highestConfidence
        ];
    }
    
    /**
     * Extract items and prices from the OCR text
     */
    protected function extractItems($ocrText, Establishment $establishment)
    {
        $items = [];
        $confidence = 0;
        $method = 'unknown';
        
        // If we have template data for this establishment, use it
        if (!empty($establishment->template_data) && isset($establishment->template_data['item_patterns'])) {
            $patterns = $establishment->template_data['item_patterns'];
            $method = 'template_patterns';
            
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $ocrText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if (isset($match['name']) && isset($match['price'])) {
                            $name = trim($match['name']);
                            $price = $this->parsePrice($match['price']);
                            $quantity = isset($match['quantity']) ? $this->parseQuantity($match['quantity']) : 1;
                            $total = $price * $quantity;
                            
                            $items[] = [
                                'name' => $name,
                                'price' => $price,
                                'quantity' => $quantity,
                                'total' => $total
                            ];
                        }
                    }
                    
                    $confidence = 0.9; // High confidence when using patterns
                }
            }
        }
        
        // If no items found with patterns, use general line-by-line approach
        if (empty($items)) {
            $method = 'general_extraction';
            
            // Split the text into lines
            $lines = preg_split('/\\r\\n|\\r|\\n/', $ocrText);
            
            foreach ($lines as $line) {
                // Look for lines containing a product and a price
                // Most receipts have a pattern like "Product name     10.99"
                if (preg_match('/^(.+?)(?:\\s{2,}|\\t)([0-9]+[.,][0-9]{2})(?:\\s|$)/u', $line, $matches)) {
                    $name = trim($matches[1]);
                    $price = $this->parsePrice($matches[2]);
                    
                    // Try to extract quantity
                    $quantity = 1;
                    if (preg_match('/([0-9]+)\\s*[xX]\\s*([0-9]+[.,][0-9]{2})/', $line, $qtyMatches)) {
                        $quantity = intval($qtyMatches[1]);
                        // Use the unit price instead
                        $price = $this->parsePrice($qtyMatches[2]);
                    }
                    
                    $total = $price * $quantity;
                    
                    $items[] = [
                        'name' => $name,
                        'price' => $price,
                        'quantity' => $quantity,
                        'total' => $total
                    ];
                }
            }
            
            $confidence = count($items) > 0 ? 0.7 : 0;
        }
        
        if (empty($items)) {
            return [
                'status' => 'error',
                'message' => 'Could not extract any items',
                'method' => $method
            ];
        }
        
        return [
            'status' => 'success',
            'items' => $items,
            'confidence' => $confidence,
            'method' => $method
        ];
    }
    
    /**
     * Extract the total amount from the OCR text
     */
    protected function extractTotalAmount($ocrText)
    {
        // Common patterns for total amount in receipts
        $patterns = [
            '/total(?:\\s|:)+([0-9]+[.,][0-9]{2})/i',
            '/total(?:.+):?\\s*([0-9]+[.,][0-9]{2})/i',
            '/suma(?:\\s|:)+([0-9]+[.,][0-9]{2})/i',
            '/importe(?:\\s|:)+([0-9]+[.,][0-9]{2})/i',
            '/(?:^|\\s)total\\s*(?:€|EUR)?\\s*([0-9]+[.,][0-9]{2})/im',
            '/(?:^|\\s)TOTAL:?\\s*([0-9]+[.,][0-9]{2})/im'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $ocrText, $matches)) {
                return $this->parsePrice($matches[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract the ticket date from the OCR text
     */
    protected function extractTicketDate($ocrText)
    {
        // Common date formats in receipts (adjust as needed for your locale)
        $patterns = [
            // DD/MM/YYYY
            '/(?:date|fecha|data|datum)(?:\\s|:)+([0-9]{1,2}[\/\.-][0-9]{1,2}[\/\.-][0-9]{2,4})/i',
            // MM/DD/YYYY
            '/(?:date|fecha|data|datum)(?:\\s|:)+([0-9]{1,2}[\/\.-][0-9]{1,2}[\/\.-][0-9]{2,4})/i',
            // Just look for date patterns
            '/([0-9]{1,2}[\/\.-][0-9]{1,2}[\/\.-][0-9]{2,4})/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $ocrText, $matches)) {
                // Try to parse the date
                try {
                    return date('Y-m-d', strtotime($matches[1]));
                } catch (\Exception $e) {
                    // Continue to next pattern if this one fails
                    continue;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Map extracted items to known products
     */
    protected function mapItemsToProducts($items, Establishment $establishment)
    {
        $result = [
            'items' => [],
            'unmapped_count' => 0,
            'log' => []
        ];
        
        foreach ($items as $item) {
            $mapping = ItemProductMapping::where('establishment_id', $establishment->id)
                ->where('item_name', $item['name'])
                ->orderBy('confidence', 'desc')
                ->first();
            
            if ($mapping) {
                $item['product_id'] = $mapping->product_id;
                $item['product_name'] = $mapping->product->name;
                $item['mapping_confidence'] = $mapping->confidence;
                
                $result['log'][] = [
                    'item' => $item['name'],
                    'mapped_to' => $mapping->product->name,
                    'confidence' => $mapping->confidence
                ];
            } else {
                // Try to find similar products using text similarity
                $similarProduct = $this->findSimilarProduct($item['name']);
                
                if ($similarProduct) {
                    $item['product_id'] = $similarProduct['product']->id;
                    $item['product_name'] = $similarProduct['product']->name;
                    $item['mapping_confidence'] = $similarProduct['confidence'];
                    
                    $result['log'][] = [
                        'item' => $item['name'],
                        'mapped_to' => $similarProduct['product']->name,
                        'confidence' => $similarProduct['confidence'],
                        'method' => 'similarity'
                    ];
                    
                    // Create a new mapping with low confidence (it will be reviewed)
                    ItemProductMapping::create([
                        'establishment_id' => $establishment->id,
                        'item_name' => $item['name'],
                        'product_id' => $similarProduct['product']->id,
                        'confidence' => $similarProduct['confidence'],
                        'manually_verified' => false
                    ]);
                } else {
                    $result['unmapped_count']++;
                    
                    $result['log'][] = [
                        'item' => $item['name'],
                        'mapped_to' => null,
                        'confidence' => 0,
                        'method' => 'none'
                    ];
                }
            }
            
            $result['items'][] = $item;
        }
        
        return $result;
    }
    
    /**
     * Find a similar product based on name similarity
     */
    protected function findSimilarProduct($itemName)
    {
        $products = Product::all();
        $bestMatch = null;
        $highestSimilarity = 0;
        
        foreach ($products as $product) {
            $similarity = $this->calculateTextSimilarity($itemName, $product->name);
            
            if ($similarity > 0.7 && $similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatch = $product;
            }
        }
        
        if ($bestMatch) {
            return [
                'product' => $bestMatch,
                'confidence' => $highestSimilarity
            ];
        }
        
        return null;
    }
    
    /**
     * Parse a price string to a float
     */
    protected function parsePrice($priceString)
    {
        // Replace comma with dot for decimal separator
        $priceString = str_replace(',', '.', $priceString);
        
        // Remove any non-numeric characters except the decimal point
        $priceString = preg_replace('/[^0-9.]/', '', $priceString);
        
        return floatval($priceString);
    }
    
    /**
     * Parse a quantity string to a float
     */
    protected function parseQuantity($quantityString)
    {
        // Replace comma with dot for decimal separator
        $quantityString = str_replace(',', '.', $quantityString);
        
        // Remove any non-numeric characters except the decimal point
        $quantityString = preg_replace('/[^0-9.]/', '', $quantityString);
        
        $quantity = floatval($quantityString);
        return $quantity > 0 ? $quantity : 1;
    }
    
    /**
     * Calculate the similarity between two text strings
     */
    protected function calculateTextSimilarity($str1, $str2)
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
        
        // Check if str1 is contained in str2
        if (strpos($str2, $str1) !== false) {
            return 0.9; // High confidence for substring match
        }
        
        // Use Levenshtein distance for approximate matching
        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength === 0) {
            return 0;
        }
        
        return 1 - ($levenshtein / $maxLength);
    }
    
    /**
     * Create ticket items from the extracted items
     */
    protected function createTicketItems(Ticket $ticket, array $items)
    {
        foreach ($items as $item) {
            TicketItem::create([
                'ticket_id' => $ticket->id,
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
                'total' => $item['total'],
                'product_id' => $item['product_id'] ?? null,
                'manually_verified' => false
            ]);
        }
    }
    
    /**
     * Update a ticket's status and processing log
     */
    protected function updateTicketStatus(Ticket $ticket, $status, array $processingData)
    {
        $ticket->status = $status;
        $ticket->ai_analysis = $processingData;
        $ticket->save();
    }
    
    /**
     * Move a file to a new directory
     */
    protected function moveFile($file, $destinationDir)
    {
        $filename = basename($file);
        $destination = $destinationDir . '/' . $filename;
        
        // Ensure destination doesn't already exist
        if (File::exists($destination)) {
            $extension = File::extension($destination);
            $name = File::name($destination);
            $destination = $destinationDir . '/' . $name . '_' . time() . '.' . $extension;
        }
        
        File::move($file, $destination);
        
        $this->line("Moved file to: $destination");
    }
}
