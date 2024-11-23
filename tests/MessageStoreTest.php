<?php

declare(strict_types=1);

namespace Denshoch\MsgStack\Tests;

use PHPUnit\Framework\TestCase;
use Denshoch\MsgStack\MessageStore;
use Denshoch\MsgStack\MessageType;
use Denshoch\MsgStack\Exception\MessageException;

class MessageStoreTest extends TestCase
{
    private string $fixturesDir;
    private MessageStore $store;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
        $this->store = new MessageStore($this->fixturesDir . '/messages');
        $this->store->setContinueOnError(true);
    }

    public function testConstructorWithValidDirectory(): void
    {
        $store = new MessageStore($this->fixturesDir . '/messages');
        $this->assertInstanceOf(MessageStore::class, $store);
    }

    public function testConstructorWithInvalidDirectory(): void
    {
        $this->expectException(MessageException::class);
        new MessageStore($this->fixturesDir . '/nonexistent');
    }

    public function testSetLanguageWithValidLanguage(): void
    {
        $this->store->setLanguage('ja');
        $this->assertTrue(true); // No exception thrown
    }

    public function testAddMessageWithValidCode(): void
    {
        $this->store->addMessage(MessageType::ERROR, 'E001', ['path' => '/test']);
        $messages = $this->store->getMessages(MessageType::ERROR);
        $this->assertCount(1, $messages);
        $this->assertEquals('E001', $messages[0]['code']);
    }

    public function testAddMessageWithInvalidCode(): void
    {
        $this->expectException(MessageException::class);
        $this->store->addMessage(MessageType::ERROR, 'INVALID', []);
    }

    public function testGetMessagesWithType(): void
    {
        $this->store->addMessage(MessageType::ERROR, 'E001', []);
        $this->store->addMessage(MessageType::WARNING, 'W001', []);
        
        $errors = $this->store->getMessages(MessageType::ERROR);
        $this->assertCount(1, $errors);
        $this->assertEquals('E001', $errors[0]['code']);
    }

    public function testGetAllMessages(): void
    {
        $this->store->addMessage(MessageType::ERROR, 'E001', []);
        $this->store->addMessage(MessageType::WARNING, 'W001', []);
        
        $all = $this->store->getMessages();
        $this->assertCount(2, $all);
    }

    public function testHasMessages(): void
    {
        $this->assertFalse($this->store->hasMessages(MessageType::ERROR));
        
        $this->store->addMessage(MessageType::ERROR, 'E001', []);
        $this->assertTrue($this->store->hasMessages(MessageType::ERROR));
    }

    public function testContinueOnError(): void
    {
        $store = new MessageStore($this->fixturesDir . '/messages');
        
        // continueOnError=falseの場合は例外をスロー
        $this->expectException(MessageException::class);
        $store->addMessage(MessageType::ERROR, 'E001', []);
    }

    public function testContinueOnErrorEnabled(): void
    {
        $store = new MessageStore($this->fixturesDir . '/messages');
        $store->setContinueOnError(true);
        
        // continueOnError=trueの場合は例外をスローしない
        $store->addMessage(MessageType::ERROR, 'E001', []);
        $this->assertTrue($store->hasMessages(MessageType::ERROR));
    }

    public function testMessageParameterReplacement(): void
    {
        $this->store->addMessage(MessageType::ERROR, 'E001', ['path' => '/test/path']);
        
        $messages = $this->store->getMessages(MessageType::ERROR);
        $this->assertStringContainsString('/test/path', $messages[0]['message']);
    }

    public function testClearMessages(): void
    {
        $this->store->addMessage(MessageType::ERROR, 'E001', []);
        $this->assertTrue($this->store->hasMessages(MessageType::ERROR));
        
        $this->store->clearMessages();
        $this->assertFalse($this->store->hasMessages(MessageType::ERROR));
    }

    public function testSetLanguageFallbackToEn(): void
    {
        $store = new MessageStore($this->fixturesDir . '/messages');
        $store->setContinueOnError(true);
        
        // 存在しない言語を指定
        $store->setLanguage('invalid');
        
        // メッセージが英語で取得できることを確認
        $store->addMessage(MessageType::WARNING, 'W001', ['filename' => 'test.jpg']);
        $messages = $store->getMessages(MessageType::WARNING);
        
        // 英語のメッセージであることを確認
        $this->assertStringContainsString('Low resolution image detected', $messages[0]['message']);
    }

    public function testSetLanguageThrowsExceptionWhenNoFallback(): void
    {
        // テスト用の一時ディレクトリを作成
        $tempDir = sys_get_temp_dir() . '/msg-stack-test-' . uniqid();
        mkdir($tempDir);
        
        try {
            // ja.ymlのみを含むテストファイルを作成
            file_put_contents($tempDir . '/ja.yml', "
W001: '低解像度の画像が検出されました: {filename}'
E001: '不正なパスです: {path}'
            ");
            
            $store = new MessageStore($tempDir, 'ja');
            
            $this->expectException(MessageException::class);
            $this->expectExceptionMessage("Language not supported: invalid (fallback 'en' also not available)");
            $store->setLanguage('invalid');
        } finally {
            // テスト用ディレクトリを削除
            @unlink($tempDir . '/ja.yml');
            @rmdir($tempDir);
        }
    }
} 