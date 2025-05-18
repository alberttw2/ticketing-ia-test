@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="row dashboard-stats mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Establecimientos</h6>
                            <h2 class="mb-0">{{ $totalStats['establishments'] }}</h2>
                        </div>
                        <i class="fas fa-building fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Productos</h6>
                            <h2 class="mb-0">{{ $totalStats['products'] }}</h2>
                        </div>
                        <i class="fas fa-box fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Tickets</h6>
                            <h2 class="mb-0">{{ $totalStats['tickets'] }}</h2>
                        </div>
                        <i class="fas fa-receipt fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Items</h6>
                            <h2 class="mb-0">{{ $totalStats['items'] }}</h2>
                        </div>
                        <i class="fas fa-list fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- Tickets por Estado -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2"></i> Tickets por Estado
                </div>
                <div class="card-body">
                    <canvas id="ticketStatusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tickets Mensuales -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-2"></i> Tickets Mensuales
                </div>
                <div class="card-body">
                    <canvas id="monthlyTicketsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- Top Establecimientos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-building me-2"></i> Top Establecimientos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Tickets</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topEstablishments as $establishment)
                                <tr>
                                    <td>
                                        <a href="{{ route('establishments.show', $establishment->id) }}">
                                            {{ $establishment->name }}
                                        </a>
                                    </td>
                                    <td>{{ $establishment->tickets_count }}</td>
                                </tr>
                                @endforeach
                                
                                @if($topEstablishments->isEmpty())
                                <tr>
                                    <td colspan="2" class="text-center">No hay establecimientos registrados</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Productos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-box me-2"></i> Top Productos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Apariciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $product)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show', $product->id) }}">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td>{{ $product->ticket_items_count }}</td>
                                </tr>
                                @endforeach
                                
                                @if($topProducts->isEmpty())
                                <tr>
                                    <td colspan="2" class="text-center">No hay productos registrados</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Tickets Recientes -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-receipt me-2"></i> Tickets Recientes
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Establecimiento</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>
                                        @if($ticket->establishment)
                                            {{ $ticket->establishment->name }}
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
                                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                                
                                @if($recentTickets->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center">No hay tickets recientes</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tickets por Revisar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-2"></i> Tickets por Revisar
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviewByEstablishment as $item)
                                <tr>
                                    <td>
                                        @if($item->establishment)
                                            {{ $item->establishment->name }}
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->count }}</td>
                                </tr>
                                @endforeach
                                
                                @if($reviewByEstablishment->isEmpty())
                                <tr>
                                    <td colspan="2" class="text-center">No hay tickets por revisar</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(!$reviewByEstablishment->isEmpty())
                    <div class="mt-3">
                        <a href="{{ route('tickets.index', ['status' => 'REVIEW']) }}" class="btn btn-warning w-100">
                            <i class="fas fa-search me-2"></i> Ver todos los tickets por revisar
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Tickets por Estado Chart
    const statusLabels = {
        'NEW': 'Nuevos',
        'PROCESSED': 'Procesados',
        'ERROR': 'Errores',
        'REVIEW': 'Revisión'
    };
    
    const statusColors = {
        'NEW': '#0d6efd',
        'PROCESSED': '#198754',
        'ERROR': '#dc3545',
        'REVIEW': '#ffc107'
    };
    
    const statusData = @json($ticketsByStatus);
    const statusLabelsArray = [];
    const statusDataArray = [];
    const statusColorsArray = [];
    
    for (const status in statusLabels) {
        statusLabelsArray.push(statusLabels[status]);
        statusDataArray.push(statusData[status] || 0);
        statusColorsArray.push(statusColors[status]);
    }
    
    const ticketStatusChart = new Chart(
        document.getElementById('ticketStatusChart'),
        {
            type: 'pie',
            data: {
                labels: statusLabelsArray,
                datasets: [{
                    data: statusDataArray,
                    backgroundColor: statusColorsArray,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        }
    );
    
    // Monthly Tickets Chart
    const monthlyData = @json($monthlyCounts);
    const months = [];
    const counts = [];
    
    monthlyData.forEach(item => {
        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        months.push(monthNames[item.month - 1] + ' ' + item.year);
        counts.push(item.count);
    });
    
    const monthlyTicketsChart = new Chart(
        document.getElementById('monthlyTicketsChart'),
        {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Tickets',
                    data: counts,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
</script>
@endsection