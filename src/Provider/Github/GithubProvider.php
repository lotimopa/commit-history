<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\CommitHistory\Provider\Github;

use Spiriit\CommitHistory\Contract\HttpClientInterface;
use Spiriit\CommitHistory\DTO\Commit;
use Spiriit\CommitHistory\Provider\CommitParserInterface;
use Spiriit\CommitHistory\Provider\ProviderInterface;

class GithubProvider implements ProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CommitParserInterface $parser,
        private readonly string $baseUrl,
        private readonly string $owner,
        private readonly string $repo,
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
        $params = ['per_page' => '100'];

        if (null !== $this->ref) {
            $params['sha'] = $this->ref;
        }

        if (null !== $since) {
            $params['since'] = $since->format('c');
        }

        if (null !== $until) {
            $params['until'] = $until->format('c');
        }

        $url = $this->buildUrl('/repos/'.$this->owner.'/'.$this->repo.'/commits', $params);

        do {
            $response = $this->httpClient->request('GET', $url, $this->getHeaders());
            $data = json_decode($response['body'], true);

            if (!\is_array($data)) {
                break;
            }

            foreach ($data as $item) {
                $commits[] = $this->parser->parse($item);
            }

            // Parse Link header for next page
            $linkHeader = $response['headers']['link'][0] ?? '';
            $url = $this->extractNextUrl($linkHeader);
        } while (null !== $url && !empty($data));

        return $commits;
    }

    private function extractNextUrl(string $linkHeader): ?string
    {
        if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getCommitFileNames(string $commitId): array
    {
        $data = $this->fetchCommitDetails($commitId);

        $files = [];
        foreach ($data['files'] ?? [] as $file) {
            if (!empty($file['filename'])) {
                $files[] = $file['filename'];
            }
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public function getCommitDiff(string $commitId): array
    {
        $data = $this->fetchCommitDetails($commitId);

        $result = [];
        foreach ($data['files'] ?? [] as $file) {
            $filename = $file['filename'] ?? '';
            if (!empty($filename) && isset($file['patch'])) {
                $result[$filename] = $file['patch'];
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCommitDetails(string $commitId): array
    {
        $url = $this->buildUrl('/repos/'.$this->owner.'/'.$this->repo.'/commits/'.$commitId);

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
        $headers = [
            'Accept' => 'application/vnd.github+json',
        ];

        if (null !== $this->token) {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return $headers;
    }
}
