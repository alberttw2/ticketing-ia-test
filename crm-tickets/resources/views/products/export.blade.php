@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Exportación de Datos: {{ $product->name }}</h1>
        <a href="{{ route('products.show', $product->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> 
        Esta vista simula una exportación a Excel. En una implementación real, se generaría un archivo Excel para descargar.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-2"></i> Información del Producto
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID:</strong> {{ $product->id }}</p>
                    <p><strong>Nombre:</strong> {{ $product->name }}</p>
                    @if($product->description)
                    <p><strong>Descripción:</strong> {{ $product->description }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    @if($product->category)
                    <p><strong>Categoría:</strong> {{ $product->category }}</p>
                    @endif
                    <p><strong>Fecha de creación:</strong> {{ $product->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Establecimientos relacionados:</strong> {{ count($establishments) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-building me-2"></i> Establecimientos Relacionados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($establishments as $establishment)
                        <tr>
                            <td>{{ $establishment->id }}</td>
                            <td>{{ $establishment->name }}</td>
                            <td>{{ Str::limit($establishment->description, 50) }}</td>
                        </tr>
                        @endforeach
                        
                        @if($establishments->isEmpty())
                        <tr>
                            <td colspan="3" class="text-center">No hay establecimientos relacionados</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-receipt me-2"></i> Detalles de Tickets
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Establecimiento</th>
                            <th>Ticket</th>
                            <th>Fecha</th>
                            <th>Nombre en Ticket</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ticketItems as $item)
                        <tr>
                            <td>{{ $item->establishment_name }}</td>
                            <td>{{ $item->filename }}</td>
                            <td>
                                @if($item->ticket_date)
                                    {{ \Carbon\Carbon::parse($item->ticket_date)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ number_format($item->price, 2) }} €</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->total, 2) }} €</td>
                        </tr>
                        @endforeach
                        
                        @if($ticketItems->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center">No hay apariciones en tickets</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="button" class="btn btn-success" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Imprimir esta página
        </button>
    </div>
</div>
@endsection