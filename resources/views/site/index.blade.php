<template>
    <div class="site-index">
        <div class="text-center bg-transparent mt-5 mb-5">
            <h1 class="text-4xl font-semibold">Добро пожаловать</h1>
            <p class="text-xl mt-2 mb-4">на платформу для проверки качества звонков!</p>

            <p>
                <template v-if="!auth.user">
                    <!-- Кнопка для гостей -->
                    <a
                        :href="route('login')"
                        class="inline-block bg-blue-500 text-white py-2 px-6 rounded-lg text-lg"
                    >
                        {{ __('Войти') }}
                    </a>
                </template>
                <template v-else>
                    <!-- Кнопка для авторизованных пользователей -->
                    <a
                        :href="route('file-send')"
                        class="inline-block bg-blue-500 text-white py-2 px-6 rounded-lg text-lg"
                    >
                        {{ __('Рабочее место') }}
                    </a>
                </template>
            </p>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            auth: {
                type: Object,
                required: true,
            },
        },
    };
</script>

<style scoped>
    /* Добавьте свои стили при необходимости */
</style>
