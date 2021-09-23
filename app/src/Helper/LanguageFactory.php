<?php

namespace App\Helper;

use App\Entity\Language;

/**
 * Class LanguageFactory
 * @package App\Helper
 */
class LanguageFactory implements EntityFactoryInterface
{
    public function createEntity(string $json): Language
    {
        $data = json_decode($json);

        $language = new Language();
        $language->setName($data->name);

        return $language;
    }
}