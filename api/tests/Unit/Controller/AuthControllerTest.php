<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AuthController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthControllerTest extends TestCase
{
    private const CLIENT_ID = 'test-client-id';
    private const CLIENT_SECRET = 'test-client-secret';
    private const WELL_KNOWN_URL = 'https://idp.example.com/.well-known/openid-configuration';
    private const AUTHORIZATION_ENDPOINT = 'https://idp.example.com/authorize';
    private const TOKEN_ENDPOINT = 'https://idp.example.com/token';
    private const CALLBACK_URL = 'https://localhost/auth/callback';

    private HttpClientInterface&MockObject $httpClient;
    private CacheItemPoolInterface&MockObject $cache;
    private LoggerInterface&MockObject $logger;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private AuthController $controller;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new AuthController(
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            self::WELL_KNOWN_URL,
            $this->httpClient,
            $this->cache,
            $this->logger,
        );

        // AbstractController needs a container for redirect(), generateUrl(), render()
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')
            ->willReturnCallback(fn (string $route) => match ($route) {
                'app_auth_callback' => self::CALLBACK_URL,
                default => '',
            });

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->method('render')->willReturn('<html>mock</html>');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => in_array($id, ['router', 'twig']));
        $container->method('get')->willReturnCallback(fn (string $id) => match ($id) {
            'router' => $this->urlGenerator,
            'twig' => $twig,
            default => null,
        });

        $this->controller->setContainer($container);
    }

    // --- startOAuth2Flow tests ---

    public function testStartOAuth2FlowRedirectsToIdpWithPkceAndState(): void
    {
        $this->mockWellKnownResponse();
        $this->mockCacheSave();

        $response = $this->controller->startOAuth2Flow();

        $this->assertSame(302, $response->getStatusCode());
        $locationHeader = $response->headers->get('Location');
        $this->assertNotNull($locationHeader);

        // Parse the redirect URL
        parse_str(parse_url($locationHeader, \PHP_URL_QUERY) ?? '', $queryParams);

        $this->assertSame(self::CLIENT_ID, $queryParams['client_id']);
        $this->assertSame(self::CALLBACK_URL, $queryParams['redirect_uri']);
        $this->assertSame('code', $queryParams['response_type']);
        $this->assertStringContainsString('openid', $queryParams['scope']);
        $this->assertStringContainsString('profile', $queryParams['scope']);
        $this->assertStringContainsString('email', $queryParams['scope']);
        $this->assertStringContainsString('offline_access', $queryParams['scope']);
        $this->assertSame('S256', $queryParams['code_challenge_method']);
        $this->assertNotEmpty($queryParams['code_challenge']);
        $this->assertNotEmpty($queryParams['state']);
    }

    public function testStartOAuth2FlowStoresCodeVerifierInCache(): void
    {
        $this->mockWellKnownResponse();

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with($this->callback(fn ($v) => is_string($v)));
        $cacheItem->expects($this->once())->method('expiresAfter')->with(300);

        $this->cache->expects($this->once())->method('getItem')
            ->with($this->stringStartsWith('oauth2_state_'))
            ->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $this->controller->startOAuth2Flow();
    }

    public function testStartOAuth2FlowReturnsErrorWhenAuthorizationEndpointNotFound(): void
    {
        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([]));
        $this->httpClient->method('request')->willReturn($wellKnownResponse);

        $response = $this->controller->startOAuth2Flow();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('authorization_endpoint_not_found', $data['error']);
    }

    public function testStartOAuth2FlowHandlesWellKnownFailure(): void
    {
        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(500);
        $this->httpClient->method('request')->willReturn($wellKnownResponse);

        $response = $this->controller->startOAuth2Flow();

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testStartOAuth2FlowHandlesWellKnownHttpException(): void
    {
        $this->httpClient->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $response = $this->controller->startOAuth2Flow();

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    // --- callback tests ---

    public function testCallbackExchangesCodeForTokensSuccessfully(): void
    {
        $tokenResponseData = [
            'access_token' => 'eyJhbGciOiJSUzI1NiJ9.test',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh-token-value',
        ];

        $this->mockCacheHit('test-state', 'test-code-verifier');
        $this->mockTokenExchange($tokenResponseData);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'auth-code-from-idp',
            'state' => 'test-state',
        ]);

        $response = $this->controller->callback($request);

        // The controller renders a Twig template (mocked), so we get a 200 HTML response
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackDeletesCacheEntryToPreventReplay(): void
    {
        $tokenResponseData = [
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
        ];

        $this->mockCacheHit('replay-state', 'verifier');
        $this->cache->expects($this->once())->method('deleteItem')
            ->with('oauth2_state_replay-state');
        $this->mockTokenExchange($tokenResponseData);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
            'state' => 'replay-state',
        ]);

        $this->controller->callback($request);
    }

    public function testCallbackRendersErrorWhenIdpReturnsError(): void
    {
        $request = Request::create('/auth/callback', 'GET', [
            'error' => 'access_denied',
            'error_description' => 'User denied consent.',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenCodeIsMissing(): void
    {
        $request = Request::create('/auth/callback', 'GET', [
            'state' => 'some-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenStateIsMissing(): void
    {
        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenStateNotFoundInCache(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $this->cache->method('getItem')->willReturn($cacheItem);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
            'state' => 'expired-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenTokenEndpointNotFound(): void
    {
        $this->mockCacheHit('test-state', 'verifier');

        // Well-known returns no token_endpoint
        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([]));
        $this->httpClient->method('request')->willReturn($wellKnownResponse);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
            'state' => 'test-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenIdpReturnsTokenError(): void
    {
        $this->mockCacheHit('test-state', 'verifier');

        $idpError = [
            'error' => 'invalid_grant',
            'error_description' => 'The authorization code has expired.',
        ];
        $this->mockTokenExchange($idpError, 400);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'expired-code',
            'state' => 'test-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenTokenExchangeThrowsException(): void
    {
        $this->mockCacheHit('test-state', 'verifier');

        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([
            'token_endpoint' => self::TOKEN_ENDPOINT,
        ]));

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method) use ($wellKnownResponse) {
                if ('GET' === $method) {
                    return $wellKnownResponse;
                }
                throw new \RuntimeException('Connection timed out');
            });

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
            'state' => 'test-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCallbackRendersErrorWhenIdpReturnsInvalidJson(): void
    {
        $this->mockCacheHit('test-state', 'verifier');

        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([
            'token_endpoint' => self::TOKEN_ENDPOINT,
        ]));

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(200);
        $tokenResponse->method('getContent')->willReturn('not-json');
        $tokenResponse->method('getHeaders')->willReturn([]);

        $this->httpClient->method('request')
            ->willReturnCallback(fn (string $method) => 'GET' === $method ? $wellKnownResponse : $tokenResponse);

        $request = Request::create('/auth/callback', 'GET', [
            'code' => 'some-code',
            'state' => 'test-state',
        ]);

        $response = $this->controller->callback($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    // --- Helper methods ---

    private function mockWellKnownResponse(): void
    {
        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([
            'authorization_endpoint' => self::AUTHORIZATION_ENDPOINT,
            'token_endpoint' => self::TOKEN_ENDPOINT,
        ]));

        $this->httpClient->method('request')->willReturn($wellKnownResponse);
    }

    private function mockCacheSave(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->method('save')->willReturn(true);
    }

    private function mockCacheHit(string $state, string $codeVerifier): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($codeVerifier);

        $this->cache->method('getItem')
            ->with('oauth2_state_'.$state)
            ->willReturn($cacheItem);
    }

    private function mockTokenExchange(array $responseData, int $statusCode = 200): void
    {
        $wellKnownResponse = $this->createMock(ResponseInterface::class);
        $wellKnownResponse->method('getStatusCode')->willReturn(200);
        $wellKnownResponse->method('getContent')->willReturn(json_encode([
            'authorization_endpoint' => self::AUTHORIZATION_ENDPOINT,
            'token_endpoint' => self::TOKEN_ENDPOINT,
        ]));

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn($statusCode);
        $tokenResponse->method('getContent')->willReturn(json_encode($responseData));
        $tokenResponse->method('getHeaders')->willReturn([]);

        $this->httpClient->method('request')
            ->willReturnCallback(fn (string $method) => 'GET' === $method ? $wellKnownResponse : $tokenResponse);
    }
}
