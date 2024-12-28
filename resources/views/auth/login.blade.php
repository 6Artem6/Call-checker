@extends('layouts.app')

@section('title', 'Вход')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="w-full max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        @csrf
        <div class="mb-4">
            <label for="user_name" class="block text-sm font-medium text-gray-700">{{ __('Логин') }}</label>
            <input
                type="text"
                id="user_name"
                name="user_name"
                value="{{ old('user_name') }}"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
            @error('user_name')
            <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Пароль') }}</label>
            <input
                type="password"
                id="password"
                name="password"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
            @error('password')
            <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
        <div class="flex items-center mb-4">
            <input
                type="checkbox"
                id="rememberMe"
                name="rememberMe"
                {{ old('rememberMe') ? 'checked' : '' }}
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            >
            <label for="rememberMe" class="ml-2 block text-sm text-gray-900">{{ __('Запомнить меня') }}</label>
        </div>
        <button
            type="submit"
            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            {{ __('Войти') }}
        </button>
    </form>


@endsection
