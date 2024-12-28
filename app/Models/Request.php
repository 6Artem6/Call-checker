<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * @property int|null $user_id
 * @property int|null $request_id
 * @property int|null $theme_id
 * @property int|null $status_id
 * @property string|null $request_datetime
 */
class Request extends Model
{
    protected $table = 'request';

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'request_id',
        'theme_id',
        'request_datetime',
    ];

    protected $hidden = [
        'user_id',
        'status_id',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(RequestFileStatus::class, 'status_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'request_id');
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(RequestInstruction::class, 'request_id');
    }

    protected static function booted(): void
    {
        static::creating(static function ($request) {
            $request->user_id = auth()->id();
            $request->request_datetime = now();
        });
    }

    /**
     * @return Collection
     */
    public static function getUserList(): Collection
    {
        return self::with(['status', 'files'])
            ->where('user_id', auth()->id())
            ->get();
    }

    /**
     * @param int $id
     * @return Request
     */
    public static function getUserRecord(int $id): Request
    {
        return self::with(['status', 'files', 'files.status'])
            ->where('request.user_id', auth()->id())
            ->where('request.request_id', $id)
            ->first();
    }

    /**
     * @param Instruction[] $instructionList
     * @param array $fileList
     * @return bool
     */
    public function saveFiles(array $instructionList, array $fileList): bool
    {
        $this->user_id = Auth::id();
        $this->status_id = RequestFileStatus::STATUS_CREATED;
        if ($this->save()) {
            foreach ($fileList as $file) {
                $fileModel = new File();
                $fileModel->status_id = RequestFileStatus::STATUS_CREATED;
                // Сохранение файла
                if ($fileModel->saveData($this->request_id, $file)) {
                    foreach ($instructionList as $instruction_id => $is_set) {
                        if ($is_set == 1 /*&& $instruction->theme_id === $this->theme_id*/) {
                            $requestInstruction = new RequestInstruction();
                            $requestInstruction->instruction_id = $instruction_id;
                            $requestInstruction->request_id = $this->request_id;
                            $requestInstruction->firstOrCreate();
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function saveDataTranscribe(): void
    {
        // Получаем все запросы с соответствующим статусом
        $requests = Request::whereIn('status_id', [
            RequestFileStatus::STATUS_CREATED,
            RequestFileStatus::STATUS_BEGIN_TRANSCRIBE,
            RequestFileStatus::STATUS_ERROR_TRANSCRIBE,
        ])
            ->with('files') // Загружаем связанные файлы
            ->limit(6) // Ограничиваем количество записей
            ->get();
        foreach ($requests as $request) {
            // Если статус "создан", изменяем на "начало транскрипции"
            if ($request->status_id === RequestFileStatus::STATUS_CREATED) {
                $request->status_id = RequestFileStatus::STATUS_BEGIN_TRANSCRIBE;
                $request->save();
            }

            foreach ($request->files as $file) {
                // Пропускаем файлы с завершённой или ошибочной транскрипцией
                if (in_array($file->status_id, [
                    RequestFileStatus::STATUS_END_TRANSCRIBE,
                    RequestFileStatus::STATUS_ERROR_TRANSCRIBE
                ], true)) {
                    continue;
                }

                // Если файл создан, начинаем транскрипцию
                if ($file->status_id === RequestFileStatus::STATUS_CREATED) {
                    $file->status_id = RequestFileStatus::STATUS_BEGIN_TRANSCRIBE;
                    $file->save();
                }

                // Получаем путь к файлу и выполняем транскрипцию
                $path = $file->getLocalFilePath();
                [$transcribe_output, $status] = $this->transcribe($path);
                if ($status && !empty($transcribe_output) && is_array($transcribe_output)) {
                    $file->status_id = RequestFileStatus::STATUS_END_TRANSCRIBE;

                    // Сохраняем транскрипцию в чанках
                    foreach ($transcribe_output as $data) {
                        $chunk = new FileChunk();
                        $chunk->saveData($file->file_id, $data);
                    }
                } else {
                    $file->status_id = RequestFileStatus::STATUS_ERROR_TRANSCRIBE;
                }

                $file->save();
            }

            // Обновляем статус запроса
            $request->status_id = RequestFileStatus::STATUS_END_TRANSCRIBE;
            $request->save();
        }
    }

    public function saveDataAnalysis(): void
    {
        $requests = self::with([
            'instructions.instruction',
            'files.chunks',
            'theme'
        ])
            ->whereIn('status_id', [
                RequestFileStatus::STATUS_END_TRANSCRIBE,
                RequestFileStatus::STATUS_BEGIN_ANALYSIS
            ])
            ->limit(4)
            ->get();
        foreach ($requests as $request) {
            if ($request->status_id === RequestFileStatus::STATUS_END_TRANSCRIBE) {
                $request->status_id = RequestFileStatus::STATUS_BEGIN_ANALYSIS;
                $request->save();
            }
            $instruction_text = "";
            foreach ($request->instructions as $instruction) {
                $instruction_text .= "\n- " . $instruction->instruction->instruction_text;
            }
            foreach ($request->files as $file) {
                if (in_array($file->status_id, [
                    RequestFileStatus::STATUS_END_ANALYSIS,
                    RequestFileStatus::STATUS_ERROR_ANALYSIS
                ], true)) {
                    continue;
                }
                if ($file->status_id === RequestFileStatus::STATUS_END_TRANSCRIBE) {
                    $file->status_id = RequestFileStatus::STATUS_BEGIN_ANALYSIS;
                    $file->save();
                }
                $chunk_list = [];
                foreach ($file->chunks as $chunk) {
                    $chunk_data = $chunk->toArray();
                    unset($chunk_data['chunk_id'], $chunk_data['file_id']);
                    $chunk_list[] = $chunk_data;
                }
                $analysis_data = [
                    'theme' => $request->theme->theme_name,
                    'phrases' => $chunk_list
                ];
                $analysis_content = json_encode($analysis_data, JSON_UNESCAPED_UNICODE);
                [$analysis_output, $status] = $this->analysis($analysis_content, $instruction_text);
                if (!empty($analysis_output) and $status) {
                    $file->status_id = RequestFileStatus::STATUS_END_ANALYSIS;
                    FileAnalysis::create([
                        'file_id' => $file->id,
                        'analysis_data' => $analysis_output
                    ]);
                } else {
                    $file->status_id = RequestFileStatus::STATUS_ERROR_ANALYSIS;
                }
                $file->save();
            }
            $request->status_id = RequestFileStatus::STATUS_END_ANALYSIS;
            $request->save();

            sleep(60);
        }
    }

    public function saveDataAnalysisNew(): void
    {
        $requests = self::with([
            'instructions.instruction',
            'files.chunks',
            'theme'
        ])
            ->whereIn('status_id', [
                RequestFileStatus::STATUS_END_TRANSCRIBE,
                RequestFileStatus::STATUS_BEGIN_ANALYSIS
            ])
            ->limit(4)
            ->get();
        foreach ($requests as $request) {
            if ($request->status_id === RequestFileStatus::STATUS_END_TRANSCRIBE) {
                $request->status_id = RequestFileStatus::STATUS_BEGIN_ANALYSIS;
                $request->save();
            }
            $instruction_list = [];
            foreach ($request->instructions as $instruction) {
                $instruction_list[] = $instruction->instruction->instruction_text;
            }
            foreach ($request->files as $file) {
                if (in_array($file->status_id, [
                    RequestFileStatus::STATUS_END_ANALYSIS,
                    RequestFileStatus::STATUS_ERROR_ANALYSIS
                ], true)) {
                    continue;
                }
                if ($file->status_id === RequestFileStatus::STATUS_END_TRANSCRIBE) {
                    $file->status_id = RequestFileStatus::STATUS_BEGIN_ANALYSIS;
                    $file->save();
                }
                $check_list = [];
                $correct_list = [];
                $determine_list = [];
                foreach ($file->chunks as $chunk) {
                    $chunk_data = (array)$chunk->attributes;

                    unset($chunk_data['chunk_id'], $chunk_data['file_id']);
                    $check_list[] = $chunk_data;

                    unset($chunk_data['start_time'], $chunk_data['end_time']);
                    $correct_list[] = $chunk_data;

                    unset($chunk_data['confidence']);
                    $determine_list[] = $chunk_data;
                }

                $determine_count = count($determine_list);
                if ($determine_count > 6) {
                    $tmp_list = [];
                    for ($i = 0; $i < 3; ++$i) {
                        $tmp_list[] = $determine_list[$i];
                    }
                    for ($i = $determine_count - 3; $i < $determine_count; ++$i) {
                        $tmp_list[] = $determine_list[$i];
                    }
                    $determine_list = $tmp_list;
                }

                $analysis_data = [
                    'theme' => $request->theme->theme_name,
                    'phrases' => $check_list
                ];
                $analysis_content = json_encode($analysis_data, JSON_UNESCAPED_UNICODE);
                [$analysis_output, $status] = $this->analysisNew($analysis_content, $instruction_list);
                if (!empty($analysis_output) and $status) {
                    $file->status_id = RequestFileStatus::STATUS_END_ANALYSIS;
                    FileAnalysis::create([
                        'file_id' => $file->id,
                        'analysis_data' => $analysis_output
                    ]);
                } else {
                    $file->status_id = RequestFileStatus::STATUS_ERROR_ANALYSIS;
                }
                $file->save();
            }
            $request->status_id = RequestFileStatus::STATUS_END_ANALYSIS;
            $request->save();

            sleep(60);
        }
    }

    // Работа с API для транскрибирования
    private function transcribe(string $filePath)
    {
        $apiKey = config('services.assemblyai.api_key'); // Получить API ключ из конфигурации
        $base_url = config('services.assemblyai.api_url') . "/v2";
        $url = $base_url . "/upload";
        $headers = [
            "authorization: ". $apiKey,
            "content-type: application/json"
        ];

        // Загрузка файла
        $file = file_get_contents($filePath);
        $response_data = $this->sendCurlRequest($url, $file, $headers);
        $upload_url = $response_data["upload_url"];

        $url = $base_url . "/transcript";
        $data = json_encode([
            'audio_url' => $upload_url,  // URL загрузки
            'language_code' => 'ru',
            'speakers_expected' => 2,
            'speaker_labels' => true,
            'punctuate' => true,
        ]);

        $response_data = $this->sendCurlRequest($url, $data, $headers);

        $transcriptId = $response_data['id'] ?? null;
        if ($transcriptId) {
            return $this->pollForTranscription($transcriptId, $headers);
        }

        return [[], false];
    }

    // Полифония для получения транскрипта
    private function pollForTranscription($transcriptId, $headers)
    {
        $status = true;
        $url = config('services.assemblyai.api_url') . "/v2/transcript/{$transcriptId}";
        $wait = true;

        while ($wait && $status) {
            $response = $this->sendCurlRequest($url, [], $headers, false);
            if ($response['status'] === 'completed') {
                $wait = false;
            } else if ($response['status'] === 'error') {
                $status = false;
            } else {
                sleep(3);
            }
        }

        $result = [];
        if ($status) {
            foreach ($response['utterances'] as $part) {
                $result[] = [
                    'text' => $part['text'],
                    'speaker' => ord($part['speaker']) - ord('A') + 1,
                    'start_milliseconds' => round($part['start']),
                    'end_milliseconds' => round($part['end']),
                    'confidence' => round($part['confidence'] * 100),
                ];
            }
        }

        return [$result, $status];
    }
    // Метод для анализа с дополнительными инструкциями
    public function analysis(string $content, string $instructionText = "")
    {
        $service = new OpenAIAnalysisService;
        $status = false;
        $result = "";

        // Создаем сообщение пользователя
        $service->createMessage($content);

        // Запускаем выполнение
        $run = $service->createRun($content, null, $instructionText);
        $runId = $run['id'] ?? null;

        if ($runId) {
            $status = true;

            // Ожидаем завершения выполнения задачи
            [$run, $messageId] = $service->threadWait($runId);

            // Получаем результат
            $result = $service->getResult($run, $messageId);
        }

        return [$result, $status];
    }
    private function analysisNew(string $content, array $instruction_list = [])
    {

        $determine_speaker_name = "determine_speaker";
        $determine_speaker_output = json_encode([
            "1" => "Клиент",
            "2" => "Оператор"
        ]);

        $determine_speaker_result = $this->analysisFunction($content,
            $determine_speaker_name, $determine_speaker_output, $instruction_list);


        $check_sample_name = "check_sample";
        $check_sample_output = json_encode([
            [
                "check" => "Уточняется ли город доставки",
                "result" => "Да",
                "details" => "Москва"
            ],
            [
                "check" => "Говорится ли, что у собеседника был пропущенный вызов от другого",
                "result" => "Нет",
                "details" => null
            ],
            [
                "check" => "Называется ли требуемое количество цветов и какое, каких видов",
                "result" => "Да",
                "details" => "Розы - 12 штук, маки - 10 штук"
            ],
            [
                "check" => "Изменяется ли в разговоре требуемое количество цветов и на какое",
                "result" => "Да",
                "details" => "Розы - с 10 до 25"
            ],
            [
                "check" => "Спрашивает ли клиент, какие есть цветы",
                "result" => "Ошибка",
                "details" => "Оператор не называет цветы в ответ на просьбу клиента"
            ],
            [
                "check" => "Говорится ли оператором, что цветы перевозятся в коробках и в каком количестве",
                "result" => "Да",
                "details" => "Перевозится товар в коробках прямоугольных по 20 штук до 500 в одной большой коробке"
            ]
        ], JSON_THROW_ON_ERROR);

        return $this->analysisFunction($content,
            $check_sample_name, $check_sample_output, $instruction_list);
    }

    // Метод для анализа с дополнительными инструкциями
    public function analysisFunction(string $content, string $function_name,
                                     string $function_output, array $instruction_list = [])
    {
        $service = new OpenAIAnalysisService;
        $status = true;
        $messageId = null;

        // Создаем запрос на выполнение функции
        $run = $service->createRun($content, $function_name, $instruction_list);
        $runId = $run['id'] ?? null;

        if (!$runId) {
            return ['', false];
        }

        // Ожидаем завершения выполнения запроса
        [$run, $messageId] = $service->threadWait($runId);

        // Получаем результат
        $result = $service->getResult($run, $messageId);

        // Если нужно отправить выходные данные для функции
        $service->submitFunctionOutputs($run, $function_name, $function_output);

        // Повторно ожидаем завершение
        [$run, $messageId] = $service->threadWait($runId);

        // Возвращаем результат
        return $service->getResult($run, $messageId);
    }

    // Простой метод отправки CURL запроса
    private function sendCurlRequest($url, $data, $headers, $set_post = true)
    {
        $curl = curl_init($url);
        if ($set_post) {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $responseData = json_decode($response, true);
        curl_close($curl);

        return $responseData;
    }
}
