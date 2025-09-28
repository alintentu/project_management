<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const accentByStatus = {
    backlog: 'border-slate-300',
    in_progress: 'border-blue-500',
    in_review: 'border-amber-400',
    done: 'border-emerald-500',
};

const sections = computed(() =>
    props.sections.map((section) => ({
        ...section,
        accent: `border-l-4 ${accentByStatus[section.key] ?? 'border-slate-200'}`,
    })),
);

const topSectionKeys = ['in_progress', 'in_review', 'done'];

const topSections = computed(() =>
    sections.value.filter((section) => topSectionKeys.includes(section.key)),
);

const backlogSection = computed(() =>
    sections.value.find((section) => section.key === 'backlog') ?? null,
);

const statusOptions = computed(() => props.statuses);

const updatingTaskId = ref(null);
const updatingStatusId = ref(null);

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

const handleStatusChange = (taskId, event) => {
    const value = event.target.value;

    if (!value) {
        return;
    }

    updatingStatusId.value = taskId;

    router.patch(route('tasks.status', taskId), { status: value }, {
        preserveScroll: true,
        onFinish: () => {
            updatingStatusId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="space-y-6 px-4 sm:px-6 lg:px-10">
                <div
                    class="grid gap-6 md:grid-cols-3"
                >
                    <section
                        v-for="section in topSections"
                        :key="section.key"
                        class="flex flex-col rounded-lg bg-white shadow-sm"
                    >
                        <header
                            class="border-b border-gray-200 px-6 py-4"
                        >
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ section.title }}
                            </h3>
                        </header>

                        <ul class="divide-y divide-gray-200">
                            <li
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="flex flex-col gap-2 px-6 py-4"
                                :class="section.accent"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <h4 class="text-base font-medium text-gray-900">
                                        <Link
                                            :href="route('tasks.show', task.id)"
                                            class="hover:text-blue-600"
                                        >
                                            {{ task.title }}
                                        </Link>
                                    </h4>
                                    <span
                                        v-if="task.due_date"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700"
                                    >
                                        Scadent: {{ task.due_date }}
                                    </span>
                                </div>
                                <p
                                    v-if="task.description"
                                    class="text-sm text-gray-600"
                                >
                                    {{ task.description }}
                                </p>
                                <div class="flex items-center gap-3 text-sm text-gray-500">
                                    <div class="flex flex-1 items-center gap-2">
                                        <label
                                            class="text-xs uppercase tracking-wide text-slate-400"
                                        >
                                            Asignat
                                        </label>
                                        <select
                                            class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                            :value="task.assignee_id ?? ''"
                                            :disabled="updatingTaskId === task.id"
                                            @change="handleAssigneeChange(task.id, $event)"
                                        >
                                            <option value="">
                                                Neasignat
                                            </option>
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
                                class="px-6 py-8 text-center text-sm text-gray-400"
                            >
                                Niciun task disponibil încă.
                            </li>
                        </ul>
                    </section>
                </div>
                <section
                    v-if="backlogSection"
                    class="flex flex-col rounded-lg bg-white shadow-sm"
                >
                    <header
                        class="border-b border-gray-200 px-6 py-4"
                    >
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ backlogSection.title }}
                        </h3>
                    </header>

                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="task in backlogSection.tasks"
                            :key="task.id"
                            class="flex flex-col gap-2 px-6 py-4"
                            :class="backlogSection.accent"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <h4 class="text-base font-medium text-gray-900">
                                    <Link
                                        :href="route('tasks.show', task.id)"
                                        class="hover:text-blue-600"
                                    >
                                        {{ task.title }}
                                    </Link>
                                </h4>
                                <span
                                    v-if="task.due_date"
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700"
                                >
                                    Scadent: {{ task.due_date }}
                                </span>
                            </div>
                            <p
                                v-if="task.description"
                                class="text-sm text-gray-600"
                            >
                                {{ task.description }}
                            </p>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <div class="flex flex-1 items-center gap-2">
                                    <label
                                        class="text-xs uppercase tracking-wide text-slate-400"
                                    >
                                        Asignat
                                    </label>
                                    <select
                                        class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-200"
                                        :value="task.assignee_id ?? ''"
                                        :disabled="updatingTaskId === task.id"
                                        @change="handleAssigneeChange(task.id, $event)"
                                    >
                                        <option value="">
                                            Neasignat
                                        </option>
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
                            class="px-6 py-8 text-center text-sm text-gray-400"
                        >
                            Niciun task disponibil încă.
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
