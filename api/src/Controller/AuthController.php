<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Controller responsible for handling authentication-related actions,
 * mainly for the browser extensions and PWA to authenticate with this service using OAuth2.
 *
 * 
 */
final class AuthController extends AbstractController
{
    public function __construct(
        #[Autowire('%oidc.client_id%')]
        private string $clientId,
        #[Autowire('%oidc.client_secret%')]
        private string $clientSecret,
        #[Autowire('%oidc.well_known_url%')]
        private string $wellKnownUrl,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    )
    {
    }

    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

    #[Route('/auth/start-oauth2-flow', name: 'app_start_oauth2_flow')]
    public function startOAuth2Flow(): Response
    {
        $authorizationEndpoint = $this->getAuthorizationEndpoint();
        if (!$authorizationEndpoint) {
            return new Response('Authorization endpoint not found', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Redirect the user to the authorization endpoint
        return $this->redirect($authorizationEndpoint);
    }

    #[Route('/auth/code-to-token', name: 'app_auth_code_to_token', methods: ['POST'])]
    public function codeToToken(Request $request): Response
    {
        if(!$request->getContentTypeFormat() === 'json') {
            return new Response('Invalid content type', Response::HTTP_BAD_REQUEST);
        }

        $body = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        // get `code` and `code_verifier` from the request
        $code = $body['code'] ?? null;
        $codeVerifier = $body['code_verifier'] ?? null;
        $redirectUri = $body['redirect_uri'] ?? null;

        // Make sure all required parameters are present
        if (!$code || !$codeVerifier || !$redirectUri) {
            return new Response('Missing required parameters', Response::HTTP_BAD_REQUEST);
        }

        // Get the token endpoint from the well-known URL
        $tokenEndpoint = $this->getTokenEndpoint();
        if (!$tokenEndpoint) {
            return new Response('Token endpoint not found', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = $this->httpClient->request('POST', $tokenEndpoint, [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'code_verifier' => $codeVerifier,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        $this->logger->debug(
            'Token endpoint response',
            [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders()
            ]
        );

        $decodedContent = json_decode($response->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to decode token endpoint response', ['error' => json_last_error_msg()]);
            return new Response('Failed to decode token endpoint response', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($decodedContent);
    }

    private function getTokenEndpoint(): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $this->wellKnownUrl);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getContent(), true);
            return $data['token_endpoint'] ?? null;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            return null;
        }
    }
}
