<?php

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function successResponse(array $data = [], int $statusCode = 200, ?string $message = null): void
{
    $response = ['success' => true];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== []) {
        $response['data'] = $data;
    }

    jsonResponse($statusCode, $response);
}

function errorResponse(string $message, int $statusCode = 400, array $details = []): void
{
    $response = [
        'success' => false,
        'error' => [
            'message' => $message,
        ],
    ];

    if ($details !== []) {
        $response['error']['details'] = $details;
    }

    jsonResponse($statusCode, $response);
}
