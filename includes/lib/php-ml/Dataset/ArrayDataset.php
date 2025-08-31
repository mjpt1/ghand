<?php
/**
 * Placeholder for the php-ml library's ArrayDataset class.
 */
namespace Phpml\Dataset;

class ArrayDataset implements Dataset
{
    protected $samples;
    protected $targets;

    public function __construct(array $samples, array $targets)
    {
        $this->samples = $samples;
        $this->targets = $targets;
    }

    public function getSamples()
    {
        return $this->samples;
    }

    public function getTargets()
    {
        return $this->targets;
    }
}
