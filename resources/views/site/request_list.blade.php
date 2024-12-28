@extends('layouts.app')

@section('title', 'Просмотр запросов')

@section('content')
    <div class="site">
        <h1>{{ __('Просмотр запросов') }}</h1>

        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-10 col-lg-6">
                <div class="card bg-light text-dark">
                    @foreach ($list as $index => $request)
                        <div class="card-body">
                            <h5 class="card-title">
                                {{ '#' . $request->request_id . ". Файлов: " . count($request->files) . " - " . $request->status->status_name }}
                            </h5>
                            <p>
                                {!! $request->getViewHtml() !!}
                            </p>
                            <p class="card-text">
                                <a href="{{ route('files.list', ['id' => $request->request_id]) }}" class="btn btn-info btn-sm">
                                    {{ __('Список файлов') }}
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
