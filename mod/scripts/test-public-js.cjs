'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '..');

class FakeElement {
    constructor(tagName) {
        this.style = Object.create(null);
        this.tagName = String(tagName || 'div').toUpperCase();
        this.attributes = Object.create(null);
        this.children = [];
        this.parentNode = null;
        this.listeners = Object.create(null);
        this.hidden = false;
        this.textContent = '';
        this.className = '';
        this.alt = '';
        this.src = '';
        this.width = 0;
        this.height = 0;
        this.drawCalls = [];
        this.context = {
            clearRect() {},
            drawImage: (...args) => this.drawCalls.push(args),
        };
        this.files = [];
    }

    get nextSibling() {
        if (!this.parentNode) {
            return null;
        }
        const siblings = this.parentNode.children;
        return siblings[siblings.indexOf(this) + 1] || null;
    }

    setAttribute(name, value) {
        this.attributes[name] = String(value);
    }

    getAttribute(name) {
        return Object.hasOwn(this.attributes, name) ? this.attributes[name] : null;
    }

    removeAttribute(name) {
        delete this.attributes[name];
    }

    getContext(type) {
        return this.tagName === 'CANVAS' && type === '2d' ? this.context : null;
    }

    addEventListener(name, callback) {
        this.listeners[name] = callback;
    }

    dispatch(name) {
        if (this.listeners[name]) {
            this.listeners[name].call(this, { target: this });
        }
    }

    appendChild(child) {
        if (child.parentNode) {
            child.parentNode.removeChild(child);
        }
        this.children.push(child);
        child.parentNode = this;
        return child;
    }

    insertBefore(child, reference) {
        if (child.parentNode) {
            child.parentNode.removeChild(child);
        }
        const index = reference ? this.children.indexOf(reference) : -1;
        if (index < 0) {
            this.children.push(child);
        } else {
            this.children.splice(index, 0, child);
        }
        child.parentNode = this;
        return child;
    }

    removeChild(child) {
        const index = this.children.indexOf(child);
        if (index >= 0) {
            this.children.splice(index, 1);
            child.parentNode = null;
        }
        return child;
    }

    remove() {
        if (this.parentNode) {
            this.parentNode.removeChild(this);
        }
    }
}

function runScript(source, globals) {
    vm.runInNewContext(source, globals, { timeout: 1000 });
}

function readPluginScript(name) {
    return fs.readFileSync(path.join(pluginRoot, 'public', name), 'utf8');
}

function createPreviewFixture() {
    const input = new FakeElement('input');
    input.setAttribute('data-mod-preview', 'preview_logo_upload');
    input.setAttribute('data-mod-default-preview', 'preview_logo_saved');
    input.setAttribute('data-mod-status-preview', 'preview_logo_status');

    const savedPreview = new FakeElement('img');
    const canvas = new FakeElement('canvas');
    canvas.setAttribute('data-mod-max-width', '640');
    canvas.setAttribute('data-mod-max-height', '360');
    canvas.hidden = true;
    const status = new FakeElement('div');
    status.hidden = true;

    const elements = {
        preview_logo_saved: savedPreview,
        preview_logo_upload: canvas,
        preview_logo_status: status,
    };
    const documentListeners = Object.create(null);
    const document = {
        readyState: 'loading',
        addEventListener(name, callback) {
            documentListeners[name] = callback;
        },
        querySelectorAll() {
            return [input];
        },
        getElementById(id) {
            return elements[id] || null;
        },
    };

    return { input, savedPreview, canvas, status, document, documentListeners };
}

async function flushPromises() {
    await new Promise((resolve) => setImmediate(resolve));
    await Promise.resolve();
}

test('saved previews remain visible and selected images are rendered on canvas', async () => {
    const fixture = createPreviewFixture();
    let closeCount = 0;

    runScript(readPluginScript('uibranding.js'), {
        document: fixture.document,
        createImageBitmap(file) {
            assert.equal(file.name, 'new-logo.png');
            return Promise.resolve({
                width: 1280,
                height: 640,
                close() {
                    closeCount += 1;
                },
            });
        },
    });
    fixture.documentListeners.DOMContentLoaded();

    assert.equal(fixture.savedPreview.hidden, false);
    assert.equal(fixture.canvas.hidden, true);

    fixture.input.files = [{ name: 'new-logo.png' }];
    fixture.input.dispatch('change');
    await flushPromises();

    assert.equal(fixture.canvas.hidden, false);
    assert.equal(fixture.canvas.width, 640);
    assert.equal(fixture.canvas.height, 320);
    assert.equal(fixture.canvas.getAttribute('aria-label'), 'new-logo.png');
    assert.equal(fixture.canvas.drawCalls.length, 1);
    assert.equal(fixture.savedPreview.hidden, true);
    assert.equal(fixture.status.hidden, true);
    assert.equal(closeCount, 1);
});

test('unsupported images use literal filename text and keep the saved preview visible', () => {
    const fixture = createPreviewFixture();
    const hostileFilename = '<img src=x onerror=alert(1)> & "quoted".ico';

    runScript(readPluginScript('uibranding.js'), { document: fixture.document });
    fixture.documentListeners.DOMContentLoaded();

    fixture.input.files = [{ name: hostileFilename }];
    fixture.input.dispatch('change');

    assert.equal(fixture.canvas.hidden, true);
    assert.equal(fixture.status.hidden, false);
    assert.equal(fixture.status.textContent, hostileFilename);
    assert.equal(fixture.savedPreview.hidden, false);
    assert.equal(fixture.status.children.length, 0);
});

test('clearing a selected upload restores the saved preview and clears the canvas', async () => {
    const fixture = createPreviewFixture();

    runScript(readPluginScript('uibranding.js'), {
        document: fixture.document,
        createImageBitmap() {
            return Promise.resolve({
                width: 320,
                height: 180,
                close() {},
            });
        },
    });
    fixture.documentListeners.DOMContentLoaded();

    fixture.input.files = [{ name: 'replacement.png' }];
    fixture.input.dispatch('change');
    await flushPromises();
    assert.equal(fixture.savedPreview.hidden, true);

    fixture.input.files = [];
    fixture.input.dispatch('change');

    assert.equal(fixture.savedPreview.hidden, false);
    assert.equal(fixture.canvas.hidden, true);
    assert.equal(fixture.canvas.width, 0);
    assert.equal(fixture.canvas.height, 0);
    assert.equal(fixture.canvas.getAttribute('aria-label'), null);
    assert.equal(fixture.status.hidden, true);
    assert.equal(fixture.status.textContent, '');
});

test('preview script does not create DOM URL sinks or interpret filenames as markup', () => {
    const source = readPluginScript('uibranding.js');

    assert.doesNotMatch(source, /createObjectURL|revokeObjectURL|\bURL\b/);
    assert.doesNotMatch(source, /createElement\s*\(/);
    assert.doesNotMatch(source, /\.(?:src|href)\s*=/);
    assert.doesNotMatch(source, /setAttribute\s*\(\s*['"](?:src|href)['"]/i);
    assert.doesNotMatch(source, /innerHTML|outerHTML|insertAdjacentHTML/i);
    assert.match(source, /drawImage\s*\(/);
    assert.match(source, /textContent\s*=/);
});

test('public preview script never creates elements or assigns src/href', () => {
    const source = readPluginScript('uibranding.js');
    assert.doesNotMatch(source, /createElement\s*\(/);
    assert.doesNotMatch(source, /\.(?:src|href)\s*=/);
    assert.doesNotMatch(source, /setAttribute\s*\(\s*['"](?:src|href)['"]/i);
    assert.doesNotMatch(source, /innerHTML|outerHTML|insertAdjacentHTML/i);
});

test('favicon script does not create URL-bearing DOM elements or copy href values', () => {
    const source = readPluginScript('favicon.js');
    assert.doesNotMatch(source, /createElement\s*\(/);
    assert.doesNotMatch(source, /\.(?:src|href)\s*=/);
    assert.doesNotMatch(source, /setAttribute\s*\(\s*['"]href['"]/i);
    assert.doesNotMatch(source, /innerHTML|outerHTML|insertAdjacentHTML/i);
});

test('saved image previews remain in the configuration template', () => {
    const template = fs.readFileSync(path.join(pluginRoot, 'templates', 'uibranding.html.twig'), 'utf8');

    assert.match(template, /<img id="preview_background"[\s\S]*?src="\{\{ preview_url \}\}\/background"/);
    assert.match(template, /<img id="preview_favicon"[\s\S]*?src="\{\{ preview_url \}\}\/favicon"/);
    assert.equal((template.match(/<img id="preview_\{\{ logo\.name \}\}"/g) || []).length, 2);
    assert.ok(template.includes('data-mod-default-preview="preview_{{ logo.name }}"'));
});

test('preview containers remain hidden when no file is selected', () => {
    const template = fs.readFileSync(path.join(pluginRoot, 'templates', 'uibranding.html.twig'), 'utf8');

    assert.match(
        template,
        /\.mod-preview-background\[hidden\],\s*\.mod-preview-logo\[hidden\],\s*\.mod-preview-favicon\[hidden\]\s*\{\s*display:\s*none\s*!important;/s
    );
});

test('release workflow gates publication on the exact numeric tag', () => {
    const workflow = fs.readFileSync(path.resolve(pluginRoot, '..', '.github', 'workflows', 'ci.yml'), 'utf8');

    assert.match(workflow, /if: github\.ref == 'refs\/tags\/12\.0\.2'/);
    assert.doesNotMatch(workflow, /startsWith\(github\.ref/);
    assert.doesNotMatch(workflow, /v12\.0\.2/);
});

test('favicon logic reuses the trusted server-rendered link and removes native duplicates', () => {
    const head = new FakeElement('head');
    const custom = new FakeElement('link');
    custom.setAttribute('rel', 'icon');
    custom.setAttribute('data-mod-favicon', '1');
    custom.setAttribute('href', '/plugins/mod/UIBranding/Asset/favicon?v=trusted');
    const native = new FakeElement('link');
    native.setAttribute('rel', 'icon');
    const shortcut = new FakeElement('link');
    shortcut.setAttribute('rel', 'shortcut icon');
    const stylesheet = new FakeElement('link');
    stylesheet.setAttribute('rel', 'stylesheet');

    head.appendChild(custom);
    head.appendChild(native);
    head.appendChild(shortcut);
    head.appendChild(stylesheet);

    const document = {
        readyState: 'complete',
        head,
        querySelector(selector) {
            assert.equal(selector, 'link[data-mod-favicon]');
            return custom;
        },
        querySelectorAll(selector) {
            assert.equal(selector, 'link[rel~="icon"], link[rel="shortcut icon"]');
            return [custom, native, shortcut];
        },
        createElement() {
            assert.fail('favicon script must reuse the server-rendered link');
        },
    };

    runScript(readPluginScript('favicon.js'), { document });

    assert.equal(custom.parentNode, head);
    assert.deepEqual(head.children, [stylesheet, custom]);
    assert.equal(custom.getAttribute('href'), '/plugins/mod/UIBranding/Asset/favicon?v=trusted');
    assert.equal(custom.getAttribute('rel'), 'icon');
});
