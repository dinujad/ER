<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HikvisionService
{
    protected $appKey;
    protected $appSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->appKey = config('services.hikvision.app_key');
        $this->appSecret = config('services.hikvision.app_secret');
        $this->baseUrl = config('services.hikvision.base_url');
    }

    /**
     * Get attendance records from Hikvision OpenAPI
     * 
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getAttendanceRecords($page = 1, $pageSize = 20)
    {
        // NOTE: Adjust this endpoint based on your specific Hikvision product (e.g. Hik-Connect Team, HikCentral)
        // Common endpoint for attendance records:
        $apiPath = '/api/v1/attendance/records'; 

        $body = [
            'pageNo'   => $page,
            'pageSize' => $pageSize,
        ];

        return $this->request('POST', $apiPath, $body);
    }

    /**
     * Send Request with Authentication Signature
     */
    protected function request($method, $path, $data = [])
    {
        $headers = [
            'Accept'          => '*/*',
            'Content-Type'    => 'application/json',
            'X-Ca-Key'        => $this->appKey,
            'X-Ca-Timestamp'  => (string) round(microtime(true) * 1000),
            'X-Ca-Nonce'      => (string) Str::uuid(),
        ];

        // Generate Signature
        $signature = $this->generateSignature($method, $path, $headers);
        $headers['X-Ca-Signature'] = $signature;

        // Make Request
        $response = Http::withHeaders($headers)
            ->post($this->baseUrl . $path, $data);

        // Error Handling
        if ($response->failed()) {
            // Log error or throw exception
            logger()->error('Hikvision API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        return $response->json();
    }

    /**
     * Generate HMAC-SHA256 Signature
     */
    protected function generateSignature($method, $path, $headers)
    {
        // 1. Build String to Sign
        // Format:
        // HTTPMethod + "\n" +
        // Accept + "\n" +
        // Content-MD5 + "\n" +
        // Content-Type + "\n" +
        // Date + "\n" +
        // CanonicalizedHeaders +
        // CanonicalizedResource

        $stringToSign = strtoupper($method) . "\n";
        
        // Accept
        $stringToSign .= ($headers['Accept'] ?? '') . "\n";
        
        // Content-MD5 (Empty)
        $stringToSign .= "" . "\n";
        
        // Content-Type
        $stringToSign .= ($headers['Content-Type'] ?? '') . "\n";
        
        // Date (Empty if using X-Ca-Timestamp)
        $stringToSign .= "" . "\n";

        // CanonicalizedHeaders
        // - Lowercase header keys
        // - Sort alphabetically
        // - Include headers starting with "x-ca-"
        $hikHeaders = [];
        foreach ($headers as $key => $value) {
            if (str_starts_with(strtolower($key), 'x-ca-')) {
                $hikHeaders[strtolower($key)] = $value;
            }
        }
        ksort($hikHeaders);

        $canonicalizedHeaders = "";
        foreach ($hikHeaders as $key => $value) {
            $canonicalizedHeaders .= $key . ":" . $value . "\n";
        }
        $stringToSign .= $canonicalizedHeaders;

        // CanonicalizedResource
        $stringToSign .= $path;

        // 2. Compute HMAC-SHA256
        return base64_encode(hash_hmac('sha256', $stringToSign, $this->appSecret, true));
    }
}
