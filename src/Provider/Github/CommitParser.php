<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Provider\Github;

use Lotimopa\CommitHistory\DTO\Commit;
use Lotimopa\CommitHistory\Provider\CommitParserInterface;

class CommitParser implements CommitParserInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function parse(array $data): Commit
    {
        $commitData = $data['commit'] ?? [];
        $authorData = $commitData['author'] ?? [];

        $message = (string) ($commitData['message'] ?? '');

        return new Commit(
            id: substr((string) $data['sha'], 0, 8),
            title: $this->extractTitle($message),
            date: new \DateTimeImmutable((string) ($authorData['date'] ?? 'now')),
            author: (string) ($authorData['name'] ?? ''),
            url: (string) ($data['html_url'] ?? ''),
            authorEmail: $authorData['email'] ?? null,
            message: '' !== $message ? $message : null,
        );
    }

    private function extractTitle(string $message): string
    {
        $lines = explode("\n", $message);

        return trim($lines[0]);
    }
}
