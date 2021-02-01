<?php declare(strict_types=1);

namespace PcComponentes\OpenApiMessagingContext\Tests\Messaging;

use PcComponentes\Ddd\Application\Command;

final class CommandFake extends Command
{
    protected function assertPayload(): void
    {
        //nothing
    }

    public static function messageName(): string
    {
        return 'pccomponentes.'
            . 'test.'
            . self::messageVersion() . '.'
            . self::messageType() . '.'
            . 'test_context' . '.'
            . 'test_name';
    }

    public static function messageVersion(): string
    {
        return '1';
    }
}
