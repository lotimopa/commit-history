# Commit History Library

A framework-agnostic PHP library for fetching commit history from GitHub and GitLab repositories, with support for detecting dependency changes.

## Features

- Fetch commit history from **GitHub** (including Enterprise) and **GitLab** (including self-hosted)
- Detect dependency changes in commits (Composer and npm)
- Year-based filtering with automatic pagination
- Support for private repositories via access tokens
- Fully typed with PHP 8.2+ readonly classes

## Requirements

- PHP 8.2 or higher

## Installation

```bash
composer require spiriitlabs/commit-history
```

## Usage

### Basic Setup with GitHub

```php
use Lotimopa\CommitHistory\Contract\HttpClientInterface;
use Lotimopa\CommitHistory\Provider\Github\GithubProvider;
use Lotimopa\CommitHistory\Provider\Github\CommitParser;
use Lotimopa\CommitHistory\Service\FeedFetcher;

// Implement the HttpClientInterface (see Contracts section below)
$httpClient = new MyHttpClient();

// Create a GitHub provider
$provider = new GithubProvider(
    httpClient: $httpClient,
    parser: new CommitParser(),
    baseUrl: 'https://api.github.com',
    owner: 'symfony',
    repo: 'symfony',
    token: 'ghp_xxxx', // optional, required for private repos
    ref: 'main',       // optional, filter by branch
);

// Create the feed fetcher
$feedFetcher = new FeedFetcher(
    provider: $provider,
    availableYearsCount: 6, // optional, defaults to 6 years
);

// Fetch commits for a specific year
$commits = $feedFetcher->fetch(2024);

foreach ($commits as $commit) {
    echo sprintf(
        "[%s] %s by %s\n",
        $commit->date->format('Y-m-d'),
        $commit->title,
        $commit->author
    );
}
```

### Using GitLab

```php
use Lotimopa\CommitHistory\Provider\Gitlab\GitlabProvider;
use Lotimopa\CommitHistory\Provider\Gitlab\CommitParser;

$provider = new GitlabProvider(
    httpClient: $httpClient,
    parser: new CommitParser(),
    baseUrl: 'https://gitlab.com',              // or your self-hosted instance
    projectId: '12345678',
    token: 'glpat-xxxx',                        // optional
    ref: 'main',                                // optional
);
```

### Detecting Dependency Changes

Enable dependency detection to flag commits that modify dependency files:

```php
use Lotimopa\CommitHistory\Service\DependencyDetectionService;

$dependencyService = new DependencyDetectionService(
    provider: $provider,
    dependencyFiles: ['composer.json', 'composer.lock', 'package.json', 'package-lock.json'],
    trackDependencyChanges: true,
);

$feedFetcher = new FeedFetcher(
    provider: $provider,
    dependencyDetectionService: $dependencyService,
);

$commits = $feedFetcher->fetch(2024);

foreach ($commits as $commit) {
    if ($commit->hasDependenciesChanges) {
        echo $commit->title . " (has dependency changes)\n";
    }
}
```

### Available Methods

```php
// Fetch commits for a specific year (defaults to current year)
$commits = $feedFetcher->fetch(2024);

// Get available years for filtering
$years = $feedFetcher->getAvailableYears(); // [2024, 2023, 2022, ...]
```

## Contracts

### HttpClientInterface

You must provide an implementation of `HttpClientInterface`:

```php
interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, list<string>>, body: string}
     */
    public function request(string $method, string $url, array $headers = []): array;
}
```

Example implementation using Guzzle:

```php
use Lotimopa\CommitHistory\Contract\HttpClientInterface;
use GuzzleHttp\Client;

class GuzzleHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function request(string $method, string $url, array $headers = []): array
    {
        $response = $this->client->request($method, $url, ['headers' => $headers]);

        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
        ];
    }
}
```

## Data Objects

### Commit

| Property | Type | Description |
|----------|------|-------------|
| `id` | `string` | Commit SHA (short) |
| `title` | `string` | Commit message (first line) |
| `date` | `DateTimeImmutable` | Commit date |
| `author` | `string` | Author name |
| `url` | `string` | URL to commit on GitHub/GitLab |
| `authorEmail` | `?string` | Author email |
| `hasDependenciesChanges` | `bool` | Whether commit modifies dependency files |

## Extending

### Adding a New Provider

1. Create a `CommitParser` implementing `CommitParserInterface`
2. Create a provider implementing `ProviderInterface`

```php
use Lotimopa\CommitHistory\Provider\ProviderInterface;
use Lotimopa\CommitHistory\Provider\CommitParserInterface;
use Lotimopa\CommitHistory\DTO\Commit;

class BitbucketCommitParser implements CommitParserInterface
{
    public function parse(array $data): Commit
    {
        // Parse Bitbucket API response into Commit DTO
    }
}

class BitbucketProvider implements ProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CommitParserInterface $parser,
        // ... other params
    ) {}

    public function getCommits(?\DateTimeImmutable $since = null, ?\DateTimeImmutable $until = null): array
    {
        // Fetch and parse commits from Bitbucket API
    }

    public function getCommitFileNames(string $commitId): array
    {
        // Return list of changed files for dependency detection
    }

    public function getCommitDiff(string $commitId): array
    {
        // Return map of filename => diff content
    }
}
```

## Symfony Integration

For Symfony projects, use the [SpiriitLabs Commit History Bundle](https://github.com/spiriitlabs/commit-history-bundle) which provides:

- Pre-configured service definitions
- Symfony HTTP client adapter
- Twig templates for rendering a timeline UI
- YAML/PHP configuration

## License

MIT License. See [LICENSE](LICENSE) for details.
