<?php
require '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);

echo "DomPDF Version: " . $dompdf::VERSION . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";

// Test PDF generation with minimal content
$html = '<html><body><h1>Test PDF</h1><p>This is a test.</p></body></html>';
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
if (strlen($output) > 0) {
    echo "PDF Generation successful! Size: " . strlen($output) . " bytes\n";
} else {
    echo "PDF Generation failed!\n";
}