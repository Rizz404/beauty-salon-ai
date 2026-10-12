import assert from 'node:assert/strict';
import test from 'node:test';

import { renderChatText } from '../../resources/js/shared/chat-text.js';

test('plain text becomes a paragraph, and line breaks are kept', () => {
    assert.equal(renderChatText('Halo!\nApa kabar?'), '<p>Halo!<br>Apa kabar?</p>');
    assert.equal(renderChatText('Satu\n\nDua'), '<p>Satu</p><p>Dua</p>');
});

test('bold and italic are rendered', () => {
    assert.equal(renderChatText('Coba **Balayage** yang *lembut*'), '<p>Coba <strong>Balayage</strong> yang <em>lembut</em></p>');
});

test('bullet and numbered lists become real lists', () => {
    assert.equal(renderChatText('Pilihan:\n- **Gel Manicure** (Rp220.000)\n- Scalp Spa'), '<p>Pilihan:</p><ul><li><strong>Gel Manicure</strong> (Rp220.000)</li><li>Scalp Spa</li></ul>');
    assert.equal(renderChatText('1. Satu\n2) Dua'), '<ol><li>Satu</li><li>Dua</li></ol>');
});

test('headings, code and rules do not leak symbols', () => {
    assert.equal(renderChatText('## Layanan\n---\n```\n`rambut`\n```'), '<p><strong>Layanan</strong></p><p>rambut</p>');
});

test('markup from the model is escaped', () => {
    assert.equal(renderChatText('<img src=x onerror=alert(1)> **b**'), '<p>&lt;img src=x onerror=alert(1)&gt; <strong>b</strong></p>');
});
