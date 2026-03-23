<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\Provider\Github;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spiriit\CommitHistory\Provider\Github\CommitParser;

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
            'sha' => 'abc123456789',
            'html_url' => 'https://github.com/org/repo/commit/abc123456789',
            'commit' => [
                'message' => "Fix critical bug in parser\n\nThis is the extended description.",
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('abc12345', $commit->id);
        $this->assertSame('Fix critical bug in parser', $commit->title);
        $this->assertSame('John Doe', $commit->author);
        $this->assertSame('john@example.com', $commit->authorEmail);
        $this->assertSame('https://github.com/org/repo/commit/abc123456789', $commit->url);
        $this->assertSame('2024-01-15', $commit->date->format('Y-m-d'));
        $this->assertSame("Fix critical bug in parser\n\nThis is the extended description.", $commit->message);
    }

    public function testParseExtractsFirstLineAsTitle(): void
    {
        $data = [
            'sha' => 'abc123456789',
            'html_url' => 'https://github.com/org/repo/commit/abc123456789',
            'commit' => [
                'message' => "First line title\n\nSecond paragraph\nThird line",
                'author' => [
                    'name' => 'Jane Doe',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('First line title', $commit->title);
    }

    public function testParseTruncatesIdToEightCharacters(): void
    {
        $data = [
            'sha' => 'abc123456789abcdef',
            'html_url' => 'https://example.com',
            'commit' => [
                'message' => 'Test commit',
                'author' => [
                    'name' => 'Test',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('abc12345', $commit->id);
    }

    public function testParseHandlesMissingAuthorEmail(): void
    {
        $data = [
            'sha' => 'abc12345',
            'html_url' => 'https://example.com',
            'commit' => [
                'message' => 'Test commit',
                'author' => [
                    'name' => 'Test Author',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertNull($commit->authorEmail);
    }

    public function testParseHandlesSingleLineMessage(): void
    {
        $data = [
            'sha' => 'abc12345',
            'html_url' => 'https://example.com',
            'commit' => [
                'message' => 'Single line commit message',
                'author' => [
                    'name' => 'Test',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertSame('Single line commit message', $commit->title);
    }

    public function testParseDefaultsHasDependenciesChangesToFalse(): void
    {
        $data = [
            'sha' => 'abc12345',
            'html_url' => 'https://example.com',
            'commit' => [
                'message' => 'Test commit',
                'author' => [
                    'name' => 'Test',
                    'date' => '2024-01-15T10:30:00Z',
                ],
            ],
        ];

        $commit = $this->parser->parse($data);

        $this->assertFalse($commit->hasDependenciesChanges);
    }
}
