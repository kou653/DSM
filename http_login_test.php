<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

$controller = new AuthController();
$request = Request::create('/api/login', 'POST', [
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
]);

try {
    $response = $controller->login($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        echo "Response Content: " . $e->getResponse()->getContent() . "\n";
    }
}
