<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Env;
use RuntimeException;

/**
 * Sole point of contact with the OpenAI API. No other class in the system
 * should open an HTTP connection to OpenAI — everything goes through here,
 * so the provider can be swapped later without touching business logic.
 */
final class OpenAIProvider
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
    ) {
    }

    private function key(): string
    {
        $key = $this->apiKey ?? Env::get('OPENAI_API_KEY');

        if (!$key) {
            throw new RuntimeException('OPENAI_API_KEY não configurada no .env.');
        }

        return $key;
    }

    private function modelName(): string
    {
        return $this->model ?? Env::get('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * Sends a system + user prompt pair and returns the raw JSON text produced by the model.
     * Forces JSON-object output mode so the caller can safely json_decode() the result.
     */
    public function completeJson(string $systemPrompt, string $userPrompt): string
    {
        $payload = [
            'model' => $this->modelName(),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0,
        ];

        $response = $this->request('/v1/chat/completions', $payload);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    private function request(string $path, array $payload): array
    {
        $ch = curl_init('https://api.openai.com' . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->key(),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 120,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new RuntimeException('Falha de rede ao chamar a OpenAI: ' . $error);
        }

        $decoded = json_decode((string) $raw, true);

        if ($httpCode >= 400) {
            $message = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('Erro da API OpenAI: ' . $message);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Resposta inválida da API OpenAI.');
        }

        return $decoded;
    }
}
