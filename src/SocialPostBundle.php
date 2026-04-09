<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle;

use Janwebdev\SocialPostBundle\DependencyInjection\DisabledProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * SocialPostBundle - Modern Symfony bundle for social media posting.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
class SocialPostBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new DisabledProvidersPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
