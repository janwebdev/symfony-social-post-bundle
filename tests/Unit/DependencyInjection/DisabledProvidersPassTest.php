<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Unit\DependencyInjection;

use Janwebdev\SocialPostBundle\DependencyInjection\DisabledProvidersPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \Janwebdev\SocialPostBundle\DependencyInjection\DisabledProvidersPass
 */
class DisabledProvidersPassTest extends TestCase
{
    private ContainerBuilder $container;
    private DisabledProvidersPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new DisabledProvidersPass();
    }

    public function testEnabledProviderKeepsTag(): void
    {
        $definition = new Definition(\stdClass::class);
        $definition->addTag('social_post.provider', ['enabled' => true]);
        $this->container->setDefinition('provider.enabled', $definition);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->getDefinition('provider.enabled')->hasTag('social_post.provider'));
    }

    public function testDisabledProviderLosesTag(): void
    {
        $definition = new Definition(\stdClass::class);
        $definition->addTag('social_post.provider', ['enabled' => false]);
        $this->container->setDefinition('provider.disabled', $definition);

        $this->pass->process($this->container);

        $this->assertFalse($this->container->getDefinition('provider.disabled')->hasTag('social_post.provider'));
    }

    public function testProviderWithoutEnabledAttributeKeepsTag(): void
    {
        $definition = new Definition(\stdClass::class);
        $definition->addTag('social_post.provider');
        $this->container->setDefinition('provider.no_attr', $definition);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->getDefinition('provider.no_attr')->hasTag('social_post.provider'));
    }

    public function testDisabledProviderServiceStillExistsInContainer(): void
    {
        $definition = new Definition(\stdClass::class);
        $definition->addTag('social_post.provider', ['enabled' => false]);
        $this->container->setDefinition('provider.disabled', $definition);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->hasDefinition('provider.disabled'));
    }

    public function testMixedProviders(): void
    {
        $enabled = new Definition(\stdClass::class);
        $enabled->addTag('social_post.provider', ['enabled' => true]);
        $this->container->setDefinition('provider.a', $enabled);

        $disabled = new Definition(\stdClass::class);
        $disabled->addTag('social_post.provider', ['enabled' => false]);
        $this->container->setDefinition('provider.b', $disabled);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->getDefinition('provider.a')->hasTag('social_post.provider'));
        $this->assertFalse($this->container->getDefinition('provider.b')->hasTag('social_post.provider'));
    }
}
