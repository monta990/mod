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

test('upload selection displays a safe text-only state without DOM URL sinks', () => {
    const input = new FakeElement('input');
    input.setAttribute('data-mod-preview', 'preview_logo');
    const preview = new FakeElement('div');
    preview.hidden = true;
    preview.style = Object.create(null);
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
            return id === 'preview_logo' ? preview : null;
        },
    };

    runScript(readPluginScript('uibranding.js'), {
        document,
        Map,
        URL,
    });
    documentListeners.DOMContentLoaded();

    input.files = [{ name: 'logo-new.png' }];
    input.dispatch('change');

    assert.equal(preview.hidden, false);
    assert.equal(preview.style.backgroundImage, 'none');
    assert.equal(preview.style.backgroundColor, 'var(--tblr-bg-surface-secondary)');
    assert.equal(preview.getAttribute('aria-label'), 'logo-new.png');
    assert.equal(preview.textContent, 'logo-new.png');
    assert.equal(preview.src, '');
    assert.equal(preview.getAttribute('src'), null);

    input.files = [];
    input.dispatch('change');

    assert.equal(preview.hidden, true);
    assert.equal(preview.style.backgroundImage, 'none');
    assert.equal(preview.getAttribute('aria-label'), null);
    assert.equal(preview.textContent, '');
});

test('selected filenames are rendered as literal text, never interpreted as HTML', () => {
    const input = new FakeElement('input');
    input.setAttribute('data-mod-preview', 'preview_logo');
    const preview = new FakeElement('div');
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
            return id === 'preview_logo' ? preview : null;
        },
    };

    runScript(readPluginScript('uibranding.js'), { document });
    documentListeners.DOMContentLoaded();

    const hostileFilename = '<img src=x onerror=alert(1)> & "quoted".png';
    input.files = [{ name: hostileFilename }];
    input.dispatch('change');

    assert.equal(preview.hidden, false);
    assert.equal(preview.textContent, hostileFilename);
    assert.equal(preview.getAttribute('aria-label'), hostileFilename);
    assert.equal(preview.children.length, 0);
    assert.equal(preview.getAttribute('src'), null);
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

test('preview containers remain hidden when no file is selected', () => {
    const template = fs.readFileSync(path.join(pluginRoot, 'templates', 'uibranding.html.twig'), 'utf8');

    assert.match(
        template,
        /\.mod-preview-background\[hidden\],\s*\.mod-preview-logo\[hidden\],\s*\.mod-preview-favicon\[hidden\]\s*\{\s*display:\s*none\s*!important;/s
    );
});

test('release workflow gates publication on the exact numeric tag', () => {
    const workflow = fs.readFileSync(path.resolve(pluginRoot, '..', '.github', 'workflows', 'ci.yml'), 'utf8');

    assert.match(workflow, /if: github\.ref == 'refs\/tags\/12\.0\.1'/);
    assert.doesNotMatch(workflow, /startsWith\(github\.ref/);
    assert.doesNotMatch(workflow, /v12\.0\.1/);
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
