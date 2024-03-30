<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle;

use Janwebdev\SocialPostBundle\DependencyInjection\Compiler\AllInOnePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle as BaseBundle;

/**
 * @since 1.0.0
 *
 * @license https://opensource.org/licenses/MIT
 *
 * @see https://github.com/janwebdev/symfony-social-post-bundle
 */
class SocialPostBundle extends BaseBundle
{
    public function build(ContainerBuilder $containerBuilder): void
    {
        parent::build($containerBuilder);

        $containerBuilder->addCompilerPass(new AllInOnePass());
    }
}
