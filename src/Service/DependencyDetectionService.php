<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\CommitHistory\Service;

use Spiriit\CommitHistory\DTO\Commit;
use Spiriit\CommitHistory\Provider\ProviderInterface;

class DependencyDetectionService
{
    /**
     * @param string[] $dependencyFiles
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly array $dependencyFiles,
        private readonly bool $trackDependencyChanges,
    ) {
    }

    /**
     * Detect dependency changes for a list of commits.
     *
     * @param Commit[] $commits
     *
     * @return Commit[]
     */
    public function detectForCommits(array $commits): array
    {
        if (!$this->trackDependencyChanges) {
            return $commits;
        }

        $result = [];
        foreach ($commits as $commit) {
            $hasDeps = $this->hasDependencyChanges($commit->id);
            $result[] = $commit->withHasDependenciesChanges($hasDeps);
        }

        return $result;
    }

    /**
     * Check if a commit has dependency changes.
     */
    public function hasDependencyChanges(string $commitId): bool
    {
        if (!$this->trackDependencyChanges) {
            return false;
        }

        return $this->checkCommitForDependencyFiles($commitId);
    }

    /**
     * Check if any of the changed files in the commit are dependency files.
     */
    private function checkCommitForDependencyFiles(string $commitId): bool
    {
        try {
            $fileNames = $this->provider->getCommitFileNames($commitId);
        } catch (\Throwable) {
            return false;
        }

        foreach ($fileNames as $fileName) {
            $baseName = basename($fileName);
            if (\in_array($baseName, $this->dependencyFiles, true)) {
                return true;
            }
        }

        return false;
    }
}
