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
        // Twitter
        if (isset($providers['twitter']['enabled']) && $providers['twitter']['enabled']) {
            $container->setParameter('social_post.twitter.enabled', true);
            $container->setParameter('social_post.twitter.api_key', $providers['twitter']['api_key'] ?? '');
            $container->setParameter('social_post.twitter.api_secret', $providers['twitter']['api_secret'] ?? '');
            $container->setParameter('social_post.twitter.access_token', $providers['twitter']['access_token'] ?? '');
            $container->setParameter('social_post.twitter.access_token_secret', $providers['twitter']['access_token_secret'] ?? '');
        } else {
            $container->setParameter('social_post.twitter.enabled', false);
        }

        // Facebook
        if (isset($providers['facebook']['enabled']) && $providers['facebook']['enabled']) {
            $container->setParameter('social_post.facebook.enabled', true);
            $container->setParameter('social_post.facebook.page_id', $providers['facebook']['page_id'] ?? '');
            $container->setParameter('social_post.facebook.access_token', $providers['facebook']['access_token'] ?? '');
            $container->setParameter('social_post.facebook.graph_version', $providers['facebook']['graph_version'] ?? 'v20.0');
        } else {
            $container->setParameter('social_post.facebook.enabled', false);
        }

        // LinkedIn
        if (isset($providers['linkedin']['enabled']) && $providers['linkedin']['enabled']) {
            $container->setParameter('social_post.linkedin.enabled', true);
            $container->setParameter('social_post.linkedin.organization_id', $providers['linkedin']['organization_id'] ?? '');
            $container->setParameter('social_post.linkedin.access_token', $providers['linkedin']['access_token'] ?? '');
        } else {
            $container->setParameter('social_post.linkedin.enabled', false);
        }

        // Telegram
        if (isset($providers['telegram']['enabled']) && $providers['telegram']['enabled']) {
            $container->setParameter('social_post.telegram.enabled', true);
            $container->setParameter('social_post.telegram.bot_token', $providers['telegram']['bot_token'] ?? '');
            $container->setParameter('social_post.telegram.channel_id', $providers['telegram']['channel_id'] ?? '');
        } else {
            $container->setParameter('social_post.telegram.enabled', false);
        }

        // Instagram
        if (isset($providers['instagram']['enabled']) && $providers['instagram']['enabled']) {
            $container->setParameter('social_post.instagram.enabled', true);
            $container->setParameter('social_post.instagram.account_id', $providers['instagram']['account_id'] ?? '');
            $container->setParameter('social_post.instagram.access_token', $providers['instagram']['access_token'] ?? '');
            $container->setParameter('social_post.instagram.graph_version', $providers['instagram']['graph_version'] ?? 'v20.0');
        } else {
            $container->setParameter('social_post.instagram.enabled', false);
        }

        // Discord
        if (isset($providers['discord']['enabled']) && $providers['discord']['enabled']) {
            $container->setParameter('social_post.discord.enabled', true);
            $container->setParameter('social_post.discord.webhook_url', $providers['discord']['webhook_url'] ?? '');
        } else {
            $container->setParameter('social_post.discord.enabled', false);
        }

        // WhatsApp
        if (isset($providers['whatsapp']['enabled']) && $providers['whatsapp']['enabled']) {
            $container->setParameter('social_post.whatsapp.enabled', true);
            $container->setParameter('social_post.whatsapp.phone_number_id', $providers['whatsapp']['phone_number_id'] ?? '');
            $container->setParameter('social_post.whatsapp.access_token', $providers['whatsapp']['access_token'] ?? '');
            $container->setParameter('social_post.whatsapp.api_version', $providers['whatsapp']['api_version'] ?? 'v20.0');
        } else {
            $container->setParameter('social_post.whatsapp.enabled', false);
        }

        // Threads
        if (isset($providers['threads']['enabled']) && $providers['threads']['enabled']) {
            $container->setParameter('social_post.threads.enabled', true);
            $container->setParameter('social_post.threads.user_id', $providers['threads']['user_id'] ?? '');
            $container->setParameter('social_post.threads.access_token', $providers['threads']['access_token'] ?? '');
            $container->setParameter('social_post.threads.api_version', $providers['threads']['api_version'] ?? 'v1.0');
        } else {
            $container->setParameter('social_post.threads.enabled', false);
        }
    }
}
