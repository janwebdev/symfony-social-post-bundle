<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * SocialPostBundle - Modern Symfony bundle for social media posting.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
class SocialPostBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
