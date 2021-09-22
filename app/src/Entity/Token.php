<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $tokenAccess;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $access;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUsed;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * Token constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->tokenAccess = bin2hex(random_bytes(32));
    }

    /**
     * Regenerates the token of the API (not being used for now)
     * @throws Exception
     */
    public function regenerateToken() {
        $this->tokenAccess = bin2hex(random_bytes(32));
    }

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
    public function getTokenAccess(): ?string
    {
        return $this->tokenAccess;
    }

    /**
     * @param string $tokenAccess
     * @return $this
     */
    public function setTokenAccess(string $tokenAccess): self
    {
        $this->tokenAccess = $tokenAccess;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccess(): ?string
    {
        return $this->access;
    }

    /**
     * @param string $access
     * @return $this
     */
    public function setAccess(string $access): self
    {
        $this->access = $access;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastUsed(): ?DateTime
    {
        return $this->lastUsed;
    }

    /**
     * @param DateTime|null $lastUsed
     * @return Token
     */
    public function setLastUsed(?DateTime $lastUsed): Token
    {
        $this->lastUsed = $lastUsed;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
