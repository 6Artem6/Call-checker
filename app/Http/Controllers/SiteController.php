<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\{Request, Theme, Instruction, File};
use App\Http\Requests\{InstructionRequest, RequestRequest};

class SiteController extends Controller
{
    /**
     * Главная страница
     */
    public function index(): Response
    {
        return Inertia::render('Site/Index');
    }

    /**
     * Отправка файлов
     */
    public function requestCreate(): Response
    {
        $model = new Request();
        $instructionModel = new Instruction();
        $theme_list = Theme::getUserSelectList();
        if (!empty($theme_list)) {
            $model->theme_id = array_key_first($theme_list);
        }
        return Inertia::render('Site/RequestSend', [
            'model' => $model,
            'instructionModel' => $instructionModel,
            'theme_list' => $theme_list,
        ]);
    }

    public function requestSend(RequestRequest $request): RedirectResponse
    {
        $model = new Request();
        $theme_list = Theme::getUserSelectList();
        $request->validate([
            'upload_files.*' => 'file|mimes:mp3,wav|max:10240', // Ограничения на файлы
        ]);
        $data = $request->all();
        $model->fill($data);
        $fileList = $request->file('upload_files');
        $instructionList = $request->input('Instruction', []);
        if ($model->saveFiles($instructionList, $fileList)) {
            Session::flash('success', 'Файлы сохранены');
        } else {
            Session::flash('error', 'Не удалось сохранить файлы');
        }
        return redirect()->route('file-list', ['id' => $model->request_id]);
    }

    /**
     * Список запросов
     */
    public function requestList(): Response
    {
        $requests = Request::getUserList();
        return Inertia::render('Site/RequestList', [
            'requests' => $requests,
        ]);
    }

    /**
     * Список файлов
     */
    public function fileList($id = 0)
    {
        $request = Request::getUserRecord($id);
        if (!$request) {
            return redirect()->route('request-send');
        }
        return Inertia::render('Site/FileList', [
            'request' => $request,
        ]);
    }

    /**
     * Информация о файле
     */
    public function fileInfo($id = 0)
    {
        $model = File::getViewAnalysisRecord($id);
        if (!$model) {
            return redirect()->route('request-send');
        }

        $chunks = $model->chunks;
        $segments = [];
        foreach ($chunks as $k => $chunk) {
            $segments[] = [
                'start' => $chunk->start_milliseconds / 1000,
                'end' => $chunk->end_milliseconds / 1000,
                'speaker' => $chunk->speaker,
                'label' => (string)($k + 1),
            ];
        }

        return Inertia::render('Site/FileInfo', [
            'model' => $model,
            'segments' => $segments,
            'chunks' => $chunks,
            'instructions' => $model->request->instructions,
            'analysisText' => $model->analysis?->getText()
        ]);
    }

    /**
     * Создание инструкции (AJAX)
     */
    public function instructionCreate(InstructionRequest $request): JsonResponse
    {
        $data = $request->validated(); // Получаем только валидированные данные

        // Создаём новую инструкцию
        $instruction = Instruction::create([
            'instruction_text' => $data['instruction_text'],
            'user_id' => Auth::id(),
            'theme_id' => $data['theme_id'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Инструкция успешно создана',
            'instruction' => $instruction->only(['instruction_text', 'instruction_id']),
        ]);
    }

    /**
     * Получение списка инструкций (AJAX)
     */
    public function instructionList(HttpRequest $request): JsonResponse
    {
        $themeId = $request->input('id');
        $data = [];

        if ($themeId) {
            $data = Instruction::getLoadViewList($themeId);
        }

        return response()->json([
            'status' => !empty($data),
            'data' => $data,
        ]);
    }

    /**
     * Получение файла
     */
    public function file($id = 0): BinaryFileResponse
    {
        $model = File::getUserFile($id);
        if (!$model || !$model->getIsFileExists()) {
            abort(404);
        }

        $filePath = $model->getLocalFilePath();
        $fileName = $model->file_system_name;

        return response()->download($filePath, $fileName, [
            'Content-Type' => Storage::mimeType($filePath),
        ]);
    }
}
