<?php


namespace App\Helper;


use App\Entity\KeyReference;
use App\Entity\Language;
use App\Entity\Translation;

/**
 * Class TranslationFactory
 * @package App\Helper
 */
class TranslationFactory
{

    /**
     * @param Language $language
     * @param KeyReference $keyReference
     * @param string $value
     * @return Translation
     */
    public function create(Language $language, KeyReference $keyReference, string $value): Translation
    {
        $translation = new Translation();
        $translation
            ->setLanguage($language)
            ->setKeyReference($keyReference)
            ->setValue($value)
        ;

        return $translation;
    }
}