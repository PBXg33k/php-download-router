<?php

namespace App\Security\Core\User;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OidcUserProvider implements AttributesBasedUserProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function loadUserByIdentifier(string $identifier, array $attributes = []): UserInterface
    {
        $this->logger->debug('Loading OIDC user', ['identifier' => $identifier, 'attributes' => $attributes]);

        $issuer = $attributes['issuer'] ?? 'default_issuer';
        $sub = $attributes['sub'] ?? $identifier;
        $email = $attributes['email'] ?? null;
        $preferredUsername = $attributes['preferred_username'] ?? null;
        $roles = $attributes['roles'] ?? ['ROLE_USER'];

        // For OIDC providers (e.g. authentik) that expose a "groups" claim, convert group names from "Group Name" to "ROLE_GROUP_NAME"
        if (isset($attributes['groups']) && is_array($attributes['groups'])) {
            $groupRoles = array_map(
                fn ($group) => 'ROLE_'.strtoupper(str_replace(' ', '_', $group)),
                $attributes['groups']
            );
            $roles = array_values(array_unique(array_merge($roles, $groupRoles)));
        }

        // Always add ROLE_USER to ensure every authenticated user has at least this role
        $roles[] = 'ROLE_USER';
        $roles = array_values(array_unique($roles));

        return new OidcUser(
            issuer: $issuer,
            sub: $sub,
            name: $attributes['name'] ?? '',
            givenName: $attributes['given_name'] ?? '',
            nickName: $attributes['nickname'] ?? '',
            email: $email,
            preferredUsername: $preferredUsername,
            roles: $roles
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $this->logger->debug('Refreshing OIDC user', ['user' => $user->getUserIdentifier()]);

        // Since OidcUser is stateless, we can simply return the user as is.
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        $this->logger->debug('Checking support for class', ['class' => $class]);

        return OidcUser::class === $class || is_subclass_of($class, OidcUser::class);
    }
}
