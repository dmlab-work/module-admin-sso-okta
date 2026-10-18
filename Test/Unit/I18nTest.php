<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSsoOkta\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts i18n/en_US.csv covers every translatable phrase the module ships.
 */
class I18nTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function translations(): array
    {
        $handle = fopen(dirname(__DIR__, 2) . '/i18n/en_US.csv', 'r');
        self::assertNotFalse($handle);
        $phrases = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            self::assertCount(2, $row);
            $phrases[$row[0]] = $row[1];
        }
        fclose($handle);

        return $phrases;
    }

    /**
     * @return string[]
     */
    private function systemXmlPhrases(): array
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->load(dirname(__DIR__, 2) . '/etc/adminhtml/system.xml'));
        $xpath = new \DOMXPath($dom);

        $phrases = [];
        foreach ($xpath->query('//*[@translate]') as $element) {
            foreach (preg_split('/\s+/', trim($element->getAttribute('translate'))) as $child) {
                foreach ($xpath->query($child, $element) as $node) {
                    $phrases[] = trim($node->textContent);
                }
            }
        }

        return $phrases;
    }

    public function testEverySystemXmlLabelAndCommentIsTranslated(): void
    {
        $translations = $this->translations();
        $phrases = $this->systemXmlPhrases();

        self::assertContains('Org Domain', $phrases);
        foreach ($phrases as $phrase) {
            self::assertArrayHasKey($phrase, $translations);
        }
    }

    public function testEveryPhpPhraseIsTranslated(): void
    {
        $translations = $this->translations();
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/Model'));
        $phrases = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $source = (string)file_get_contents($file->getPathname());
                preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'/", $source, $m);
                array_push($phrases, ...$m[1]);
            }
        }

        self::assertNotEmpty($phrases);
        foreach ($phrases as $phrase) {
            self::assertArrayHasKey(stripslashes($phrase), $translations);
        }
    }
}
