<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

$email = env('ADMIN_EMAIL');
$password = env('ADMIN_PASSWORD');

echo "Testing login for: $email\n";
echo "Password used (from env): $password\n";

if (Auth::attempt(['email' => $email, 'password' => $password])) {
    echo "SUCCESS: Login attempt passed!\n";
} else {
    echo "FAILURE: Login attempt failed. Wrong credentials or hashing issue.\n";
    
    $user = \App\Models\User::where('email', $email)->first();
    if ($user) {
        echo "User exists. Password hash matches Hash::check? " . (\Hash::check($password, $user->password) ? 'YES' : 'NO') . "\n";
    }
}
