<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class RegistrationApiController
 * - Basically this is going to create a new user to be used for JWT authentication
 *
 * @package App\Controller
 */
class RegistrationApiController extends BaseApiController
{
    const REGISTRATION_CREATE_FOUND_MESSAGE = 'Username %s already exists';
    const REGISTRATION_CREATE_SUCCESS_MESSAGE = 'Username %s successfully created';

    /**
     * Creating a new user to generate a JWT token
     *
     * @Route("/registration", name="api.registration.create", methods={"POST"})
     *
     * @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="username", type="string", example="", description="username"),
     *    	@OA\Property(property="password",type="string", example="", description="password"),),),),
     *  summary="Registration",
     * @OA\Response(response="201", description="Registration")
     *
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function createAction(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $data = json_decode($request->getContent(),true);

        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;

        try {
            $user = $userRepository->findOneBy(['userName' => $username]);

            if (!is_null($user)) {
                return new JsonResponse([
                    'message' => sprintf(self::REGISTRATION_CREATE_FOUND_MESSAGE, $username)],
                    Response::HTTP_NOT_FOUND
                );
            }

            $newUser = new User();
            $newUser->setUserName($username);
            $newUser->setPassword($encoder->encodePassword($newUser, $password));

            $this->em->persist($newUser);
            $this->em->flush();

            return new JsonResponse([
                'message' => sprintf(self::REGISTRATION_CREATE_SUCCESS_MESSAGE, $newUser->getUsername())],
                Response::HTTP_CREATED
            );

        } catch (\Exception $exception) {
            dd($exception->getMessage());
            $this->logger->critical('An error occurred: '.$username.' - '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }
}
