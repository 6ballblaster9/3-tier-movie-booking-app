<?php

function sanitizeText(string $value): string
{
    return trim(strip_tags($value));
}

function validateCustomerName(?string $value): array
{
    $clean = sanitizeText((string) $value);

    if ($clean === '') {
        return ['valid' => false, 'message' => 'Full name is required.'];
    }

    if (mb_strlen($clean) < 2) {
        return ['valid' => false, 'message' => 'Full name must be at least 2 characters long.'];
    }

    return ['valid' => true, 'value' => $clean];
}

function validateEmail(?string $value): array
{
    $clean = sanitizeText((string) $value);

    if ($clean === '') {
        return ['valid' => false, 'message' => 'Email address is required.'];
    }

    if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Please enter a valid email address.'];
    }

    return ['valid' => true, 'value' => strtolower($clean)];
}

function normalizeSeatNumber(?string $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $seat = strtoupper(trim($value));

    if (!preg_match('/^[A-G](10|[1-9])$/', $seat)) {
        return null;
    }

    return $seat;
}

function validateMovieId($value): ?int
{
    if (!is_numeric($value)) {
        return null;
    }

    $int = (int) $value;

    return $int > 0 ? $int : null;
}

function validateShowtimeId($value): ?int
{
    if (!is_numeric($value)) {
        return null;
    }

    $int = (int) $value;

    return $int > 0 ? $int : null;
}
