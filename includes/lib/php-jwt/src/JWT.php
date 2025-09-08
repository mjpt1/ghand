<?php
/**
 * A functional, dependency-free JWT implementation (heavily simplified).
 * This is a higher-fidelity placeholder than the previous version.
 * It correctly handles encoding, decoding, and exceptions, but simulates signature verification.
 */
namespace JWT;

class JWT
{
    public static function encode(object|array $payload, string $key, string $alg): string
    {
        $header = ['typ' => 'JWT', 'alg' => $alg];
        $header_encoded = self::urlsafeB64Encode(json_encode($header));
        $payload_encoded = self::urlsafeB64Encode(json_encode($payload));

        $signature_input = "$header_encoded.$payload_encoded";

        // --- SIMULATED SIGNATURE ---
        // In a real library, this would use hash_hmac.
        $signature = hash_hmac('sha256', $signature_input, $key, true);
        $signature_encoded = self::urlsafeB64Encode($signature);

        return "$signature_input.$signature_encoded";
    }

    public static function decode(string $jwt, string $key, array $allowed_algs): object
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Wrong number of segments');
        }

        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

        $header = json_decode(self::urlsafeB64Decode($header_encoded));
        if ($header === null) {
            throw new \Exception('Invalid header encoding');
        }

        $payload = json_decode(self::urlsafeB64Decode($payload_encoded));
        if ($payload === null) {
            throw new \Exception('Invalid claims encoding');
        }

        $signature = self::urlsafeB64Decode($signature_encoded);

        if (!in_array($header->alg, $allowed_algs)) {
            throw new \Exception('Algorithm not allowed');
        }

        // --- SIMULATED SIGNATURE VERIFICATION ---
        // In a real library, we would re-calculate the signature and compare.
        $signature_input = "$header_encoded.$payload_encoded";
        $expected_signature = hash_hmac('sha256', $signature_input, $key, true);

        if (!hash_equals($expected_signature, $signature)) {
             throw new \Exception('Signature verification failed');
        }

        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new \Exception('Expired token');
        }

        return $payload;
    }

    private static function urlsafeB64Encode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private static function urlsafeB64Decode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
