import './bootstrap';

import Alpine from 'alpinejs';
import WaveSurfer from 'wavesurfer.js';
import RecordPlugin from 'wavesurfer.js/dist/plugins/record.esm.js';
import Plyr from 'plyr';
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import 'plyr/dist/plyr.css';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

window.Alpine = Alpine;

FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
);

window.videoPlayer = () => ({
    player: null,
    init() {
        this.player = new Plyr(this.$refs.video, {
            controls: ['play', 'progress', 'current-time', 'mute', 'fullscreen'],
            ratio: '4:5',
        });
    },
    destroy() {
        this.player?.destroy();
        this.player = null;
    },
});

window.inboxPage = () => ({
    profileOpen: false,
    mediaViewer: {
        open: false,
        type: null,
        src: null,
        alt: '',
    },
    player: null,
    dragStartY: null,
    dragY: 0,
    openMedia(media) {
        this.closeMedia();
        this.mediaViewer = {
            open: true,
            type: media.type,
            src: media.src,
            alt: media.alt || '',
        };

        if (media.type === 'video') {
            this.$nextTick(() => {
                if (!this.$refs.mediaVideo) {
                    return;
                }

                this.player?.destroy();
                this.player = new Plyr(this.$refs.mediaVideo, {
                    controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'fullscreen'],
                    ratio: '4:5',
                });
                this.player.play().catch(() => {});
            });
        }
    },
    closeMedia() {
        this.mediaViewer.open = false;
        this.mediaViewer.type = null;
        this.mediaViewer.src = null;
        this.mediaViewer.alt = '';
        this.dragStartY = null;
        this.dragY = 0;
        this.player?.destroy();
        this.player = null;
    },
    startMediaDrag(event) {
        this.dragStartY = event.clientY ?? event.touches?.[0]?.clientY ?? null;
    },
    moveMediaDrag(event) {
        if (this.dragStartY === null) {
            return;
        }

        const y = event.clientY ?? event.touches?.[0]?.clientY ?? this.dragStartY;
        this.dragY = y - this.dragStartY;
    },
    endMediaDrag() {
        if (Math.abs(this.dragY) > 90) {
            this.closeMedia();
            return;
        }

        this.dragStartY = null;
        this.dragY = 0;
    },
});

window.videoPreview = (sourceUrl) => ({
    open: false,
    player: null,
    dragStartY: null,
    dragY: 0,
    openPlayer() {
        this.open = true;

        this.$nextTick(() => {
            this.player?.destroy();
            this.player = new Plyr(this.$refs.modalVideo, {
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'fullscreen'],
                ratio: '4:5',
            });
            this.player.play().catch(() => {});
        });
    },
    closePlayer() {
        this.open = false;
        this.dragStartY = null;
        this.dragY = 0;
        this.player?.destroy();
        this.player = null;
    },
    startDrag(event) {
        this.dragStartY = event.clientY ?? event.touches?.[0]?.clientY ?? null;
    },
    moveDrag(event) {
        if (this.dragStartY === null) {
            return;
        }

        const y = event.clientY ?? event.touches?.[0]?.clientY ?? this.dragStartY;
        this.dragY = y - this.dragStartY;
    },
    endDrag() {
        if (Math.abs(this.dragY) > 90) {
            this.closePlayer();
            return;
        }

        this.dragStartY = null;
        this.dragY = 0;
    },
    sourceUrl,
});

window.swipeReplyMessage = (message) => ({
    startX: null,
    startY: null,
    offsetX: 0,
    swiped: false,
    begin(event) {
        const point = event.touches?.[0] || event;

        this.startX = point.clientX;
        this.startY = point.clientY;
        this.swiped = false;
    },
    move(event) {
        if (this.startX === null || this.startY === null) {
            return;
        }

        const point = event.touches?.[0] || event;
        const deltaX = point.clientX - this.startX;
        const deltaY = point.clientY - this.startY;

        if (Math.abs(deltaY) > Math.abs(deltaX)) {
            return;
        }

        if (Math.abs(deltaX) > 8 && event.cancelable) {
            event.preventDefault();
        }

        this.offsetX = Math.max(-52, Math.min(52, deltaX));

        if (Math.abs(this.offsetX) > 34) {
            this.swiped = true;
        }
    },
    end() {
        if (this.swiped) {
            window.dispatchEvent(new CustomEvent('set-reply-message', { detail: message }));
        }

        this.startX = null;
        this.startY = null;
        this.offsetX = 0;
        this.swiped = false;
    },
});

window.voiceNotePlayer = (sourceUrl) => ({
    wave: null,
    playing: false,
    elapsed: '0:00',
    duration: '0:00',
    displayTime: '0:00',
    ready: false,
    format(seconds) {
        seconds = Number.isFinite(seconds) ? Math.floor(seconds) : 0;
        return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    },
    init() {
        this.wave = WaveSurfer.create({
            container: this.$refs.waveform,
            url: sourceUrl,
            height: 30,
            barWidth: 2,
            barGap: 2,
            barRadius: 2,
            cursorWidth: 0,
            waveColor: '#CBD5E1',
            progressColor: '#2563EB',
            normalize: true,
        });

        this.wave.on('ready', (duration) => {
            this.ready = true;
            this.duration = this.format(duration);
            this.displayTime = this.duration;
        });
        this.wave.on('play', () => {
            this.playing = true;
        });
        this.wave.on('pause', () => {
            this.playing = false;
        });
        this.wave.on('finish', () => {
            this.playing = false;
            this.elapsed = '0:00';
            this.displayTime = this.duration;
            this.wave.seekTo(0);
        });
        this.wave.on('timeupdate', (currentTime) => {
            this.elapsed = this.format(currentTime);
            this.displayTime = this.elapsed === '0:00' ? this.duration : this.elapsed;
        });
    },
    toggle() {
        if (!this.ready) {
            return;
        }

        this.wave.playPause();
    },
    destroy() {
        this.wave?.destroy();
        this.wave = null;
    },
});

window.inboxComposer = (initialAutomationPaused = false) => ({
    fileCount: 0,
    genericFiles: [],
    mediaFileCount: 0,
    emojiOpen: false,
    composerHasText: false,
    mediaPond: null,
    mediaPondReady: false,
    automationPaused: initialAutomationPaused,
    recorder: null,
    recorderWave: null,
    recording: false,
    recordError: '',
    recordElapsed: '0:00',
    recordedVoiceElapsed: '0:00',
    voiceNoteReady: false,
    replyTo: null,
    recordingStopResolver: null,
    maxVoiceBytes: 10 * 1024 * 1024,
    init() {
        this.setupMediaPond();
        window.addEventListener('set-reply-message', (event) => {
            this.replyTo = event.detail || null;
            this.$refs.messageInput?.focus();
        });
    },
    setupMediaPond() {
        if (!this.$refs.imageInput || this.mediaPond) {
            return;
        }

        this.mediaPond = FilePond.create(this.$refs.imageInput, {
            allowMultiple: true,
            storeAsFile: true,
            credits: false,
            maxFiles: 6,
            maxFileSize: '10MB',
            acceptedFileTypes: ['image/*', 'video/*'],
            labelIdle: '<span class="filepond--label-action">Choose image or video</span>',
            labelFileTypeNotAllowed: 'Use an image or video file.',
            fileValidateTypeLabelExpectedTypes: 'Images or videos only',
        });

        this.mediaPond.on('updatefiles', (files) => {
            this.mediaFileCount = files.length;
            this.updateFiles();
        });
        this.mediaPondReady = true;
    },
    browseMedia() {
        this.mediaPond?.browse();
    },
    updateFiles() {
        this.genericFiles = Array.from(this.$refs.fileInput?.files || []).map((file) => ({
            name: file.name,
            size: file.size,
        }));
        this.fileCount = this.genericFiles.length + this.mediaFileCount;
        this.voiceNoteReady = (this.$refs.audioInput?.files?.length || 0) > 0;
    },
    updateTyping() {
        this.composerHasText = (this.$refs.messageInput?.value || '').trim().length > 0;
    },
    insertEmoji(emoji) {
        const input = this.$refs.messageInput;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;

        input.value = input.value.slice(0, start) + emoji + input.value.slice(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + emoji.length;
        this.updateTyping();
        this.emojiOpen = false;
    },
    format(seconds) {
        seconds = Number.isFinite(seconds) ? Math.floor(seconds) : 0;
        return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    },
    tickRecorder() {
        return;
    },
    async toggleRecorder() {
        if (this.recording) {
            this.stopRecorder();
            return;
        }

        await this.startRecorder();
    },
    async startRecorder() {
        this.recordError = '';
        this.clearVoiceNote();

        if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
            this.recordError = 'Voice recording is not supported in this browser.';
            return;
        }

        try {
            this.recording = true;
            this.recordElapsed = '0:00';

            await this.$nextTick();
            this.initRecorder();
            await this.recorder.startRecording();
        } catch (error) {
            this.recordError = 'Microphone access was blocked.';
            this.cleanupRecorder();
        }
    },
    initRecorder() {
        this.destroyRecorder();

        this.recorderWave = WaveSurfer.create({
            container: this.$refs.recordWaveform,
            height: 30,
            barWidth: 2,
            barGap: 2,
            barRadius: 2,
            cursorWidth: 0,
            waveColor: '#CBD5E1',
            progressColor: '#DC2626',
            normalize: true,
            interact: false,
        });

        this.recorder = this.recorderWave.registerPlugin(RecordPlugin.create({
            scrollingWaveform: true,
            scrollingWaveformWindow: 8,
            renderRecordedAudio: false,
            audioBitsPerSecond: 128000,
        }));

        this.recorder.on('record-progress', (duration) => {
            this.recordElapsed = this.format(duration / 1000);
        });

        this.recorder.on('record-end', (blob) => {
            this.attachVoiceNote(blob);
        });
    },
    stopRecorder() {
        if (this.recorder?.isRecording?.()) {
            this.recorder.stopRecording();
            return;
        }

        this.cleanupRecorder();
    },
    finalizeRecording() {
        if (!this.recording || !this.recorder?.isRecording?.()) {
            return Promise.resolve(true);
        }

        return new Promise((resolve) => {
            this.recordingStopResolver = resolve;
            this.recorder.stopRecording();
        });
    },
    async submitAfterRecording(event) {
        if (!this.recording) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const form = event.target;
        const submitter = event.submitter || null;
        const attached = await this.finalizeRecording();

        if (!attached) {
            return;
        }

        requestAnimationFrame(() => form.requestSubmit(submitter));
    },
    attachVoiceNote(blob) {
        const audioInput = this.$refs.audioInput;
        const type = blob?.type || 'audio/webm';
        const resolveStop = this.recordingStopResolver;

        this.recordingStopResolver = null;

        if (blob.size > this.maxVoiceBytes) {
            this.recordError = 'Voice note must be 10MB or smaller.';
            this.cleanupRecorder();
            resolveStop?.(false);
            return;
        }

        if (audioInput) {
            const extension = type.includes('ogg') ? 'ogg' : 'webm';
            const file = new File([blob], `voice-note-${Date.now()}.${extension}`, { type });
            const transfer = new DataTransfer();

            transfer.items.add(file);
            audioInput.files = transfer.files;
            this.recordedVoiceElapsed = this.recordElapsed;
            this.updateFiles();
        }

        this.cleanupRecorder();
        resolveStop?.(true);
    },
    cleanupRecorder() {
        this.recording = false;
        this.recordElapsed = '0:00';
        this.destroyRecorder();
        this.recordingStopResolver = null;
    },
    destroyRecorder() {
        this.recorderWave?.destroy();
        this.recorderWave = null;
        this.recorder = null;
    },
    clearVoiceNote() {
        const audioInput = this.$refs.audioInput;

        if (audioInput) {
            audioInput.value = '';
        }

        this.voiceNoteReady = false;
        this.recordedVoiceElapsed = '0:00';
        this.updateFiles();
    },
    clearReply() {
        this.replyTo = null;
    },
});

if (!window.__perpetualInboxAlpineStarted) {
    Alpine.start();
    window.__perpetualInboxAlpineStarted = true;
}

const shouldInitializeSpa = !window.__perpetualInboxSpaInitialized;

window.__perpetualInboxSpaInitialized = true;

const dashboardPathPrefix = '/dashboard';
const inboxPulsePath = '/dashboard/inbox/pulse';
const inboxPollIntervalMs = 2500;
let latestInboxVersion = null;
let pendingInboxRefresh = false;
let inboxPulseInFlight = false;

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

function isInboxPage() {
    return window.location.pathname.replace(/\/+$/, '') === '/dashboard/inbox';
}

function hasUnsavedUserInput() {
    const activeElement = document.activeElement;

    if (activeElement?.isContentEditable && activeElement.textContent?.trim() !== '') {
        return true;
    }

    const fields = document.querySelectorAll('input, textarea, select');

    return Array.from(fields).some((field) => {
        if (field.closest('.filepond--root') || field.type === 'hidden') {
            return false;
        }

        if (field instanceof HTMLInputElement && field.type === 'file') {
            return field.files?.length > 0;
        }

        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
            return field.value !== field.defaultValue;
        }

        if (field instanceof HTMLSelectElement) {
            return field.value !== field.querySelector('option[selected]')?.value;
        }

        return false;
    });
}

function dashboardIsBusy() {
    return document.documentElement.classList.contains('spa-loading') ||
        document.querySelector('.spa-pending') !== null ||
        document.querySelector('form[aria-busy="true"]') !== null;
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

function inboxPulseUrl() {
    const pulseUrl = new URL(inboxPulsePath, window.location.origin);
    const currentUrl = new URL(window.location.href);

    for (const key of ['state', 'channel', 'q', 'conversation']) {
        const value = currentUrl.searchParams.get(key);

        if (value) {
            pulseUrl.searchParams.set(key, value);
        }
    }

    return pulseUrl;
}

function renderedInboxVersion() {
    return document.querySelector('[data-inbox-version]')?.dataset?.inboxVersion || null;
}

function syncInboxVersionFromDom() {
    if (!isInboxPage()) {
        latestInboxVersion = null;
        pendingInboxRefresh = false;
        return;
    }

    latestInboxVersion = renderedInboxVersion() || latestInboxVersion;
}

function normalizeInboxViewport() {
    if (!isInboxPage()) {
        return;
    }

    window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
}

function scrollActiveChatToBottom() {
    const chatPane = document.querySelector('[data-chat-scroll]');

    normalizeInboxViewport();

    if (!chatPane) {
        return;
    }

    const scroll = () => {
        chatPane.scrollTop = chatPane.scrollHeight;
    };

    requestAnimationFrame(() => {
        scroll();
        requestAnimationFrame(scroll);
    });
}

async function pollInbox() {
    if (!isInboxPage() || document.hidden || dashboardIsBusy() || inboxPulseInFlight) {
        return;
    }

    latestInboxVersion = latestInboxVersion || renderedInboxVersion();

    if (pendingInboxRefresh && !hasUnsavedUserInput()) {
        pendingInboxRefresh = false;
        await visit(window.location.href, { replace: true });
        return;
    }

    inboxPulseInFlight = true;

    try {
        const response = await fetch(inboxPulseUrl().href, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();

        if (!payload?.version) {
            return;
        }

        if (latestInboxVersion === null) {
            latestInboxVersion = payload.version;
            return;
        }

        if (payload.version !== latestInboxVersion) {
            latestInboxVersion = payload.version;

            if (hasUnsavedUserInput()) {
                pendingInboxRefresh = true;
                return;
            }

            await visit(window.location.href, { replace: true });
        }
    } finally {
        inboxPulseInFlight = false;
    }
}

async function visit(url, options = {}) {
    const targetUrl = new URL(url, window.location.href);

    if (!isDashboardUrl(targetUrl)) {
        window.location.href = targetUrl.href;
        return;
    }

    if (isInboxPage() && document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
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

        if (isInboxPage()) {
            normalizeInboxViewport();
            scrollActiveChatToBottom();
        } else {
            window.scrollTo({ top: 0, behavior: 'instant' });
        }
        window.Alpine?.initTree(document.querySelector('[data-spa-frame]') || document.querySelector('[data-spa-shell]'));
        window.Alpine?.initTree(document.querySelector('.app-sidebar') || document.querySelector('[data-spa-shell]'));
        syncInboxVersionFromDom();
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

    if (isInboxPage() && document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
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
        if (isInboxPage()) {
            normalizeInboxViewport();
            scrollActiveChatToBottom();
        } else {
            window.scrollTo({ top: 0, behavior: 'instant' });
        }
        window.Alpine?.initTree(document.querySelector('[data-spa-shell]'));
        syncInboxVersionFromDom();
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

    syncInboxVersionFromDom();
    scrollActiveChatToBottom();
    window.setTimeout(pollInbox, 300);
    window.setInterval(pollInbox, inboxPollIntervalMs);
    window.addEventListener('focus', () => {
        pollInbox();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            pollInbox();
        }
    });
}
