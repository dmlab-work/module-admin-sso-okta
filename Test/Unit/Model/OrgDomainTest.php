<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSsoOkta\Test\Unit\Model;

use DmLab\AdminSsoOkta\Model\OrgDomain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrgDomainTest extends TestCase
{
    /**
     * @return array<string,array{string,string}>
     */
    public static function validProvider(): array
    {
        return [
            'bare host' => ['dev-123.okta.com', 'https://dev-123.okta.com'],
            'https kept' => ['https://dev-123.okta.com', 'https://dev-123.okta.com'],
            'trailing slash stripped' => ['https://dev-123.okta.com/', 'https://dev-123.okta.com'],
            'bare host trailing slash' => ['dev-123.okta.com/', 'https://dev-123.okta.com'],
            'port kept' => ['https://dev-123.okta.com:8443', 'https://dev-123.okta.com:8443'],
            'bare host with port' => ['dev-123.okta.com:8443', 'https://dev-123.okta.com:8443'],
            'case-insensitive scheme' => ['HTTPS://dev-123.okta.com', 'https://dev-123.okta.com'],
            'surrounding whitespace' => ['  login.acme.com  ', 'https://login.acme.com'],
            'host lowercased' => ['HTTPS://Dev-123.OKTA.com', 'https://dev-123.okta.com'],
            'default port dropped' => ['https://dev-123.okta.com:443', 'https://dev-123.okta.com'],
        ];
    }

    #[DataProvider('validProvider')]
    public function testNormalizesValidDomain(string $input, string $expected): void
    {
        self::assertSame($expected, (new OrgDomain())->normalize($input));
    }

    public function testBlankStaysBlank(): void
    {
        self::assertSame('', (new OrgDomain())->normalize('   '));
    }

    /**
     * @return array<string,array{string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'http' => ['http://dev-123.okta.com'],
            'http uppercase' => ['HTTP://dev-123.okta.com'],
            'ftp' => ['ftp://dev-123.okta.com'],
            'path' => ['https://dev-123.okta.com/oauth2/default'],
            'bare host with path' => ['dev-123.okta.com/evil'],
            'query' => ['https://dev-123.okta.com?x=1'],
            'empty query' => ['https://dev-123.okta.com?'],
            'fragment' => ['https://dev-123.okta.com#x'],
            'userinfo' => ['https://user:pass@dev-123.okta.com'],
            'user only' => ['user@dev-123.okta.com'],
            'no host' => ['https://'],
            'invalid host chars' => ['dev_123 .okta.com'],
            'port zero' => ['https://dev-123.okta.com:0'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidDomain(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OrgDomain())->normalize($input);
    }
}
