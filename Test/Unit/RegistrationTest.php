<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSsoOkta\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    public function testModuleIsRegistered(): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);

        self::assertArrayHasKey('DmLab_AdminSsoOkta', $paths);
    }

    public function testRegisteredPathPointsAtThisModule(): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);
        $path = $paths['DmLab_AdminSsoOkta'] ?? null;

        self::assertNotNull($path);
        self::assertDirectoryExists($path);
        self::assertFileExists($path . '/etc/module.xml');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sequencedModuleProvider(): array
    {
        return [
            'sso-core' => ['DmLab_SsoCore'],
            'admin-sso' => ['DmLab_AdminSso'],
            'config' => ['Magento_Config'],
        ];
    }

    #[DataProvider('sequencedModuleProvider')]
    public function testModuleSequencesAfterDependency(string $dependency): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);
        $moduleXml = ($paths['DmLab_AdminSsoOkta'] ?? '') . '/etc/module.xml';

        $dom = new \DOMDocument();
        self::assertTrue($dom->load($moduleXml));

        $sequenced = [];
        foreach ($dom->getElementsByTagName('sequence') as $sequence) {
            foreach ($sequence->getElementsByTagName('module') as $module) {
                $sequenced[] = $module->getAttribute('name');
            }
        }

        self::assertContains($dependency, $sequenced);
    }
}
