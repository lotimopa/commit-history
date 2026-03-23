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
use PHPUnit\Framework\TestCase;
use Lotimopa\CommitHistory\DiffParser\DiffParserInterface;
use Lotimopa\CommitHistory\DiffParser\DiffParserRegistry;
use Lotimopa\CommitHistory\DTO\DependencyChange;

#[CoversClass(DiffParserRegistry::class)]
class DiffParserRegistryTest extends TestCase
{
    public function testSupportsReturnsTrueWhenParserSupportsFile(): void
    {
        $parser = $this->createMock(DiffParserInterface::class);
        $parser->method('supports')
            ->with('composer.json')
            ->willReturn(true);

        $registry = new DiffParserRegistry([$parser]);

        $this->assertTrue($registry->supports('composer.json'));
    }

    public function testSupportsReturnsFalseWhenNoParserSupportsFile(): void
    {
        $parser = $this->createMock(DiffParserInterface::class);
        $parser->method('supports')
            ->willReturn(false);

        $registry = new DiffParserRegistry([$parser]);

        $this->assertFalse($registry->supports('unknown.file'));
    }

    public function testSupportsReturnsFalseWithEmptyParsers(): void
    {
        $registry = new DiffParserRegistry([]);

        $this->assertFalse($registry->supports('composer.json'));
    }

    public function testParseReturnsChangesFromSupportingParser(): void
    {
        $expectedChange = new DependencyChange(
            name: 'symfony/http-client',
            type: DependencyChange::TYPE_ADDED,
            newVersion: '^7.0',
        );

        $parser = $this->createMock(DiffParserInterface::class);
        $parser->method('supports')
            ->with('composer.json')
            ->willReturn(true);
        $parser->method('parse')
            ->with('diff content', 'composer.json')
            ->willReturn([$expectedChange]);

        $registry = new DiffParserRegistry([$parser]);

        $changes = $registry->parse('diff content', 'composer.json');

        $this->assertCount(1, $changes);
        $this->assertSame($expectedChange, $changes[0]);
    }

    public function testParseReturnsEmptyArrayWhenNoParserSupportsFile(): void
    {
        $parser = $this->createMock(DiffParserInterface::class);
        $parser->method('supports')
            ->willReturn(false);

        $registry = new DiffParserRegistry([$parser]);

        $changes = $registry->parse('diff content', 'unknown.file');

        $this->assertCount(0, $changes);
    }

    public function testParseUsesFirstSupportingParser(): void
    {
        $firstChange = new DependencyChange(
            name: 'first-package',
            type: DependencyChange::TYPE_ADDED,
        );
        $secondChange = new DependencyChange(
            name: 'second-package',
            type: DependencyChange::TYPE_ADDED,
        );

        $firstParser = $this->createMock(DiffParserInterface::class);
        $firstParser->method('supports')->willReturn(true);
        $firstParser->method('parse')->willReturn([$firstChange]);

        $secondParser = $this->createMock(DiffParserInterface::class);
        $secondParser->method('supports')->willReturn(true);
        $secondParser->method('parse')->willReturn([$secondChange]);

        $registry = new DiffParserRegistry([$firstParser, $secondParser]);

        $changes = $registry->parse('diff content', 'composer.json');

        $this->assertCount(1, $changes);
        $this->assertSame('first-package', $changes[0]->name);
    }

    public function testParseAllAggregatesChangesFromMultipleFiles(): void
    {
        $composerChange = new DependencyChange(
            name: 'symfony/http-client',
            type: DependencyChange::TYPE_ADDED,
        );
        $packageChange = new DependencyChange(
            name: 'lodash',
            type: DependencyChange::TYPE_ADDED,
        );

        $composerParser = $this->createMock(DiffParserInterface::class);
        $composerParser->method('supports')
            ->willReturnCallback(static fn (string $f) => 'composer.json' === $f);
        $composerParser->method('parse')
            ->willReturn([$composerChange]);

        $packageParser = $this->createMock(DiffParserInterface::class);
        $packageParser->method('supports')
            ->willReturnCallback(static fn (string $f) => 'package.json' === $f);
        $packageParser->method('parse')
            ->willReturn([$packageChange]);

        $registry = new DiffParserRegistry([$composerParser, $packageParser]);

        $diffs = [
            'composer.json' => 'composer diff',
            'package.json' => 'package diff',
        ];

        $changes = $registry->parseAll($diffs);

        $this->assertCount(2, $changes);

        $names = array_map(static fn ($c) => $c->name, $changes);
        $this->assertContains('symfony/http-client', $names);
        $this->assertContains('lodash', $names);
    }

    public function testParseAllIgnoresUnsupportedFiles(): void
    {
        $composerChange = new DependencyChange(
            name: 'symfony/http-client',
            type: DependencyChange::TYPE_ADDED,
        );

        $composerParser = $this->createMock(DiffParserInterface::class);
        $composerParser->method('supports')
            ->willReturnCallback(fn (string $f) => 'composer.json' === $f);
        $composerParser->method('parse')
            ->willReturn([$composerChange]);

        $registry = new DiffParserRegistry([$composerParser]);

        $diffs = [
            'composer.json' => 'composer diff',
            'unknown.file' => 'unknown diff',
        ];

        $changes = $registry->parseAll($diffs);

        $this->assertCount(1, $changes);
        $this->assertSame('symfony/http-client', $changes[0]->name);
    }

    public function testParseAllWithEmptyDiffs(): void
    {
        $parser = $this->createMock(DiffParserInterface::class);
        $registry = new DiffParserRegistry([$parser]);

        $changes = $registry->parseAll([]);

        $this->assertCount(0, $changes);
    }
}
