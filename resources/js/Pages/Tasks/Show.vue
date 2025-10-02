<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

const fallbackTheme = {
    icon: '🗂️',
    accent: 'border-l-4 border-slate-200',
    card: 'bg-white',
    chip: 'bg-slate-100 text-slate-600',
    badge: 'bg-slate-100 text-slate-600',
    dot: 'bg-slate-400',
    ring: 'ring-slate-300',
    text: 'text-slate-700',
    header: 'bg-slate-50',
};

const mergeTheme = (theme) => ({
    ...fallbackTheme,
    ...(theme ?? {}),
});

const statusesByValue = computed(() => {
    const map = {};
    props.statuses.forEach((status) => {
        map[status.value] = {
            ...status,
            meta: mergeTheme(status.meta),
        };
    });

    return map;
});

const form = useForm({
    title: props.task.title ?? '',
    description: props.task.description ?? '',
    status: props.task.status,
    assigned_to_id: props.task.assigned_to_id ?? '',
    due_date: props.task.due_date ?? '',
});

const attachmentForm = useForm({
    attachments: [],
});

const fileInput = ref(null);
const deletingAttachmentId = ref(null);

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

const statusOptions = computed(() => Object.values(statusesByValue.value));
const userOptions = computed(() => props.users);
const page = usePage();
const flashMessage = computed(() => page.props.flash?.message ?? null);
const attachments = computed(() => props.task.attachments ?? []);
const currentStatusMeta = computed(() =>
    mergeTheme(statusesByValue.value[form.status]?.meta ?? props.task.meta)
);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            assigned_to_id: data.assigned_to_id === '' ? null : data.assigned_to_id,
            due_date: data.due_date === '' ? null : data.due_date,
        }))
        .patch(route('tasks.update', props.task.id), {
            preserveScroll: true,
        });
};

const handleAttachmentSelection = (event) => {
    attachmentForm.attachments = Array.from(event.target.files ?? []);
};

const submitAttachments = () => {
    if (attachmentForm.attachments.length === 0) {
        return;
    }

    attachmentForm.post(route('tasks.attachments.store', props.task.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            attachmentForm.reset();
            attachmentForm.clearErrors();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: () => {
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const deleteAttachment = (attachmentId) => {
    deletingAttachmentId.value = attachmentId;

    router.delete(
        route('tasks.attachments.destroy', [props.task.id, attachmentId]),
        {
            preserveScroll: true,
            onFinish: () => {
                deletingAttachmentId.value = null;
            },
        }
    );
};

const setStatus = (value) => {
    form.status = value;
};

const isImage = (attachment) => (attachment.mime_type ?? '').startsWith('image/');

const attachmentIcon = (attachment) => {
    const type = attachment.mime_type ?? '';

    if (type.startsWith('image/')) {
        return '🖼️';
    }

    if (type.startsWith('video/')) {
        return '🎬';
    }

    if (type === 'application/pdf') {
        return '📄';
    }

    if (type.includes('excel') || type.includes('spreadsheet')) {
        return '📊';
    }

    if (type.includes('word') || type.includes('text')) {
        return '📝';
    }

    return '📎';
};

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('ro-RO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Task #${props.task.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 flex items-center gap-2">
                        <span class="text-2xl">{{ currentStatusMeta.icon }}</span>
                        {{ form.title || 'Task fără titlu' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Status curent:
                        <span class="font-medium text-gray-700">
                            {{ statusesByValue[form.status]?.label ?? props.task.status_label }}
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
                <div
                    class="flex items-center justify-between rounded-lg border px-6 py-4 shadow-sm"
                    :class="currentStatusMeta.card"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ currentStatusMeta.icon }}</span>
                        <div>
                            <p class="text-sm text-gray-500">Status curent</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ statusesByValue[form.status]?.label ?? props.task.status_label }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2 text-sm text-gray-600">
                        <p>
                            <span class="font-medium">Scadent:</span>
                            <span>{{ form.due_date || '—' }}</span>
                        </p>
                        <p>
                            <span class="font-medium">Ultima actualizare:</span>
                            <span>{{ formatDate(props.task.updated_at) }}</span>
                        </p>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div
                        v-if="flashMessage"
                        class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700"
                    >
                        {{ flashMessage }}
                    </div>

                    <div class="mb-6 flex flex-wrap gap-2">
                        <span class="text-xs uppercase tracking-wide text-gray-400">
                            Schimbă rapid statusul
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="status in statusOptions"
                                :key="status.value"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold transition"
                                :class="[
                                    form.status === status.value
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                                ]"
                                @click="setStatus(status.value)"
                            >
                                <span>{{ status.meta.icon }}</span>
                                {{ status.label }}
                            </button>
                        </div>
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

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="assigned_to_id">
                                    Asignat către
                                </label>
                                <select
                                    id="assigned_to_id"
                                    v-model="form.assigned_to_id"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Neasignat</option>
                                    <option v-for="user in userOptions" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p>
                                    <span class="font-medium">Creat la:</span>
                                    <span>{{ formatDate(props.task.created_at) }}</span>
                                </p>
                                <p>
                                    <span class="font-medium">Actualizat la:</span>
                                    <span>{{ formatDate(props.task.updated_at) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Anulează
                            </Link>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                :disabled="form.processing"
                            >
                                Salvează și revino la board
                            </button>
                        </div>
                    </form>
                </div>

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <header class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Fișiere atașate</h3>
                            <p class="text-sm text-gray-500">
                                Încarcă documente, planșe sau imagini relevante pentru task.
                            </p>
                        </div>
                        <form class="flex items-center gap-3" @submit.prevent="submitAttachments">
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                class="text-sm text-gray-600"
                                @change="handleAttachmentSelection"
                            />
                            <button
                                type="submit"
                                class="inline-flex items-center rounded bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                :disabled="attachmentForm.processing"
                            >
                                Încarcă
                            </button>
                        </form>
                    </header>

                    <div v-if="attachments.length" class="grid gap-4 md:grid-cols-2">
                        <article
                            v-for="file in attachments"
                            :key="file.id"
                            class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
                        >
                            <div
                                v-if="isImage(file)"
                                class="h-40 w-full overflow-hidden bg-slate-100"
                            >
                                <img
                                    :src="file.url"
                                    :alt="file.file_name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <span class="text-2xl">{{ attachmentIcon(file) }}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ file.file_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ file.size_label }} · {{ file.mime_type || 'necunoscut' }}
                                    </p>
                                    <div class="mt-3 flex items-center gap-3 text-sm">
                                        <a
                                            :href="file.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-500"
                                        >
                                            Descarcă
                                        </a>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 text-red-600 hover:text-red-500"
                                            :disabled="deletingAttachmentId === file.id"
                                            @click="deleteAttachment(file.id)"
                                        >
                                            {{ deletingAttachmentId === file.id ? 'Ștergere…' : 'Șterge' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                    <p v-else class="rounded border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-gray-500">
                        Nu există fișiere atașate încă.
                    </p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
