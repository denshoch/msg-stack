<?php

declare(strict_types=1);

namespace Denshoch\MsgStack;

use Symfony\Component\Yaml\Yaml;
use Denshoch\MsgStack\Exception\MessageException;

class MessageStore
{
    /** @var array<string, array<string, string>> */
    private array $messages = [];

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $messageLog = [];

    private string $language = 'en';

    private bool $continueOnError = false;

    /**
     * @param string $messagesDir Directory containing message YAML files
     * @param string $language Default language code (e.g. 'en', 'ja')
     */
    public function __construct(string $messagesDir, string $language = 'en')
    {
        $this->loadMessages($messagesDir);
        $this->setLanguage($language);
    }

    /**
     * Load message definitions from YAML files
     * @throws MessageException When directory not found or invalid message file
     */
    private function loadMessages(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new MessageException("Messages directory not found: $dir");
        }

        $files = array_merge(
            glob("$dir/*.yml") ?: [],
            glob("$dir/*.yaml") ?: []
        );

        if (empty($files)) {
            throw new MessageException("No message files found in: $dir");
        }

        foreach ($files as $file) {
            $lang = preg_replace('/\.ya?ml$/', '', basename($file));
            $messages = Yaml::parseFile($file);
            if (!is_array($messages)) {
                throw new MessageException("Invalid message file: $file");
            }
            $this->messages[$lang] = $messages;
        }
    }

    /**
     * Set current language
     * @throws MessageException When language is not supported
     */
    public function setLanguage(string $language): void
    {
        if (!isset($this->messages[$language])) {
            throw new MessageException("Language not supported: $language");
        }
        $this->language = $language;
    }

    /**
     * Set continue on error flag
     */
    public function setContinueOnError(bool $continue): void
    {
        $this->continueOnError = $continue;
    }

    /**
     * Add a message to the log
     * @param MessageType $type Message type
     * @param string $code Message code
     * @param array<string, string|int> $params Parameters to replace in message
     * @throws MessageException When message code not found or when error occurs with continueOnError=false
     */
    public function addMessage(MessageType $type, string $code, array $params = []): void
    {
        if (!isset($this->messages[$this->language][$code])) {
            throw new MessageException("Message code not found: $code");
        }

        $message = $this->messages[$this->language][$code];
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", (string)$value, $message);
        }

        $this->messageLog[$type->value][] = [
            'code' => $code,
            'message' => $message,
            'params' => $params,
            'timestamp' => time()
        ];

        if (!$this->continueOnError) {
            throw new MessageException($message);
        }
    }

    /**
     * Get logged messages
     * @param MessageType|null $type Filter by message type
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(?MessageType $type = null): array
    {
        if ($type === null) {
            return empty($this->messageLog) ? [] : array_merge(...array_values($this->messageLog));
        }
        return $this->messageLog[$type->value] ?? [];
    }

    /**
     * Check if messages of given type exist
     */
    public function hasMessages(MessageType $type): bool
    {
        return !empty($this->messageLog[$type->value]);
    }

    /**
     * Clear all messages
     */
    public function clearMessages(): void
    {
        $this->messageLog = [];
    }
}
