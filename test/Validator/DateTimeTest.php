<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use DateTime;
use IntlDateFormatter;
use Laminas\I18n\Validator\DateTime as DateTimeValidator;
use LaminasTest\I18n\TestCase;
use Locale;
use PHPUnit\Framework\Attributes\DataProvider;

use function date_default_timezone_get;
use function date_default_timezone_set;
use function sprintf;

class DateTimeTest extends TestCase
{
    private DateTimeValidator $validator;
    /** @var non-empty-string */
    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = date_default_timezone_get();

        $this->validator = new DateTimeValidator([
            'locale'   => 'en',
            'timezone' => 'Europe/Amsterdam',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        date_default_timezone_set($this->timezone);
    }

    /**
     * Ensures that the validator follows expected behavior
     *
     * @param string               $value    that will be tested
     * @param boolean              $expected expected result of assertion
     * @param array<string, mixed> $options  fed into the validator before validation
     */
    #[DataProvider('basicProvider')]
    public function testBasic(string $value, bool $expected, array $options = []): void
    {
        $this->validator->setOptions($options);

        self::assertEquals(
            $expected,
            $this->validator->isValid($value),
            sprintf('Failed expecting %s being %s', $value, $expected ? 'true' : 'false')
                . sprintf(
                    ' (locale:%s, dateType: %s, timeType: %s, pattern:%s)',
                    (string) $this->validator->getLocale(),
                    (string) $this->validator->getDateType(),
                    (string) $this->validator->getTimeType(),
                    (string) $this->validator->getPattern()
                )
        );
    }

    /** @return array<array-key, array{0: string, 1: boolean, 2: array<string, mixed>}> */
    public static function basicProvider(): array
    {
        $trueArray      = [];
        $testingDate    = new DateTime();
        $testingLocales = ['en', 'de', 'zh-TW', 'ja', 'ar', 'ru', 'si', 'ml-IN', 'hi'];
        $testingFormats = [
            IntlDateFormatter::FULL,
            IntlDateFormatter::LONG,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
        ];

        //Loop locales and formats for a more thorough set of "true" test data
        foreach ($testingLocales as $locale) {
            foreach ($testingFormats as $dateFormat) {
                foreach ($testingFormats as $timeFormat) {
                    if (($timeFormat !== IntlDateFormatter::NONE) || ($dateFormat !== IntlDateFormatter::NONE)) {
                        $formatter = IntlDateFormatter::create($locale, $dateFormat, $timeFormat);
                        self::assertNotNull($formatter);
                        $trueArray[] = [
                            $formatter->format($testingDate),
                            true,
                            ['locale' => $locale, 'dateType' => $dateFormat, 'timeType' => $timeFormat],
                        ];
                    }
                }
            }
        }

        $falseArray = [
            [
                'May 38, 2013',
                false,
                [
                    'locale'   => 'en',
                    'dateType' => IntlDateFormatter::FULL,
                    'timeType' => IntlDateFormatter::NONE,
                ],
            ],
        ];

        return [...$trueArray, ...$falseArray];
    }

    /**
     * Ensures that getMessages() returns expected default value
     */
    public function testGetMessages(): void
    {
        self::assertEquals([], $this->validator->getMessages());
    }

    /**
     * Ensures that set/getLocale() works
     */
    public function testOptionLocale(): void
    {
        $this->validator->setLocale('de');
        self::assertEquals('de', $this->validator->getLocale());
    }

    public function testApplicationOptionLocale(): void
    {
        Locale::setDefault('nl');
        $valid = new DateTimeValidator();
        self::assertEquals(Locale::getDefault(), $valid->getLocale());
    }

    /**
     * Ensures that set/getTimezone() works
     */
    public function testOptionTimezone(): void
    {
        $this->validator->setLocale('Europe/Berlin');
        self::assertEquals('Europe/Berlin', $this->validator->getLocale());
    }

    public function testApplicationOptionTimezone(): void
    {
        date_default_timezone_set('Europe/Berlin');
        $valid = new DateTimeValidator();
        self::assertEquals(date_default_timezone_get(), $valid->getTimezone());
    }

    /**
     * Ensures that an omitted pattern results in a calculated pattern by IntlDateFormatter
     */
    public function testOptionPatternOmitted(): void
    {
        // null before validation
        self::assertNull($this->validator->getPattern());

        $this->validator->isValid('does not matter');

        // set after
        self::assertEquals('yyyyMMdd hh:mm a', $this->validator->getPattern());
    }

    public function testSettingThePatternToNullIsAcceptable(): void
    {
        $this->validator->setPattern(null);
        self::assertTrue($this->validator->isValid('20200101 12:34 am'));
    }

    public function testSettingThePatternToAnEmptyStringIsAcceptable(): void
    {
        $this->validator->setPattern('');
        self::assertTrue($this->validator->isValid('20200101 12:34 am'));
    }

    /**
     * Ensures that setting the pattern results in pattern used (by the validation process)
     */
    public function testOptionPattern(): void
    {
        $this->validator->setOptions(['pattern' => 'hh:mm']);

        self::assertTrue($this->validator->isValid('02:00'));
        self::assertEquals('hh:mm', $this->validator->getPattern());
    }

    public function testMultipleIsValidCalls(): void
    {
        $formatter = IntlDateFormatter::create('en', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
        self::assertNotNull($formatter);
        $validValue = $formatter->format(new DateTime());
        $this->validator
            ->setLocale('en')
            ->setDateType(IntlDateFormatter::FULL)
            ->setTimeType(IntlDateFormatter::FULL);

        self::assertTrue($this->validator->isValid($validValue));
        self::assertFalse($this->validator->isValid('12/31/2015'));
        self::assertFalse($this->validator->isValid('23:59:59'));
        self::assertFalse($this->validator->isValid('does not matter'));
        self::assertTrue($this->validator->isValid($validValue));
    }
}
