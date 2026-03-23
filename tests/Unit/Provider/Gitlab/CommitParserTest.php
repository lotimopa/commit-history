<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\Provider\Gitlab;

use Lotimopa\CommitHistory\Provider\Gitlab\CommitParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommitParser::class)]
class CommitParserTest extends TestCase
{
    private CommitParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CommitParser();
    }

    public function testParseReturnsCommitWithAllFields(): void
    {
        $data = [
            'id' => 'abc123456789',
            'title' => 'Fix critical bug in parser',
            'message' => "Fix critical bug in parser\n\nThis is the extended description.",
            'created_at' => '2024-01-15T10:30:00Z',
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'web_url' => 'https://gitlab.com/org/repo/-/commit/abc123456789',
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('abc12345', $commit->id);
        $this->assertSame('Fix critical bug in parser', $commit->title);
        $this->assertSame('John Doe', $commit->author);
        $this->assertSame('john@example.com', $commit->authorEmail);
        $this->assertSame('https://gitlab.com/org/repo/-/commit/abc123456789', $commit->url);
        $this->assertSame('2024-01-15', $commit->date->format('Y-m-d'));
        $this->assertSame("Fix critical bug in parser\n\nThis is the extended description.", $commit->message);
    }

    public function testParseTruncatesIdToEightCharacters(): void
    {
        $data = [
            'id' => 'abc123456789abcdef',
            'title' => 'Test commit',
            'created_at' => '2024-01-15T10:30:00Z',
            'author_name' => 'Test',
            'web_url' => 'https://example.com',
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('abc12345', $commit->id);
    }

    public function testParseHandlesMissingAuthorEmail(): void
    {
        $data = [
            'id' => 'abc12345',
            'title' => 'Test commit',
            'created_at' => '2024-01-15T10:30:00Z',
            'author_name' => 'Test Author',
            'web_url' => 'https://example.com',
        ];

        $commit = $this->parser->parse($data);

        $this->assertNull($commit->authorEmail);
    }

    public function testParseHandlesDateWithTimezone(): void
    {
        $data = [
            'id' => 'abc12345',
            'title' => 'Test commit',
            'created_at' => '2024-01-15T10:30:00+02:00',
            'author_name' => 'Test',
            'web_url' => 'https://example.com',
        ];

        $commit = $this->parser->parse($data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $commit->date);
    }

    public function testParseDefaultsHasDependenciesChangesToFalse(): void
    {
        $data = [
            'id' => 'abc12345',
            'title' => 'Test commit',
            'created_at' => '2024-01-15T10:30:00Z',
            'author_name' => 'Test',
            'web_url' => 'https://example.com',
        ];

        $commit = $this->parser->parse($data);

        $this->assertFalse($commit->hasDependenciesChanges);
    }

    public function testParsePreservesTitleAsIs(): void
    {
        $data = [
            'id' => 'abc12345',
            'title' => 'feat(api): add new endpoint for user management',
            'created_at' => '2024-01-15T10:30:00Z',
            'author_name' => 'Test',
            'web_url' => 'https://example.com',
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('feat(api): add new endpoint for user management', $commit->title);
    }
}
