<?php

declare(strict_types=1);

namespace ArchAnalyzer\Infrastructure\Console;

class Console
{
    public static function getTerminalWidth(): int
    {
        return (int) shell_exec("tput cols");
    }

    public static function write(string $message = '', bool $rewrite = false): void
    {
        echo $rewrite ? str_pad($message, self::getTerminalWidth()) : $message;
    }

    public static function writeln(string $message = ''): void
    {
        self::write(PHP_EOL . $message);
    }
}
