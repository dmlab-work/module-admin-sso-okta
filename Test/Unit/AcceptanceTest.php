<?php
/**
 * Copyright © MageDevGroup. All rights reserved.
 */
declare(strict_types=1);

namespace MageDevGroup\AdminSsoOkta\Test\Unit;

use MageDevGroup\AdminSso\Model\ActiveProviderResolver;
use MageDevGroup\AdminSso\Model\Config as AdminSsoConfig;
use MageDevGroup\AdminSso\Model\Config\Source\ActiveProvider;
use MageDevGroup\AdminSso\Model\Oidc\AuthorizationStarter;
use MageDevGroup\AdminSso\Model\PresetRegistry;
use MageDevGroup\AdminSsoOkta\Model\Config as OktaConfig;
use MageDevGroup\AdminSsoOkta\Model\OktaPreset;
use MageDevGroup\SsoCore\Api\AuthorizationStateStorageInterface;
use MageDevGroup\SsoCore\Model\Oidc\AuthorizationRequestFactory;
use MageDevGroup\SsoCore\Model\Oidc\DiscoveryClient;
use MageDevGroup\SsoCore\Model\Oidc\ProviderMetadata;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use PHPUnit\Framework\TestCase;

/**
 * Acceptance test for the module's contract with `admin-sso`: with the Okta
 * preset registered (as di.xml declares) into the capability core, `okta` shows
 * up in the provider dropdown and drives the OIDC authorization URL end-to-end.
 *
 * Discovery and token exchange are stubbed — this proves the seam (registry →
 * dropdown, registry → resolver → discovery URL → auth request), not the network.
 */
class AcceptanceTest extends TestCase
{
    /** Okta org domain used across the acceptance scenario. */
    private const OKTA_DOMAIN = 'dev-123.okta.com';

    /** OIDC client id issued by the IdP. */
    private const CLIENT_ID = '0oaexampleclientid';

    /** Authorization endpoint the stubbed discovery document advertises. */
    private const AUTH_ENDPOINT = 'https://dev-123.okta.com/oauth2/v1/authorize';

    /** Admin callback URL registered as the redirect URI. */
    private const CALLBACK_URL = 'https://magento.loc/admin/adminsso/sso/callback';

    public function testOktaAppearsInProviderDropdown(): void
    {
        $source = new ActiveProvider($this->registry());

        $options = $source->toOptionArray();
        $okta = $this->optionByValue($options, 'okta');

        self::assertNotNull($okta, 'The "okta" option is missing from the provider dropdown.');
        self::assertSame('Okta', (string)$okta['label']);
    }

    public function testOktaDrivesTheAuthorizationUrl(): void
    {
        $registry = $this->registry();
        $capturedDiscoveryUrl = null;

        $discoveryClient = $this->createStub(DiscoveryClient::class);
        $discoveryClient->method('discover')->willReturnCallback(
            function (string $url) use (&$capturedDiscoveryUrl): ProviderMetadata {
                $capturedDiscoveryUrl = $url;

                return new ProviderMetadata(
                    'https://dev-123.okta.com',
                    self::AUTH_ENDPOINT,
                    'https://dev-123.okta.com/oauth2/v1/token',
                    'https://dev-123.okta.com/oauth2/v1/keys'
                );
            }
        );

        $starter = new AuthorizationStarter(
            $this->adminConfig(),
            new ActiveProviderResolver($this->activeProviderScopeConfig(), $registry),
            $discoveryClient,
            new AuthorizationRequestFactory($this->fixedRandom()),
            $this->createStub(AuthorizationStateStorageInterface::class),
            $this->backendUrl()
        );

        $url = $starter->start();

        // The Okta preset resolved by the registry built the discovery URL.
        self::assertSame(
            'https://' . self::OKTA_DOMAIN . '/.well-known/openid-configuration',
            $capturedDiscoveryUrl
        );

        // The auth URL is anchored on the discovered endpoint and carries the
        // Okta default scopes and client id.
        self::assertStringStartsWith(self::AUTH_ENDPOINT . '?', $url);
        self::assertStringContainsString('client_id=' . self::CLIENT_ID, $url);
        self::assertStringContainsString('scope=openid%20profile%20email%20groups', $url);
        self::assertStringContainsString('code_challenge_method=S256', $url);
        self::assertStringContainsString('redirect_uri=' . rawurlencode(self::CALLBACK_URL), $url);
    }

    /**
     * PresetRegistry seeded with the Okta preset exactly as etc/di.xml wires it.
     */
    private function registry(): PresetRegistry
    {
        return new PresetRegistry(['okta' => $this->oktaPreset()]);
    }

    /**
     * Okta preset over a config whose org domain is set to {@see OKTA_DOMAIN}.
     */
    private function oktaPreset(): OktaPreset
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string =>
                $path === OktaConfig::XML_PATH_DOMAIN ? self::OKTA_DOMAIN : null
        );

        $assetRepository = $this->createStub(AssetRepository::class);
        $assetRepository->method('getUrl')->willReturn('https://magento.loc/static/okta.svg');

        return new OktaPreset(new OktaConfig($scopeConfig), $assetRepository);
    }

    /**
     * admin-sso config stub: SSO enabled with a configured client id.
     */
    private function adminConfig(): AdminSsoConfig
    {
        $config = $this->createStub(AdminSsoConfig::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('getClientId')->willReturn(self::CLIENT_ID);

        return $config;
    }

    /**
     * Scope-config stub selecting `okta` as the active provider.
     */
    private function activeProviderScopeConfig(): ScopeConfigInterface
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string =>
                $path === ActiveProviderResolver::XML_PATH_ACTIVE_PROVIDER ? 'okta' : null
        );

        return $scopeConfig;
    }

    /**
     * Random stub returning fixed bytes so the auth request is deterministic.
     */
    private function fixedRandom(): Random
    {
        $random = $this->createStub(Random::class);
        $random->method('getRandomBytes')->willReturn(str_repeat("\x01", 32));

        return $random;
    }

    /**
     * Backend-URL stub returning the fixed admin callback URL.
     */
    private function backendUrl(): BackendUrlInterface
    {
        $backendUrl = $this->createStub(BackendUrlInterface::class);
        $backendUrl->method('getUrl')->willReturn(self::CALLBACK_URL);

        return $backendUrl;
    }

    /**
     * Find a dropdown option by its value.
     *
     * @param array<int,array{value:mixed,label:mixed}> $options
     * @param string $value
     * @return array{value:mixed,label:mixed}|null
     */
    private function optionByValue(array $options, string $value): ?array
    {
        foreach ($options as $option) {
            if (($option['value'] ?? null) === $value) {
                return $option;
            }
        }

        return null;
    }
}
