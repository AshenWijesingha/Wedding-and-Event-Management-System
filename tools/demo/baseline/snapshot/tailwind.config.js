/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    dark: 'var(--color-primary-dark)',
                    light: 'var(--color-primary-light)',
                },
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                    dark: 'var(--color-secondary-dark)',
                    light: 'var(--color-secondary-light)',
                },
                // Static editorial-pro neutrals (not tenant-overridden)
                surface: {
                    DEFAULT: 'var(--surface)',
                    muted: 'var(--surface-muted)',
                    sunken: 'var(--surface-sunken)',
                },
                border: {
                    DEFAULT: 'var(--border)',
                    strong: 'var(--border-strong)',
                },
                ink: {
                    DEFAULT: 'var(--ink)',
                    muted: 'var(--ink-muted)',
                    subtle: 'var(--ink-subtle)',
                },
                success: { DEFAULT: 'var(--success)', soft: 'var(--success-soft)' },
                warning: { DEFAULT: 'var(--warning)', soft: 'var(--warning-soft)' },
                danger:  { DEFAULT: 'var(--danger)',  soft: 'var(--danger-soft)' },
                info:    { DEFAULT: 'var(--info)',    soft: 'var(--info-soft)' },
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                display: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
            },
            borderRadius: {
                md: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
