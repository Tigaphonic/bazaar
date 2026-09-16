// Bazaar Shell JS build (Story 1.2, AD-33). Minifies resources/js/shell.js to
// resources/dist/shell.js via esbuild. Dev-tooling only -- run once at
// Bazaar's own release time (`npm run build`) and the output committed; never
// invoked by bazaar:install/bazaar:status or the test suite.
import { build } from 'esbuild';

await build({
    entryPoints: ['resources/js/shell.js'],
    outfile: 'resources/dist/shell.js',
    bundle: false,
    minify: true,
    format: 'iife',
    target: ['es2019'],
    legalComments: 'none',
});

console.log('Built resources/dist/shell.js');
