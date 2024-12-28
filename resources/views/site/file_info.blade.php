@extends('layouts.app')

@section('title', 'Просмотр информации о записи')

@section('content')
    <div class="container">
        <div class="d-md-flex flex-md-row-reverse align-items-center justify-content-between mb-3">
            <a href="{{ route('file-list', ['id' => $model->request_id]) }}" class="btn btn-info btn-sm mb-2 mb-md-0">Назад</a>
            <h1 class="bd-title">Просмотр информации о записи</h1>
        </div>

        <div class="row justify-content-center">
            <!-- Карточка с транскриптом -->
            <div class="col-sm-12 col-md-6 col-lg-5">
                <div class="card bg-light text-dark mb-3">
                    <div class="card-header">
                        <div class="float-start">
                            Транскрипт
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <h5 class="card-title">{{ $model->file_name }}</h5>
                        <p>
                            <audio controls>
                                <source src="{{ $model->getUrl() }}" type="audio/mpeg">
                            </audio>
                        <div id="waveform"></div>
                        </p>
                        <hr>
                        <div class="accordion" id="accordionTranscription">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseTranscription" aria-expanded="true" aria-controls="collapseTranscription">
                                        Расшифровка:
                                    </button>
                                </h2>
                                <div id="collapseTranscription" class="accordion-collapse collapse show" data-bs-parent="#collapseTranscription">
                                    <div class="accordion-body">
                                        @foreach ($model->chunks as $chunk)
                                            <h5 class="card-title">{{ $chunk->speaker }} ({{ $chunk->start_time }} - {{ $chunk->end_time }}):</h5>
                                            <p class="card-text">- {{ $chunk->text }} ({{ $chunk->confidence }} %)</p>
                                            <hr>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Карточка анализа -->
            <div class="col-sm-12 col-md-6 col-lg-5">
                <div class="card bg-light text-dark mb-3">
                    <div class="card-header">
                        <div class="float-start">
                            Анализ
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="accordion" id="accordionInstructions">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseInstructions" aria-expanded="true" aria-controls="collapseInstructions">
                                        Инструкции:
                                    </button>
                                </h2>
                                <div id="collapseInstructions" class="accordion-collapse collapse show" data-bs-parent="#accordionInstructions">
                                    <div class="accordion-body">
                                        <ul class="list-group list-group-flush">
                                            @foreach ($model->request->instructions as $instruction)
                                                <li class="list-group-item">{{ $instruction->instruction->instruction_text }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Результат проверки -->
                <div class="card bg-light text-dark sticky-top">
                    <div class="card-body p-2">
                        <div class="accordion" id="accordionCheck">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseCheck" aria-expanded="true" aria-controls="collapseCheck">
                                        Результат проверки:
                                    </button>
                                </h2>
                                <div id="collapseCheck" class="accordion-collapse collapse show" data-bs-parent="#accordionCheck">
                                    <div class="accordion-body">
                                        {{ $model->analysis?->getText() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS для инициализации Wavesurfer -->
    <script>
        // Передача данных из Laravel в JavaScript
        var url = @json($model->getUrl()); // URL аудиофайла
        var segments = @json($segments);   // Сегменты для регионов

        // Инициализация WaveSurfer.js
        var wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: 'violet',
            progressColor: 'purple',
            url: url,
        });

        // Регистрируем плагин Regions для добавления областей
        const wsRegions = wavesurfer.registerPlugin(RegionsPlugin.create());

        wavesurfer.on('decode', () => {
            segments.forEach(function(segment) {
                wsRegions.addRegion({
                    start: segment.start,
                    end: segment.end,
                    drag: false, // Запрещаем перемещение сегмента
                    resize: false, // Запрещаем изменение размера сегмента
                    color: 'rgba(255, 0, 0, 0.3)', // Цвет маркера
                    content: segment.label // Подпись сегмента
                });
            });
        });
    </script>
@endsection
