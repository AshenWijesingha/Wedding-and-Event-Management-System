<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, Button, TextField, TextareaField, SelectField, Alert } from '@/Components/ui';

const props = defineProps({
    progress: Object, // { branding, venue, package, team, completed }
    tenant: Object,
});

const page = usePage();

const steps = [
    { key: 'branding', label: 'Branding',  hint: 'Make it yours' },
    { key: 'venue',    label: 'First venue', hint: 'Your inventory' },
    { key: 'package',  label: 'First package', hint: 'What you sell' },
    { key: 'team',     label: 'Invite team', hint: 'Bring others in' },
];

// Start on the first incomplete step; user can click the rail to revisit any step.
const firstIncomplete = steps.findIndex((s) => ! props.progress[s.key]);
const current = ref(firstIncomplete === -1 ? steps.length : firstIncomplete);
const currentKey = computed(() => steps[current.value]?.key ?? 'done');

const brandingForm = useForm({
    name: props.tenant?.name ?? '',
    company_name: '',
    tagline: '',
    primary_color: props.tenant?.primary_color ?? '#6366f1',
    logo_url: '',
});
const venueForm = useForm({ name: '', capacity_min: 10, capacity_max: 100, base_price: '', description: '' });
const packageForm = useForm({ name: '', base_price: '', min_guests: null, max_guests: null, description: '' });
const teamForm = useForm({ name: '', email: '', role: 'manager' });

const opts = { preserveScroll: true };
const submitBranding = () => brandingForm.post('/admin/onboarding/branding', opts);
const submitVenue = () => venueForm.post('/admin/onboarding/venue', { ...opts, onSuccess: () => venueForm.reset() });
const submitPackage = () => packageForm.post('/admin/onboarding/package', { ...opts, onSuccess: () => packageForm.reset() });
const submitTeam = () => teamForm.post('/admin/onboarding/invite', { ...opts, onSuccess: () => teamForm.reset() });
const finish = () => router.post('/admin/onboarding/finish');

const invitedPassword = computed(() => page.props.flash?.invited_password);
const invitedEmail = computed(() => page.props.flash?.invited_email);
</script>

<template>
    <AppLayout title="Set up your workspace">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-subtle">Welcome to EventPro</p>
                <h2 class="font-display text-3xl font-semibold text-ink mt-1">Let's set up your workspace</h2>
                <p class="text-sm text-ink-muted mt-2">A few quick steps and you're ready to take bookings.</p>
            </div>

            <!-- Stepper rail -->
            <div class="flex items-center justify-between gap-2">
                <button v-for="(s, i) in steps" :key="s.key" type="button" @click="current = i"
                    class="flex-1 flex flex-col items-center gap-1.5 group">
                    <span :class="[
                        'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition',
                        progress[s.key] ? 'bg-success text-white border-success'
                            : current === i ? 'bg-primary text-white border-primary'
                            : 'bg-surface text-ink-subtle border-border group-hover:border-primary'
                    ]">
                        <svg v-if="progress[s.key]" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span v-else>{{ i + 1 }}</span>
                    </span>
                    <span :class="['text-xs font-medium', current === i ? 'text-ink' : 'text-ink-subtle']">{{ s.label }}</span>
                </button>
            </div>

            <!-- Branding -->
            <Card v-if="currentKey === 'branding'">
                <h3 class="font-display text-lg font-semibold text-ink mb-1">Brand your workspace</h3>
                <p class="text-sm text-ink-muted mb-4">This appears across your portal, quotations and receipts.</p>
                <div class="grid grid-cols-2 gap-4">
                    <TextField class="col-span-2" v-model="brandingForm.name" label="Business name" required :error="brandingForm.errors.name" />
                    <TextField v-model="brandingForm.company_name" label="Display name" :error="brandingForm.errors.company_name" hint="Defaults to business name" />
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Primary color</label>
                        <div class="flex items-center gap-2">
                            <input v-model="brandingForm.primary_color" type="color" class="h-10 w-12 rounded-lg border-border cursor-pointer" />
                            <input v-model="brandingForm.primary_color" type="text" class="flex-1 rounded-lg border-border text-sm focus:border-primary focus:ring-primary" />
                        </div>
                    </div>
                    <TextField class="col-span-2" v-model="brandingForm.tagline" label="Tagline" :error="brandingForm.errors.tagline" />
                    <TextField class="col-span-2" v-model="brandingForm.logo_url" label="Logo URL" type="url" placeholder="https://..." :error="brandingForm.errors.logo_url" />
                </div>
                <div class="flex justify-between items-center mt-5">
                    <button type="button" class="text-sm text-ink-subtle hover:text-ink" @click="finish">Skip for now</button>
                    <Button :loading="brandingForm.processing" @click="submitBranding">Save &amp; continue</Button>
                </div>
            </Card>

            <!-- Venue -->
            <Card v-else-if="currentKey === 'venue'">
                <h3 class="font-display text-lg font-semibold text-ink mb-1">Add your first venue</h3>
                <p class="text-sm text-ink-muted mb-4">Venues are the spaces you book out for events.</p>
                <div class="grid grid-cols-2 gap-4">
                    <TextField class="col-span-2" v-model="venueForm.name" label="Venue name" required :error="venueForm.errors.name" />
                    <TextField v-model="venueForm.capacity_min" label="Min capacity" type="number" required :error="venueForm.errors.capacity_min" />
                    <TextField v-model="venueForm.capacity_max" label="Max capacity" type="number" required :error="venueForm.errors.capacity_max" />
                    <TextField class="col-span-2" v-model="venueForm.base_price" label="Base price (LKR)" type="number" step="0.01" required :error="venueForm.errors.base_price" />
                    <TextareaField class="col-span-2" v-model="venueForm.description" label="Description" :rows="2" :error="venueForm.errors.description" />
                </div>
                <div class="flex justify-between items-center mt-5">
                    <button type="button" class="text-sm text-ink-subtle hover:text-ink" @click="finish">Skip for now</button>
                    <Button :loading="venueForm.processing" @click="submitVenue">Save &amp; continue</Button>
                </div>
            </Card>

            <!-- Package -->
            <Card v-else-if="currentKey === 'package'">
                <h3 class="font-display text-lg font-semibold text-ink mb-1">Create a package</h3>
                <p class="text-sm text-ink-muted mb-4">Packages bundle your services into something clients can buy.</p>
                <div class="grid grid-cols-2 gap-4">
                    <TextField class="col-span-2" v-model="packageForm.name" label="Package name" required :error="packageForm.errors.name" />
                    <TextField class="col-span-2" v-model="packageForm.base_price" label="Base price (LKR)" type="number" step="0.01" required :error="packageForm.errors.base_price" />
                    <TextField v-model="packageForm.min_guests" label="Min guests" type="number" :error="packageForm.errors.min_guests" />
                    <TextField v-model="packageForm.max_guests" label="Max guests" type="number" :error="packageForm.errors.max_guests" />
                    <TextareaField class="col-span-2" v-model="packageForm.description" label="Description" :rows="2" :error="packageForm.errors.description" />
                </div>
                <div class="flex justify-between items-center mt-5">
                    <button type="button" class="text-sm text-ink-subtle hover:text-ink" @click="finish">Skip for now</button>
                    <Button :loading="packageForm.processing" @click="submitPackage">Save &amp; continue</Button>
                </div>
            </Card>

            <!-- Team -->
            <Card v-else-if="currentKey === 'team'">
                <h3 class="font-display text-lg font-semibold text-ink mb-1">Invite a team member</h3>
                <p class="text-sm text-ink-muted mb-4">Give a colleague login access. They'll get a temporary password to change on first sign-in.</p>

                <Alert v-if="invitedPassword" variant="success" class="mb-4">
                    Invited <strong>{{ invitedEmail }}</strong>. Temporary password (shown once):
                    <code class="font-mono font-semibold">{{ invitedPassword }}</code> — share it securely.
                </Alert>

                <div class="grid grid-cols-2 gap-4">
                    <TextField v-model="teamForm.name" label="Name" required :error="teamForm.errors.name" />
                    <TextField v-model="teamForm.email" label="Email" type="email" required :error="teamForm.errors.email" />
                    <SelectField class="col-span-2" v-model="teamForm.role" label="Role" :error="teamForm.errors.role">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="staff">Staff</option>
                    </SelectField>
                </div>
                <div class="flex justify-between items-center mt-5">
                    <button type="button" class="text-sm text-ink-subtle hover:text-ink" @click="finish">Skip for now</button>
                    <div class="flex gap-2">
                        <Button variant="secondary" :loading="teamForm.processing" @click="submitTeam">Send invite</Button>
                        <Button @click="finish">Finish</Button>
                    </div>
                </div>
            </Card>

            <!-- Done -->
            <Card v-else>
                <div class="text-center py-6">
                    <div class="mx-auto w-12 h-12 rounded-full bg-success-soft text-success flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold text-ink">You're all set!</h3>
                    <p class="text-sm text-ink-muted mt-1">Your workspace is ready. Jump into the dashboard to start taking bookings.</p>
                    <Button class="mt-5" @click="finish">Go to dashboard</Button>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
