<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\LaravelAiKitService::class);
try {
    $res = $service->assistant(['message' => 'Which model do you recommend for living room with size of 6m x 4m']);
    print_r($res);
} catch (\Exception $e) {
    echo $e->getMessage();
}
