<?php
require 'vendor/autoload.php';
require 'src/Kernel.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$salesRepo = $container->get(App\Repository\SalesRepository::class);
$result = $salesRepo->getCountByStatus();

echo "getCountByStatus() result:\n";
var_dump($result);

echo "\nBuilding array as MainController does:\n";
$salesStatusCounts = [
    'completed' => $result['completed'] ?? 0,
    'pending' => $result['pending'] ?? 0,
    'cancelled' => $result['cancelled'] ?? 0,
];
var_dump($salesStatusCounts);

echo "\nPipeline values:\n";
echo json_encode([
    $salesStatusCounts['completed'] ?? 0,
    $salesStatusCounts['pending'] ?? 0,
    $salesStatusCounts['cancelled'] ?? 0,
]);
