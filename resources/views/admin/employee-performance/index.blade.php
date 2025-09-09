@extends('layouts.app')

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-dark font-weight-medium">Employee Performance Dashboard</h2>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h4>{{ $totalTransactions ?? 0 }}</h4>
                                        <p>Total Transactions</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h4>Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</h4>
                                        <p>Total Revenue</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h4>Rp {{ number_format($averageTransaction ?? 0, 0, ',', '.') }}</h4>
                                        <p>Average Transaction</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h4>{{ $topEmployee->employee_name ?? 'N/A' }}</h4>
                                        <p>Top Performer</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <select id="periodFilter" class="form-control">
                                    <option value="">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" id="employeeFilter" class="form-control" placeholder="Search employee...">
                            </div>
                            <div class="col-md-3">
                                <select id="sortFilter" class="form-control">
                                    <option value="highest">Highest Revenue</option>
                                    <option value="lowest">Lowest Revenue</option>
                                    <option value="latest">Latest Activity</option>
                                    <option value="oldest">Oldest Activity</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#bonusModal">
                                    <i class="fas fa-gift"></i> Give Bonus
                                </button>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="performanceTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Total Transactions</th>
                                        <th>Total Revenue</th>
                                        <th>Average Transaction</th>
                                        <th>Last Transaction</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bonus Modal -->
<div class="modal fade" id="bonusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Give Bonus</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="bonusForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bonusEmployeeName">Employee Name:</label>
                        <input type="text" id="bonusEmployeeName" name="employee_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bonusAmount">Bonus Amount:</label>
                        <input type="number" id="bonusAmount" name="bonus_amount" class="form-control" min="0" step="1000" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodStart">Period Start:</label>
                                <input type="date" id="periodStart" name="period_start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodEnd">Period End:</label>
                                <input type="date" id="periodEnd" name="period_end" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bonusNotes">Notes (Optional):</label>
                        <textarea id="bonusNotes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Give Bonus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('style-alt')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/dataTables.bootstrap4.min.css">
@endpush

@push('script-alt')
<script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.3/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    // DataTables initialization with server-side processing
    var table = $('#performanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.employee-performance.data') }}",
            data: function(d) {
                d.period = $('#periodFilter').val();
                d.employee = $('#employeeFilter').val();
                d.sort_by = $('#sortFilter').val();
            }
        },
        columns: [
            {data: 'employee_name', name: 'employee_name'},
            {data: 'total_transactions', name: 'total_transactions'},
            {data: 'formatted_revenue', name: 'total_revenue'},
            {data: 'formatted_average', name: 'average_transaction'},
            {data: 'formatted_date', name: 'last_transaction'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ]
    });

    // Filter change handlers
    $('#periodFilter, #employeeFilter, #sortFilter').change(function() {
        table.ajax.reload();
    });

    // Bonus form submission
    $('#bonusForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&_token={{ csrf_token() }}';
        
        $.ajax({
            url: '{{ route("admin.employee-performance.giveBonus") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#bonusModal').modal('hide');
                    $('#bonusForm')[0].reset();
                    table.ajax.reload();
                }
            },
            error: function(xhr, status, error) {
                alert('Error giving bonus: ' + error);
            }
        });
    });
});

// Show bonus modal for specific employee
function showBonusModal(employeeName) {
    $('#bonusEmployeeName').val(employeeName);
    $('#bonusModal').modal('show');
}
</script>
@endpush
