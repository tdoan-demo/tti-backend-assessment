<?php

namespace Tests\Unit;

use App\Support\AnswerValueCaster;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnswerValueCasterTest extends TestCase
{
    #[DataProvider('validScaleValues')]
    public function test_scale_1_5_accepts_integer_like_values_within_range(mixed $value): void
    {
        $this->assertTrue(
            AnswerValueCaster::isValid(AnswerValueCaster::SCALE_1_5, $value)
        );
    }

    #[DataProvider('invalidScaleValues')]
    public function test_scale_1_5_rejects_values_outside_its_contract(mixed $value): void
    {
        $this->assertFalse(
            AnswerValueCaster::isValid(AnswerValueCaster::SCALE_1_5, $value)
        );
    }

    #[DataProvider('validYesNoValues')]
    public function test_yes_no_accepts_supported_boolean_like_values(mixed $value): void
    {
        $this->assertTrue(
            AnswerValueCaster::isValid(AnswerValueCaster::YES_NO, $value)
        );
    }

    #[DataProvider('invalidYesNoValues')]
    public function test_yes_no_rejects_unsupported_or_ambiguous_values(mixed $value): void
    {
        $this->assertFalse(
            AnswerValueCaster::isValid(AnswerValueCaster::YES_NO, $value)
        );
    }

    #[DataProvider('validFreeTextValues')]
    public function test_free_text_accepts_strings_and_null_for_empty_input(mixed $value): void
    {
        $this->assertTrue(
            AnswerValueCaster::isValid(AnswerValueCaster::FREE_TEXT, $value)
        );
    }

    #[DataProvider('invalidFreeTextValues')]
    public function test_free_text_rejects_non_string_structured_or_scalar_values(mixed $value): void
    {
        $this->assertFalse(
            AnswerValueCaster::isValid(AnswerValueCaster::FREE_TEXT, $value)
        );
    }

    #[DataProvider('normalizationCases')]
    public function test_it_normalizes_valid_values_for_storage(
        string $responseType,
        mixed $input,
        string $expected
    ): void {
        $this->assertSame(
            $expected,
            AnswerValueCaster::normalizeForStorage($responseType, $input)
        );
    }

    #[DataProvider('outputCastingCases')]
    public function test_it_casts_stored_values_back_to_api_output_shapes(
        ?string $responseType,
        mixed $storedValue,
        mixed $expected
    ): void {
        $this->assertSame(
            $expected,
            AnswerValueCaster::castForOutput($responseType, $storedValue)
        );
    }

    public static function validScaleValues(): array
    {
        return [
            'lowest integer boundary' => [1],
            'highest integer boundary' => [5],
            'lowest numeric string boundary' => ['1'],
            'highest numeric string boundary' => ['5'],
            'middle integer' => [3],
            'middle numeric string' => ['3'],
        ];
    }

    public static function invalidScaleValues(): array
    {
        return [
            'below lower bound' => [0],
            'above upper bound' => [6],
            'negative integer' => [-1],
            'decimal number' => [3.5],
            'decimal string' => ['3.5'],
            'non numeric string' => ['five'],
            'boolean true' => [true],
            'boolean false' => [false],
            'null' => [null],
            'array' => [[1]],
            'object' => [(object) ['value' => 3]],
        ];
    }

    public static function validYesNoValues(): array
    {
        return [
            'native true' => [true],
            'native false' => [false],
            'integer one' => [1],
            'integer zero' => [0],
            'string one' => ['1'],
            'string zero' => ['0'],
            'string true' => ['true'],
            'string false' => ['false'],
            'uppercase true string' => ['TRUE'],
            'uppercase false string' => ['FALSE'],
        ];
    }

    public static function invalidYesNoValues(): array
    {
        return [
            'unsupported positive integer' => [2],
            'unsupported negative integer' => [-1],
            'ambiguous natural language yes' => ['yes'],
            'ambiguous natural language no' => ['no'],
            'ambiguous maybe' => ['maybe'],
            'empty string' => [''],
            'null' => [null],
            'array' => [[true]],
            'object' => [(object) ['value' => true]],
        ];
    }

    public static function validFreeTextValues(): array
    {
        return [
            'normal sentence' => ['fatigue improved this week'],
            'empty string' => [''],
            'whitespace string' => ['   '],
            'null from empty request input' => [null],
        ];
    }

    public static function invalidFreeTextValues(): array
    {
        return [
            'integer' => [123],
            'float' => [3.14],
            'boolean true' => [true],
            'boolean false' => [false],
            'array' => [['note']],
            'object' => [(object) ['note' => 'hi']],
        ];
    }

    public static function normalizationCases(): array
    {
        return [
            'scale integer becomes string' => [
                AnswerValueCaster::SCALE_1_5,
                5,
                '5',
            ],
            'scale numeric string stays normalized' => [
                AnswerValueCaster::SCALE_1_5,
                '3',
                '3',
            ],
            'yes true becomes one' => [
                AnswerValueCaster::YES_NO,
                true,
                '1',
            ],
            'yes false string becomes zero' => [
                AnswerValueCaster::YES_NO,
                'false',
                '0',
            ],
            'free text null becomes empty string' => [
                AnswerValueCaster::FREE_TEXT,
                null,
                '',
            ],
            'free text normal string preserved' => [
                AnswerValueCaster::FREE_TEXT,
                'fatigue improved',
                'fatigue improved',
            ],
        ];
    }

    public static function outputCastingCases(): array
    {
        return [
            'scale stored string becomes integer' => [
                AnswerValueCaster::SCALE_1_5,
                '4',
                4,
            ],
            'yes stored one becomes true' => [
                AnswerValueCaster::YES_NO,
                '1',
                true,
            ],
            'yes stored zero becomes false' => [
                AnswerValueCaster::YES_NO,
                '0',
                false,
            ],
            'free text null becomes empty string' => [
                AnswerValueCaster::FREE_TEXT,
                null,
                '',
            ],
            'free text string stays string' => [
                AnswerValueCaster::FREE_TEXT,
                'some notes',
                'some notes',
            ],
            'unknown type falls through' => [
                null,
                'raw-value',
                'raw-value',
            ],
        ];
    }
}