<?php

namespace App\Controller;

use App\Helper\TranslationFactory;
use App\Repository\KeyReferenceRepository;
use App\Repository\LanguageRepository;
use App\Repository\TranslationRepository;
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
 * Class TranslationApiController
 * - This entity has no relation with users
 *
 * @package App\Controller
 * @Route("/api")
 */
class TranslationApiController extends BaseApiController
{
    /**
     * @var KeyReferenceRepository
     */
    private KeyReferenceRepository $keyReferenceRepository;
    /**
     * @var TranslationRepository
     */
    private TranslationRepository $translationRepository;
    /**
     * @var LanguageRepository
     */
    private LanguageRepository $languageRepository;
    /**
     * @var TranslationFactory
     */
    private TranslationFactory $translationFactory;

    /**
     * TranslationApiController constructor.
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param LanguageRepository $languageRepository
     * @param KeyReferenceRepository $keyReferenceRepository
     * @param TranslationRepository $translationRepository
     * @param TranslationFactory $translationFactory
     */
    public function __construct(LoggerInterface $logger,
                                EntityManagerInterface $entityManager,
                                LanguageRepository $languageRepository,
                                KeyReferenceRepository $keyReferenceRepository,
                                TranslationRepository $translationRepository,
                                TranslationFactory $translationFactory)
    {
        parent::__construct($logger, $entityManager);
        $this->keyReferenceRepository = $keyReferenceRepository;
        $this->translationRepository = $translationRepository;
        $this->languageRepository = $languageRepository;
        $this->translationFactory = $translationFactory;
    }

    /**
     * Retrieving the translation by key
     *
     * @Route("/translation/{keyReference}", name="api.translation.list", methods={"GET"})
     *
     * @param string $keyReference
     * @return JsonResponse
     */
    public function listByKey(string $keyReference): Response
    {
        try {
            // Cheking if the given reference key it does exist
            $keyRepo = $this->keyReferenceRepository->findOneBy(['name' => $keyReference]);

            if (is_null($keyRepo)) {
                return new Response("", Response::HTTP_NO_CONTENT);
            }

            // Checking if there is any translation for the given key
            $translationList = $this->translationRepository->findBy(['keyReference' => $keyRepo]);

            if (count($translationList) === 0) {
                return new Response("", Response::HTTP_NO_CONTENT);
            }

            return new JsonResponse([
                'total' => count($translationList),
                'translations' => $translationList
            ], Response::HTTP_OK);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Creating a translation by key
     *
     * @Route("/translation/{keyReference}", name="api.translation.create", methods={"POST"})
     *
     * @OA\Post(
     *   path="/api/translation/{keyReference}",
     *   @Security(name="Bearer"),
     *   @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *      @OA\Schema(
     *         @OA\Property(property="tokenAccess", type="string", example="", description="Token access"),
     *         @OA\Property(property="translations", type="object",
     *            @OA\Property(property="isoCode", example="Lorem ipsum dolor sit amet", type="string"),),),),),
     *  summary="Creating a translation by key",
     *  @OA\Response(response="201", description="Creating a key"))
     *
     * @param string $keyReference
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function create(string $keyReference, Request $request): Response
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(),true);

        try {
            // Checking if the given key does exist
            $keyReference = $this->keyReferenceRepository->findOneBy(['name' => $keyReference]);
            if (is_null($keyReference)) {
                return new JsonResponse(['message' => KeyApiController::KEY_NOT_FOUND_MESSAGE], Response::HTTP_NOT_FOUND);
            }

            $translations = $data['translations'];
            $httpResponse = Response::HTTP_NOT_FOUND;
            foreach ($translations as $key => $value) {

                // Checking if the language is a valid one
                $language = $this->languageRepository->findOneBy(['isoCode' => $key]);

                if (is_null($language)) {
                    continue;
                }

                $translationRepo = $this->translationRepository->findOneBy([
                    'language' => $language,
                    'keyReference' => $keyReference
                ]);

                if (is_null($translationRepo)) {
                    $httpResponse = Response::HTTP_CREATED;

                    $newTranslation = $this->translationFactory->create($language, $keyReference, $value);
                    $this->em->persist($newTranslation);
                }
            }

            $this->em->flush();

            return new Response("", $httpResponse);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Updating the translation by key
     *
     * @Route("/translation/{keyReference}", name="api.translation.update", methods={"PUT"})
     *
     * @OA\Put(
     *   path="/api/translation/{keyReference}",
     *   @Security(name="Bearer"),
     *   @OA\RequestBody(
     *      @OA\MediaType(mediaType="application/json",
     *      @OA\Schema(
     *         @OA\Property(property="tokenAccess", type="string", example="", description="Token access"),
     *         @OA\Property(property="translations", type="object",
     *            @OA\Property(property="isoCode", example="Lorem ipsum dolor sit amet", type="string"),),),),),
     *  @OA\Response(response="201", description="Updating a key"))
     *
     * @param string $keyReference
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function update(string $keyReference, Request $request): Response
    {
        if (!$this->tokenPermission($request)) {
            return new JsonResponse(self::ERROR_PERMISSION_MESSAGE, Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(),true);

        try {
            // Checking if the given key does exist
            $keyReference = $this->keyReferenceRepository->findOneBy(['name' => $keyReference]);
            if (is_null($keyReference)) {
                return new JsonResponse(['message' => KeyApiController::KEY_NOT_FOUND_MESSAGE],Response::HTTP_NOT_FOUND);
            }

            $translations = $data['translations'];
            $httpResponse = Response::HTTP_NOT_FOUND;

            foreach ($translations as $key => $value) {

                // Checking if the language is a valid one
                $language = $this->languageRepository->findOneBy(['isoCode' => $key]);

                if (is_null($language)) {
                    continue;
                }

                $translation = $this->translationRepository->findOneBy([
                    'language' => $language,
                    'keyReference' => $keyReference
                ]);

                if (!is_null($translation)) {
                    $httpResponse = Response::HTTP_OK;

                    $newTranslation = $this->translationFactory->create($language, $keyReference, $value);

                    $translation
                        ->setLanguage($newTranslation->getLanguage())
                        ->setKeyReference($newTranslation->getKeyReference())
                        ->setValue($newTranslation->getValue())
                    ;
                }
            }

            $this->em->flush();

            return new Response("", $httpResponse);

        } catch (Exception $exception) {
            $this->logger->critical('An error occurred: '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }
}