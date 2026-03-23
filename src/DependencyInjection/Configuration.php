<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for SocialPostBundle.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('social_post');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('providers')
                    ->children()
                        ->arrayNode('twitter')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('consumer_key')->defaultValue('')->end()
                                ->scalarNode('consumer_secret')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                                ->scalarNode('access_token_secret')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('facebook')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('page_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                                ->scalarNode('graph_version')->defaultValue('v22.0')->end()
                            ->end()
                        ->end()
                        ->arrayNode('linkedin')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('organization_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('telegram')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('bot_token')->defaultValue('')->end()
                                ->scalarNode('channel_id')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('instagram')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('account_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                                ->scalarNode('graph_version')->defaultValue('v22.0')->end()
                            ->end()
                        ->end()
                        ->arrayNode('discord')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('webhook_url')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('whatsapp')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('phone_number_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                                ->scalarNode('api_version')->defaultValue('v22.0')->end()
                            ->end()
                        ->end()
                        ->arrayNode('threads')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('user_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                                ->scalarNode('api_version')->defaultValue('v1.0')->end()
                            ->end()
                        ->end()
                        ->arrayNode('pinterest')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('board_id')->defaultValue('')->end()
                                ->scalarNode('access_token')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
