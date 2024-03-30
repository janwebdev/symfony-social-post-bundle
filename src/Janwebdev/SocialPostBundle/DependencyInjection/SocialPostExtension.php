<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @since 1.0.0
 *
 * @license https://opensource.org/licenses/MIT
 *
 * @see https://github.com/janwebdev/symfony-social-post-bundle
 */
class SocialPostExtension extends Extension
{
    /**
     * @var array
     */
    private array $configuration;

    /**
     * @var ContainerBuilder
     */
    private ContainerBuilder $container;

    /**
     * @var YamlFileLoader
     */
    private YamlFileLoader $loader;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->container = $container;
        $this->loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/service'));
        $this->configuration = $this->processConfiguration(new Configuration(), $configs);

        $this->container->setParameter('social_post.configuration.publish_on', $this->configuration['publish_on']);

        $this->setFacebookParameters();
        $this->setTwitterParameters();

        $this->loader->load('all_in_one.yml');
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function setFacebookParameters(): void
    {
        $configuration = $this->configuration;

        if (!\in_array('facebook', $configuration['publish_on'], true)) {
            return;
        }

        if (!isset($configuration['providers']['facebook'])) {
            throw new InvalidConfigurationException('Found no configuration for the Facebook provider');
        }

        $facebookConfiguration = $configuration['providers']['facebook'];
        $this->container->setParameter('social_post.configuration.facebook', $facebookConfiguration);
        $this->container->setParameter('social_post.configuration.facebook.page_id', $facebookConfiguration['page_id']);

        $this->loader->load('facebook.yml');
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function setTwitterParameters(): void
    {
        $configuration = $this->configuration;

        if (!\in_array('twitter', $configuration['publish_on'], true)) {
            return;
        }

        if (!isset($configuration['providers']['twitter'])) {
            throw new InvalidConfigurationException('Found no configuration for the Twitter provider');
        }

        $twitterConfiguration = $configuration['providers']['twitter'];
        $twitterParameters = ['consumer_key', 'consumer_secret', 'access_token', 'access_token_secret'];
        foreach ($twitterParameters as $parameter) {
            $this->container->setParameter('social_post.configuration.twitter.'.$parameter, $twitterConfiguration[$parameter]);
        }

        $this->loader->load('twitter.yml');
    }
}
