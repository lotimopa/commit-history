<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\DTO;

use Lotimopa\CommitHistory\DTO\Commit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Commit::class)]
class CommitTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $date = new \DateTimeImmutable('2024-01-15 10:30:00');

        $commit = new Commit(
            id: 'abc12345',
            title: 'Fix bug in parser',
            date: $date,
            author: 'John Doe',
            url: 'https://github.com/org/repo/commit/abc12345',
            authorEmail: 'john@example.com',
            hasDependenciesChanges: true,
            message: "Fix bug in parser\n\nExtended description.",
        );

        $this->assertSame('abc12345', $commit->id);
        $this->assertSame('Fix bug in parser', $commit->title);
        $this->assertSame($date, $commit->date);
        $this->assertSame('John Doe', $commit->author);
        $this->assertSame('https://github.com/org/repo/commit/abc12345', $commit->url);
        $this->assertSame('john@example.com', $commit->authorEmail);
        $this->assertTrue($commit->hasDependenciesChanges);
        $this->assertSame("Fix bug in parser\n\nExtended description.", $commit->message);
    }

    public function testDefaultValues(): void
    {
        $commit = new Commit(
            id: 'abc12345',
            title: 'Some commit',
            date: new \DateTimeImmutable(),
            author: 'Jane Doe',
            url: 'https://example.com',
        );

        $this->assertNull($commit->authorEmail);
        $this->assertFalse($commit->hasDependenciesChanges);
        $this->assertNull($commit->message);
    }

    public function testWithHasDependenciesChangesReturnsNewInstance(): void
    {
        $date = new \DateTimeImmutable();
        $commit = new Commit(
            id: 'abc12345',
            title: 'Some commit',
            date: $date,
            author: 'Jane Doe',
            url: 'https://example.com',
            authorEmail: 'jane@example.com',
            hasDependenciesChanges: false,
            message: "Some commit\n\nDetails.",
        );

        $newCommit = $commit->withHasDependenciesChanges(true);

        $this->assertNotSame($commit, $newCommit);
        $this->assertFalse($commit->hasDependenciesChanges);
        $this->assertTrue($newCommit->hasDependenciesChanges);
        $this->assertSame($commit->id, $newCommit->id);
        $this->assertSame($commit->title, $newCommit->title);
        $this->assertSame($commit->date, $newCommit->date);
        $this->assertSame($commit->author, $newCommit->author);
        $this->assertSame($commit->url, $newCommit->url);
        $this->assertSame($commit->authorEmail, $newCommit->authorEmail);
        $this->assertSame($commit->message, $newCommit->message);
    }
}
