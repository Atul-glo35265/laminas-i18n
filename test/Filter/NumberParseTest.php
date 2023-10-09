<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Filter;

use Laminas\I18n\Filter\NumberParse as NumberParseFilter;
use LaminasTest\I18n\TestCase;
use NumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class NumberParseTest extends TestCase
{
    public function testConstructWithOptions(): void
    {
        $filter = new NumberParseFilter([
            'locale' => 'en_US',
            'style'  => NumberFormatter::DECIMAL,
        ]);

        self::assertEquals('en_US', $filter->getLocale());
        self::assertEquals(NumberFormatter::DECIMAL, $filter->getStyle());
    }

    public function testConstructWithParameters(): void
    {
        $filter = new NumberParseFilter('en_US', NumberFormatter::DECIMAL);

        self::assertEquals('en_US', $filter->getLocale());
        self::assertEquals(NumberFormatter::DECIMAL, $filter->getStyle());
    }

    /**
     * @param NumberFormatter::TYPE_* $type
     */
    #[DataProvider('formattedToNumberProvider')]
    public function testFormattedToNumber(string $locale, int $style, int $type, string $value, float $expected): void
    {
        $filter = new NumberParseFilter($locale, $style, $type);
        self::assertSame($expected, $filter->filter($value));
    }

    /** @return array<array-key, array{0: string, 1: int, 2: NumberFormatter::TYPE_*, 3: string, 4:float}> */
    public static function formattedToNumberProvider(): array
    {
        return [
            [
                'en_US',
                NumberFormatter::DEFAULT_STYLE,
                NumberFormatter::TYPE_DOUBLE,
                '1,234,567.891',
                1234567.891,
            ],
            [
                'de_DE',
                NumberFormatter::DEFAULT_STYLE,
                NumberFormatter::TYPE_DOUBLE,
                '1.234.567,891',
                1234567.891,
            ],
            [
                'ru_RU',
                NumberFormatter::DEFAULT_STYLE,
                NumberFormatter::TYPE_DOUBLE,
                '1 234 567,891',
                1234567.891,
            ],
        ];
    }

    #[DataProvider('formatNonNumberProvider')]
    public function testFormattedWithNonNumbers(
        mixed $value,
        mixed $expected
    ): void {
        $filter = new NumberParseFilter('en_US', NumberFormatter::DEFAULT_STYLE, NumberFormatter::TYPE_DOUBLE);
        self::assertEquals($expected, $filter->filter($value));
    }

    /** @return array<array-key, array{0: mixed, 1: mixed}> */
    public static function formatNonNumberProvider(): array
    {
        return [
            [
                null,
                null,
            ],
            [
                [],
                [],
            ],
            [
                new stdClass(),
                new stdClass(),
            ],
            [
                false,
                false,
            ],
        ];
    }

    public function testTheNumberFormatterCanBeManuallyInjected(): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::DEFAULT_STYLE);
        $filter    = new NumberParseFilter();

        self::assertNotSame($formatter, $filter->getFormatter());

        $filter->setFormatter($formatter);

        self::assertSame($formatter, $filter->getFormatter());
    }
}
