<?php

declare(strict_types=1);

namespace Janwebdev\SocialPostBundle\Tests\Integration\DependencyInjection;

use Janwebdev\SocialPostBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \Janwebdev\SocialPostBundle\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function testTwitterConfiguration(): void
    {
        $config = [
            'providers' => [
                'twitter' => [
                    'enabled' => true,
                    'api_key' => 'test_key',
                    'api_secret' => 'test_secret',
                    'access_token' => 'test_token',
                    'access_token_secret' => 'test_token_secret',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['twitter']['enabled']);
        $this->assertEquals('test_key', $processedConfig['providers']['twitter']['api_key']);
    }

    public function testFacebookConfiguration(): void
    {
        $config = [
            'providers' => [
                'facebook' => [
                    'enabled' => true,
                    'page_id' => '123456',
                    'access_token' => 'test_token',
                    'graph_version' => 'v20.0',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['facebook']['enabled']);
        $this->assertEquals('123456', $processedConfig['providers']['facebook']['page_id']);
        $this->assertEquals('v22.0', $processedConfig['providers']['facebook']['graph_version']);
    }

    public function testInstagramConfiguration(): void
    {
        $config = [
            'providers' => [
                'instagram' => [
                    'enabled' => true,
                    'account_id' => 'ig_account_123',
                    'access_token' => 'test_token',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['instagram']['enabled']);
        $this->assertEquals('ig_account_123', $processedConfig['providers']['instagram']['account_id']);
    }

    public function testDiscordConfiguration(): void
    {
        $config = [
            'providers' => [
                'discord' => [
                    'enabled' => true,
                    'webhook_url' => 'https://discord.com/api/webhooks/123/abc',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['discord']['enabled']);
        $this->assertStringContainsString('discord.com', $processedConfig['providers']['discord']['webhook_url']);
    }

    public function testWhatsAppConfiguration(): void
    {
        $config = [
            'providers' => [
                'whatsapp' => [
                    'enabled' => true,
                    'phone_number_id' => '123456',
                    'access_token' => 'test_token',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['whatsapp']['enabled']);
        $this->assertEquals('123456', $processedConfig['providers']['whatsapp']['phone_number_id']);
    }

    public function testAllProvidersConfiguration(): void
    {
        $config = [
            'providers' => [
                'twitter' => ['enabled' => true, 'api_key' => 'key1', 'api_secret' => 's1', 'access_token' => 't1', 'access_token_secret' => 'ts1'],
                'facebook' => ['enabled' => true, 'page_id' => 'p1', 'access_token' => 't1'],
                'linkedin' => ['enabled' => true, 'organization_id' => 'org1', 'access_token' => 't1'],
                'telegram' => ['enabled' => true, 'bot_token' => 'bot1', 'channel_id' => 'ch1'],
                'instagram' => ['enabled' => true, 'account_id' => 'acc1', 'access_token' => 't1'],
                'discord' => ['enabled' => true, 'webhook_url' => 'https://discord.com/api/webhooks/1/a'],
                'whatsapp' => ['enabled' => true, 'phone_number_id' => 'ph1', 'access_token' => 't1'],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        // Verify all 7 providers are configured
        $this->assertTrue($processedConfig['providers']['twitter']['enabled']);
        $this->assertTrue($processedConfig['providers']['facebook']['enabled']);
        $this->assertTrue($processedConfig['providers']['linkedin']['enabled']);
        $this->assertTrue($processedConfig['providers']['telegram']['enabled']);
        $this->assertTrue($processedConfig['providers']['instagram']['enabled']);
        $this->assertTrue($processedConfig['providers']['discord']['enabled']);
        $this->assertTrue($processedConfig['providers']['whatsapp']['enabled']);
    }

    public function testThreadsConfiguration(): void
    {
        $config = [
            'providers' => [
                'threads' => [
                    'enabled' => true,
                    'user_id' => '123456',
                    'access_token' => 'test_token',
                    'api_version' => 'v1.0',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertTrue($processedConfig['providers']['threads']['enabled']);
        $this->assertEquals('123456', $processedConfig['providers']['threads']['user_id']);
        $this->assertEquals('v1.0', $processedConfig['providers']['threads']['api_version']);
    }

    public function testDefaultValues(): void
    {
        $config = ['providers' => []];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);

        // All providers should have default enabled=false
        $this->assertFalse($processedConfig['providers']['twitter']['enabled']);
        $this->assertFalse($processedConfig['providers']['facebook']['enabled']);
        $this->assertFalse($processedConfig['providers']['threads']['enabled']);
        $this->assertEquals('v22.0', $processedConfig['providers']['facebook']['graph_version']);
        $this->assertEquals('v1.0', $processedConfig['providers']['threads']['api_version']);
    }
}
