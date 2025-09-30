<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: props.roles[0]?.name ?? '',
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message ?? null);

const formatRoleLabel = (role) => {
    if (!role) {
        return '';
    }

    const formatted = role.replace(/_/g, ' ');
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
};

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email', 'password', 'password_confirmation');
            form.role = props.roles[0]?.name ?? '';
        },
    });
};
</script>

<template>
    <Head title="Utilizatori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight text-gray-800">
                        Utilizatori
                    </h2>
                    <p class="text-sm text-gray-500">
                        Creează utilizatori noi și atribuie roluri profesionale.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="space-y-6 px-4 sm:px-6 lg:px-10">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div
                        v-if="flashMessage"
                        class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700"
                    >
                        {{ flashMessage }}
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900">
                        Adaugă utilizator
                    </h3>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="name">
                                    Nume
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="email">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="password">
                                    Parolă
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="password_confirmation">
                                    Confirmă parola
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="role">
                                    Rol
                                </label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.name"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.role }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Creează utilizator
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Utilizatori existenți
                    </h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium uppercase tracking-wide text-gray-500">
                                        Nume
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium uppercase tracking-wide text-gray-500">
                                        Email
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium uppercase tracking-wide text-gray-500">
                                        Roluri
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium uppercase tracking-wide text-gray-500">
                                        Creat la
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in users" :key="user.id">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ user.name }}
                                        <span
                                            v-if="user.roles.length > 0"
                                            class="text-sm font-normal text-gray-500"
                                        >
                                            ({{ formatRoleLabel(user.roles[0]) }})
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <span
                                            v-if="user.roles.length === 0"
                                            class="text-gray-400"
                                        >
                                            --
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex flex-wrap gap-2"
                                        >
                                            <span
                                                v-for="role in user.roles"
                                                :key="role"
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700"
                                            >
                                                {{ formatRoleLabel(role) }}
                                            </span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ user.created_at || '—' }}
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                        Niciun utilizator creat încă.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
