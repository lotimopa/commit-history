<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Provider\Gitlab;

use Lotimopa\CommitHistory\Contract\HttpClientInterface;
use Lotimopa\CommitHistory\DTO\Commit;
use Lotimopa\CommitHistory\Provider\CommitParserInterface;
use Lotimopa\CommitHistory\Provider\ProviderInterface;

class GitlabProvider implements ProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CommitParserInterface $parser,
        private readonly string $baseUrl,
        private readonly string $projectId,
        private readonly ?string $token = null,
        private readonly ?string $ref = null,
    ) {
    }

    /**
     * @return Commit[]
     */
    public function getCommits(?\DateTimeImmutable $since = null, ?\DateTimeImmutable $until = null): array
    {
        $commits = [];
        $page = 1;
        $perPage = 100;

        do {
            $queryParams = [
                'page' => (string) $page,
                'per_page' => (string) $perPage,
            ];

            if (null !== $this->ref) {
                $queryParams['ref_name'] = $this->ref;
            }

            if (null !== $since) {
                $queryParams['since'] = $since->format('c');
            }

            if (null !== $until) {
                $queryParams['until'] = $until->format('c');
            }

            $url = $this->buildUrl('/api/v4/projects/'.urlencode($this->projectId).'/repository/commits', $queryParams);

            $response = $this->httpClient->request('GET', $url, $this->getHeaders());
            $data = json_decode($response['body'], true);

            if (empty($data) || !\is_array($data)) {
                break;
            }

            foreach ($data as $item) {
                $commits[] = $this->parser->parse($item);
            }

            ++$page;
        } while (\count($data) === $perPage);

        return $commits;
    }

    /**
     * @return string[]
     */
    public function getCommitFileNames(string $commitId): array
    {
        $diffs = $this->fetchCommitDiff($commitId);

        $files = [];
        foreach ($diffs as $diff) {
            if (!empty($diff['new_path'])) {
                $files[] = $diff['new_path'];
            } elseif (!empty($diff['old_path'])) {
                $files[] = $diff['old_path'];
            }
        }

        return array_unique($files);
    }

    /**
     * @return array<string, string>
     */
    public function getCommitDiff(string $commitId): array
    {
        $diffs = $this->fetchCommitDiff($commitId);

        $result = [];
        foreach ($diffs as $diff) {
            $filename = $diff['new_path'] ?? $diff['old_path'] ?? '';
            if (!empty($filename) && isset($diff['diff'])) {
                $result[$filename] = $diff['diff'];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCommitDiff(string $commitId): array
    {
        $url = $this->buildUrl('/api/v4/projects/'.urlencode($this->projectId).'/repository/commits/'.$commitId.'/diff');

        $response = $this->httpClient->request('GET', $url, $this->getHeaders());

        return json_decode($response['body'], true) ?? [];
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function buildUrl(string $path, array $queryParams = []): string
    {
        $url = rtrim($this->baseUrl, '/').$path;

        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        $headers = [];

        if (null !== $this->token) {
            $headers['PRIVATE-TOKEN'] = $this->token;
        }

        return $headers;
    }
}
