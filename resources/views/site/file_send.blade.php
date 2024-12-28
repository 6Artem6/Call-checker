@extends('layouts.app')

@section('title', 'Загрузка файлов')

@section('content')

        <div class="site-contact">
            <h1 class="text-3xl font-semibold">{{ __('Загрузка файлов') }}</h1>

            <div class="flex justify-center mt-6">
                <div class="w-full max-w-md">
                    <form id="send-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="mb-4">
                            <label for="upload_files" class="block text-lg font-medium text-gray-700">{{ __('Выберите файлы') }}</label>
                            <input id="upload_files" name="upload_files[]" type="file" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <x-input-label for="info_file" value="Info File" /> {{-- Label for info file --}}
                            <label for="upload_files" class="block mt-2">
                                <span class="sr-only">{{ __('Выберите файлы') }}</span> {{-- Screen reader text --}}
                                <input type="file" id="info_file" name="info_file" accept=".mp3,.ogg,.wav" class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-violet-50 file:text-violet-700
                                    hover:file:bg-violet-100
                                " /> {{-- File input field --}}
                            </label>
                            {{-- Display existing file if it exists --}}
                            @isset($task->info_file)
                                <div class="shrink-0 my-2">
                                    <span>File Exists: </span> {{-- Display text indicating file existence --}}
                                    <a href="{{ Storage::url($task->info_file) }}">{{ explode('/', $task->info_file)[3] }}</a> {{-- Display file name with link --}}
                                </div>
                            @endisset
                            <x-input-error class="mt-2" :messages="$errors->get('info_file')" /> {{-- Display validation errors for info file --}}
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Отправить') }}
                            </button>
                        </div>

                        <div class="mb-4">
                            <label for="theme_id" class="block text-lg font-medium text-gray-700">{{ __('Выберите тему') }}</label>
                            <select id="theme_id" name="theme_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('Выберите тему') }}</option>
                                @foreach($theme_list as $id => $theme)
                                    <option value="{{ $id }}">{{ $theme }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="py-5">
                            <div class="overflow-auto" style="max-height: 500px;">
                                <table id="instruction-table" class="min-w-full table-auto border-collapse">
                                    <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2">{{ __('Инструкция') }}</th>
                                        <th class="px-4 py-2">{{ __('Использовать') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Динамическое содержание таблицы -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>

                    <p class="mt-6">
                        <button class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" type="button" data-bs-toggle="collapse" data-bs-target="#createForm" aria-expanded="false" aria-controls="createForm">
                            {{ __('Создать инструкцию') }}
                        </button>
                    </p>

                    <div class="collapse" id="createForm">
                        <div class="card card-body mt-4">
                            <form id="create-form" action="{{ route('instruction-create') }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" id="instruction-theme_id" name="theme_id">
                                <div class="form-group">
                                    <label for="instruction_text" class="block text-lg font-medium text-gray-700">{{ __('Текст инструкции') }}</label>
                                    <input type="text" id="instruction_text" name="instruction_text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        {{ __('Создать') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <script>
        function addInstruction(id, text, select = true) {
            let rowHtml = `
            <tr>
                <td>${text}</td>
                <td>
                    <div class="btn-group">
                        <input type="hidden" name="Instruction[${id}][is_set]" value="0">
                        <input type="radio" id="instruction-${id}-is_set-yes" value="1" name="Instruction[${id}][is_set]"
                               class="btn-check" ${select ? 'checked' : ''}>
                        <label class="btn btn-outline-success" for="instruction-${id}-is_set-yes">
                            <i class="bi bi-check-lg"></i>
                        </label>
                        <input type="radio" id="instruction-${id}-is_set-no" value="0" name="Instruction[${id}][is_set]"
                               class="btn-check" ${!select ? 'checked' : ''}>
                        <label class="btn btn-outline-danger" for="instruction-${id}-is_set-no">
                            <i class="bi bi-x-lg"></i>
                        </label>
                    </div>
                </td>
            </tr>
        `;
            $('#instruction-table tbody').append(rowHtml);
        }

        function updateInstructionList() {
            let themeId = $('#theme_id').val();
            $('#instruction-theme_id').val(themeId);
            if (themeId) {
                $.ajax({
                    url: '{{ route('instruction-list') }}',
                    type: 'POST',
                    data: {id: themeId, _token: '{{ csrf_token() }}'},
                    success: function(response) {
                        if (response.status) {
                            $('#instruction-table tbody').html('');
                            response.data.forEach(item => addInstruction(item.id, item.text, false));
                        } else {
                            console.error('Error:', response.data);
                        }
                    },
                    error: function(jqXHR, errMsg) {
                        console.error(errMsg);
                    }
                });
            }
        }

        $('#create-form').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status) {
                        addInstruction(response.data.id, response.data.text, true);
                        $('#createForm').collapse('hide');
                        $('#create-form')[0].reset();
                    } else {
                        console.error('Error:', response.data);
                    }
                },
                error: function(jqXHR, errMsg) {
                    console.error(errMsg);
                }
            });
        });

        $('#theme_id').on('change', function() {
            updateInstructionList();
        });

        $(document).ready(function() {
            updateInstructionList();
        });
    </script>
@endsection
