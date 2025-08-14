<?php
class DuitkuHeader {
    public static function generate($merchantCode, $apiKey)
    {
        if (empty($merchantCode) || empty($apiKey)) {
            throw new Exception('Merchant Code and API Key are required for header generation');
        }

        $timestamp = self::getJakartaTimestamp();
        $signature = self::generateSignature($merchantCode, $timestamp, $apiKey);

        $headers = [
            'x-duitku-signature' => $signature,
            'x-duitku-timestamp' => $timestamp,
            'x-duitku-merchantcode' => $merchantCode,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        return $headers;
    }


    private static function getJakartaTimestamp()
    {
        $jakarta_tz = new DateTimeZone('Asia/Jakarta');
        $datetime = new DateTime('now', $jakarta_tz);
        return $datetime->getTimestamp() * 1000;
    }

    private static function generateSignature($merchantCode, $timestamp, $apiKey)
    {
        $signature_string = $merchantCode . $timestamp . $apiKey;
        return hash('sha256', $signature_string);
    }
}