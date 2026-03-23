<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Lotimopa\CommitHistory\DTO\DependencyChange;

#[CoversClass(DependencyChange::class)]
class DependencyChangeTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $change = new DependencyChange(
            name: 'symfony/http-client',
            type: DependencyChange::TYPE_UPDATED,
            oldVersion: '^6.4',
            newVersion: '^7.0',
            sourceFile: 'composer.json',
        );

        $this->assertSame('symfony/http-client', $change->name);
        $this->assertSame(DependencyChange::TYPE_UPDATED, $change->type);
        $this->assertSame('^6.4', $change->oldVersion);
        $this->assertSame('^7.0', $change->newVersion);
        $this->assertSame('composer.json', $change->sourceFile);
    }

    public function testDefaultValues(): void
    {
        $change = new DependencyChange(
            name: 'lodash',
            type: DependencyChange::TYPE_ADDED,
        );

        $this->assertNull($change->oldVersion);
        $this->assertNull($change->newVersion);
        $this->assertNull($change->sourceFile);
    }

    public function testTypeConstants(): void
    {
        $this->assertSame('added', DependencyChange::TYPE_ADDED);
        $this->assertSame('updated', DependencyChange::TYPE_UPDATED);
        $this->assertSame('removed', DependencyChange::TYPE_REMOVED);
    }

    public function testAddedDependency(): void
    {
        $change = new DependencyChange(
            name: 'new-package/lib',
            type: DependencyChange::TYPE_ADDED,
            newVersion: '^1.0',
            sourceFile: 'composer.json',
        );

        $this->assertSame(DependencyChange::TYPE_ADDED, $change->type);
        $this->assertNull($change->oldVersion);
        $this->assertSame('^1.0', $change->newVersion);
    }

    public function testRemovedDependency(): void
    {
        $change = new DependencyChange(
            name: 'old-package/lib',
            type: DependencyChange::TYPE_REMOVED,
            oldVersion: '^2.0',
            sourceFile: 'composer.json',
        );

        $this->assertSame(DependencyChange::TYPE_REMOVED, $change->type);
        $this->assertSame('^2.0', $change->oldVersion);
        $this->assertNull($change->newVersion);
    }
}
