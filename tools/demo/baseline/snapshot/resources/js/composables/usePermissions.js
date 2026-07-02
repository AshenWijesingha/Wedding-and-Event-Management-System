import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Frontend permission/role helpers backed by the props shared from
 * HandleInertiaRequests (`auth.permissions`, `auth.roles`). Use these to show or
 * hide UI only — server-side middleware and policies are the real gate.
 *
 * Usage:
 *   const { can, hasRole, isSuperAdmin } = usePermissions();
 *   <button v-if="can('venues.delete')">Delete</button>
 */
export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => page.props.auth?.permissions ?? []);
    const roles = computed(() => page.props.auth?.roles ?? []);

    const can = (permission) => !permission || permissions.value.includes(permission);
    const canAny = (list = []) => list.some((p) => permissions.value.includes(p));
    const hasRole = (role) => roles.value.includes(role);
    const isSuperAdmin = computed(() => roles.value.includes('super_admin'));

    return { permissions, roles, can, canAny, hasRole, isSuperAdmin };
}
