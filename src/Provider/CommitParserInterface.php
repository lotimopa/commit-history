<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/commit-history package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lotimopa\CommitHistory\Provider;

use Spiriit\CommitHistory\DTO\Commit;

interface CommitParserInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function parse(array $data): Commit;
}
