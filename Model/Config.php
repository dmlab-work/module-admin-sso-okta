<?php
/**
 * Copyright © MageDevGroup. All rights reserved.
 */
declare(strict_types=1);

namespace MageDevGroup\AdminSsoOkta\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Typed reader over the Okta-specific admin configuration.
 *
 * Okta is standard OIDC, so the only IdP-specific settings are the org domain and
 * an optional custom authorization server; both live under the admin-sso section
 * (group `okta`). {@see OktaPreset} reads them here to build the discovery URL,
 * keeping the provider-agnostic core free of Okta config paths.
 */
class Config
{
    /** Okta org domain (e.g. `dev-123.okta.com`). */
    public const XML_PATH_DOMAIN = 'magedevgroup_admin_sso/okta/domain';

    /** Optional custom authorization server id (e.g. `default`). */
    public const XML_PATH_AUTH_SERVER = 'magedevgroup_admin_sso/okta/auth_server';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Configured Okta org domain, or null when unset.
     */
    public function getDomain(): ?string
    {
        return $this->readNonEmptyString(self::XML_PATH_DOMAIN);
    }

    /**
     * Configured custom authorization server id, or null when unset (org server).
     */
    public function getAuthServer(): ?string
    {
        return $this->readNonEmptyString(self::XML_PATH_AUTH_SERVER);
    }

    /**
     * Read a config value as a trimmed non-empty string, or null.
     *
     * @param string $path
     */
    private function readNonEmptyString(string $path): ?string
    {
        $value = $this->scopeConfig->getValue($path);
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
