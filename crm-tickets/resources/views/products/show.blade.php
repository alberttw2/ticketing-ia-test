@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ $product->name }}</h1>
        <div>
            <a href="{{ route('products.export', $product->id) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i> Exportar
            </a>
            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning ms-2">
                <i class="fas fa-edit me-2"></i> Editar
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i> Información
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>ID:</strong> {{ $product->id }}
                    </div>
                    <div class="mb-3">
                        <strong>Nombre:</strong> {{ $product->name }}
                    </div>
                    @if($product->description)
                    <div class="mb-3">
                        <strong>Descripción:</strong>
                        <p>{{ $product->description }}</p>
                    </div>
                    @endif
                    @if($product->category)
                    <div class="mb-3">
                        <strong>Categoría:</strong> {{ $product->category }}
                    </div>
                    @endif
                    <div>
                        <strong>Creado:</strong> {{ $product->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-building me-2"></i> Establecimientos
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($establishments as $establishment)
                        <a href="{{ route('establishments.show', $establishment->id) }}" class="list-group-item list-group-item-action">
                            {{ $establishment->name }}
                        </a>
                        @endforeach
                        
                        @if($establishments->isEmpty())
                        <div class="list-group-item text-center text-muted">
                            No hay establecimientos relacionados
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-receipt me-2"></i> Apariciones en Tickets
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Nombre en Ticket</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketItems as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('establishments.show', $item->establishment_id) }}">
                                            {{ $item->establishment_name }}
                                        </a>
                                    </td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ number_format($item->price, 2) }} €</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->total, 2) }} €</td>
                                    <td>
                                        @if($item->ticket_date)
                                            {{ \Carbon\Carbon::parse($item->ticket_date)->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if(count($ticketItems) === 0)
                                <tr>
                                    <td colspan="6" class="text-center">No hay apariciones en tickets</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $ticketItems->links() }}
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-link me-2"></i> Mapeos de Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Nombre del Item</th>
                                    <th>Confianza</th>
                                    <th>Verificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappings as $mapping)
                                <tr>
                                    <td>
                                        <a href="{{ route('establishments.show', $mapping->establishment_id) }}">
                                            {{ $mapping->establishment->name }}
                                        </a>
                                    </td>
                                    <td>{{ $mapping->item_name }}</td>
                                    <td>{{ number_format($mapping->confidence * 100, 0) }}%</td>
                                    <td>
                                        @if($mapping->manually_verified)
                                            <span class="badge bg-success">Sí</span>
                                        @else
                                            <span class="badge bg-warning">No</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if($mappings->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center">No hay mapeos registrados</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection