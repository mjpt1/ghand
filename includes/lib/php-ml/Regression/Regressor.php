<?php
/**
 * Placeholder for the php-ml library's Regressor interface.
 */
namespace Phpml\Regression;

interface Regressor
{
    public function train(array $samples, array $targets);
    public function predict($sample);
}
