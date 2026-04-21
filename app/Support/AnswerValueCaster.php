<?php

namespace App\Support;

use InvalidArgumentException;

class AnswerValueCaster
{
    public const SCALE_1_5 = 'scale_1_5';
    public const YES_NO = 'yes_no';
    public const FREE_TEXT = 'free_text';

    public static function allowedTypes(): array
    {
        return [
            self::SCALE_1_5,
            self::YES_NO,
            self::FREE_TEXT,
        ];
    }

    public static function isValid(string $responseType, mixed $value): bool
    {
        return match ($responseType) {
            self::SCALE_1_5 => ! is_bool($value)
                && filter_var($value, FILTER_VALIDATE_INT) !== false
                && (int) $value >= 1
                && (int) $value <= 5,
            self::YES_NO => self::isBoolLike($value),
            self::FREE_TEXT => is_string($value) || $value === null,
            default => false,
        };
    }

    public static function normalizeForStorage(string $responseType, mixed $value): string
    {
        if (! self::isValid($responseType, $value)) {
            throw new InvalidArgumentException('Invalid answer value for response type.');
        }

        return match ($responseType) {
            self::SCALE_1_5 => (string) ((int) $value),
            self::YES_NO => self::toBool($value) ? '1' : '0',
            self::FREE_TEXT => (string) ($value ?? ''),
            default => throw new InvalidArgumentException('Unsupported response type.'),
        };
    }

    public static function castForOutput(?string $responseType, mixed $value): mixed
    {
        if ($responseType === null) {
            return $value;
        }

        return match ($responseType) {
            self::SCALE_1_5 => $value === null ? null : (int) $value,
            self::YES_NO => $value === null ? null : self::toBool($value),
            self::FREE_TEXT => (string) ($value ?? ''),
            default => $value,
        };
    }

    public static function isBoolLike(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value)) {
            return in_array($value, [0, 1], true);
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['0', '1', 'true', 'false'], true);
        }

        return false;
    }

    public static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true'], true);
        }

        return false;
    }
}