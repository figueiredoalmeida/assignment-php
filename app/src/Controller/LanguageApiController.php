<?php

namespace App\Controller;

use App\Entity\Language;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class LanguageApiController
 *
 * @package App\Controller
 * @Route("/api")
 */
class LanguageApiController extends BaseApiController
{
    const LANGUAGE_LIST_NOTFOUND_MESSAGE = 'There are no languages registered. Please, read documentation to create a new one.';
    const LANGUAGE_CREATE_SUCCESS_MESSAGE = 'Languages created with success';

    /**
     * @var LanguageRepository
     */
    private LanguageRepository $languageRepository;

    public function __construct(LoggerInterface $logger,
                                EntityManagerInterface $entityManager,
                                LanguageRepository $languageRepository)
    {
        parent::__construct($logger, $entityManager);
        $this->languageRepository = $languageRepository;
    }

    /**
     * Listing all the languages
     *
     * @return JsonResponse
     * @Route("/languages", name="api.language.list", methods={"GET"})
     */
    public function list(): Response
    {
        try {
            $languageList = $this->languageRepository->findAll();

            if (count($languageList) === 0) {
                return new Response("", Response::HTTP_NO_CONTENT);
            }

            return new JsonResponse([
                'total' => count($languageList),
                'keys' => $languageList,
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Creating language(s)
     *
     * @Route("/languages", name="api.language.create", methods={"POST"})
     *
     * @OA\Post(
     *  path="/api/languages",
     *  @Security(name="Bearer"),
     *  @OA\RequestBody(@OA\MediaType(mediaType="application/json",
     *     @OA\Schema(
     *        @OA\Property(property="tokenAccess", type="string", example="", description="key_token_access"),
     *        @OA\Property(property="languages", type="object",
     *            @OA\Property(property="isoCode", example="en", type="string"),
     *            @OA\Property(property="name", example="English", type="string"),
     *            @OA\Property(property="leftToRight", example="1", type="string"),),),),),
     *  @OA\Response(response="201", description="Creating a key"))
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

        $data = json_decode($request->getContent(),true);
        $languages = $data['languages'];

        try {
            foreach ($languages as $value) {

                $languageRepo = $this->languageRepository->findBy([
                   'isoCode' => $value['isoCode'],
                   'name' => $value['name'],
                   'ltr' => $value['leftToRight'],
                ]);

                if (count($languageRepo) > 0) {
                    continue;
                }

                $language = new Language();
                $language
                    ->setIsoCode($value['isoCode'])
                    ->setName($value['name'])
                    ->setLtr($value['leftToRight'])
                ;

                $this->em->persist($language);
            }

            $this->em->flush();

            return new JsonResponse([
                'message' => self::LANGUAGE_CREATE_SUCCESS_MESSAGE],
                Response::HTTP_CREATED
            );
        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }
}
