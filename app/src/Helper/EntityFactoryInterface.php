<?php

namespace App\Helper;

/**
 * Interface EntityFactoryInterface
 * @package App\Helper
 */
interface EntityFactoryInterface
{
    public function createEntity(string $json);
}