<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Core\Database;
use Dompdf\Dompdf;
use Dompdf\Options;

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) {
    die('Order ID required.');
}

$db = Database::getInstance()->getConnection();

// Fetch order details
$orderStmt = $db->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email, u.contact_number, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    die('Order not found.');
}

// Fallback shipping address: use user's address if shipping_address is null
$shippingAddress = $order['shipping_address'] ?? '';
if (empty(trim($shippingAddress))) {
    $shippingAddress = $order['address'] ?? 'Not provided';
}

// Fetch order items with product details
$itemsStmt = $db->prepare("
    SELECT oi.*, p.product_name, pv.size 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// HTML content for the receipt (no status line)
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Receipt #' . $order['id'] . '</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.4; }
        .receipt { max-width: 700px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; }
        .header p { color: #666; margin: 5px 0 0; }
        .order-info { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .order-info strong { width: 120px; display: inline-block; }
        .customer-info { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f5f5f5; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; color: #888; font-size: 10px; }
    </style>
</head>
<body>
<div class="receipt">
    <div class="header">
        <h2>DRIFT - Order Receipt</h2>
        <p>Thank you for shopping with us!</p>
    </div>
    <div class="order-info">
        <p><strong>Order ID:</strong> #' . str_pad($order['id'], 5, '0', STR_PAD_LEFT) . '</p>
        <p><strong>Order Date:</strong> ' . date('F d, Y H:i', strtotime($order['created_at'])) . '</p>
        <p><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
    </div>
    <div class="customer-info">
        <p><strong>Customer:</strong> ' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
        <p><strong>Phone:</strong> ' . htmlspecialchars($order['contact_number']) . '</p>
        <p><strong>Shipping Address:</strong> ' . nl2br(htmlspecialchars($shippingAddress)) . '</p>
    </div>
    <table class="items-table">
        <thead>
            <tr><th>Product</th><th>Size</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
';
foreach ($items as $item) {
    $subtotal = $item['quantity'] * $item['price_at_purchase'];
    $html .= '
            <tr>
                <td>' . htmlspecialchars($item['product_name']) . '</td>
                <td>' . htmlspecialchars($item['size']) . '</td>
                <td>' . $item['quantity'] . '</td>
                <td>₱' . number_format($item['price_at_purchase'], 2) . '</td>
                <td>₱' . number_format($subtotal, 2) . '</td>
            </tr>
    ';
}
$html .= '
        </tbody>
    </table>
    <div class="total">
        <p><strong>Total Amount: ₱' . number_format($order['total_amount'], 2) . '</strong></p>
    </div>
    <div class="footer">
        <p>This is a system-generated receipt. No signature required.</p>
        <p>DRIFT – Authentic Streetwear</p>
    </div>
</div>
</body>
</html>';

// Generate PDF (inline view, not forced download)
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF in browser (inline) – user can then download if needed
$dompdf->stream("order_{$orderId}_receipt.pdf", ["Attachment" => false]);
exit;