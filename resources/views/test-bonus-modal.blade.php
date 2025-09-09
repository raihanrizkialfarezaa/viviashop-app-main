<!DOCTYPE html>
<html>
<head>
    <title>Test Bonus Modal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Bonus Modal Functionality</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#bonusModal">
                    <i class="fas fa-gift"></i> Give Bonus (Bootstrap Modal)
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-info" onclick="showBonusModal('Test Employee')">
                    <i class="fas fa-gift"></i> Show Bonus Modal (JS Function)
                </button>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <button type="button" class="btn btn-warning" onclick="testModalJS()">
                    Test Modal JS
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-primary" onclick="testAjaxRoute()">
                    Test AJAX Route
                </button>
            </div>
        </div>
        
        <div id="testOutput" class="mt-4"></div>
    </div>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#testOutput').append('<p>✅ jQuery loaded</p>');
        
        if (typeof $.fn.modal !== 'undefined') {
            $('#testOutput').append('<p>✅ Bootstrap modal available</p>');
        } else {
            $('#testOutput').append('<p>❌ Bootstrap modal NOT available</p>');
        }
        
        $('#bonusForm').submit(function(e) {
            e.preventDefault();
            $('#testOutput').append('<p>🔄 Form submitted</p>');
            
            var formData = $(this).serialize();
            formData += '&_token={{ csrf_token() }}';
            
            $.ajax({
                url: '{{ route("admin.employee-performance.giveBonus") }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#testOutput').append('<p>✅ AJAX Success: ' + response.message + '</p>');
                        $('#bonusModal').modal('hide');
                        $('#bonusForm')[0].reset();
                    }
                },
                error: function(xhr, status, error) {
                    $('#testOutput').append('<p>❌ AJAX Error: ' + error + '</p>');
                    $('#testOutput').append('<p>Response: ' + xhr.responseText + '</p>');
                }
            });
        });
    });
    
    function showBonusModal(employeeName) {
        $('#testOutput').append('<p>🔄 showBonusModal called with: ' + employeeName + '</p>');
        $('#bonusEmployeeName').val(employeeName);
        $('#bonusModal').modal('show');
    }
    
    function testModalJS() {
        $('#testOutput').append('<p>🔄 Testing modal show via JS</p>');
        if (typeof $('#bonusModal').modal === 'function') {
            $('#bonusModal').modal('show');
            $('#testOutput').append('<p>✅ Modal show function exists</p>');
        } else {
            $('#testOutput').append('<p>❌ Modal show function NOT exists</p>');
        }
    }
    
    function testAjaxRoute() {
        $('#testOutput').append('<p>🔄 Testing AJAX route</p>');
        $.ajax({
            url: '{{ route("admin.employee-performance.giveBonus") }}',
            type: 'POST',
            data: {
                employee_name: 'Test Employee',
                bonus_amount: 100000,
                period_start: '2025-09-01',
                period_end: '2025-09-30',
                notes: 'Test bonus',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#testOutput').append('<p>✅ Route accessible: ' + JSON.stringify(response) + '</p>');
            },
            error: function(xhr, status, error) {
                $('#testOutput').append('<p>❌ Route error: ' + error + '</p>');
                $('#testOutput').append('<p>Status: ' + xhr.status + '</p>');
                $('#testOutput').append('<p>Response: ' + xhr.responseText + '</p>');
            }
        });
    }
    </script>
</body>
</html>
