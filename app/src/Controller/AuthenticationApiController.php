<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use OpenApi\Annotations as OA;

/**
 * AuthenticationApiController
 * - It is going to return a JWT token according to the user provided at /registration endpoint
 * - Also, it is going to generate a token  to be updated at the token entity just for access purposes
 * - (this is how I understood reading the specifications)
 *
 * @Route("/api")
 */
class AuthenticationApiController
{
    const PERMISSION_READ = 'read';
    const PERMISSION_WRITE = 'write';
    const ERROR_VALIDATION_MESSAGE = 'Missing keys or value is empty, please read the documentation';

    const AUTHENTICATION_LOGIN_ACCESS_ERROR_MESSAGE = 'Access value is not valid: use read or write';
    const AUTHENTICATION_LOGIN_USER_NOTFOUND_MESSAGE = 'User %s not found';
    const AUTHENTICATION_LOGIN_PASSWORD_ERROR_MESSAGE = 'Password is not valid';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->em = $entityManager;
    }

    /**
     * Login via token authentication
     *
     * Output:
     * - tokenJWT: Authentication token
     * - tokenPermission: from the Token entity (for read or read/write content)
     *
     * @param JWTTokenManagerInterface $JWTManager
     * @param Request $request
     *
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @OA\RequestBody(@OA\MediaType(mediaType="application/json", @OA\Schema(
     *      @OA\Property(property="username", type="string", example="", description="username"),
     *    	@OA\Property(property="password",type="string", example="", description="password"),
     *      @OA\Property(property="access",type="string", example="read/write", description="access"),),),),
     * @OA\Response(response="200", description="Login authentication")
     *
     * @Route("/login", name="api.login", methods={"POST"})
     */
    public function loginAction(JWTTokenManagerInterface $JWTManager,
                          Request $request,
                          UserRepository $userRepository,
                          UserPasswordEncoderInterface $encoder): JsonResponse
    {
        $data = json_decode($request->getContent(),true);

        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;
        $access = isset($data['access']) ? $data['access'] : null;

        if (is_null($username) && is_null($password) && is_null($username)) {
            return new JsonResponse(['message' => self::ERROR_VALIDATION_MESSAGE], Response::HTTP_CONFLICT);
        }

        if ($access !== self::PERMISSION_READ && $access !== self::PERMISSION_WRITE) {
            return new JsonResponse(['message' => self::AUTHENTICATION_LOGIN_ACCESS_ERROR_MESSAGE], Response::HTTP_NOT_FOUND);
        }

        try {
            $user = $userRepository->findOneBy(['userName' => $username]);

            if (!$user instanceof User) {
                return new JsonResponse([
                    'message' => sprintf(self::AUTHENTICATION_LOGIN_USER_NOTFOUND_MESSAGE, $username)],
                     Response::HTTP_NOT_FOUND);
            }

            $isPasswordValid = $encoder->isPasswordValid($user, $password);

            if (!$isPasswordValid) {
                return new JsonResponse(['message' => self::AUTHENTICATION_LOGIN_PASSWORD_ERROR_MESSAGE], Response::HTTP_NOT_FOUND);
            }

            // Generating a JWT Token
            $tokenJWT = $JWTManager->create($user);

            // Generating a token - for permissions purposes
            /** @var Token $tokenPermission */
            $tokenPermission = new Token();
            $tokenPermission
                ->setUser($user)
                ->setTokenAccess($tokenPermission->getTokenAccess())
                ->setAccess($access);
            $this->em->persist($tokenPermission);
            $this->em->flush();

            $this->logger->info('User '.$username.' is logged.');
            return new JsonResponse([
                'tokenJWT' => $tokenJWT,
                'tokenPermission' => $tokenPermission->getTokenAccess()],
                Response::HTTP_OK);

        } catch (\Exception $exception) {
            $this->logger->critical('An error occurred: '.$username.' - '.$exception->getMessage());
            return new JsonResponse(['message' => self::ERROR_EXCEPTION_MESSAGE], Response::HTTP_CONFLICT);
        }
    }
}
