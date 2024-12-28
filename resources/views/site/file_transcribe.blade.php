<?php
/**
 * @var $file App\Models\File
 */
?>
@extends('layouts.app')

@section('title', __('Просмотр расшифровки'))

@section('content')
    <div class="site">
        <h1>{{ __('Просмотр расшифровки') }}</h1>

        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-10 col-lg-6">
                <div class="card bg-light text-dark">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ __('Текст') }}</span>
                        <a href="{{ route('files.list', ['id' => $file->request_id]) }}" class="btn btn-info btn-sm">
                            {{ __('Назад') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $file->file_name }}</h5>
                        <p>
                            {!! $file->getViewHtml() !!}
                        </p>
                        <hr>
                        @foreach ($file->chunks as $chunk)
                            <h5 class="card-title">
                                {{ $chunk->speaker }} ({{ $chunk->start_time }} - {{ $chunk->end_time }}):
                            </h5>
                            <p class="card-text">
                                - {{ $chunk->text }} ({{ $chunk->confidence }}%)
                            </p>
                            <hr>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
