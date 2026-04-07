<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    echo "DB Connection: " . config('database.default') . "\n";
    echo "DB Database: " . config('database.connections.' . config('database.default') . '.database') . "\n";
    echo "User count: " . User::count() . "\n";
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    if ($admin) {
        echo "Admin found: " . $admin->email . " (Role: " . $admin->role . ")\n";
    } else {
        echo "Admin NOT found for email: " . env('ADMIN_EMAIL') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
