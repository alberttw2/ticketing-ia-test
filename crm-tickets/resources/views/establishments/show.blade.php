@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ $establishment->name }}</h1>
        <div>
            <a href="{{ route('establishments.edit', $establishment->id) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i> Editar
            </a>
            <a href="{{ route('establishments.template.view', $establishment->id) }}" class="btn btn-info ms-2">
                <i class="fas fa-cog me-2"></i> Plantilla
            </a>
            <a href="{{ route('establishments.index') }}" class="btn btn-secondary ms-2">
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
                        <strong>ID:</strong> {{ $establishment->id }}
                    </div>
                    <div class="mb-3">
                        <strong>Nombre:</strong> {{ $establishment->name }}
                    </div>
                    @if($establishment->description)
                    <div class="mb-3">
                        <strong>Descripción:</strong>
                        <p>{{ $establishment->description }}</p>
                    </div>
                    @endif
                    <div class="mb-3">
                        <strong>Tickets:</strong> {{ count($tickets) }}
                    </div>
                    <div>
                        <strong>Creado:</strong> {{ $establishment->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-link me-2"></i> Mapeos de Productos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Producto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappings as $mapping)
                                <tr>
                                    <td>{{ $mapping->item_name }}</td>
                                    <td>
                                        <a href="{{ route('products.show', $mapping->product_id) }}">
                                            {{ $mapping->product->name }}
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if($mappings->isEmpty())
                                <tr>
                                    <td colspan="2" class="text-center">No hay mapeos registrados</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(!$mappings->isEmpty())
                    <div class="mt-3">
                        <a href="{{ route('mappings.index', ['establishment_id' => $establishment->id]) }}" class="btn btn-sm btn-primary w-100">
                            Ver todos los mapeos
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-receipt me-2"></i> Tickets
                    </div>
                    <a href="{{ route('tickets.index', ['establishment_id' => $establishment->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-search me-2"></i> Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Archivo</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>{{ $ticket->filename }}</td>
                                    <td>
                                        @switch($ticket->status)
                                            @case('NEW')
                                                <span class="badge bg-primary">Nuevo</span>
                                                @break
                                            @case('PROCESSED')
                                                <span class="badge bg-success">Procesado</span>
                                                @break
                                            @case('ERROR')
                                                <span class="badge bg-danger">Error</span>
                                                @break
                                            @case('REVIEW')
                                                <span class="badge bg-warning">Revisión</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $ticket->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        @if($ticket->total_amount)
                                            {{ number_format($ticket->total_amount, 2) }} €
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if($tickets->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">No hay tickets para este establecimiento</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection