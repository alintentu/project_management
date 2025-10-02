<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
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

const buildColumns = (sections) =>
    sections.map((section) => {
        const status = statusesByValue.value[section.key];
        const meta = mergeTheme(section.meta ?? status?.meta);

        return {
            ...section,
            title: status?.label ?? section.title,
            meta,
            tasks: (section.tasks ?? []).map((task) => ({
                ...task,
                meta: mergeTheme(task.meta ?? statusesByValue.value[task.status]?.meta),
            })),
        };
    });

const columns = ref(buildColumns(props.sections));

watch(
    () => props.sections,
    (next) => {
        columns.value = buildColumns(next);
    },
    { deep: true }
);

watch(statusesByValue, () => {
    columns.value = buildColumns(props.sections);
});

const topSectionKeys = ['in_progress', 'in_review', 'done'];

const topSections = computed(() =>
    columns.value.filter((column) => topSectionKeys.includes(column.key))
);

const backlogSection = computed(
    () => columns.value.find((column) => column.key === 'backlog') ?? null
);

const statusOptions = computed(() => Object.values(statusesByValue.value));

const defaultStatus = computed(
    () =>
        statusOptions.value.find((status) => status.value === 'backlog')?.value ??
        statusOptions.value[0]?.value ??
        'backlog'
);

const showCreateForm = ref(false);

const createForm = useForm({
    title: '',
    description: '',
    status: defaultStatus.value,
    assigned_to_id: '',
    due_date: '',
});

watch(defaultStatus, (value) => {
    if (!createForm.status) {
        createForm.status = value;
    }
});

watch(
    () => props.statuses,
    () => {
        if (!statusOptions.value.some((status) => status.value === createForm.status)) {
            createForm.status = defaultStatus.value;
        }
    }
);

const updatingTaskId = ref(null);
const updatingStatusId = ref(null);
const isOrdering = ref(false);
const dragState = ref({ taskId: null, fromStatus: null });
const hoverState = ref({ status: null, taskId: null });

const columnsPayload = computed(() =>
    columns.value.map((column) => ({
        status: column.key,
        task_ids: column.tasks.map((task) => task.id),
    }))
);

const persistOrder = () => {
    if (isOrdering.value) {
        return;
    }

    isOrdering.value = true;

    router.post(
        route('tasks.order'),
        { columns: columnsPayload.value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isOrdering.value = false;
            },
        }
    );
};

const handleAssigneeChange = (taskId, event) => {
    const value = event.target.value;
    const payload = {
        assigned_to_id: value === '' ? null : Number(value),
    };

    updatingTaskId.value = taskId;

    router.patch(route('tasks.assignee', taskId), payload, {
        preserveScroll: true,
        onFinish: () => {
            updatingTaskId.value = null;
        },
    });
};

const toggleCreateForm = () => {
    showCreateForm.value = !showCreateForm.value;

    if (!showCreateForm.value) {
        createForm.reset();
        createForm.status = defaultStatus.value;
        createForm.clearErrors();
    }
};

const submitCreateForm = () => {
    createForm
        .transform((data) => ({
            ...data,
            assigned_to_id: data.assigned_to_id === '' ? null : Number(data.assigned_to_id),
            due_date: data.due_date === '' ? null : data.due_date,
            status: data.status || defaultStatus.value,
        }))
        .post(route('tasks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showCreateForm.value = false;
                createForm.reset();
                createForm.status = defaultStatus.value;
            },
        });
};

const handleStatusChange = (taskId, event) => {
    const value = event.target.value;

    if (!value) {
        return;
    }

    updatingStatusId.value = taskId;

    router.patch(
        route('tasks.status', taskId),
        { status: value },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingStatusId.value = null;
            },
        }
    );
};

const onDragStart = (statusKey, taskId, event) => {
    dragState.value = { taskId, fromStatus: statusKey };
    hoverState.value = { status: statusKey, taskId: null };

    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(taskId));
    }
};

const onDragEnd = () => {
    dragState.value = { taskId: null, fromStatus: null };
    hoverState.value = { status: null, taskId: null };
};

const onDragEnterTask = (statusKey, taskId) => {
    hoverState.value = { status: statusKey, taskId };
};

const onDragLeaveTask = (statusKey, taskId) => {
    if (
        hoverState.value.status === statusKey &&
        hoverState.value.taskId === taskId
    ) {
        hoverState.value = { status: statusKey, taskId: null };
    }
};

const onDragOverTask = (statusKey, taskId, event) => {
    event.preventDefault();
    hoverState.value = { status: statusKey, taskId };
};

const handleTaskDrop = (statusKey, taskId) => {
    if (!dragState.value.taskId) {
        return;
    }

    if (taskId === dragState.value.taskId) {
        onDragEnd();
        return;
    }

    const moved = moveTask(dragState.value.fromStatus, statusKey, taskId);

    if (moved) {
        persistOrder();
    }

    onDragEnd();
};

const handleColumnDragOver = (statusKey, event) => {
    event.preventDefault();
    hoverState.value = { status: statusKey, taskId: null };
};

const handleColumnDrop = (statusKey) => {
    if (!dragState.value.taskId) {
        return;
    }

    const moved = moveTask(dragState.value.fromStatus, statusKey, null);

    if (moved) {
        persistOrder();
    }

    onDragEnd();
};

const isHoveringTask = (statusKey, taskId) =>
    hoverState.value.status === statusKey && hoverState.value.taskId === taskId;

const isHoveringColumn = (statusKey) =>
    hoverState.value.status === statusKey && hoverState.value.taskId === null;

const moveTask = (fromStatus, toStatus, beforeTaskId = null) => {
    if (!dragState.value.taskId) {
        return false;
    }

    const updatedColumns = columns.value.map((column) => ({
        ...column,
        tasks: column.tasks.map((task) => ({ ...task })),
    }));

    const sourceColumn = updatedColumns.find((column) => column.key === fromStatus);
    const destinationColumn = updatedColumns.find(
        (column) => column.key === toStatus
    );

    if (!sourceColumn || !destinationColumn) {
        return false;
    }

    const sourceIndex = sourceColumn.tasks.findIndex(
        (task) => task.id === dragState.value.taskId
    );

    if (sourceIndex === -1) {
        return false;
    }

    const wasLastInSource = sourceIndex === sourceColumn.tasks.length - 1;

    const [task] = sourceColumn.tasks.splice(sourceIndex, 1);

    if (fromStatus === toStatus && beforeTaskId === null && wasLastInSource) {
        sourceColumn.tasks.splice(sourceIndex, 0, task);
        return false;
    }

    let targetIndex = destinationColumn.tasks.length;

    if (beforeTaskId !== null) {
        if (beforeTaskId === task.id) {
            sourceColumn.tasks.splice(sourceIndex, 0, task);
            return false;
        }

        targetIndex = destinationColumn.tasks.findIndex(
            (candidate) => candidate.id === beforeTaskId
        );

        if (targetIndex === -1) {
            targetIndex = destinationColumn.tasks.length;
        }
    }

    if (fromStatus === toStatus) {
        if (beforeTaskId !== null) {
            if (targetIndex >= sourceIndex) {
                targetIndex -= 1;
            }

            if (targetIndex === sourceIndex) {
                sourceColumn.tasks.splice(sourceIndex, 0, task);
                return false;
            }
        } else if (targetIndex === sourceIndex) {
            sourceColumn.tasks.splice(sourceIndex, 0, task);
            return false;
        }
    }

    task.status = destinationColumn.key;
    const statusMeta = statusesByValue.value[destinationColumn.key];
    task.status_label = statusMeta?.label ?? task.status_label;
    task.meta = mergeTheme(statusMeta?.meta);

    destinationColumn.tasks.splice(targetIndex, 0, task);

    columns.value = updatedColumns;

    return true;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60"
                        @click="toggleCreateForm"
                    >
                        {{ showCreateForm ? 'Închide formularul' : 'Adaugă task' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="space-y-6 px-4 sm:px-6 lg:px-10">
                <div class="grid gap-6 md:grid-cols-3">
                    <section
                        v-for="section in topSections"
                        :key="section.key"
                        class="flex flex-col rounded-lg bg-white shadow-sm"
                        @dragover.prevent="handleColumnDragOver(section.key, $event)"
                        @drop.prevent="handleColumnDrop(section.key)"
                    >
                        <header
                            class="border-b border-gray-200 px-6 py-4"
                            :class="section.meta.header"
                        >
                            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                <span class="text-xl">{{ section.meta.icon }}</span>
                                {{ section.title }}
                            </h3>
                        </header>

                        <ul
                            class="space-y-3 px-6 py-4 min-h-[14rem]"
                            :class="{
                                'rounded-lg border border-dashed border-blue-300 bg-blue-50/40': isHoveringColumn(section.key),
                            }"
                        >
                            <li
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="flex flex-col gap-2 rounded-lg border border-transparent px-4 py-3 shadow-sm transition"
                                :class="[
                                    section.meta.accent,
                                    section.meta.card,
                                    isHoveringTask(section.key, task.id)
                                        ? 'ring-2 ring-blue-400 ring-offset-2'
                                        : '',
                                    dragState.taskId === task.id ? 'opacity-50' : '',
                                ]"
                                draggable="true"
                                @dragstart="onDragStart(section.key, task.id, $event)"
                                @dragend="onDragEnd"
                                @dragenter.prevent="onDragEnterTask(section.key, task.id)"
                                @dragleave="onDragLeaveTask(section.key, task.id)"
                                @dragover.prevent="onDragOverTask(section.key, task.id, $event)"
                                @drop.prevent="handleTaskDrop(section.key, task.id)"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <h4 class="flex items-center gap-2 text-base font-medium text-gray-900">
                                        <span class="text-xl">{{ section.meta.icon }}</span>
                                        <Link
                                            :href="route('tasks.show', task.id)"
                                            class="hover:text-blue-600"
                                        >
                                            {{ task.title }}
                                        </Link>
                                    </h4>
                                    <span
                                        v-if="task.due_date"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="task.meta.chip"
                                    >
                                        Scadent: {{ task.due_date }}
                                    </span>
                                </div>
                                <p v-if="task.description" class="text-sm text-gray-600">
                                    {{ task.description }}
                                </p>
                                <div class="flex items-center gap-3 text-sm text-gray-500">
                                    <div class="flex flex-1 items-center gap-2">
                                        <label class="text-xs uppercase tracking-wide text-slate-400">
                                            Asignat
                                        </label>
                                        <select
                                            class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                            :value="task.assignee_id ?? ''"
                                            :disabled="updatingTaskId === task.id"
                                            @change="handleAssigneeChange(task.id, $event)"
                                        >
                                            <option value="">Neasignat</option>
                                            <option
                                                v-for="user in props.users"
                                                :key="user.id"
                                                :value="user.id"
                                            >
                                                {{ user.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs uppercase tracking-wide text-slate-400">
                                            Status
                                        </label>
                                        <select
                                            class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                            :value="task.status"
                                            :disabled="updatingStatusId === task.id"
                                            @change="handleStatusChange(task.id, $event)"
                                        >
                                            <option
                                                v-for="status in statusOptions"
                                                :key="status.value"
                                                :value="status.value"
                                            >
                                                {{ status.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </li>
                            <li
                                v-if="section.tasks.length === 0"
                                class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400"
                            >
                                Trage un task aici
                            </li>
                        </ul>
                    </section>
                </div>

                <section
                    v-if="backlogSection"
                    class="flex flex-col rounded-lg bg-white shadow-sm"
                    @dragover.prevent="handleColumnDragOver(backlogSection.key, $event)"
                    @drop.prevent="handleColumnDrop(backlogSection.key)"
                >
                    <header
                        class="border-b border-gray-200 px-6 py-4"
                        :class="backlogSection.meta.header"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                <span class="text-xl">{{ backlogSection.meta.icon }}</span>
                                {{ backlogSection.title }}
                            </h3>
                            <button
                                type="button"
                                class="inline-flex items-center rounded border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                @click="toggleCreateForm"
                            >
                                {{ showCreateForm ? 'Ascunde formularul' : 'Adaugă task în backlog' }}
                            </button>
                        </div>
                    </header>

                    <div v-if="showCreateForm" class="border-b border-gray-200 px-6 py-6">
                        <form class="space-y-5" @submit.prevent="submitCreateForm">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="new-task-title">
                                        Titlu
                                    </label>
                                    <input
                                        id="new-task-title"
                                        v-model="createForm.title"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        required
                                    />
                                    <p v-if="createForm.errors.title" class="mt-1 text-sm text-red-500">
                                        {{ createForm.errors.title }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="new-task-status">
                                        Status
                                    </label>
                                    <select
                                        id="new-task-status"
                                        v-model="createForm.status"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option
                                            v-for="status in statusOptions"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <p v-if="createForm.errors.status" class="mt-1 text-sm text-red-500">
                                        {{ createForm.errors.status }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="new-task-description">
                                    Descriere
                                </label>
                                <textarea
                                    id="new-task-description"
                                    v-model="createForm.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                ></textarea>
                                <p v-if="createForm.errors.description" class="mt-1 text-sm text-red-500">
                                    {{ createForm.errors.description }}
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="new-task-due">
                                        Data scadenței
                                    </label>
                                    <input
                                        id="new-task-due"
                                        v-model="createForm.due_date"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="createForm.errors.due_date" class="mt-1 text-sm text-red-500">
                                        {{ createForm.errors.due_date }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="new-task-assignee">
                                        Asignat către
                                    </label>
                                    <select
                                        id="new-task-assignee"
                                        v-model="createForm.assigned_to_id"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="">Neasignat</option>
                                        <option v-for="user in props.users" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                    <p v-if="createForm.errors.assigned_to_id" class="mt-1 text-sm text-red-500">
                                        {{ createForm.errors.assigned_to_id }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="text-sm text-gray-500 hover:text-gray-700"
                                    @click="toggleCreateForm"
                                >
                                    Renunță
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                    :disabled="createForm.processing"
                                >
                                    Salvează task
                                </button>
                            </div>
                        </form>
                    </div>

                    <ul
                        class="space-y-3 px-6 py-4 min-h-[14rem]"
                        :class="{
                            'rounded-lg border border-dashed border-blue-300 bg-blue-50/40': isHoveringColumn(backlogSection.key),
                        }"
                    >
                        <li
                            v-for="task in backlogSection.tasks"
                            :key="task.id"
                            class="flex flex-col gap-2 rounded-lg border border-transparent px-4 py-3 shadow-sm transition"
                            :class="[
                                backlogSection.meta.accent,
                                backlogSection.meta.card,
                                isHoveringTask(backlogSection.key, task.id)
                                    ? 'ring-2 ring-blue-400 ring-offset-2'
                                    : '',
                                dragState.taskId === task.id ? 'opacity-50' : '',
                            ]"
                            draggable="true"
                            @dragstart="onDragStart(backlogSection.key, task.id, $event)"
                            @dragend="onDragEnd"
                            @dragenter.prevent="onDragEnterTask(backlogSection.key, task.id)"
                            @dragleave="onDragLeaveTask(backlogSection.key, task.id)"
                            @dragover.prevent="onDragOverTask(backlogSection.key, task.id, $event)"
                            @drop.prevent="handleTaskDrop(backlogSection.key, task.id)"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <h4 class="flex items-center gap-2 text-base font-medium text-gray-900">
                                    <span class="text-xl">{{ backlogSection.meta.icon }}</span>
                                    <Link
                                        :href="route('tasks.show', task.id)"
                                        class="hover:text-blue-600"
                                    >
                                        {{ task.title }}
                                    </Link>
                                </h4>
                                <span
                                    v-if="task.due_date"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="task.meta.chip"
                                >
                                    Scadent: {{ task.due_date }}
                                </span>
                            </div>
                            <p v-if="task.description" class="text-sm text-gray-600">
                                {{ task.description }}
                            </p>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <div class="flex flex-1 items-center gap-2">
                                    <label class="text-xs uppercase tracking-wide text-slate-400">
                                        Asignat
                                    </label>
                                    <select
                                        class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                        :value="task.assignee_id ?? ''"
                                        :disabled="updatingTaskId === task.id"
                                        @change="handleAssigneeChange(task.id, $event)"
                                    >
                                        <option value="">Neasignat</option>
                                        <option
                                            v-for="user in props.users"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs uppercase tracking-wide text-slate-400">
                                        Status
                                    </label>
                                    <select
                                        class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                        :value="task.status"
                                        :disabled="updatingStatusId === task.id"
                                        @change="handleStatusChange(task.id, $event)"
                                    >
                                        <option
                                            v-for="status in statusOptions"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li
                            v-if="backlogSection.tasks.length === 0"
                            class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400"
                        >
                            Niciun task disponibil încă.
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
