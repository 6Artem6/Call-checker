<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIAnalysisService
{
    protected $apiKey;
    protected $assistantId;
    protected $threadId;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.assemblyai.api_key');
        $this->assistantId = config('services.openai.assistant_id');
        $this->threadId = config('services.openai.thread_id');
        $this->apiUrl = config('services.openai.api_url');
    }

    // Создание Run для анализа
    public function createRun(string $content, ?string $functionName, array $instructionList)
    {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl . '/threads/' . $this->threadId . '/messages', [
            'role' => 'user',
            'content' => $content,
        ]);

        // Создание выполнения запроса
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl . '/threads/' . $this->threadId . '/runs', [
            'assistant_id' => $this->assistantId,
            'additional_instructions' => implode("\n", $instructionList),
            'tools' => $functionName ? [
                'type' => 'function',
                'function' => $functionName,
            ] : null,
        ])->json();
    }

    // Проверка статуса Run
    public function isRunInProgress(string $status): bool
    {
        return in_array($status, ['queued', 'in_progress', 'cancelling']);
    }

    // Получение данных выполнения
    public function getRun(string $runId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->apiUrl . '/threads/' . $this->threadId . '/runs/' . $runId);

        return $response->json();
    }

    // Получение шагов Run
    public function getRunSteps(string $runId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->apiUrl . '/threads/' . $this->threadId . '/runs/' . $runId . '/steps');

        return $response->json()['data'] ?? [];
    }

    // Получение данных Run
    public function retrieveRun(string $runId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->apiUrl . '/threads/' . $this->threadId . '/runs/' . $runId);

        return $response->json();
    }

    // Отмена Run
    public function cancelRun(string $runId): void
    {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->apiUrl . '/threads/' . $this->threadId . '/runs/' . $runId . '/cancel');
    }

    // Получение результата из Run
    public function getResult(array $run, ?string $messageId): array
    {
        $result = '';

        if ($run['status'] === 'completed') {
            $messages = $this->getMessages();
            if (!empty($messages)) {
                $result = $messages[0]['content'][0]['text']['value'] ?? '';
            }
        } elseif ($messageId) {
            $message = $this->getMessageById($messageId);
            $result = $message['content'][0]['text']['value'] ?? '';
        }

        return [$result, !empty($result)];
    }

    // Получение всех сообщений из Run
    public function getMessages()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->apiUrl . '/threads/' . $this->threadId . '/messages');

        return $response->json()['data'] ?? [];
    }

    // Получение конкретного сообщения по ID
    public function getMessageById(string $messageId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->apiUrl . '/threads/' . $this->threadId . '/messages/' . $messageId);

        return $response->json();
    }

    // Ожидание завершения выполнения
    public function threadWait(string $runId): array
    {
        $messageId = null;
        $stop = false;
        $run = null;
        while (!$stop) {
            sleep(0.25);
            $steps = $this->getRunSteps($runId);

            foreach ($steps as $step) {
                if ($step['status'] === 'completed') {
                    $stop = true;
                    break;
                }
            }

            if ($stop) {
                $messageId = $steps[0]['stepDetails']['messageCreation']['messageId'] ?? null;
            } else {
                $run = $this->getRun($runId);
            }
        }

        return [$run, $messageId];
    }

    // Отправка выходных данных для функции
    public function submitFunctionOutputs(array $run, string $functionName, string $functionOutput): bool
    {
        $toolOutputs = [];
        foreach ($run['required_action']['submit_tool_outputs']['tool_calls'] as $tool) {
            if ($tool['function']['name'] === $functionName) {
                $toolOutputs[] = [
                    "tool_call_id" => $tool['id'],
                    "output" => $functionOutput,
                ];
            }
        }

        $status = true;
        if (!empty($toolOutputs)) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->post($this->apiUrl . '/threads/' . $this->threadId . '/runs/' . $run['id'] . '/submit_tool_outputs', [
                    'tool_outputs' => $toolOutputs,
                ]);
            } catch (Exception) {
                // Логирование ошибки или обработка
                $status = false;
            }
        }

        return $status;
    }
}
