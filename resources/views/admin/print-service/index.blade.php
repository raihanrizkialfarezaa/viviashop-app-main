@extends('admin.layout.master')

@section('title', 'Print Service Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button class="btn btn-primary" id="generate-session-btn">
                        <i class="mdi mdi-qrcode"></i> Generate New Session
                    </button>
                </div>
                <h4 class="page-title">Print Service Dashboard</h4>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Active Sessions">Active Sessions</h5>
                            <h3 class="my-2 py-1">{{ $activeSessions }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="sessions-chart" class="apex-charts"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Pending Orders">Pending Payments</h5>
                            <h3 class="my-2 py-1">{{ $pendingOrders }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-clock-outline text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Print Queue">Print Queue</h5>
                            <h3 class="my-2 py-1">{{ $printQueue }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-printer text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Today Orders">Today's Orders</h5>
                            <h3 class="my-2 py-1">{{ $todayOrders }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-calendar-today text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Recent Print Orders</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Customer</th>
                                    <th>Pages</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="recent-orders">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">QR Code for Tablet</h4>
                </div>
                <div class="card-body text-center">
                    <div id="qr-code-display">
                        <p class="text-muted">Click "Generate New Session" to create QR code</p>
                    </div>
                    <div id="session-info" style="display: none;">
                        <small class="text-muted">Session expires in: <span id="session-timer"></span></small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.print-service.queue') }}" class="btn btn-outline-primary">
                            <i class="mdi mdi-format-list-bulleted"></i> View Print Queue
                        </a>
                        <a href="{{ route('admin.print-service.orders') }}" class="btn btn-outline-info">
                            <i class="mdi mdi-file-document-multiple"></i> All Orders
                        </a>
                        <a href="{{ route('admin.print-service.reports') }}" class="btn btn-outline-success">
                            <i class="mdi mdi-chart-line"></i> Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Modal -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Print Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="modal-qr-code"></div>
                <div class="mt-3">
                    <p><strong>Session URL:</strong></p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="session-url" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copySessionUrl()">
                            <i class="mdi mdi-content-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Display this QR code on your tablet for customers to scan</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="activateSession()">Activate Session</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    let currentSession = null;
    let sessionTimer = null;

    $(document).ready(function() {
        loadRecentOrders();
        
        $('#generate-session-btn').click(function() {
            generateNewSession();
        });
    });

    async function generateNewSession() {
        try {
            const response = await fetch('{{ route("admin.print-service.generate-session") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                currentSession = data.session;
                showSessionModal(data);
            } else {
                alert('Failed to generate session: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error generating session:', error);
            alert('Failed to generate session');
        }
    }

    function showSessionModal(data) {
        document.getElementById('modal-qr-code').innerHTML = data.qr_code_svg || '';
        document.getElementById('session-url').value = data.qr_code_url;
        
        $('#sessionModal').modal('show');
    }

    function activateSession() {
        if (currentSession) {
            // Update main dashboard QR code
            document.getElementById('qr-code-display').innerHTML = document.getElementById('modal-qr-code').innerHTML;
            document.getElementById('session-info').style.display = 'block';
            
            startSessionTimer();
            $('#sessionModal').modal('hide');
        }
    }

    function startSessionTimer() {
        if (sessionTimer) clearInterval(sessionTimer);
        
        const expiresAt = new Date(currentSession.expires_at);
        
        sessionTimer = setInterval(function() {
            const now = new Date();
            const remaining = expiresAt - now;
            
            if (remaining <= 0) {
                clearInterval(sessionTimer);
                document.getElementById('session-timer').textContent = 'Expired';
                document.getElementById('qr-code-display').innerHTML = '<p class="text-muted">Session expired</p>';
                return;
            }
            
            const hours = Math.floor(remaining / (1000 * 60 * 60));
            const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((remaining % (1000 * 60)) / 1000);
            
            document.getElementById('session-timer').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    function copySessionUrl() {
        const urlInput = document.getElementById('session-url');
        urlInput.select();
        document.execCommand('copy');
        
        // Show toast or alert
        alert('URL copied to clipboard');
    }

    async function loadRecentOrders() {
        try {
            // This would load recent orders from the API
            // For now, we'll show a placeholder
            document.getElementById('recent-orders').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">No recent orders</td>
                </tr>
            `;
        } catch (error) {
            console.error('Error loading recent orders:', error);
        }
    }
</script>
@endsection
