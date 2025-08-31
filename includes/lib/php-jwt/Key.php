<?php
/**
 * Placeholder for the firebase/php-jwt library's Key class.
 */
namespace Firebase\JWT;

class Key
{
    private $keyMaterial;
    private $algorithm;

    public function __construct($keyMaterial, $algorithm)
    {
        $this->keyMaterial = $keyMaterial;
        $this->algorithm = $algorithm;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    public function getKeyMaterial()
    {
        return $this->keyMaterial;
    }
}
