# msg-stack

A message stack system that accumulates messages while continuing process.

## Features

- Stack messages with different severity levels (ERROR, WARNING, INFO, SUCCESS)
- Continue processing even after errors (configurable)
- Multi-language support via YAML message definitions
- Type-safe implementation using PHP 8.1 enums
- Simple and intuitive API

## Installation 

=== 
composer require denshoch/msg-stack
===

## Basic Usage

=== php
use Denshoch\MsgStack\MessageStore;
use Denshoch\MsgStack\MessageType;

// Initialize with message directory and language
$messageStore = new MessageStore(__DIR__ . '/messages', 'ja');

// Configure error handling
$messageStore->setContinueOnError(true);

try {
    // Add messages of different types
    $messageStore->addMessage(
        MessageType::WARNING,
        'W001',
        ['filename' => 'example.jpg']
    );

    // Process continues...
    $messageStore->addMessage(
        MessageType::ERROR,
        'E001',
        ['path' => '/invalid/path']
    );

} catch (MessageException $e) {
    // Handle errors when continueOnError is false
} finally {
    // Get all accumulated messages
    $allMessages = $messageStore->getMessages();
    
    // Or get messages by type
    $warnings = $messageStore->getMessages(MessageType::WARNING);
    
    // Check for specific message types
    if ($messageStore->hasMessages(MessageType::ERROR)) {
        // Handle errors...
    }
}
===

## Message Definition

Create YAML files in your messages directory:

=== yaml
# messages/en.yml
W001: 'Low resolution image detected: {filename}'
E001: 'Invalid path: {path}'

# messages/ja.yml
W001: '低解像度の画像が検出されました: {filename}'
E001: '不正なパスです: {path}'
===

## API Reference

### MessageStore

- `__construct(string $messagesDir, string $language = 'en')`
- `setLanguage(string $language): void`
- `setContinueOnError(bool $continue): void`
- `addMessage(MessageType $type, string $code, array $params = []): void`
- `getMessages(?MessageType $type = null): array`
- `hasMessages(MessageType $type): bool`
- `clearMessages(): void`

### MessageType

=== php
enum MessageType: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SUCCESS = 'success';
}
===

## Requirements

- PHP 8.1 or later
- symfony/yaml ^6.0

## License

MIT License

## Author

Densho Channel <https://denshochan.com/>