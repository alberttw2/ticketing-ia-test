@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Establecimientos</h1>
        <a href="{{ route('establishments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Nuevo Establecimiento
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tickets</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($establishments as $establishment)
                        <tr>
                            <td>{{ $establishment->id }}</td>
                            <td>{{ $establishment->name }}</td>
                            <td>{{ $establishment->tickets_count }}</td>
                            <td>
                                <a href="{{ route('establishments.show', $establishment->id) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('establishments.edit', $establishment->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('establishments.destroy', $establishment->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este establecimiento?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($establishments->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center">No hay establecimientos registrados</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection