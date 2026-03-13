<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class DownloadControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        self::$alwaysBootKernel = true;
    }

    /**
     * Confirms that the /download/* endpoint does not require OIDC authentication.
     * The endpoint uses its own token-based access control in DownloadController,
     * so unauthenticated requests with an invalid UUID/token should return 404 (not 401).
     *
     * @see \App\Controller\DownloadController
     * @see https://github.com/PBXg33k/php-download-router/security.yaml PUBLIC_ACCESS rule for ^/download
     */
    public function testDownloadEndpointIsPubliclyAccessibleWithoutAuthentication(): void
    {
        // A request without Authorization header and an invalid UUID/token should return 404,
        // not 401 (which would indicate the firewall is requiring authentication).
        $fakeUuid = '00000000-0000-0000-0000-000000000000';
        $fakeToken = str_repeat('0', 64);

        static::createClient()->request(
            'GET',
            "/download/{$fakeUuid}/{$fakeToken}/files/1"
        );

        // 404 means the endpoint was reached and the controller rejected it (invalid token/job).
        // 401 would mean the Symfony firewall blocked it, which would be a regression.
        $this->assertResponseStatusCodeSame(404);
    }
}
