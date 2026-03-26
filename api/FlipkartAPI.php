<?php
// api/FlipkartAPI.php
// Flipkart Sandbox API Integration
// Sandbox URL: https://sandbox-api.flipkart.com

class FlipkartAPI {

    // Sandbox base URL (Testing साठी)
    private $base_url = 'https://sandbox-api.flipkart.com/sellers';

    // Live URL (Real account साठी - नंतर switch करा)
    // private $base_url = 'https://api.flipkart.com/sellers';

    private $app_id;
    private $app_secret;
    private $access_token;
    private $conn;

    public function __construct($app_id, $app_secret, $conn) {
        $this->app_id     = $app_id;
        $this->app_secret = $app_secret;
        $this->conn       = $conn;
    }

    // ─────────────────────────────────────────
    // STEP 1: Access Token मिळवा
    // Flipkart OAuth 2.0 वापरतो
    // ─────────────────────────────────────────
    public function getAccessToken() {

        // DB मध्ये valid token आहे का check करा
        $res = mysqli_query($this->conn,
            "SELECT access_token, token_expiry FROM api_settings WHERE platform='flipkart'"
        );
        $row = mysqli_fetch_assoc($res);

        if ($row && $row['access_token'] && strtotime($row['token_expiry']) > time()) {
            $this->access_token = $row['access_token'];
            return $this->access_token;
        }

        // नवीन token मागवा
        $url  = 'https://sandbox-api.flipkart.com/oauth-service/oauth/token';
        $data = 'grant_type=client_credentials&scope=Seller_Api';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_USERPWD        => $this->app_id . ':' . $this->app_secret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => false,  // Sandbox साठी
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logAPI('flipkart', 'get_token', $url, $response, $http_code == 200 ? 'success' : 'error');

        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            $token   = clean($this->conn, $result['access_token']);
            $expiry  = date('Y-m-d H:i:s', time() + ($result['expires_in'] ?? 3600));

            mysqli_query($this->conn,
                "UPDATE api_settings SET access_token='$token', token_expiry='$expiry' WHERE platform='flipkart'"
            );

            $this->access_token = $result['access_token'];
            return $this->access_token;
        }

        return false;
    }

    // ─────────────────────────────────────────
    // STEP 2: Product Listing बनवा
    // ─────────────────────────────────────────
    public function listProduct($product) {

        if (!$this->access_token && !$this->getAccessToken()) {
            return ['success' => false, 'message' => 'Token मिळाला नाही'];
        }

        $url = $this->base_url . '/listings/v3';

        // Flipkart ला लागणारा format
        $payload = [
            'skuId'       => $product['sku'],
            'productId'   => 'dummy_' . $product['sku'],  // Sandbox साठी
            'mrp'         => (float)$product['mrp'],
            'sellingPrice'=> (float)$product['price'],
            'listingStatus' => 'ACTIVE',
            'stock'       => (int)$product['stock'],
            'attributes'  => [
                'name'        => $product['name'],
                'description' => $product['description'] ?: $product['name'],
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logAPI('flipkart', 'list_product', json_encode($payload), $response,
            ($http_code >= 200 && $http_code < 300) ? 'success' : 'error');

        $result = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'message' => 'Flipkart वर listed!', 'data' => $result];
        }

        return ['success' => false, 'message' => $response, 'http_code' => $http_code];
    }

    // ─────────────────────────────────────────
    // STEP 3: Stock Update करा
    // ─────────────────────────────────────────
    public function updateStock($sku, $quantity) {

        if (!$this->access_token && !$this->getAccessToken()) {
            return ['success' => false, 'message' => 'Token नाही'];
        }

        $url     = $this->base_url . '/listings/v3/' . $sku . '/stock';
        $payload = ['stock' => (int)$quantity];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logAPI('flipkart', 'update_stock', json_encode($payload), $response,
            $http_code == 200 ? 'success' : 'error');

        return ['success' => $http_code == 200, 'message' => $response];
    }

    // ─────────────────────────────────────────
    // API Log DB मध्ये save करा (debugging साठी)
    // ─────────────────────────────────────────
    private function logAPI($platform, $action, $request, $response, $status) {
        $platform = clean($this->conn, $platform);
        $action   = clean($this->conn, $action);
        $request  = clean($this->conn, substr($request, 0, 2000));
        $response = clean($this->conn, substr($response, 0, 2000));
        $status   = clean($this->conn, $status);

        mysqli_query($this->conn,
            "INSERT INTO api_logs (platform, action, request, response, status)
             VALUES ('$platform','$action','$request','$response','$status')"
        );
    }
}
?>
