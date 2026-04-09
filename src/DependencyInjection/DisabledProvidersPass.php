<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the social_post.provider tag from providers that have enabled: false in config.
 * This prevents disabled providers from being collected by Publisher via !tagged_iterator.
 *
 * @since 3.2.10
 * @license https://opensource.org/licenses/MIT
 */
class DisabledProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('social_post.provider') as $id => $tags) {
            foreach ($tags as $tag) {
                if (isset($tag['enabled']) && $tag['enabled'] === false) {
                    $container->getDefinition($id)->clearTag('social_post.provider');
                    break;
                }
            }
        }
    }
}
