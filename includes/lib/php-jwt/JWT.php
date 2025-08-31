<?php
/**
 * Placeholder for the firebase/php-jwt library.
 * In a real environment, this would be the actual library file.
 * This allows us to write the application code that uses this class and its methods.
 */
namespace Firebase\JWT;

class JWT
{
    /**
     * @param object|array $payload
     * @param string $key
     * @param string $alg
     * @return string
     */
    public static function encode($payload, $key, $alg)
    {
        // This is a dummy implementation.
        // It does not perform real JWT encoding.
        $header = ['alg' => $alg, 'typ' => 'JWT'];
        $header_encoded = base64_encode(json_encode($header));
        $payload_encoded = base64_encode(json_encode($payload));
        return "$header_encoded.$payload_encoded.dummy_signature";
    }

    /**
     * @param string $jwt
     * @param Key $key
     * @return object
     */
    public static function decode($jwt, Key $key)
    {
        // This is a dummy implementation.
        // It does not perform real JWT decoding or verification.
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid JWT format');
        }
        $payload = json_decode(base64_decode($parts[1]));

        // Simulate checking for expiration
        if (isset($payload->exp) && $payload->exp < time()) {
             throw new ExpiredException('Expired token');
        }

        return $payload;
    }
}
