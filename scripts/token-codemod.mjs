// One-shot codemod: migrate hardcoded indigo/gray Tailwind classes to the
// editorial-pro design tokens across Vue pages/components. Idempotent.
import { readFileSync, writeFileSync } from 'node:fs';
import { execSync } from 'node:child_process';

const map = [
    // accent
    [/indigo-700/g, 'primary-dark'],
    [/indigo-800/g, 'primary-dark'],
    [/indigo-900/g, 'primary-dark'],
    [/indigo-600/g, 'primary'],
    [/indigo-500/g, 'primary'],
    [/indigo-400/g, 'primary'],
    [/indigo-100/g, 'primary/10'],
    [/indigo-50(?![0-9])/g, 'primary/5'],

    // surfaces
    [/bg-white(?![a-z-])/g, 'bg-surface'],
    [/bg-gray-50(?![0-9])/g, 'bg-surface-muted'],
    [/bg-gray-100(?![0-9])/g, 'bg-surface-sunken'],
    [/bg-gray-200(?![0-9])/g, 'bg-surface-sunken'],
    [/bg-gray-800(?![0-9])/g, 'bg-ink'],
    [/bg-gray-900(?![0-9])/g, 'bg-ink'],

    // text
    [/text-gray-900(?![0-9])/g, 'text-ink'],
    [/text-gray-800(?![0-9])/g, 'text-ink'],
    [/text-gray-700(?![0-9])/g, 'text-ink-muted'],
    [/text-gray-600(?![0-9])/g, 'text-ink-muted'],
    [/text-gray-500(?![0-9])/g, 'text-ink-subtle'],
    [/text-gray-400(?![0-9])/g, 'text-ink-subtle'],

    // borders / divides
    [/border-gray-200(?![0-9])/g, 'border-border'],
    [/border-gray-300(?![0-9])/g, 'border-border'],
    [/border-gray-100(?![0-9])/g, 'border-border'],
    [/divide-gray-200(?![0-9])/g, 'divide-border'],
    [/divide-gray-100(?![0-9])/g, 'divide-border'],

    // hovers / rings
    [/hover:bg-gray-50(?![0-9])/g, 'hover:bg-surface-muted'],
    [/hover:bg-gray-100(?![0-9])/g, 'hover:bg-surface-sunken'],
    [/hover:text-gray-900(?![0-9])/g, 'hover:text-ink'],
    [/hover:text-gray-700(?![0-9])/g, 'hover:text-ink'],
    [/hover:text-gray-600(?![0-9])/g, 'hover:text-ink'],
    [/ring-indigo-500/g, 'ring-primary'],
];

// gather target files via git (portable, respects repo)
const files = execSync('git ls-files "resources/js/Pages/*.vue" "resources/js/Pages/**/*.vue" "resources/js/Components/*.vue"', { encoding: 'utf8' })
    .split('\n').filter(Boolean)
    .filter((f) => !f.includes('/Components/ui/')); // ui lib already tokenized

let changed = 0;
for (const file of files) {
    const before = readFileSync(file, 'utf8');
    let after = before;
    for (const [re, to] of map) after = after.replace(re, to);
    if (after !== before) { writeFileSync(file, after); changed++; }
}
console.log(`codemod: updated ${changed}/${files.length} files`);
