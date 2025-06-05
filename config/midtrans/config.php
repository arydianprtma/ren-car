<?php
/**
 * Konfigurasi Midtrans Payment Gateway untuk Rental Mobil
 */

// Konstanta Mode Midtrans: true untuk mode sandbox, false untuk mode produksi
define('MIDTRANS_IS_SANDBOX', true);

// Client key dan server key
if (MIDTRANS_IS_SANDBOX) {
    // Sandbox credentials
    define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-AJaI2SrRmdzdy86VeIEWhqon');
    define('MIDTRANS_CLIENT_KEY', 'SSB-Mid-client-P_Fr_h47xbe9IgFz');
    define('MIDTRANS_MERCHANT_ID', 'G804644923');
    define('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js');
    define('MIDTRANS_SNAP_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
} else {
    // Production credentials
    define('MIDTRANS_SERVER_KEY', 'Mid-server-YOUR_PRODUCTION_SERVER_KEY');
    define('MIDTRANS_CLIENT_KEY', 'Mid-client-YOUR_PRODUCTION_CLIENT_KEY');
    define('MIDTRANS_MERCHANT_ID', 'YOUR_PRODUCTION_MERCHANT_ID');
    define('MIDTRANS_SNAP_URL', 'https://app.midtrans.com/snap/snap.js');
    define('MIDTRANS_SNAP_API_URL', 'https://app.midtrans.com/snap/v1/transactions');
}

// URL untuk notification handler
define('MIDTRANS_NOTIFICATION_URL', BASE_URL . 'payments/midtrans/notification.php');

// URL untuk finish, unfinish, dan error redirect
define('MIDTRANS_FINISH_URL', BASE_URL . 'payments/midtrans/finish.php');
define('MIDTRANS_UNFINISH_URL', BASE_URL . 'payments/midtrans/unfinish.php');
define('MIDTRANS_ERROR_URL', BASE_URL . 'payments/midtrans/error.php');

// Jenis pembayaran yang diaktifkan
define('MIDTRANS_ENABLED_PAYMENTS', [
    'credit_card', 
    'gopay', 
    'shopeepay', 
    'bank_transfer',
    'echannel',
    'bca_va',
    'bni_va',
    'bri_va',
    'permata_va',
    'cimb_va',
    'alfamart',
    'indomaret'
]);

/**
 * Fungsi untuk mendapatkan konfigurasi dasar Midtrans Snap
 */
function getMidtransConfig() {
    $config = [
        'enabled_payments' => MIDTRANS_ENABLED_PAYMENTS,
        'finish_redirect_url' => MIDTRANS_FINISH_URL,
        'unfinish_redirect_url' => MIDTRANS_UNFINISH_URL,
        'error_redirect_url' => MIDTRANS_ERROR_URL,
    ];
    
    return $config;
}

/**
 * Fungsi untuk mendapatkan autentikasi dasar Midtrans
 */
function getMidtransAuthString() {
    $auth = base64_encode(MIDTRANS_SERVER_KEY . ':');
    return $auth;
}

/**
 * Fungsi untuk mendapatkan status pembayaran dari Midtrans berdasarkan order ID
 */
function getMidtransStatus($order_id) {
    $url = 'https://' . (MIDTRANS_IS_SANDBOX ? 'api.sandbox.midtrans.com' : 'api.midtrans.com') . '/v2/' . $order_id . '/status';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . getMidtransAuthString()
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        return ['status' => 'error', 'message' => $err];
    } else {
        return json_decode($response, true);
    }
} 