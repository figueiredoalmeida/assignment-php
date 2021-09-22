<?php

namespace App\Controller;

use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class BaseApiController extends AbstractController
{
    const PERMISSION_READ = 'read';
    const PERMISSION_WRITE = 'write';
    const ERROR_PERMISSION_MESSAGE = 'You have only read permission';
    const ERROR_VALIDATION_MESSAGE = 'Missing keys or value is empty, please read the documentation';
    const ERROR_EXCEPTION_MESSAGE = 'An error occurred, please, contact the support.';

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * BaseApiController constructor.
     *
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Returns the token permission if read or read/write
     * - As we are using JWT for authentication
     * - I am using the Token.token (entity.field) for access permissions only
     * - (just following what I understood from the documentation)
     *
     * @param Request $request
     *
     * @return Token|null
     * @throws Exception
     */
    public function tokenPermission(Request $request): ?Token
    {
        $data = json_decode($request->getContent(),true);

        if (!isset($data['tokenAccess'])) {
            return null;
        }

        /** @var Token $token */
        $token = $this->em->getRepository(Token::class)->findOneBy([
            'user' => $this->get('security.token_storage')->getToken()->getUser(),
            'tokenAccess' => $data['tokenAccess']
        ]);

        if ( true === $token instanceof Token ) {
            // Updating the last time the token was used
            $token->setLastUsed(new \DateTime());
//            $this->em->persist($token);
            $this->em->flush();
        }

        return $token;
    }
}