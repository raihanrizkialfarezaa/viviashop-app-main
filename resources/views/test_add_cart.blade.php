<!DOCTYPE html>
<html>
<head>
    <title>Test Add to Cart</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Add Variant to Cart</h1>
    
    <div>
        <h3>Add Product 133 Variant 10 to Cart</h3>
        <button onclick="addToCart()">Add Variant to Cart</button>
        <div id="result"></div>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="/carts" target="_blank">View Cart Page</a>
    </div>
    
    <script>
        function addToCart() {
            const formData = {
                product_id: 133,
                qty: 1,
                variant_id: 10,
                _token: document.querySelector('meta[name="csrf-token"]').content
            };
            
            fetch('/carts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerHTML = 
                    '<div style="margin-top: 10px; padding: 10px; background: ' + 
                    (data.status === 'success' ? '#d4edda' : '#f8d7da') + '">' +
                    '<strong>Status:</strong> ' + data.status + '<br>' +
                    '<strong>Message:</strong> ' + (data.message || 'No message') +
                    '</div>';
                    
                if (data.status === 'success') {
                    setTimeout(() => {
                        window.open('/carts', '_blank');
                    }, 1000);
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 
                    '<div style="margin-top: 10px; padding: 10px; background: #f8d7da">' +
                    '<strong>Error:</strong> ' + error.message +
                    '</div>';
            });
        }
    </script>
</body>
</html>
