<?php

namespace App\Controller;

use App\Helper\KeyFactory;
use App\Repository\KeyReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * Class KeyApiController
 *
 * @package App\Controller
 * @Route("/api")
 */
class KeyApiController extends BaseApiController
{
    const KEY_FOUND_MESSAGE = 'Key already exists';
    const KEY_NOT_FOUND_MESSAGE = 'Key does not exist';
    const KEY_CREATE_SUCCESS_MESSAGE = 'Key created with success';
    const KEY_UPDATE_SUCCESS_MESSAGE = 'Key updated with success';
    const KEY_DELETED_SUCCESS_MESSAGE = 'KeyReference %s deleted with success';

    /**
     * @var KeyFactory
     */
    private KeyFactory $keyFactory;

    /**
     * @var KeyReferenceRepository
     */
    private KeyReferenceRepository $keyReferenceRepository;

    public function __construct(KeyFactory $keyFactory,
                                LoggerInterface $logger,
                                EntityManagerInterface $entityManager,
                                KeyReferenceRepository $keyReferenceRepository)
    {
        parent::__construct($logger, $entityManager);
        $this->keyFactory = $keyFactory;
        $this->keyReferenceRepository = $keyReferenceRepository;
    }


    /**
     * Listing the keys
     *
     * @Route("/keys", name="api.keys.list", methods={"GET"})
     *
     * @return Response
     */
    public function list(): Response
    {
         try {
            $keysList = $this->keyReferenceRepository->findAll();

            if (count($keysList) === 0) {
                return new Response("", Response::HTTP_NO_CONTENT);
            }

            return new JsonResponse([
                'total' => count($keysList),
                'keys' => $keysList,
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
             $this->logger->critical('An error occurred: '.$exception->getMessage());
             return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Creating a key
     *
     * @Route("/keys", name="api.key.create", methods={"POST"})
     *
     * @OA\Post(
     *  path="/api/keys",
     *  @Security(name="Bearer"),
     *  @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="tokenAccess", type="string", example="", description="key_token_access"),
     *      @OA\Property(property="name", type="string", example="", description="key_name"),),),),
     *  summary="Creating a key",
     *  @OA\Response(response="201", description="Creating a key")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        try {
            $data = json_decode($request->getContent());

            $keyRepo = $this->keyReferenceRepository->findOneBy(['name' => $data->name]);

            if (!is_null($keyRepo)) {
                return new JsonResponse([
                    'message' => self::KEY_FOUND_MESSAGE],
                    Response::HTTP_NOT_FOUND);
            }

            $key = $this->keyFactory->create($request->getContent());
            $this->em->persist($key);
            $this->em->flush();

            return new JsonResponse([
                'message' => self::KEY_CREATE_SUCCESS_MESSAGE],
                Response::HTTP_CREATED
            );
        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Updating a key
     *
     * @Route("/keys/{nameFrom}", name="api.key.update", methods={"PUT"})
     *
     * @OA\Put(
     *  path="/api/keys/{nameFrom}",
     *  @Security(name="Bearer"),
     *  @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="tokenAccess", type="string", example="", description="Token access"),
     *      @OA\Property(property="name", type="string", example="index.title", description="Name to be updated"),),),),
     *  summary="Updating a key",
     *  @OA\Response(response="200", description="Updating a key")
     * )
     *
     * @param Request $request
     * @param string $nameFrom
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, string $nameFrom): JsonResponse
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        try {
             $key = $this->keyFactory->create($request->getContent());

             $keyRepo = $this->keyReferenceRepository->findOneBy(['name' => $nameFrom]);

             if (is_null($key)) {
                return new JsonResponse(self::KEY_FOUND_MESSAGE,Response::HTTP_NOT_FOUND);
             }

             $keyRepo->setName($key->getName());
             $this->em->flush();

             return new JsonResponse(self::KEY_UPDATE_SUCCESS_MESSAGE, Response::HTTP_CREATED);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Deleting a key
     *
     * @Route("/keys/{name}", name="api.key.delete", methods={"DELETE"})
     *
     * @OA\Delete(
     *  path="/api/keys/{name}",
     *  @Security(name="Bearer"),
     *  @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="tokenAccess", type="string", example="", description="key_token_access"),),),),
     *  summary="Creating a key",
     *  @OA\Response(response="200", description="Deleting a key")
     * )
     *
     * @param string $name
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function delete(string $name, Request $request): Response
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        try {
            $key = $this->keyReferenceRepository->findOneBy(['name' => $name]);

            if (is_null($key)) {
                return new JsonResponse([
                    'message' => self::KEY_NOT_FOUND_MESSAGE],
                    Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($key);
            $this->em->flush();

            return new JsonResponse(sprintf(
                self::KEY_DELETED_SUCCESS_MESSAGE, $name),
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }
}
