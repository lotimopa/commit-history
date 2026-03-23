<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Service;

use Spiriit\CommitHistory\DTO\Commit;
use Spiriit\CommitHistory\Provider\ProviderInterface;

class FeedFetcher implements FeedFetcherInterface
{
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly int $availableYearsCount = 6,
        private readonly ?DependencyDetectionService $dependencyDetectionService = null,
    ) {
    }

    /**
     * @return Commit[]
     */
    public function fetch(?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        [$since, $until] = $this->getYearDateRange($year);

        $commits = $this->provider->getCommits($since, $until);

        // Detect dependency changes for each commit
        if (null !== $this->dependencyDetectionService) {
            $commits = $this->dependencyDetectionService->detectForCommits($commits);
        }

        return $commits;
    }

    /**
     * @return int[]
     */
    public function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        $years = [];

        for ($i = 0; $i < $this->availableYearsCount; ++$i) {
            $years[] = $currentYear - $i;
        }

        return $years;
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function getYearDateRange(int $year): array
    {
        $since = new \DateTimeImmutable(\sprintf('%d-01-01T00:00:00+00:00', $year));
        $until = new \DateTimeImmutable(\sprintf('%d-12-31T23:59:59+00:00', $year));

        return [$since, $until];
    }
}
