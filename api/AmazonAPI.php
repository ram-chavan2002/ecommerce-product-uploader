<?php
class AmazonAPI {
    private $sandbox_url = 'https://sandbox.sellingpartnerapi-eu.amazon.com';
    private $live_url    = 'https://sellingpartnerapi-eu.amazon.com';
    private $base_url;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $conn;
    private $sandbox_mode;

    public function __construct($client_id, $client_secret, $conn, $sandbox_mode = true) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->conn          = $conn;
        $this->sandbox_mode  = $sandbox_mode;
        $this->base_url      = $sandbox_mode ? $this->sandbox_url : $this->live_url;
    }

    public function getAccessToken() {
        $res = mysqli_query($this->conn, "SELECT access_token, token_expiry FROM api_settings WHERE platform='amazon'");
        $row = mysqli_fetch_assoc($res);
        if ($row && $row['access_token'] && strtotime($row['token_expiry']) > time()) {
            $this->access_token = $row['access_token'];
            return $this->access_token;
        }

        $url  = 'https://api.amazon.com/auth/o2/token';
        $data = http_build_query([
            'grant_type'    => 'client_credentials',
            'scope'         => 'sellingpartnerapi::catalog_items',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logAPI('amazon', 'get_token', 'LWA token request', $response, $http_code == 200 ? 'success' : 'error');
        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            $token  = clean($this->conn, $result['access_token']);
            $expiry = date('Y-m-d H:i:s', time() + ($result['expires_in'] ?? 3600));
            mysqli_query($this->conn, "UPDATE api_settings SET access_token='$token', token_expiry='$expiry' WHERE platform='amazon'");
            $this->access_token = $result['access_token'];
            return $this->access_token;
        }
        return false;
    }

    public function listProduct($product, $seller_id = 'TEST_SELLER') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return ['success' => false, 'message' => 'Token मिळाला नाही'];
        }

        $sku         = urlencode($product['sku']);
        $marketplace = $this->sandbox_mode ? 'ATVPDKIKX0DER' : 'A21TJRUUN4KGV';
        $url         = $this->base_url . "/listings/2021-08-01/items/{$seller_id}/{$sku}?marketplaceIds={$marketplace}";

        $payload = [
            'productType' => 'PRODUCT',
            'attributes'  => [
                'item_name' => [[
                    'value'          => $product['name'],
                    'language_tag'   => 'en_IN',
                    'marketplace_id' => $marketplace
                ]],
                'list_price' => [[
                    'value'    => (float)$product['price'],
                    'currency' => 'INR'
                ]],
                'fulfillment_availability' => [[
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity'                 => (int)$product['stock']
                ]],
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->access_token,
                'x-amz-access-token: '  . $this->access_token,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logAPI('amazon', 'list_product', json_encode($payload), $response,
            ($http_code >= 200 && $http_code < 300) ? 'success' : 'error');

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'message' => 'Amazon वर listed!'];
        }
        return ['success' => false, 'message' => $response, 'http_code' => $http_code];
    }

    private function logAPI($platform, $action, $request, $response, $status) {
        $platform = clean($this->conn, $platform);
        $action   = clean($this->conn, $action);
        $request  = clean($this->conn, substr($request,  0, 2000));
        $response = clean($this->conn, substr($response, 0, 2000));
        $status   = clean($this->conn, $status);
        mysqli_query($this->conn, "INSERT INTO api_logs (platform, action, request, response, status) VALUES ('$platform','$action','$request','$response','$status')");
    }
}
?>