<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    title: props.task.title ?? '',
    description: props.task.description ?? '',
    status: props.task.status,
    assigned_to_id: props.task.assigned_to_id ?? '',
    due_date: props.task.due_date ?? '',
});

watch(
    () => props.task,
    (task) => {
        form.title = task.title ?? '';
        form.description = task.description ?? '';
        form.status = task.status;
        form.assigned_to_id = task.assigned_to_id ?? '';
        form.due_date = task.due_date ?? '';
    },
    { deep: true }
);

const statusOptions = computed(() => props.statuses);
const userOptions = computed(() => props.users);
const page = usePage();
const flashMessage = computed(() => page.props.flash?.message ?? null);

const submit = () => {
    form.transform((data) => ({
        ...data,
        assigned_to_id: data.assigned_to_id === '' ? null : data.assigned_to_id,
        due_date: data.due_date === '' ? null : data.due_date,
    })).patch(route('tasks.update', props.task.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Task #${props.task.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ form.title || 'Task fără titlu' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Status curent:
                        <span class="font-medium text-gray-700">
                            {{ props.task.status_label }}
                        </span>
                    </p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="text-sm text-blue-600 hover:text-blue-500"
                >
                    ← Înapoi la dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div
                        v-if="flashMessage"
                        class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700"
                    >
                        {{ flashMessage }}
                    </div>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="title">
                                Titlu
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                required
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="description">
                                Descriere
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="status">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                >
                                    <option
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="due_date">
                                    Data scadenței
                                </label>
                                <input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.due_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.due_date }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="assignee">
                                Asignat către
                            </label>
                            <select
                                id="assignee"
                                v-model="form.assigned_to_id"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Neasignat</option>
                                <option v-for="user in userOptions" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.assigned_to_id" class="mt-1 text-sm text-red-500">
                                {{ form.errors.assigned_to_id }}
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Renunță
                            </Link>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Salvează
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Metadate
                    </h3>
                    <dl class="mt-4 space-y-2 text-sm text-gray-600">
                        <div class="flex items-center justify-between">
                            <dt class="font-medium text-gray-500">Creat la</dt>
                            <dd>{{ props.task.created_at || 'N/A' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="font-medium text-gray-500">Ultima actualizare</dt>
                            <dd>{{ props.task.updated_at || 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
