<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension for SocialPostBundle.
 *
 * @since 3.0.0
 * @license https://opensource.org/licenses/MIT
 */
class SocialPostExtension extends Extension
{
    /**
     * Default parameter values for each provider.
     * All parameters must always be set (even when disabled) for services.yaml compilation.
     *
     * @var array<string, array<string, string>>
     */
    private const PROVIDER_DEFAULTS = [
        'twitter' => [
            'consumer_key' => '',
            'consumer_secret' => '',
            'access_token' => '',
            'access_token_secret' => '',
        ],
        'facebook' => [
            'page_id' => '',
            'access_token' => '',
            'graph_version' => 'v22.0',
        ],
        'linkedin' => [
            'organization_id' => '',
            'access_token' => '',
        ],
        'telegram' => [
            'bot_token' => '',
            'channel_id' => '',
        ],
        'instagram' => [
            'account_id' => '',
            'access_token' => '',
            'graph_version' => 'v22.0',
        ],
        'discord' => [
            'webhook_url' => '',
        ],
        'whatsapp' => [
            'phone_number_id' => '',
            'access_token' => '',
            'api_version' => 'v22.0',
        ],
        'threads' => [
            'user_id' => '',
            'access_token' => '',
            'api_version' => 'v1.0',
        ],
        'pinterest' => [
            'board_id' => '',
            'access_token' => '',
        ],
        'hackernews' => [
            'username' => '',
            'password' => '',
        ],
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');

        // Set parameters for each provider
        $this->configureProviders($container, $config['providers'] ?? []);
    }

    private function configureProviders(ContainerBuilder $container, array $providers): void
    {
        foreach (self::PROVIDER_DEFAULTS as $network => $defaults) {
            $networkConfig = $providers[$network] ?? [];
            $enabled = $networkConfig['enabled'] ?? false;

            $container->setParameter("social_post.{$network}.enabled", $enabled);

            // Always set all parameters — required even when disabled for services.yaml compilation
            foreach ($defaults as $param => $default) {
                $container->setParameter(
                    "social_post.{$network}.{$param}",
                    $networkConfig[$param] ?? $default,
                );
            }
        }
    }
}
