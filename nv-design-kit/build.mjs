import { build } from 'esbuild';

// Bundle every component into one ESM entry. React is external — the
// design-sync converter re-bundles this into an IIFE on window.NVKit and
// supplies React from its own _vendor bundle. CSS is NOT imported from JS:
// the real stylesheet ships via the converter's cssEntry (src/styles/nv-kit.css).
await build({
  entryPoints: ['src/index.ts'],
  outfile: 'dist/index.es.js',
  bundle: true,
  format: 'esm',
  platform: 'browser',
  target: 'es2020',
  jsx: 'automatic',
  external: ['react', 'react-dom', 'react/jsx-runtime'],
  logLevel: 'info',
});

console.log('built dist/index.es.js');
