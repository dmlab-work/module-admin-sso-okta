<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSsoOkta\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts composer.json requires every package the module code and config reference directly.
 */
class ComposerTest extends TestCase
{
    /**
     * Namespace / config-URN prefix → package that provides it.
     */
    private const PACKAGES = [
        'DmLab\\SsoCore\\' => 'dmlab/module-sso-core',
        'DmLab\\AdminSso\\' => 'dmlab/module-admin-sso',
        'Magento\\Framework\\' => 'magento/framework',
        'urn:magento:framework:' => 'magento/framework',
        'urn:magento:module:Magento_Config:' => 'magento/module-config',
    ];

    /** @var array<string, mixed> */
    private array $composer;

    /** @var string */
    private string $moduleRoot;

    protected function setUp(): void
    {
        $this->moduleRoot = dirname(__DIR__, 2);
        $composer = json_decode(
            (string) file_get_contents($this->moduleRoot . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        self::assertIsArray($composer);
        $this->composer = $composer;
    }

    public function testVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $this->composer['version'] ?? '');
    }

    public function testEveryDirectlyUsedPackageIsRequired(): void
    {
        $used = $this->usedPackages();

        self::assertContains('dmlab/module-sso-core', $used);
        self::assertContains('magento/module-config', $used);
        foreach ($used as $package) {
            self::assertArrayHasKey($package, $this->composer['require'], $package . ' is used but not required');
        }
    }

    public function testRequireConstraints(): void
    {
        self::assertSame(
            [
                'php' => '~8.2.0||~8.3.0||~8.4.0||~8.5.0',
                'magento/framework' => '^103.0.7',
                'magento/module-config' => '^101.2.7',
                'dmlab/module-admin-sso' => '^0.2.0',
                'dmlab/module-sso-core' => '^0.2.0',
            ],
            $this->composer['require']
        );
    }

    public function testRequireDevConstraints(): void
    {
        self::assertSame(['phpunit/phpunit' => '^11.5||^12.5'], $this->composer['require-dev']);
    }

    public function testKeywordsAreDeclared(): void
    {
        self::assertIsArray($this->composer['keywords'] ?? null);
        self::assertContains('magento2', $this->composer['keywords']);
        self::assertContains('okta', $this->composer['keywords']);
        self::assertArrayNotHasKey('suggest', $this->composer);
    }

    public function testMetadataIsDeclared(): void
    {
        self::assertSame([['name' => 'DMLab', 'homepage' => 'https://dmlab.work']], $this->composer['authors']);
        self::assertSame('https://github.com/dmlab-work/module-admin-sso-okta', $this->composer['homepage']);
        self::assertSame(
            [
                'issues' => 'https://github.com/dmlab-work/module-admin-sso-okta/issues',
                'source' => 'https://github.com/dmlab-work/module-admin-sso-okta',
            ],
            $this->composer['support']
        );
    }

    /**
     * @return list<string>
     */
    private function usedPackages(): array
    {
        $used = [];
        foreach ($this->sourceFiles() as $file) {
            $content = (string) file_get_contents($file);
            foreach (self::PACKAGES as $prefix => $package) {
                if (str_contains($content, $prefix)) {
                    $used[$package] = true;
                }
            }
        }

        return array_keys($used);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [$this->moduleRoot . '/registration.php'];
        foreach (['Model', 'etc'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->moduleRoot . '/' . $dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (in_array($file->getExtension(), ['php', 'xml'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
