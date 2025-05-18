@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Plantilla: {{ $establishment->name }}</h1>
        <a href="{{ route('establishments.show', $establishment->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-2"></i> Configuración de Plantilla
        </div>
        <div class="card-body">
            <p class="mb-4">
                Configure los patrones de reconocimiento para tickets de este establecimiento. Estos patrones ayudarán al sistema a identificar mejor los tickets y los items.
            </p>
            
            <form action="{{ route('establishments.template.update', $establishment->id) }}" method="POST">
                @csrf
                
                <h5>Patrones de Identificación</h5>
                <p class="text-muted">Textos que ayudan a identificar tickets de este establecimiento.</p>
                
                <div class="mb-4">
                    <div id="patterns-container">
                        @if(isset($establishment->template_data['patterns']))
                            @foreach($establishment->template_data['patterns'] as $index => $pattern)
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="patterns[]" value="{{ $pattern }}">
                                    <button type="button" class="btn btn-danger remove-pattern">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        @else
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="patterns[]" placeholder="Ej: Nombre del establecimiento">
                                <button type="button" class="btn btn-danger remove-pattern">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <button type="button" id="add-pattern" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Agregar Patrón
                    </button>
                </div>
                
                <h5>Patrones de Items</h5>
                <p class="text-muted">Expresiones regulares para extraer items, precios y cantidades. Use nombres de grupos como 'name', 'price' y 'quantity'.</p>
                
                <div class="mb-4">
                    <div id="item-patterns-container">
                        @if(isset($establishment->template_data['item_patterns']))
                            @foreach($establishment->template_data['item_patterns'] as $index => $pattern)
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="item_patterns[]" value="{{ $pattern }}">
                                    <button type="button" class="btn btn-danger remove-item-pattern">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        @else
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="item_patterns[]" placeholder="Ej: /(?P<name>[^0-9]+)\\s+(?P<price>[0-9]+[.,][0-9]{2})/i">
                                <button type="button" class="btn btn-danger remove-item-pattern">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <button type="button" id="add-item-pattern" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Agregar Patrón de Item
                    </button>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add pattern
        document.getElementById('add-pattern').addEventListener('click', function() {
            const container = document.getElementById('patterns-container');
            const newPattern = document.createElement('div');
            newPattern.className = 'input-group mb-2';
            newPattern.innerHTML = `
                <input type="text" class="form-control" name="patterns[]" placeholder="Ej: Nombre del establecimiento">
                <button type="button" class="btn btn-danger remove-pattern">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newPattern);
        });
        
        // Remove pattern
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-pattern') || e.target.closest('.remove-pattern')) {
                const button = e.target.classList.contains('remove-pattern') ? e.target : e.target.closest('.remove-pattern');
                const inputGroup = button.parentElement;
                
                if (document.querySelectorAll('#patterns-container .input-group').length > 1) {
                    inputGroup.remove();
                } else {
                    inputGroup.querySelector('input').value = '';
                }
            }
        });
        
        // Add item pattern
        document.getElementById('add-item-pattern').addEventListener('click', function() {
            const container = document.getElementById('item-patterns-container');
            const newPattern = document.createElement('div');
            newPattern.className = 'input-group mb-2';
            newPattern.innerHTML = `
                <input type="text" class="form-control" name="item_patterns[]" placeholder="Ej: /(?P<name>[^0-9]+)\\s+(?P<price>[0-9]+[.,][0-9]{2})/i">
                <button type="button" class="btn btn-danger remove-item-pattern">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newPattern);
        });
        
        // Remove item pattern
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item-pattern') || e.target.closest('.remove-item-pattern')) {
                const button = e.target.classList.contains('remove-item-pattern') ? e.target : e.target.closest('.remove-item-pattern');
                const inputGroup = button.parentElement;
                
                if (document.querySelectorAll('#item-patterns-container .input-group').length > 1) {
                    inputGroup.remove();
                } else {
                    inputGroup.querySelector('input').value = '';
                }
            }
        });
    });
</script>
@endsection