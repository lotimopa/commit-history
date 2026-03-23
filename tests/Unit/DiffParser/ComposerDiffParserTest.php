<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\DiffParser;

use Lotimopa\CommitHistory\DiffParser\ComposerDiffParser;
use Lotimopa\CommitHistory\DTO\DependencyChange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerDiffParser::class)]
class ComposerDiffParserTest extends TestCase
{
    private ComposerDiffParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ComposerDiffParser();
    }

    #[DataProvider('supportedFilesProvider')]
    public function testSupportsComposerFiles(string $filename, bool $expected): void
    {
        $this->assertSame($expected, $this->parser->supports($filename));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function supportedFilesProvider(): iterable
    {
        yield 'composer.json' => ['composer.json', true];
        yield 'composer.lock' => ['composer.lock', true];
        yield 'path/composer.json' => ['path/to/composer.json', true];
        yield 'path/composer.lock' => ['path/to/composer.lock', true];
        yield 'package.json' => ['package.json', false];
        yield 'other.json' => ['other.json', false];
    }

    public function testParseComposerJsonAddedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,6 +10,7 @@
     "require": {
         "php": ">=8.2",
+        "symfony/http-client": "^7.0"
     },
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changes[0]->type);
        $this->assertNull($changes[0]->oldVersion);
        $this->assertSame('^7.0', $changes[0]->newVersion);
        $this->assertSame('composer.json', $changes[0]->sourceFile);
    }

    public function testParseComposerJsonRemovedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,7 +10,6 @@
     "require": {
         "php": ">=8.2",
-        "symfony/http-client": "^6.4"
     },
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_REMOVED, $changes[0]->type);
        $this->assertSame('^6.4', $changes[0]->oldVersion);
        $this->assertNull($changes[0]->newVersion);
    }

    public function testParseComposerJsonUpdatedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,7 +10,7 @@
     "require": {
         "php": ">=8.2",
-        "symfony/http-client": "^6.4",
+        "symfony/http-client": "^7.0",
     },
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_UPDATED, $changes[0]->type);
        $this->assertSame('^6.4', $changes[0]->oldVersion);
        $this->assertSame('^7.0', $changes[0]->newVersion);
    }

    public function testParseComposerJsonIgnoresNonDependencyKeys(): void
    {
        $diff = <<<'DIFF'
@@ -1,5 +1,5 @@
 {
-    "name": "old-name/package",
+    "name": "new-name/package",
-    "description": "Old description",
+    "description": "New description",
 }
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(0, $changes);
    }

    public function testParseComposerJsonIgnoresNonPackageStrings(): void
    {
        $diff = <<<'DIFF'
@@ -1,5 +1,5 @@
 {
-    "license": "MIT",
+    "license": "Apache-2.0",
 }
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(0, $changes);
    }

    public function testParseComposerJsonMultipleChanges(): void
    {
        $diff = <<<'DIFF'
@@ -10,8 +10,9 @@
     "require": {
         "php": ">=8.2",
-        "symfony/http-client": "^6.4",
+        "symfony/http-client": "^7.0",
+        "guzzlehttp/guzzle": "^7.0",
-        "old/package": "^1.0"
     },
DIFF;

        $changes = $this->parser->parse($diff, 'composer.json');

        $this->assertCount(3, $changes);

        $changesByName = [];
        foreach ($changes as $change) {
            $changesByName[$change->name] = $change;
        }

        $this->assertSame(DependencyChange::TYPE_UPDATED, $changesByName['symfony/http-client']->type);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changesByName['guzzlehttp/guzzle']->type);
        $this->assertSame(DependencyChange::TYPE_REMOVED, $changesByName['old/package']->type);
    }

    public function testParseComposerLockAddedPackage(): void
    {
        $diff = <<<'DIFF'
@@ -100,6 +100,20 @@
+            "name": "symfony/http-client",
+            "version": "v7.0.0",
DIFF;

        $changes = $this->parser->parse($diff, 'composer.lock');

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changes[0]->type);
        $this->assertNull($changes[0]->oldVersion);
        $this->assertSame('v7.0.0', $changes[0]->newVersion);
    }

    public function testParseComposerLockUpdatedPackage(): void
    {
        $diff = <<<'DIFF'
@@ -100,7 +100,7 @@
-            "name": "symfony/http-client",
-            "version": "v6.4.0",
+            "name": "symfony/http-client",
+            "version": "v7.0.0",
DIFF;

        $changes = $this->parser->parse($diff, 'composer.lock');

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_UPDATED, $changes[0]->type);
        $this->assertSame('v6.4.0', $changes[0]->oldVersion);
        $this->assertSame('v7.0.0', $changes[0]->newVersion);
    }

    public function testParseEmptyDiff(): void
    {
        $changes = $this->parser->parse('', 'composer.json');

        $this->assertCount(0, $changes);
    }
}
