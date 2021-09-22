<?php

namespace App\Entity;

use App\Repository\TranslationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TranslationRepository", repositoryClass=TranslationRepository::class)
 */
class Translation implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity=Language::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $language;

    /**
     * @ORM\ManyToOne(targetEntity=KeyReference::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $keyReference;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): Translation
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Language|null
     */
    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    /**
     * @param Language|null $language
     * @return $this
     */
    public function setLanguage(?Language $language): Translation
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return KeyReference|null
     */
    public function getKeyReference(): ?KeyReference
    {
        return $this->keyReference;
    }

    /**
     * @param KeyReference|null $keyReference
     * @return $this
     */
    public function setKeyReference(?KeyReference $keyReference): Translation
    {
        $this->keyReference = $keyReference;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            $this->getLanguage()->getIsoCode() => $this->getValue()
        ];
    }
}
