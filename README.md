# Symfony Social Post Bundle

[![Latest Stable Version](https://poser.pugx.org/janwebdev/symfony-social-post-bundle/version)](https://packagist.org/packages/janwebdev/symfony-social-post-bundle)
[![Total Downloads](https://poser.pugx.org/janwebdev/symfony-social-post-bundle/downloads)](https://packagist.org/packages/janwebdev/symfony-social-post-bundle)
[![License](https://poser.pugx.org/janwebdev/symfony-social-post-bundle/license)](https://packagist.org/packages/janwebdev/symfony-social-post-bundle)

Modern Symfony bundle for posting to multiple social networks (Twitter, Facebook, LinkedIn, Telegram) with async support.

## ✨ Features

- 🚀 **Modern Stack**: PHP 8.4+ & Symfony 7.4+
- 🌐 **Multiple Networks**: Twitter (X), Facebook, LinkedIn, Telegram, Instagram, Discord, WhatsApp, Threads
- ⚡ **Async Support**: Built-in Symfony Messenger integration
- 🎯 **Type Safe**: Full PHP 8.4 type coverage with readonly properties
- 📸 **Media Support**: Image attachments for all providers
- 🎪 **Event System**: Before/After publish events
- 🔄 **No External Dependencies**: All API clients built-in (no SDK bloat)
- 📝 **Fluent API**: MessageBuilder for easy message creation
- 🔍 **Detailed Results**: Rich result objects with post IDs and URLs
- 🛡️ **Error Handling**: Comprehensive exception handling and logging

## 📦 Installation

```bash
composer require janwebdev/symfony-social-post-bundle
```

## ⚙️ Configuration

Create `config/packages/social_post.yaml`:

```yaml
social_post:
  providers:
    twitter:
      enabled: true
      api_key: "%env(TWITTER_API_KEY)%"
      api_secret: "%env(TWITTER_API_SECRET)%"
      access_token: "%env(TWITTER_ACCESS_TOKEN)%"
      access_token_secret: "%env(TWITTER_ACCESS_TOKEN_SECRET)%"

    facebook:
      enabled: true
      page_id: "%env(FACEBOOK_PAGE_ID)%"
      access_token: "%env(FACEBOOK_ACCESS_TOKEN)%"
      graph_version: "v20.0"

    linkedin:
      enabled: true
      organization_id: "%env(LINKEDIN_ORG_ID)%"
      access_token: "%env(LINKEDIN_ACCESS_TOKEN)%"

    telegram:
      enabled: true
      bot_token: "%env(TELEGRAM_BOT_TOKEN)%"
      channel_id: "%env(TELEGRAM_CHANNEL_ID)%"

    instagram:
      enabled: true
      account_id: "%env(INSTAGRAM_ACCOUNT_ID)%"
      access_token: "%env(INSTAGRAM_ACCESS_TOKEN)%"
      graph_version: "v20.0"

    discord:
      enabled: true
      webhook_url: "%env(DISCORD_WEBHOOK_URL)%"

    whatsapp:
      enabled: true
      phone_number_id: "%env(WHATSAPP_PHONE_NUMBER_ID)%"
      access_token: "%env(WHATSAPP_ACCESS_TOKEN)%"
      api_version: "v20.0"

    threads:
      enabled: true
      user_id: "%env(THREADS_USER_ID)%"
      access_token: "%env(THREADS_ACCESS_TOKEN)%"
      api_version: "v1.0"
```

### Environment Variables

Add to your `.env`:

```env
# Twitter (X) API v2
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_access_token_secret

# Facebook Graph API
FACEBOOK_PAGE_ID=your_page_id
FACEBOOK_ACCESS_TOKEN=your_page_access_token

# LinkedIn API v2
LINKEDIN_ORG_ID=your_organization_id
LINKEDIN_ACCESS_TOKEN=your_access_token

# Telegram Bot API
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHANNEL_ID=@your_channel

# Instagram Graph API
INSTAGRAM_ACCOUNT_ID=your_instagram_business_account_id
INSTAGRAM_ACCESS_TOKEN=your_access_token

# Discord Webhooks
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_URL

# WhatsApp Channel API (BETA/UNSTABLE)
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token

# Threads API
THREADS_USER_ID=your_threads_user_id
THREADS_ACCESS_TOKEN=your_access_token
```

> ⚠️ **Note**: WhatsApp Channel integration is in BETA and may be unstable. The API is subject to change.

## 🚀 Usage

### Basic Usage

```php
use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Publisher\PublisherInterface;

class YourService
{
    public function __construct(
        private PublisherInterface $publisher
    ) {}

    public function postToSocialNetworks(): void
    {
        $message = MessageBuilder::create()
            ->setText('Hello, world! 🌍')
            ->setLink('https://example.com')
            ->build();

        $results = $this->publisher->publish($message);

        foreach ($results as $network => $result) {
            if ($result->isSuccess()) {
                echo "Posted to {$network}: {$result->getPostUrl()}\n";
            } else {
                echo "Failed to post to {$network}: {$result->getErrorMessage()}\n";
            }
        }
    }
}
```

### With Image Attachment

```php
$message = MessageBuilder::create()
    ->setText('Check out this amazing photo!')
    ->setLink('https://example.com')
    ->addImage('/path/to/image.jpg', 'Photo description')
    ->build();

$results = $this->publisher->publish($message);
```

### Publish to Specific Networks Only

```php
$message = MessageBuilder::create()
    ->setText('This goes to Twitter and Facebook only')
    ->forNetworks(['twitter', 'facebook'])
    ->build();

$results = $this->publisher->publish($message);
```

### Async Publishing

```php
// Dispatch to message queue for async processing
$this->publisher->publishAsync($message);
```

### Using Individual Providers

```php
use Janwebdev\SocialPostBundle\Provider\Twitter\TwitterProvider;

class YourService
{
    public function __construct(
        private TwitterProvider $twitterProvider
    ) {}

    public function postToTwitter(): void
    {
        $message = MessageBuilder::create()
            ->setText('Twitter-specific post')
            ->build();

        $result = $this->twitterProvider->publish($message);

        if ($result->isSuccess()) {
            echo "Tweet ID: {$result->getPostId()}\n";
            echo "Tweet URL: {$result->getPostUrl()}\n";
        }
    }
}
```

## 📚 Advanced Usage

### Event Listeners

Listen to publish events:

```php
use Janwebdev\SocialPostBundle\Event\AfterPublishEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SocialPostSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AfterPublishEvent::class => 'onAfterPublish',
        ];
    }

    public function onAfterPublish(AfterPublishEvent $event): void
    {
        $results = $event->getResults();

        // Log successful posts
        foreach ($results->getSuccessful() as $network => $result) {
            // Store post IDs in database, send notifications, etc.
        }
    }
}
```

### Custom Metadata

```php
$message = MessageBuilder::create()
    ->setText('Message with metadata')
    ->addMetadata('campaign_id', 'summer-2026')
    ->addMetadata('source', 'website')
    ->build();

// Access metadata later
$campaignId = $message->getMetadataValue('campaign_id');
```

### Working with Results

```php
$results = $this->publisher->publish($message);

// Check if all successful
if ($results->isAllSuccessful()) {
    echo "Posted to all networks!\n";
}

// Check if any successful
if ($results->hasAnySuccessful()) {
    echo "Posted to at least one network\n";
}

// Get specific result
$twitterResult = $results->getResult('twitter');
if ($twitterResult && $twitterResult->isSuccess()) {
    $postId = $twitterResult->getPostId();
    $postUrl = $twitterResult->getPostUrl();
    $metadata = $twitterResult->getMetadata();
}

// Iterate through results
foreach ($results as $network => $result) {
    echo "{$network}: ";
    echo $result->isSuccess() ? 'Success' : 'Failed';
    echo "\n";
}
```

## 🔧 API Documentation

### Twitter (X)

- **API Version**: v2
- **Authentication**: OAuth 1.0a
- **Features**: Text posts, images, auto-truncation
- **Character Limit**: 280 characters
- **Media**: Up to 4 images per tweet

### Facebook

- **API Version**: Graph API v20.0+
- **Authentication**: Page Access Token
- **Features**: Text posts, link previews, images
- **Note**: Use non-expiring page access tokens

### LinkedIn

- **API Version**: v2
- **Authentication**: OAuth 2.0 Bearer token
- **Features**: Text posts, article sharing
- **Note**: Requires organization access

### Telegram

- **API Version**: Bot API
- **Authentication**: Bot Token
- **Features**: Text messages, photos, HTML formatting
- **Note**: Works with channels and groups

### Instagram

- **API Version**: Graph API v20.0+
- **Authentication**: Access Token (Business Account)
- **Features**: Photo posts with captions, container-based publishing
- **Character Limit**: 2200 characters for captions
- **Note**: Requires Instagram Business Account linked to Facebook Page

### Discord

- **API Version**: Webhooks
- **Authentication**: Webhook URL
- **Features**: Text messages, rich embeds, image attachments
- **Note**: Very simple setup, no bot required

### WhatsApp (⚠️ BETA/UNSTABLE)

- **API Version**: Graph API v20.0+ (Channels API)
- **Authentication**: Phone Number ID + Access Token
- **Features**: Text messages, image messages
- **Note**: API is in beta, requires special access, may change without notice

### Threads

- **API Version**: Threads API v1.0
- **Authentication**: User Access Token
- **Features**: Text posts, image posts, container-based publishing
- **Character Limit**: 500 characters
- **Note**: Requires Instagram account connected to Threads, similar workflow to Instagram API

## 🧪 Testing

```bash
# Run tests
composer run-tests

# Run with coverage
composer run-tests-with-clover

# Static analysis
composer run-static-analysis

# Code style check
composer check-code-style

# Fix code style
composer fix-code-style
```

## 📖 API Credentials Setup

### Twitter (X) API v2

1. Go to [Twitter Developer Portal](https://developer.twitter.com/en/portal/dashboard)
2. Create a new App
3. Generate API Keys and Access Tokens
4. Enable OAuth 1.0a
5. Set permissions to "Read and Write"

### Facebook Graph API

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an App
3. Add Facebook Login and Pages products
4. Get a Page Access Token (make it permanent)
5. Use Graph API Explorer to test

[How to get permanent page token](https://developers.facebook.com/docs/pages/access-tokens#get-a-long-lived-page-access-token)

### LinkedIn API

1. Go to [LinkedIn Developers](https://www.linkedin.com/developers/)
2. Create an App
3. Add "Share on LinkedIn" product
4. Request access to Marketing Developer Platform
5. Generate access token with proper scopes

### Telegram Bot API

1. Talk to [@BotFather](https://t.me/botfather) on Telegram
2. Create a new bot with `/newbot`
3. Get your bot token
4. Add bot to your channel as admin
5. Use channel username (e.g., `@mychannel`) or chat ID

### Instagram Graph API

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an App (same as for Facebook)
3. Add Instagram product
4. Connect your Instagram Business Account
5. Get the Instagram Business Account ID
6. Generate access token with `instagram_basic`, `instagram_content_publish` permissions
7. **Important**: Instagram account must be a Business or Creator account

### Discord Webhooks

1. Open Discord and go to your server
2. Go to Server Settings → Integrations → Webhooks
3. Click "New Webhook"
4. Choose channel, set name and avatar
5. Copy webhook URL
6. Done! (Simplest setup ever)

### WhatsApp Channel API (⚠️ BETA)

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a WhatsApp Business App
3. Request access to Channels API (currently in beta)
4. Get Phone Number ID from WhatsApp Business Account
5. Generate access token
6. **Warning**: This API is experimental and may change

### Threads API

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an App (can be same as Instagram/Facebook)
3. Add Threads product to your app
4. Connect your Instagram account that has Threads enabled
5. Get your Threads User ID
6. Generate access token with `threads_basic`, `threads_content_publish` permissions
7. **Important**: Your Instagram account must be connected to Threads

[How to get Threads API access](https://developers.facebook.com/docs/threads/get-started)

## 🔄 Migration from v2.x

The new v3.0 uses a completely different architecture. Key changes:

### Old API (v2.x)

```php
use Janwebdev\SocialPost\Message;
use Janwebdev\SocialPost\Publisher;

$message = new Message('Hello world');
$publisher->publish($message);
```

### New API (v3.0)

```php
use Janwebdev\SocialPostBundle\Message\MessageBuilder;
use Janwebdev\SocialPostBundle\Publisher\PublisherInterface;

$message = MessageBuilder::create()
    ->setText('Hello world')
    ->build();

$results = $publisher->publish($message);
```

### Breaking Changes

- New namespace: `Janwebdev\SocialPostBundle\` instead of `Janwebdev\SocialPost\`
- No external SDK dependencies
- Different configuration structure
- Different Message API (use MessageBuilder)
- Detailed result objects instead of boolean
- Built-in async support

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 👏 Credits

- Original concept by [Martin Georgiev](https://github.com/martin-georgiev)
- Refactored and modernized by [Yan Rogozinsky](https://github.com/janwebdev)

## 🔗 Links

- [Documentation](https://github.com/janwebdev/symfony-social-post-bundle)
- [Issue Tracker](https://github.com/janwebdev/symfony-social-post-bundle/issues)
- [Packagist](https://packagist.org/packages/janwebdev/symfony-social-post-bundle)

## 📝 Version History

### 3.0.0 (2026-01-16)

- 🎉 Complete rewrite for PHP 8.4 and Symfony 7.4
- ✨ No external SDK dependencies (all built-in)
- 🚀 Twitter API v2 support
- 📊 Facebook Graph API v20+ support
- 🔗 LinkedIn API v2 support
- 💬 Telegram Bot API support
- 📸 Instagram Graph API support
- 🎮 Discord Webhooks support
- 📱 WhatsApp Channel API support (BETA)
- ⚡ Async publishing via Symfony Messenger
- 🎯 Type-safe with readonly properties
- 📦 MessageBuilder with fluent interface
- 📈 Detailed result objects
- 🎪 Event system for extensibility
- 🛡️ Comprehensive error handling
- 🧵 Threads API support
- 🌐 8 social networks total
