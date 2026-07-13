import { renderToStaticMarkup } from 'react-dom/server';
import { createElement as h } from 'react';
import { writeFileSync, mkdirSync, readFileSync } from 'node:fs';
import * as Kit from '../src/index';

// Each preview: [label, element]. Realistic props, exercised default states.
const previews: Array<[string, React.ReactElement]> = [
  ['Hero', h(Kit.Hero, { image: 'https://picsum.photos/seed/insole/560/620' })],
  ['Hero-minimal', h(Kit.Hero, { layout: 'minimal', headlineStyle: 'editorial' })],
  ['Feature', h(Kit.Feature, { image: 'https://picsum.photos/seed/feat/520/420' })],
  ['Feature-iconlist', h(Kit.Feature, { layout: 'icon-list', imageSide: 'right', image: 'https://picsum.photos/seed/f2/520/420', bullets: [{ text: 'Riktad stötdämpning', desc: 'Där du behöver den' }, { text: 'Förstärkt sidostabilitet' }, { text: 'Padelanpassad biomekanik' }], ctaText: 'Läs mer' })],
];

const css = readFileSync(new URL('../src/styles/nv-kit.css', import.meta.url), 'utf8');
mkdirSync(new URL('./out/', import.meta.url), { recursive: true });

for (const [label, el] of previews) {
  const body = renderToStaticMarkup(el);
  const html = `<!doctype html><html><head><meta charset="utf8"><style>${css}\nbody{margin:0;padding:24px;background:#fff;font-family:'Manrope',sans-serif;}</style></head><body>${body}</body></html>`;
  writeFileSync(new URL(`./out/${label}.html`, import.meta.url), html);
  console.log('wrote', label);
}
