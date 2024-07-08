<?php

declare(strict_types=1);

namespace Tests\EndToEnd;

use App\Command\Build;
use App\Command\Run;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function ob_get_clean;
use function ob_start;

class RunTest extends TestCase
{
    private Build $build;
    private Run $run;

    public function setUp(): void
    {
        $this->build = new Build();
        $this->run = new Run();
    }

    #[Test]
    #[DataProvider('runProvider')]
    public function itWillBuildAndRunAProgramCorrectly(
        string $fixture,
        int $expectedReturnCode,
        string $expectedOutput,
    ): void {
        $build = new CommandTester($this->build);
        $buildResult = $build->execute(['file' => __DIR__ . '/../Fixtures/EndToEnd/Run/' . $fixture]);

        self::assertSame(Command::SUCCESS, $buildResult, $build->getDisplay());

        $run = new CommandTester($this->run);

        ob_start();
        try {
            $runResult = $run->execute(['file' => __DIR__ . '/../../build/program']);
        } finally {
            $output = ob_get_clean();
        }

        self::assertSame($expectedReturnCode, $runResult, $run->getDisplay());
        self::assertSame($expectedOutput, $output);
    }

    public static function runProvider(): array
    {
        return [
            ['empty.txt', 10, ""],
            ['noArgFunctionCalls.txt', 9, ""],
            ['echo.txt', 4, "2222"],
        ];
    }
}