<?php
/**
 * Kelas Midtrans untuk menangani integrasi payment gateway
 */
class Midtrans
{
    private $serverKey;
    private $clientKey;
    private $isSandbox;
    private $snapUrl;
    private $snapApiUrl;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Pastikan file konfigurasi Midtrans sudah dimuat
        require_once __DIR__ . '/../../config/midtrans/config.php';
        
        $this->serverKey = MIDTRANS_SERVER_KEY;
        $this->clientKey = MIDTRANS_CLIENT_KEY;
        $this->isSandbox = MIDTRANS_IS_SANDBOX;
        $this->snapUrl = MIDTRANS_SNAP_URL;
        $this->snapApiUrl = MIDTRANS_SNAP_API_URL;
    }
    
    /**
     * Membuat transaksi pembayaran
     *
     * @param array $params Parameter transaksi
     * @return array Hasil transaksi
     */
    public function createTransaction($params)
    {
        $params = $this->prepareTransactionData($params);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->snapApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':')
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return [
                'status' => 'error',
                'message' => $err
            ];
        } else {
            return json_decode($response, true);
        }
    }
    
    /**
     * Menyiapkan data transaksi untuk Midtrans
     *
     * @param array $params Parameter transaksi
     * @return array Data transaksi yang siap digunakan
     */
    private function prepareTransactionData($params)
    {
        $defaultConfig = getMidtransConfig();
        
        // Menggabungkan parameter yang diterima dengan konfigurasi default
        $transactionData = array_merge($defaultConfig, $params);
        
        // Memastikan data yang diperlukan ada
        if (!isset($transactionData['transaction_details'])) {
            $transactionData['transaction_details'] = [
                'order_id' => 'ORDER-' . time(),
                'gross_amount' => 0
            ];
        }
        
        return $transactionData;
    }
    
    /**
     * Memeriksa status transaksi berdasarkan order ID
     *
     * @param string $orderId ID pesanan
     * @return array Status transaksi
     */
    public function getStatus($orderId)
    {
        return getMidtransStatus($orderId);
    }
    
    /**
     * Verifikasi signature notification dari Midtrans
     *
     * @param string $orderId ID pesanan
     * @param string $statusCode Kode status
     * @param string $grossAmount Jumlah kotor
     * @param string $signature Signature dari Midtrans
     * @return bool Hasil verifikasi signature
     */
    public function verifyNotificationSignature($orderId, $statusCode, $grossAmount, $signature)
    {
        $input = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $calculatedSignature = hash('sha512', $input);
        
        return ($signature === $calculatedSignature);
    }
    
    /**
     * Mendapatkan client key
     *
     * @return string Client key Midtrans
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }
    
    /**
     * Mendapatkan URL Snap.js
     *
     * @return string URL Snap.js
     */
    public function getSnapUrl()
    {
        return $this->snapUrl;
    }
    
    /**
     * Mengubah status transaksi berdasarkan status Midtrans
     *
     * @param string $transaction_status Status transaksi dari Midtrans
     * @return string Status pesanan yang sesuai dengan sistem
     */
    public function getMappedOrderStatus($transaction_status)
    {
        $status = 'menunggu';
        
        switch ($transaction_status) {
            case 'capture':
            case 'settlement':
                $status = 'dikonfirmasi';
                break;
            case 'pending':
                $status = 'menunggu';
                break;
            case 'deny':
            case 'cancel':
            case 'expire':
            case 'failure':
                $status = 'dibatalkan';
                break;
        }
        
        return $status;
    }
} 