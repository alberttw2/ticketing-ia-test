@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Tickets</h1>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i> Filtros
        </div>
        <div class="card-body">
            <form action="{{ route('tickets.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label for="establishment_id" class="form-label">Establecimiento</label>
                            <select class="form-select" id="establishment_id" name="establishment_id">
                                <option value="">Todos los establecimientos</option>
                                @foreach($establishments as $establishment)
                                <option value="{{ $establishment->id }}" {{ request('establishment_id') == $establishment->id ? 'selected' : '' }}>
                                    {{ $establishment->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos los estados</option>
                                <option value="NEW" {{ request('status') == 'NEW' ? 'selected' : '' }}>Nuevo</option>
                                <option value="PROCESSED" {{ request('status') == 'PROCESSED' ? 'selected' : '' }}>Procesado</option>
                                <option value="ERROR" {{ request('status') == 'ERROR' ? 'selected' : '' }}>Error</option>
                                <option value="REVIEW" {{ request('status') == 'REVIEW' ? 'selected' : '' }}>Revisión</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="mb-3 w-100">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Archivo</th>
                            <th>Establecimiento</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Items</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->filename }}</td>
                            <td>
                                @if($ticket->establishment)
                                    <a href="{{ route('establishments.show', $ticket->establishment->id) }}">
                                        {{ $ticket->establishment->name }}
                                    </a>
                                @else
                                    <span class="text-muted">No asignado</span>
                                @endif
                            </td>
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
                            <td>{{ $ticket->items->count() }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('tickets.destroy', $ticket->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este ticket?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($tickets->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center">No hay tickets que coincidan con los filtros</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            {{ $tickets->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection