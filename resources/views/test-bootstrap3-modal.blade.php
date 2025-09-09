<!DOCTYPE html>
<html>
<head>
    <title>Bootstrap 3 Modal Test</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
    <div class="container" style="margin-top: 50px;">
        <h1>Bootstrap 3 Modal Test</h1>
        
        <div class="row" style="margin-top: 30px;">
            <div class="col-md-4">
                <button type="button" class="btn btn-success" data-target="#bonusModal" data-toggle="modal">
                    <i class="fas fa-gift"></i> Give Bonus (Bootstrap 3 way)
                </button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-info" onclick="showBonusModal('Test Employee')">
                    <i class="fas fa-gift"></i> Show Modal (JS Function)
                </button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-warning" onclick="testModal()">
                    <i class="fas fa-test"></i> Test Modal Directly
                </button>
            </div>
        </div>
        
        <div id="output" style="margin-top: 30px; border: 1px solid #ccc; padding: 15px; background-color: #f9f9f9;">
            <h4>Test Output:</h4>
            <div id="log"></div>
        </div>
    </div>

    <div class="modal fade" id="bonusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Give Bonus</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bonusEmployeeName">Employee Name:</label>
                        <input type="text" id="bonusEmployeeName" name="employee_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bonusAmount">Bonus Amount:</label>
                        <input type="number" id="bonusAmount" name="bonus_amount" class="form-control" min="0" step="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Give Bonus</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <script>
    function log(message) {
        $('#log').append('<p>[' + new Date().toLocaleTimeString() + '] ' + message + '</p>');
    }
    
    $(document).ready(function() {
        log('✅ Document ready');
        
        if (typeof $.fn.modal !== 'undefined') {
            log('✅ Bootstrap modal function is available');
        } else {
            log('❌ Bootstrap modal function is NOT available');
        }
        
        $('#bonusModal').on('show.bs.modal', function (e) {
            log('🔄 Modal show event triggered');
        });
        
        $('#bonusModal').on('shown.bs.modal', function (e) {
            log('✅ Modal shown successfully');
        });
        
        $('#bonusModal').on('hide.bs.modal', function (e) {
            log('🔄 Modal hide event triggered');
        });
        
        $('#bonusModal').on('hidden.bs.modal', function (e) {
            log('✅ Modal hidden successfully');
        });
    });
    
    function showBonusModal(employeeName) {
        log('🔄 showBonusModal called with: ' + employeeName);
        $('#bonusEmployeeName').val(employeeName);
        
        try {
            $('#bonusModal').modal('show');
            log('✅ Modal show method called successfully');
        } catch (error) {
            log('❌ Error calling modal show: ' + error.message);
        }
    }
    
    function testModal() {
        log('🔄 Testing modal directly');
        if (typeof $('#bonusModal').modal === 'function') {
            log('✅ Modal function exists');
            $('#bonusModal').modal('show');
        } else {
            log('❌ Modal function does not exist');
        }
    }
    </script>
</body>
</html>
