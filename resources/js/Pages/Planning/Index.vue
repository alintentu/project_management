<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref, watch } from 'vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
    selectedProjectId: {
        type: [Number, String, null],
        default: null,
    },
    wbsTree: {
        type: Array,
        default: () => [],
    },
    timeline: {
        type: Array,
        default: () => [],
    },
    resources: {
        type: Array,
        default: () => [],
    },
    siteLogs: {
        type: Array,
        default: () => [],
    },
});

const selectedProjectId = ref(props.selectedProjectId);

watch(
    () => props.selectedProjectId,
    (value) => {
        selectedProjectId.value = value;
        createForm.project_id = value;
    }
);

const selectedProject = computed(() => {
    if (!selectedProjectId.value) {
        return null;
    }

    return props.projects.find((project) => Number(project.id) === Number(selectedProjectId.value)) ?? null;
});

const createForm = useForm({
    project_id: props.selectedProjectId,
    parent_id: '',
    name: '',
    code: '',
    phase_type: '',
    planned_start_date: '',
    planned_end_date: '',
    description: '',
});

const flatWbsOptions = computed(() => {
    const options = [];

    const traverse = (nodes, depth = 0) => {
        nodes.forEach((node) => {
            options.push({
                id: node.id,
                label: `${'· '.repeat(depth)}${node.name}`,
            });

            if (node.children?.length) {
                traverse(node.children, depth + 1);
            }
        });
    };

    traverse(props.wbsTree);

    return options;
});

const selectProject = (id) => {
    if (Number(id) === Number(selectedProjectId.value)) {
        return;
    }

    router.get(route('planning'), { project_id: id ?? '' }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleProjectChange = (event) => {
    const id = event.target.value || null;
    selectProject(id);
};

const submitCreate = () => {
    createForm.post(route('wbs.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'code', 'phase_type', 'planned_start_date', 'planned_end_date', 'description');
            createForm.parent_id = '';
        },
    });
};

const deleteNode = (nodeId) => {
    if (!confirm('Sigur dorești să ștergi această structură WBS?')) {
        return;
    }

    router.delete(route('wbs.destroy', nodeId), {
        preserveScroll: true,
    });
};

const WbsNode = defineComponent({
    name: 'WbsNode',
    props: {
        node: {
            type: Object,
            required: true,
        },
    },
    emits: ['delete-node'],
    setup(props, { emit }) {
        const renderTasks = () => {
            if (!props.node.tasks?.length) {
                return h('p', { class: 'mt-2 text-xs text-gray-400' }, 'Nu există taskuri asociate.');
            }

            return h(
                'ul',
                { class: 'mt-2 space-y-1 text-sm text-gray-600' },
                props.node.tasks.map((task) =>
                    h('li', { key: task.id, class: 'flex items-center justify-between rounded bg-white px-2 py-1' }, [
                        h('span', null, task.title),
                        h('span', { class: 'text-xs text-gray-400' }, `${task.progress_percent ?? 0}%`),
                    ])
                )
            );
        };

        return () =>
            h('div', { class: 'space-y-3' }, [
                h('div', { class: 'rounded border border-slate-200 p-4' }, [
                    h('div', { class: 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between' }, [
                        h('div', { class: 'space-y-1' }, [
                            h('p', { class: 'font-medium text-gray-900 flex items-center gap-2' }, [
                                h('span', { class: 'text-lg' }, '🧱'),
                                h('span', null, props.node.name),
                            ]),
                            props.node.code
                                ? h('p', { class: 'text-xs uppercase tracking-wide text-gray-400' }, `Cod: ${props.node.code}`)
                                : null,
                            h('p', { class: 'text-xs text-gray-500' }, [
                                props.node.phase_type ? `Fază: ${props.node.phase_type}` : 'Fără tip definit',
                                props.node.planned_start_date || props.node.planned_end_date
                                    ? ` • ${props.node.planned_start_date ?? '?'} → ${props.node.planned_end_date ?? '?'}`
                                    : null,
                            ]),
                            renderTasks(),
                        ]),
                        h(
                            'button',
                            {
                                class: 'self-start rounded border border-transparent px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-500',
                                onClick: () => emit('delete-node', props.node.id),
                            },
                            'Șterge'
                        ),
                    ]),
                ]),
                props.node.children?.length
                    ? h(
                          'div',
                          { class: 'ml-3 border-l-2 border-dashed border-slate-200 pl-3' },
                          props.node.children.map((child) =>
                              h(WbsNode, {
                                  key: child.id,
                                  node: child,
                                  onDeleteNode: (payload) => emit('delete-node', payload),
                              })
                          )
                      )
                    : null,
            ]);
    },
});
</script>

<template>
    <Head title="Planificare" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight text-gray-800">
                        Planificare & execuție
                    </h2>
                    <p class="text-sm text-gray-500">
                        Vizualizează structura WBS, resursele și jurnalul zilnic pentru proiectul selectat.
                    </p>
                    <p
                        v-if="selectedProject"
                        class="mt-1 text-xs text-gray-500"
                    >
                        Proiect curent: <span class="font-semibold text-gray-700">{{ selectedProject.name }}</span>
                        — Progres {{ selectedProject.progress_percent ?? 0 }}% • {{ selectedProject.status }}
                    </p>
                </div>
                <div class="sm:w-64">
                    <label class="sr-only" for="project">Selectează proiectul</label>
                    <select
                        id="project"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        :value="selectedProjectId ?? ''"
                        @change="handleProjectChange"
                    >
                        <option value="">Selectează proiect</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id"
                        >
                            {{ project.code ? `${project.code} — ${project.name}` : project.name }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="space-y-6 px-4 sm:px-6 lg:px-10">
                <section
                    v-if="projects.length"
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <article
                        v-for="project in projects"
                        :key="project.id"
                        class="cursor-pointer rounded-lg border p-4 shadow-sm transition"
                        :class="Number(project.id) === Number(selectedProjectId) ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'"
                        @click="selectProject(project.id)"
                    >
                        <div class="flex items-center justify-between text-xs uppercase tracking-wide text-gray-400">
                            <span>{{ project.code || 'Project' }}</span>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                {{ project.status }}
                            </span>
                        </div>
                        <h4 class="mt-1 text-base font-semibold text-gray-900">
                            {{ project.name }}
                        </h4>

                        <dl class="mt-3 grid gap-2 text-xs text-gray-600">
                            <div class="flex items-center justify-between">
                                <dt>Taskuri</dt>
                                <dd>{{ project.completed_tasks_count }} / {{ project.tasks_count }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>Planificat</dt>
                                <dd>
                                    {{ project.planned_start_date || '—' }} → {{ project.planned_end_date || '—' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>Real</dt>
                                <dd>
                                    {{ project.actual_start_date || '—' }} → {{ project.actual_end_date || '—' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Progres</span>
                                <span>{{ project.progress_percent ?? 0 }}%</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100">
                                <div
                                    class="h-2 rounded-full bg-blue-500"
                                    :style="{ width: `${Math.min(100, Math.max(0, project.progress_percent ?? 0))}%` }"
                                ></div>
                            </div>
                        </div>
                    </article>
                </section>

                <div v-if="!selectedProjectId" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                    Selectează un proiect pentru a vedea detaliile de planificare.
                </div>

                <div v-else class="grid gap-6 lg:grid-cols-3">
                    <section class="lg:col-span-2 space-y-6 rounded-lg bg-white p-6 shadow-sm">
                        <header class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Structură WBS</h3>
                                <p class="text-sm text-gray-500">
                                    Faze, sub-faze și taskuri asociate proiectului.
                                </p>
                            </div>
                        </header>

                        <form class="grid gap-4 rounded border border-slate-200 p-4" @submit.prevent="submitCreate">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                Adaugă element WBS
                            </h4>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-name">Nume</label>
                                    <input
                                        id="wbs-name"
                                        v-model="createForm.name"
                                        type="text"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        required
                                    />
                                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-500">
                                        {{ createForm.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-code">Cod</label>
                                    <input
                                        id="wbs-code"
                                        v-model="createForm.code"
                                        type="text"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="createForm.errors.code" class="mt-1 text-xs text-red-500">
                                        {{ createForm.errors.code }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-phase">Tip / fază</label>
                                    <input
                                        id="wbs-phase"
                                        v-model="createForm.phase_type"
                                        type="text"
                                        placeholder="ex: structura, instalatii"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-parent">Părinte</label>
                                    <select
                                        id="wbs-parent"
                                        v-model="createForm.parent_id"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="">(Nivel superior)</option>
                                        <option v-for="option in flatWbsOptions" :key="option.id" :value="option.id">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-start">Start planificat</label>
                                    <input
                                        id="wbs-start"
                                        v-model="createForm.planned_start_date"
                                        type="date"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500" for="wbs-end">Final planificat</label>
                                    <input
                                        id="wbs-end"
                                        v-model="createForm.planned_end_date"
                                        type="date"
                                        class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="createForm.errors.planned_end_date" class="mt-1 text-xs text-red-500">
                                        {{ createForm.errors.planned_end_date }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500" for="wbs-description">Descriere</label>
                                <textarea
                                    id="wbs-description"
                                    v-model="createForm.description"
                                    rows="2"
                                    class="mt-1 block w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                    :disabled="createForm.processing"
                                >
                                    Adaugă
                                </button>
                            </div>
                        </form>

                        <div class="space-y-4">
                            <template v-if="wbsTree.length">
                                <WbsNode
                                    v-for="node in wbsTree"
                                    :key="node.id"
                                    :node="node"
                                    @delete-node="deleteNode"
                                />
                            </template>
                            <p v-else class="text-sm text-gray-400">
                                Încă nu există structuri WBS pentru acest proiect.
                            </p>
                        </div>
                    </section>

                    <section class="space-y-6 rounded-lg bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Resurse disponibile</h3>
                            <p class="text-sm text-gray-500">Echipe și utilaje asignate proiectului.</p>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li
                                    v-for="resource in resources"
                                    :key="resource.id"
                                    class="rounded border border-slate-200 px-3 py-2"
                                >
                                    <p class="font-medium text-gray-900">
                                        {{ resource.name }}
                                        <span class="text-xs uppercase text-gray-400">
                                            {{ resource.type }}
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Capacitate: {{ resource.capacity ?? '—' }} | Tarif: {{ resource.cost_rate ? `${resource.cost_rate} / ${resource.cost_rate_unit}` : 'N/A' }}
                                    </p>
                                </li>
                                <li v-if="resources.length === 0" class="rounded border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-gray-400">
                                    Nu sunt definite resurse încă.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Jurnal de șantier</h3>
                            <p class="text-sm text-gray-500">Rezumatul ultimelor înregistrări zilnice.</p>
                            <ul class="mt-4 space-y-3 text-sm">
                                <li
                                    v-for="log in siteLogs"
                                    :key="log.id"
                                    class="rounded border border-slate-200 px-3 py-2"
                                >
                                    <p class="font-medium text-gray-900">
                                        {{ log.date }} — {{ log.weather || 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Progres: {{ log.progress_percent ?? 0 }}% | Personal: {{ log.manpower_count ?? 'N/A' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        {{ log.summary || 'Fără detalii' }}
                                    </p>
                                </li>
                                <li v-if="siteLogs.length === 0" class="rounded border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-gray-400">
                                    Nu există înregistrări de jurnal.
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>

                <section v-if="timeline.length" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Timeline planificat</h3>
                    <p class="text-sm text-gray-500">Vizualizare simplă a intervalelor planificate pentru taskuri.</p>

                    <div class="mt-4 overflow-x-auto">
                        <div class="min-w-full space-y-1">
                            <TimelineRow
                                v-for="item in timeline"
                                :key="item.id"
                                :item="item"
                            />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { defineComponent, h, watch } from 'vue';
import { useForm as useInertiaForm } from '@inertiajs/vue3';

const WbsNode = defineComponent({
    name: 'WbsNode',
    props: {
        node: {
            type: Object,
            required: true,
        },
    },
    emits: ['delete-node'],
    setup(props, { emit }) {
        const renderTasks = () => {
            if (!props.node.tasks?.length) {
                return h('p', { class: 'mt-2 text-xs text-gray-400' }, 'Nu există taskuri asociate.');
            }

            return h(
                'ul',
                { class: 'mt-2 space-y-1 text-sm text-gray-600' },
                props.node.tasks.map((task) =>
                    h('li', { key: task.id, class: 'flex items-center justify-between rounded bg-white px-2 py-1' }, [
                        h('span', null, task.title),
                        h('span', { class: 'text-xs text-gray-400' }, `${task.progress_percent ?? 0}%`),
                    ])
                )
            );
        };

        return () =>
            h('div', { class: 'space-y-3' }, [
                h('div', { class: 'rounded border border-slate-200 p-4' }, [
                    h('div', { class: 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between' }, [
                        h('div', { class: 'space-y-1' }, [
                            h('p', { class: 'font-medium text-gray-900 flex items-center gap-2' }, [
                                h('span', { class: 'text-lg' }, '🧱'),
                                h('span', null, props.node.name),
                            ]),
                            props.node.code
                                ? h('p', { class: 'text-xs uppercase tracking-wide text-gray-400' }, `Cod: ${props.node.code}`)
                                : null,
                            h('p', { class: 'text-xs text-gray-500' }, [
                                props.node.phase_type ? `Fază: ${props.node.phase_type}` : 'Fără tip definit',
                                props.node.planned_start_date || props.node.planned_end_date
                                    ? ` • ${props.node.planned_start_date ?? '?'} → ${props.node.planned_end_date ?? '?'}`
                                    : null,
                            ]),
                            renderTasks(),
                        ]),
                        h(
                            'button',
                            {
                                class: 'self-start rounded border border-transparent px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-500',
                                onClick: () => emit('delete-node', props.node.id),
                            },
                            'Șterge'
                        ),
                    ]),
                ]),
                props.node.children?.length
                    ? h(
                          'div',
                          { class: 'ml-3 border-l-2 border-dashed border-slate-200 pl-3' },
                          props.node.children.map((child) =>
                              h(WbsNode, {
                                  key: child.id,
                                  node: child,
                                  onDeleteNode: (payload) => emit('delete-node', payload),
                              })
                          )
                      )
                    : null,
            ]);
    },
});

const TimelineRow = defineComponent({
    name: 'TimelineRow',
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        const dayWidth = 16;
        const parseDate = (dateString) => (dateString ? new Date(dateString) : null);
        const plannedStart = parseDate(props.item.planned_start_date);
        const plannedEnd = parseDate(props.item.planned_end_date);
        const actualStart = parseDate(props.item.actual_start_date);
        const actualEnd = parseDate(props.item.actual_end_date);
        const durationDays = plannedStart && plannedEnd
            ? Math.max(1, Math.round((plannedEnd - plannedStart) / (1000 * 60 * 60 * 24)))
            : 1;
        const barWidth = durationDays * dayWidth;

        const form = useInertiaForm({
            planned_start_date: props.item.planned_start_date ?? '',
            planned_end_date: props.item.planned_end_date ?? '',
            actual_start_date: props.item.actual_start_date ?? '',
            actual_end_date: props.item.actual_end_date ?? '',
            progress_percent: props.item.progress_percent ?? 0,
        });

        watch(
            () => props.item,
            (value) => {
                form.planned_start_date = value.planned_start_date ?? '';
                form.planned_end_date = value.planned_end_date ?? '';
                form.actual_start_date = value.actual_start_date ?? '';
                form.actual_end_date = value.actual_end_date ?? '';
                form.progress_percent = value.progress_percent ?? 0;
            },
            { deep: true }
        );

        const submit = () => {
            form.patch(route('tasks.schedule', props.item.id), {
                preserveScroll: true,
            });
        };

        return () =>
            h('div', { class: 'rounded border border-slate-100 p-3 text-sm' }, [
                h('div', { class: 'flex items-center justify-between text-xs text-gray-500' }, [
                    h('span', { class: 'font-semibold text-gray-700' }, props.item.title),
                    h('span', null, props.item.wbs_name || '—'),
                ]),
                h('div', { class: 'mt-2 flex items-center gap-3' }, [
                    h('div', { class: 'flex-1 overflow-hidden rounded bg-slate-100' }, [
                        plannedStart && plannedEnd
                            ? h('div', {
                                  class: 'relative flex items-center rounded bg-sky-400 text-xs text-white shadow-inner',
                                  style: {
                                      width: `${barWidth}px`,
                                      minWidth: '48px',
                                      padding: '0.25rem 0.5rem',
                                      marginLeft: '0',
                                  },
                              }, [
                                  h('span', null, `${props.item.planned_start_date ?? '?'} → ${props.item.planned_end_date ?? '?'} (${durationDays} zile)`),
                              ])
                            : h('span', { class: 'px-2 py-1 text-xs text-slate-500' }, 'Fără date planificate'),
                    ]),
                    h('div', { class: 'text-xs text-gray-500' }, `${props.item.progress_percent ?? 0}%`),
                ]),
                h('form', {
                    class: 'mt-3 grid gap-3 md:grid-cols-5',
                    onSubmit: (event) => {
                        event.preventDefault();
                        submit();
                    },
                }, [
                    h('div', { class: 'flex flex-col gap-1' }, [
                        h('label', { class: 'text-xs font-medium text-gray-500' }, 'Start planificat'),
                        h('input', {
                            type: 'date',
                            class: 'rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
                            value: form.planned_start_date,
                            onInput: (event) => (form.planned_start_date = event.target.value),
                        }),
                    ]),
                    h('div', { class: 'flex flex-col gap-1' }, [
                        h('label', { class: 'text-xs font-medium text-gray-500' }, 'Final planificat'),
                        h('input', {
                            type: 'date',
                            class: 'rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
                            value: form.planned_end_date,
                            onInput: (event) => (form.planned_end_date = event.target.value),
                        }),
                        form.errors.planned_end_date
                            ? h('p', { class: 'text-xs text-red-500' }, form.errors.planned_end_date)
                            : null,
                    ]),
                    h('div', { class: 'flex flex-col gap-1' }, [
                        h('label', { class: 'text-xs font-medium text-gray-500' }, 'Start actual'),
                        h('input', {
                            type: 'date',
                            class: 'rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
                            value: form.actual_start_date,
                            onInput: (event) => (form.actual_start_date = event.target.value),
                        }),
                    ]),
                    h('div', { class: 'flex flex-col gap-1' }, [
                        h('label', { class: 'text-xs font-medium text-gray-500' }, 'Final actual'),
                        h('input', {
                            type: 'date',
                            class: 'rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
                            value: form.actual_end_date,
                            onInput: (event) => (form.actual_end_date = event.target.value),
                        }),
                        form.errors.actual_end_date
                            ? h('p', { class: 'text-xs text-red-500' }, form.errors.actual_end_date)
                            : null,
                    ]),
                    h('div', { class: 'flex flex-col gap-1' }, [
                        h('label', { class: 'text-xs font-medium text-gray-500' }, 'Progres %'),
                        h('input', {
                            type: 'number',
                            min: 0,
                            max: 100,
                            step: 1,
                            class: 'rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
                            value: form.progress_percent,
                            onInput: (event) => (form.progress_percent = event.target.value),
                        }),
                        form.errors.progress_percent
                            ? h('p', { class: 'text-xs text-red-500' }, form.errors.progress_percent)
                            : null,
                    ]),
                    h('div', { class: 'md:col-span-5 flex justify-end pt-2' }, [
                        h('button', {
                            type: 'submit',
                            class: 'inline-flex items-center rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50',
                            disabled: form.processing,
                        }, 'Salvează'),
                    ]),
                ]),
                actualStart && actualEnd
                    ? h('p', { class: 'mt-1 text-xs text-emerald-600' }, `Real: ${props.item.actual_start_date ?? '?'} → ${props.item.actual_end_date ?? '?'}`)
                    : null,
            ]);
    },
});

export default {
    components: {
        WbsNode,
        TimelineRow,
    },
};
</script>
