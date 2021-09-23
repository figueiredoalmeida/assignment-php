<?php

namespace App\Controller;

use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

abstract class BaseApiController extends AbstractController
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
     * @var EntityRepository
     */
    protected ObjectRepository $repository;

    /**
     * BaseApiController constructor.
     *
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param ObjectRepository $repository
     */
    public function __construct(LoggerInterface $logger,
                                EntityManagerInterface $entityManager,
                                ObjectRepository $repository)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }


    /**
     * Listing all data
     *
     * @return Response
     */
    public function list(): Response
    {
        try {
            $list = $this->repository->findAll();

            if (count($list) === 0) {
                return new Response("", Response::HTTP_NO_CONTENT);
            }

            return new JsonResponse([
                'total' => count($list),
                'keys' => $list,
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }

    }

    /**
     * Removing data
     *
     * @OA\Delete(
     *  @Security(name="Bearer"),
     *  @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="tokenAccess", type="string", example="", description="Token access"),),),),
     *  summary="Removes the record",
     *  @OA\Response(response="200", description="Removes the record")
     * )
     *
     * @param string $name
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(string $name, Request $request): Response
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        try {
            $key = $this->repository->findOneBy(['name' => $name]);

            if (is_null($key)) {
                return new JsonResponse([
                    'message' => self::KEY_NOT_FOUND_MESSAGE],
                    Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($key);
            $this->em->flush();

            return new Response("",Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
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
            $this->em->flush();
        }

        return $token;
    }
}