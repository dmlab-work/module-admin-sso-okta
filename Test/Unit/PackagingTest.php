<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSsoOkta\Test\Unit;

use PHPUnit\Framework\TestCase;

class PackagingTest extends TestCase
{
    private function ciWorkflow(): string
    {
        return (string)file_get_contents(dirname(__DIR__, 2) . '/.github/workflows/ci.yml');
    }

    public function testCiMatrixCoversPhp82To85(): void
    {
        self::assertStringContainsString("php: ['8.2', '8.3', '8.4', '8.5']", $this->ciWorkflow());
    }

    public function testCiDisablesPackagistNotifyBeforeInstall(): void
    {
        $ci = $this->ciWorkflow();
        $notify = strpos($ci, 'composer config notify-on-install false');
        $install = strpos($ci, 'ramsey/composer-install');

        self::assertNotFalse($notify);
        self::assertNotFalse($install);
        self::assertLessThan($install, $notify);
    }

    public function testReadmeStatesRequirementsInstallAndPairing(): void
    {
        $readme = $this->readme();

        self::assertStringContainsString('- Magento 2.4.7+ · PHP 8.2–8.5', $readme);
        self::assertStringContainsString(
            "## Install\n\n```bash\ncomposer require dmlab/module-admin-sso-okta",
            $readme
        );
        self::assertStringContainsString(
            'Pairs with [admin-scim-okta](https://github.com/dmlab-work/module-admin-scim-okta)',
            $readme
        );
    }

    public function testReadmeScreenshotExists(): void
    {
        self::assertStringContainsString('](.github/images/config.png)', $this->readme());
        self::assertFileExists(dirname(__DIR__, 2) . '/.github/images/config.png');
    }

    public function testReadmeSectionOrder(): void
    {
        $readme = $this->readme();
        $positions = array_map(
            static fn (string $heading): int|false => strpos($readme, "\n" . $heading . "\n"),
            [
                '## Install', '## Set up the Okta app', '## Configuration',
                '## Requirements', '## Architecture', '## License',
            ]
        );

        self::assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        self::assertSame($sorted, $positions);
    }

    public function testVersionIsReleasedInChangelog(): void
    {
        $root = dirname(__DIR__, 2);
        $composer = json_decode((string)file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $changelog = (string)file_get_contents($root . '/CHANGELOG.md');

        self::assertSame('0.2.1', $composer['version'] ?? null);
        self::assertSame(1, preg_match('/^## (\S+)$/m', $changelog, $first));
        self::assertSame('0.2.1', $first[1]);
    }

    private function readme(): string
    {
        return (string)file_get_contents(dirname(__DIR__, 2) . '/README.md');
    }
}
