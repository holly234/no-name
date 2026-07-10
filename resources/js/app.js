import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

if (!window.__perpetualInboxAlpineStarted) {
    Alpine.start();
    window.__perpetualInboxAlpineStarted = true;
}

const shouldInitializeSpa = !window.__perpetualInboxSpaInitialized;

window.__perpetualInboxSpaInitialized = true;

const dashboardPathPrefix = '/dashboard';

function isDashboardUrl(url) {
    return url.origin === window.location.origin && (
        url.pathname === dashboardPathPrefix ||
        url.pathname.startsWith(`${dashboardPathPrefix}/`) ||
        url.pathname === '/workspace/switch'
    );
}

function shouldIgnoreLink(link, event) {
    return event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey ||
        link.target ||
        link.dataset.spa === 'false' ||
        link.hasAttribute('download') ||
        !link.href;
}

function extractAppShell(html) {
    const parser = new DOMParser();
    const documentFragment = parser.parseFromString(html, 'text/html');

    return {
        title: documentFragment.title,
        shell: documentFragment.querySelector('[data-spa-shell]'),
        frame: documentFragment.querySelector('[data-spa-frame]'),
        main: documentFragment.querySelector('[data-spa-main]'),
    };
}

async function visit(url, options = {}) {
    const targetUrl = new URL(url, window.location.href);

    if (!isDashboardUrl(targetUrl)) {
        window.location.href = targetUrl.href;
        return;
    }

    document.documentElement.classList.add('spa-loading');

    try {
        const response = await fetch(targetUrl.href, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('text/html')) {
            window.location.href = response.url || targetUrl.href;
            return;
        }

        const html = await response.text();
        const next = extractAppShell(html);
        const currentShell = document.querySelector('[data-spa-shell]');
        const currentFrame = document.querySelector('[data-spa-frame]');
        const currentSidebar = document.querySelector('.app-sidebar');
        const nextSidebar = next.shell?.querySelector('.app-sidebar');

        if (!next.shell || !currentShell) {
            window.location.href = response.url || targetUrl.href;
            return;
        }

        if (next.frame && currentFrame) {
            currentFrame.replaceWith(next.frame);
            if (nextSidebar && currentSidebar && targetUrl.pathname !== window.location.pathname) {
                currentSidebar.replaceWith(nextSidebar);
            }
        } else {
            currentShell.replaceWith(next.shell);
        }

        document.title = next.title || document.title;

        const finalUrl = response.url || targetUrl.href;
        if (options.replace) {
            window.history.replaceState({}, '', finalUrl);
        } else if (finalUrl !== window.location.href) {
            window.history.pushState({}, '', finalUrl);
        }

        window.scrollTo({ top: 0, behavior: 'instant' });
        window.Alpine?.initTree(document.querySelector('[data-spa-frame]') || document.querySelector('[data-spa-shell]'));
        window.Alpine?.initTree(document.querySelector('.app-sidebar') || document.querySelector('[data-spa-shell]'));
    } catch (error) {
        window.location.href = targetUrl.href;
    } finally {
        document.documentElement.classList.remove('spa-loading');
    }
}

function markPending(element) {
    element?.classList?.add('spa-pending');
    element?.setAttribute?.('aria-busy', 'true');
}

function clearPending(element) {
    element?.classList?.remove('spa-pending');
    element?.removeAttribute?.('aria-busy');
}

function setModeToggleState(toggle, mode) {
    if (!toggle?.classList?.contains('mode-toggle')) {
        return;
    }

    const isHumanMode = mode === 'human';
    toggle.classList.toggle('mode-toggle-ai', !isHumanMode);
    toggle.classList.toggle('mode-toggle-human', isHumanMode);
    toggle.setAttribute(
        'title',
        isHumanMode ? 'Human takeover active' : 'AI mode active',
    );
    toggle.setAttribute(
        'aria-label',
        isHumanMode
            ? 'Human takeover active. Resume AI handling.'
            : 'AI mode active. Switch to human takeover.',
    );
}

function animateModeToggle(submitter) {
    if (!submitter?.classList?.contains('mode-toggle')) {
        return;
    }

    setModeToggleState(
        submitter,
        submitter.classList.contains('mode-toggle-ai') ? 'human' : 'ai',
    );
}

function animateManualReplyMode(form) {
    if (form?.dataset?.humanOnSubmit !== 'true') {
        return;
    }

    setModeToggleState(form.querySelector('.mode-toggle'), 'human');
}

async function submitForm(form, submitter = null) {
    if (form.dataset.spa === 'false') {
        form.submit();
        return;
    }

    const action = submitter?.getAttribute('formaction') || form.action || window.location.href;
    const method = (submitter?.getAttribute('formmethod') || form.method || 'GET').toUpperCase();
    const targetUrl = new URL(action, window.location.href);

    if (!isDashboardUrl(targetUrl)) {
        form.submit();
        return;
    }

    const formData = new FormData(form);

    if (submitter?.name) {
        formData.append(submitter.name, submitter.value);
    }

    if (method === 'GET') {
        targetUrl.search = '';

        for (const [key, value] of formData.entries()) {
            if (value !== '') {
                targetUrl.searchParams.append(key, value);
            }
        }
    }

    if (submitter?.dataset?.instantAction === 'true') {
        markPending(submitter);

        if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
            submitter.disabled = true;
        }

        try {
            const response = await fetch(targetUrl.href, {
                method,
                body: method === 'GET' ? null : formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Instant action failed with ${response.status}`);
            }
        } catch (error) {
            window.location.href = targetUrl.href;
        } finally {
            clearPending(submitter);

            if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
                submitter.disabled = false;
            }
        }

        return;
    }

    document.documentElement.classList.add('spa-loading');
    animateModeToggle(submitter);
    animateManualReplyMode(form);
    markPending(submitter || form);

    if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
        submitter.disabled = true;
    }

    try {
        const response = await fetch(targetUrl.href, {
            method,
            body: method === 'GET' ? null : formData,
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('text/html')) {
            window.location.href = response.url || targetUrl.href;
            return;
        }

        const html = await response.text();
        const next = extractAppShell(html);
        const currentShell = document.querySelector('[data-spa-shell]');

        if (!next.shell || !currentShell) {
            window.location.href = response.url || targetUrl.href;
            return;
        }

        currentShell.replaceWith(next.shell);
        document.title = next.title || document.title;
        window.history.replaceState({}, '', response.url || targetUrl.href);
        window.scrollTo({ top: 0, behavior: 'instant' });
        window.Alpine?.initTree(document.querySelector('[data-spa-shell]'));
    } catch (error) {
        form.submit();
    } finally {
        document.documentElement.classList.remove('spa-loading');
        clearPending(submitter || form);

        if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
            submitter.disabled = false;
        }
    }
}

if (shouldInitializeSpa) {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');

        if (!link || shouldIgnoreLink(link, event)) {
            return;
        }

        const url = new URL(link.href);

        if (!isDashboardUrl(url)) {
            return;
        }

        event.preventDefault();
        markPending(link);
        visit(url.href).finally(() => clearPending(link));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.dataset.spa === 'false') {
            return;
        }

        const submitter = event.submitter || null;
        const action = submitter?.getAttribute('formaction') || form.action || window.location.href;

        if (!isDashboardUrl(new URL(action, window.location.href))) {
            return;
        }

        event.preventDefault();
        submitForm(form, submitter);
    });

    window.addEventListener('popstate', () => {
        visit(window.location.href, { replace: true });
    });
}
