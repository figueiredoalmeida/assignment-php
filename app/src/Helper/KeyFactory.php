<?php

namespace App\Helper;

use App\Entity\KeyReference;

/**
 * Class KeyFactory
 * @package App\Helper
 */
class KeyFactory implements EntityFactoryInterface
{
    public function createEntity(string $json): KeyReference
    {
        $data = json_decode($json);

        $key = new KeyReference();
        $key->setName($data->name);

        return $key;
    }
}