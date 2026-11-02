'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '..');

class FakeElement {
    constructor(tagName) {
        this.tagName = String(tagName || 'div').toUpperCase();
        this.attributes = Object.create(null);
        this.children = [];
        this.parentNode = null;
        this.listeners = Object.create(null);
        this.hidden = false;
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

test('upload preview uses a temporary blob image and preserves the original image node', () => {
    const input = new FakeElement('input');
    input.setAttribute('data-mod-preview', 'preview_logo');

    const container = new FakeElement('div');
    const original = new FakeElement('img');
    original.src = '/plugins/mod/UIBranding/Asset/logo_m?v=trusted';
    original.className = 'img-fluid mod-preview-logo';
    original.alt = 'Logo';
    container.appendChild(original);

    const documentListeners = Object.create(null);
    const revoked = [];
    let objectUrlNumber = 0;
    const document = {
        readyState: 'loading',
        addEventListener(name, callback) {
            documentListeners[name] = callback;
        },
        querySelectorAll() {
            return [input];
        },
        getElementById(id) {
            return id === 'preview_logo' ? original : null;
        },
        createElement(tagName) {
            return new FakeElement(tagName);
        },
    };
    const URL = {
        createObjectURL(file) {
            objectUrlNumber += 1;
            return 'blob:preview/' + objectUrlNumber + '/' + file.name;
        },
        revokeObjectURL(url) {
            revoked.push(url);
        },
    };

    runScript(readPluginScript('uibranding.js'), {
        document,
        window: { addEventListener() {} },
        URL,
        Map,
        Array,
    });
    documentListeners.DOMContentLoaded();

    input.files = [{ name: 'logo-new.png' }];
    input.dispatch('change');

    assert.equal(original.hidden, true);
    assert.equal(original.src, '/plugins/mod/UIBranding/Asset/logo_m?v=trusted');
    assert.equal(container.children.length, 2);
    const temporary = container.children[1];
    assert.equal(temporary.tagName, 'IMG');
    assert.equal(temporary.src, 'blob:preview/1/logo-new.png');
    assert.equal(temporary.className, original.className);
    assert.equal(temporary.alt, original.alt);

    input.files = [];
    input.dispatch('change');

    assert.equal(original.hidden, false);
    assert.equal(container.children.length, 1);
    assert.equal(container.children[0], original);
    assert.deepEqual(revoked, ['blob:preview/1/logo-new.png']);
});

test('replacing a selected file revokes the previous blob preview', () => {
    const input = new FakeElement('input');
    input.setAttribute('data-mod-preview', 'preview_background');
    const container = new FakeElement('div');
    const original = new FakeElement('img');
    container.appendChild(original);

    const documentListeners = Object.create(null);
    const revoked = [];
    let counter = 0;
    const document = {
        readyState: 'loading',
        addEventListener(name, callback) {
            documentListeners[name] = callback;
        },
        querySelectorAll() {
            return [input];
        },
        getElementById() {
            return original;
        },
        createElement(tagName) {
            return new FakeElement(tagName);
        },
    };
    const URL = {
        createObjectURL(file) {
            counter += 1;
            return 'blob:preview/' + counter + '/' + file.name;
        },
        revokeObjectURL(url) {
            revoked.push(url);
        },
    };

    runScript(readPluginScript('uibranding.js'), {
        document,
        window: { addEventListener() {} },
        URL,
        Map,
        Array,
    });
    documentListeners.DOMContentLoaded();

    input.files = [{ name: 'first.jpg' }];
    input.dispatch('change');
    input.files = [{ name: 'second.jpg' }];
    input.dispatch('change');

    assert.equal(container.children.length, 2);
    assert.equal(container.children[1].src, 'blob:preview/2/second.jpg');
    assert.deepEqual(revoked, ['blob:preview/1/first.jpg']);
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
