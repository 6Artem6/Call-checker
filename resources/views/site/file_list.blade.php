@extends('layouts.app')

@section('title', 'Просмотр файлов запроса #' . $request->id)

@section('content')
    <div class="site">
        <h1>{{ __('Просмотр файлов запроса #') . $request->id }}</h1>

        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-10 col-lg-6">
                <div class="card bg-light text-dark">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ __('Файлы') }}</span>
                        <a href="{{ route('requests.list') }}" class="btn btn-info btn-sm">
                            {{ __('Назад') }}
                        </a>
                    </div>
                    @foreach ($request->files as $index => $file)
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ ($index + 1) . ". " . $file->file_name . " (" . $file->created_at->format('H:i:s') . ")" . " - " . $file->status->name }}
                            </h5>
                            <p>
                                {!! $file->getViewHtml() !!}
                            </p>
                            <p class="card-text">
                                <a href="{{ route('files.info', ['id' => $file->id]) }}" class="btn btn-info btn-sm">
                                    {{ __('Информация') }}
                                </a>
                            </p>
                            <hr>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
