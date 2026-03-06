<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WellKnownController extends AbstractController
{
    #[Route('/.well-known', name: 'app_well_known')]
    public function index(): Response
    {
        return $this->render('well_known/index.html.twig', [
            'controller_name' => 'WellKnownController',
        ]);
    }

    /**
     * Endpoint to provider configuration for browser extensions.
     *
     * This endpoint will provide configuration required for using this service,
     * such as authentication methods, OAuth2 endpoints, etc.
     * This will be used by browser extensions to configure themselves to work with this service.
     *
     * @return Response
     */
    #[Route('/.well-known/browser-extension', name: 'app_well_known_browser_extension')]
    public function configForBrowserExtension(): Response
    {
        return $this->json(
            [
                'auth-mode' => 'oauth2',
                'oauth2' => [
                    'authorization_endpoint' => $this->generateUrl('app_start_oauth2_flow', [], true),
                    'token_endpoint' => $this->generateUrl('app_auth_code_to_token', [], true),
                    'scopes' => [
                        'read' => 'Read access to your data',
                    ],
                ],
                'version' => '1.0',
            ]
        );
    }
}
