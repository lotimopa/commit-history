<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\DiffParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Lotimopa\CommitHistory\DiffParser\PackageDiffParser;
use Lotimopa\CommitHistory\DTO\DependencyChange;

#[CoversClass(PackageDiffParser::class)]
class PackageDiffParserTest extends TestCase
{
    private PackageDiffParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PackageDiffParser();
    }

    #[DataProvider('supportedFilesProvider')]
    public function testSupportsPackageFiles(string $filename, bool $expected): void
    {
        $this->assertSame($expected, $this->parser->supports($filename));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function supportedFilesProvider(): iterable
    {
        yield 'package.json' => ['package.json', true];
        yield 'package-lock.json' => ['package-lock.json', true];
        yield 'path/package.json' => ['path/to/package.json', true];
        yield 'path/package-lock.json' => ['path/to/package-lock.json', true];
        yield 'composer.json' => ['composer.json', false];
        yield 'other.json' => ['other.json', false];
    }

    public function testParsePackageJsonAddedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,6 +10,7 @@
   "dependencies": {
     "react": "^18.0.0",
+    "lodash": "^4.17.21"
   },
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(1, $changes);
        $this->assertSame('lodash', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changes[0]->type);
        $this->assertNull($changes[0]->oldVersion);
        $this->assertSame('^4.17.21', $changes[0]->newVersion);
        $this->assertSame('package.json', $changes[0]->sourceFile);
    }

    public function testParsePackageJsonRemovedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,7 +10,6 @@
   "dependencies": {
     "react": "^18.0.0",
-    "lodash": "^4.17.21"
   },
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(1, $changes);
        $this->assertSame('lodash', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_REMOVED, $changes[0]->type);
        $this->assertSame('^4.17.21', $changes[0]->oldVersion);
        $this->assertNull($changes[0]->newVersion);
    }

    public function testParsePackageJsonUpdatedDependency(): void
    {
        $diff = <<<'DIFF'
@@ -10,7 +10,7 @@
   "dependencies": {
-    "react": "^17.0.0",
+    "react": "^18.0.0",
   },
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(1, $changes);
        $this->assertSame('react', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_UPDATED, $changes[0]->type);
        $this->assertSame('^17.0.0', $changes[0]->oldVersion);
        $this->assertSame('^18.0.0', $changes[0]->newVersion);
    }

    public function testParsePackageJsonIgnoresNonDependencyKeys(): void
    {
        $diff = <<<'DIFF'
@@ -1,5 +1,5 @@
 {
-  "name": "old-name",
+  "name": "new-name",
-  "version": "1.0.0",
+  "version": "2.0.0",
-  "description": "Old description",
+  "description": "New description",
 }
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(0, $changes);
    }

    public function testParsePackageJsonIgnoresUrlVersions(): void
    {
        $diff = <<<'DIFF'
@@ -10,6 +10,7 @@
   "dependencies": {
+    "my-package": "https://github.com/org/repo.git",
+    "file-package": "file:../local-package",
+    "git-package": "git+https://github.com/org/repo.git",
   },
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(0, $changes);
    }

    public function testParsePackageJsonMultipleChanges(): void
    {
        $diff = <<<'DIFF'
@@ -10,8 +10,9 @@
   "dependencies": {
-    "react": "^17.0.0",
+    "react": "^18.0.0",
+    "axios": "^1.0.0",
-    "moment": "^2.29.0"
   },
DIFF;

        $changes = $this->parser->parse($diff, 'package.json');

        $this->assertCount(3, $changes);

        $changesByName = [];
        foreach ($changes as $change) {
            $changesByName[$change->name] = $change;
        }

        $this->assertSame(DependencyChange::TYPE_UPDATED, $changesByName['react']->type);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changesByName['axios']->type);
        $this->assertSame(DependencyChange::TYPE_REMOVED, $changesByName['moment']->type);
    }

    public function testParsePackageLockAddedPackage(): void
    {
        $diff = <<<'DIFF'
@@ -100,6 +100,20 @@
+    "node_modules/lodash": {
+      "version": "4.17.21",
DIFF;

        $changes = $this->parser->parse($diff, 'package-lock.json');

        $this->assertCount(1, $changes);
        $this->assertSame('lodash', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_ADDED, $changes[0]->type);
        $this->assertNull($changes[0]->oldVersion);
        $this->assertSame('4.17.21', $changes[0]->newVersion);
    }

    public function testParsePackageLockUpdatedPackage(): void
    {
        $diff = <<<'DIFF'
@@ -100,7 +100,7 @@
-    "node_modules/react": {
-      "version": "17.0.2",
+    "node_modules/react": {
+      "version": "18.2.0",
DIFF;

        $changes = $this->parser->parse($diff, 'package-lock.json');

        $this->assertCount(1, $changes);
        $this->assertSame('react', $changes[0]->name);
        $this->assertSame(DependencyChange::TYPE_UPDATED, $changes[0]->type);
        $this->assertSame('17.0.2', $changes[0]->oldVersion);
        $this->assertSame('18.2.0', $changes[0]->newVersion);
    }

    public function testParsePackageLockIgnoresNestedNodeModules(): void
    {
        $diff = <<<'DIFF'
@@ -100,6 +100,10 @@
+    "node_modules/parent/node_modules/nested": {
+      "version": "1.0.0",
DIFF;

        $changes = $this->parser->parse($diff, 'package-lock.json');

        $this->assertCount(0, $changes);
    }

    public function testParseEmptyDiff(): void
    {
        $changes = $this->parser->parse('', 'package.json');

        $this->assertCount(0, $changes);
    }
}
