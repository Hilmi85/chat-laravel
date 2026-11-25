import axios from 'axios';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const chatRoot = document.getElementById('chat-app');

if (chatRoot) {
    initChat(chatRoot);
}

function initChat(root) {
    const config = {
        participantId: Number(root.dataset.participantId || 0),
        expiredAt: root.dataset.expiredAt,
        serverTime: root.dataset.serverTime,
        pollInterval: Number(root.dataset.pollInterval || 2000),
        typingDebounce: Number(root.dataset.typingDebounce || 300),
        messagesUrl: root.dataset.messagesUrl,
        messagesStoreUrl: root.dataset.messagesStoreUrl,
        typingUrl: root.dataset.typingUrl,
        typingStatusUrl: root.dataset.typingStatusUrl,
        leaveUrl: root.dataset.leaveUrl,
        homeUrl: root.dataset.homeUrl,
        soundKey: root.dataset.soundKey || 'tempchat-sound',
    };

    const elements = {
        messages: document.getElementById('messages'),
        messageList: document.getElementById('message-list'),
        emptyState: document.getElementById('empty-state'),
        messageForm: document.getElementById('message-form'),
        messageInput: document.getElementById('message-input'),
        charCount: document.getElementById('char-count'),
        typingIndicator: document.getElementById('typing-indicator'),
        typingText: document.getElementById('typing-text'),
        onlineCount: document.getElementById('online-count'),
        countdown: document.getElementById('countdown'),
        soundToggle: document.getElementById('sound-toggle'),
        copyLink: document.getElementById('copy-link'),
        leaveRoom: document.getElementById('leave-room'),
        expiredOverlay: document.getElementById('expired-overlay'),
    };

    let lastMessageId = Number(root.dataset.lastMessageId || 0);
    let expiresAt = Date.parse(config.expiredAt);
    let serverOffset = 0;
    let expired = false;
    let stopped = false;
    let pollTimer = null;
    let typingPollTimer = null;
    let typingSendTimer = null;
    let countdownTimer = null;
    let isPollingMessages = false;
    let isPollingTyping = false;
    let isSending = false;
    let lastSentAt = 0;
    let lastGenericErrorAt = 0;
    let soundEnabled = readStorage(config.soundKey) !== 'off';
    let audioContext = null;

    const initialServerTime = Date.parse(config.serverTime);

    if (!Number.isNaN(initialServerTime)) {
        serverOffset = initialServerTime - Date.now();
    }

    updateSoundButton();
    localizeTimes();
    startCountdown();

    if (!config.participantId) {
        return;
    }

    updateCharCount();
    updateEmptyState();
    scrollToBottom(false);

    // Browsers block audio until the first user gesture. Unlock the context
    // once so notifications can play for later incoming messages.
    const unlockAudio = () => {
        const context = ensureAudioContext();

        if (context && context.state === 'suspended') {
            context.resume().catch(() => {});
        }
    };

    document.addEventListener('pointerdown', unlockAudio, { once: true, passive: true });
    document.addEventListener('keydown', unlockAudio, { once: true });

    elements.messageForm?.addEventListener('submit', sendMessage);
    elements.messageInput?.addEventListener('input', handleInput);
    elements.copyLink?.addEventListener('click', copyRoomLink);
    elements.soundToggle?.addEventListener('click', toggleSound);
    elements.leaveRoom?.addEventListener('click', leaveRoom);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !expired && !stopped) {
            clearTimeout(pollTimer);
            clearTimeout(typingPollTimer);
            pollMessages();
            pollTypingStatus();
        }
    });

    pollMessages();
    pollTypingStatus();

    function startCountdown() {
        if (!elements.countdown || Number.isNaN(expiresAt)) {
            return;
        }

        const tick = () => {
            // Use the server clock so a wrong client clock cannot break the
            // countdown. The offset is refreshed on every message poll.
            const remaining = expiresAt - (Date.now() + serverOffset);

            if (remaining <= 0) {
                elements.countdown.textContent = '00:00:00';
                showExpired();
                return;
            }

            elements.countdown.textContent = formatDuration(remaining);

            if (remaining < 60 * 60 * 1000) {
                elements.countdown.classList.add('border-warning/40', 'text-warning');
                elements.countdown.classList.remove('text-primary', 'border-secondary/20');
            } else {
                elements.countdown.classList.remove('border-warning/40', 'text-warning');
                elements.countdown.classList.add('text-primary', 'border-secondary/20');
            }

            countdownTimer = setTimeout(tick, 1000);
        };

        tick();
    }

    function formatDuration(milliseconds) {
        const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return [hours, minutes, seconds]
            .map((part) => String(part).padStart(2, '0'))
            .join(':');
    }

    function formatTime(iso, fallback = '') {
        if (!iso) {
            return fallback;
        }

        const date = new Date(iso);

        if (Number.isNaN(date.getTime())) {
            return fallback;
        }

        // Render in the viewer's timezone instead of the server timezone.
        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        });
    }

    function localizeTimes() {
        document.querySelectorAll('time[data-message-time]').forEach((element) => {
            element.textContent = formatTime(
                element.getAttribute('datetime'),
                element.textContent.trim(),
            );
        });
    }

    function readStorage(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    }

    function writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage can be blocked in private browsing; sound still works.
        }
    }

    async function pollMessages() {
        if (expired || stopped || isPollingMessages) {
            return;
        }

        if (document.hidden) {
            pollTimer = setTimeout(pollMessages, config.pollInterval);
            return;
        }

        isPollingMessages = true;

        try {
            const { data } = await axios.get(config.messagesUrl, {
                params: { after_id: lastMessageId },
            });

            if (data.server_time) {
                const serverTime = Date.parse(data.server_time);

                if (!Number.isNaN(serverTime)) {
                    serverOffset = serverTime - Date.now();
                }
            }

            if (data.expired_at) {
                const roomExpiry = Date.parse(data.expired_at);

                if (!Number.isNaN(roomExpiry)) {
                    expiresAt = roomExpiry;
                }
            }

            const incoming = Array.isArray(data.messages) ? data.messages : [];
            const wasNearBottom = isNearBottom();
            let hasNewFromOthers = false;

            incoming.forEach((message) => {
                if (appendMessage(message)) {
                    if (!message.is_own && !message.is_system) {
                        hasNewFromOthers = true;
                    }
                }
            });

            if (data.last_message_id) {
                lastMessageId = Math.max(lastMessageId, Number(data.last_message_id));
            }

            if (typeof data.online_count === 'number') {
                updateOnlineCount(data.online_count);
            }

            if (incoming.length > 0) {
                updateEmptyState();

                if (wasNearBottom || incoming.some((message) => message.is_own)) {
                    scrollToBottom();
                }
            }

            if (hasNewFromOthers) {
                playSound();
            }
        } catch (error) {
            handleError(error);
        } finally {
            isPollingMessages = false;
        }

        if (!expired && !stopped) {
            pollTimer = setTimeout(pollMessages, config.pollInterval);
        }
    }

    async function pollTypingStatus() {
        if (expired || stopped || isPollingTyping) {
            return;
        }

        if (document.hidden) {
            typingPollTimer = setTimeout(pollTypingStatus, config.pollInterval);
            return;
        }

        isPollingTyping = true;

        try {
            const { data } = await axios.get(config.typingStatusUrl);
            updateTypingIndicator(Array.isArray(data.typing) ? data.typing : []);
        } catch (error) {
            handleError(error);
        } finally {
            isPollingTyping = false;
        }

        if (!expired && !stopped) {
            typingPollTimer = setTimeout(pollTypingStatus, config.pollInterval);
        }
    }

    function appendMessage(message) {
        if (!elements.messageList || !message?.id) {
            return false;
        }

        if (elements.messageList.querySelector(`[data-message-id="${message.id}"]`)) {
            return false;
        }

        const wrapper = document.createElement('div');
        wrapper.dataset.messageId = message.id;

        if (message.is_system) {
            wrapper.className = 'flex justify-center';

            const pill = document.createElement('span');
            pill.className = 'rounded-full border border-secondary/20 bg-surface/60 px-3 py-1 font-mono text-[0.65rem] uppercase tracking-[0.12em] text-secondary';
            pill.textContent = message.message;

            wrapper.appendChild(pill);
        } else {
            wrapper.className = `flex ${message.is_own ? 'justify-end' : 'justify-start'}`;

            const content = document.createElement('div');
            content.className = 'max-w-[85%] sm:max-w-[70%]';

            if (!message.is_own) {
                const name = document.createElement('p');
                name.className = 'mb-1 px-1 font-mono text-[0.65rem] uppercase tracking-[0.12em] text-secondary';
                name.textContent = message.name || 'Anonymous';
                content.appendChild(name);
            }

            const bubble = document.createElement('div');
            bubble.className = `rounded-lg px-4 py-3 text-sm leading-6 ${message.is_own ? 'bg-tertiary text-on-primary' : 'border border-secondary/20 bg-surface text-primary'}`;

            const text = document.createElement('p');
            text.className = 'whitespace-pre-wrap break-words';
            text.textContent = message.message;

            bubble.appendChild(text);
            content.appendChild(bubble);

            const time = document.createElement('time');
            time.dateTime = message.created_at || '';
            time.dataset.messageTime = '';
            time.className = `mt-1 block px-1 font-mono text-[0.6rem] text-secondary ${message.is_own ? 'text-right' : ''}`;
            time.textContent = formatTime(message.created_at, message.time || '');

            content.appendChild(time);
            wrapper.appendChild(content);
        }

        elements.messageList.appendChild(wrapper);

        return true;
    }

    async function sendMessage(event) {
        event.preventDefault();

        if (expired || stopped || isSending || !elements.messageInput) {
            return;
        }

        const message = elements.messageInput.value.trim();

        if (!message) {
            return;
        }

        isSending = true;
        setComposerDisabled(true);

        try {
            const { data } = await axios.post(config.messagesStoreUrl, { message });

            // Cancel a pending typing ping; otherwise the debounce timer can
            // mark the user as typing again right after the message was sent.
            clearTimeout(typingSendTimer);
            typingSendTimer = null;
            lastSentAt = Date.now();

            elements.messageInput.value = '';
            updateCharCount();
            appendMessage(data.message);
            updateEmptyState();
            updateOnlineCount(data.online_count);
            scrollToBottom();
        } catch (error) {
            handleError(error);
        } finally {
            isSending = false;
            setComposerDisabled(expired || stopped);

            if (!expired && !stopped) {
                elements.messageInput.focus();
            }
        }
    }

    function handleInput() {
        updateCharCount();

        if (expired || stopped || !elements.messageInput) {
            return;
        }

        clearTimeout(typingSendTimer);

        if (!elements.messageInput.value.trim()) {
            return;
        }

        typingSendTimer = setTimeout(() => {
            typingSendTimer = null;

            // Do not re-announce typing immediately after a sent message.
            if (Date.now() - lastSentAt < 1000) {
                return;
            }

            axios.post(config.typingUrl).catch(() => {});
        }, config.typingDebounce);
    }

    function updateCharCount() {
        if (!elements.messageInput || !elements.charCount) {
            return;
        }

        const max = elements.messageInput.maxLength > 0 ? elements.messageInput.maxLength : 500;
        elements.charCount.textContent = `${elements.messageInput.value.length}/${max}`;
    }

    function updateTypingIndicator(names) {
        if (!elements.typingIndicator || !elements.typingText) {
            return;
        }

        if (names.length === 0) {
            elements.typingIndicator.classList.add('hidden');
            elements.typingText.textContent = '';
            return;
        }

        elements.typingIndicator.classList.remove('hidden');

        if (names.length === 1) {
            elements.typingText.textContent = `${names[0]} is typing`;
        } else if (names.length === 2) {
            elements.typingText.textContent = `${names[0]} and ${names[1]} are typing`;
        } else {
            elements.typingText.textContent = `${names[0]} and ${names.length - 1} others are typing`;
        }
    }

    function updateOnlineCount(count) {
        if (!elements.onlineCount || typeof count !== 'number') {
            return;
        }

        elements.onlineCount.textContent = `${count} online`;
    }

    function updateEmptyState() {
        if (!elements.emptyState || !elements.messageList) {
            return;
        }

        elements.emptyState.classList.toggle('hidden', elements.messageList.children.length > 0);
    }

    function isNearBottom() {
        if (!elements.messages) {
            return true;
        }

        return elements.messages.scrollHeight - elements.messages.scrollTop - elements.messages.clientHeight < 120;
    }

    function scrollToBottom(smooth = true) {
        if (!elements.messages) {
            return;
        }

        elements.messages.scrollTo({
            top: elements.messages.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto',
        });
    }

    async function copyRoomLink() {
        try {
            await navigator.clipboard.writeText(window.location.href);
            showToast('Room link copied.', 'success');
        } catch {
            const helper = document.createElement('textarea');
            helper.value = window.location.href;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();

            try {
                document.execCommand('copy');
                showToast('Room link copied.', 'success');
            } catch {
                showToast('Could not copy the link.', 'error');
            }

            helper.remove();
        }
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
        writeStorage(config.soundKey, soundEnabled ? 'on' : 'off');
        updateSoundButton();

        if (soundEnabled) {
            unlockAudio();
            showToast('Sound notifications on.', 'success');
        } else {
            showToast('Sound notifications off.', 'info');
        }
    }

    function updateSoundButton() {
        if (!elements.soundToggle) {
            return;
        }

        elements.soundToggle.textContent = soundEnabled ? 'Sound on' : 'Sound off';
        elements.soundToggle.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
    }

    function ensureAudioContext() {
        if (audioContext) {
            return audioContext;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClass) {
            return null;
        }

        try {
            audioContext = new AudioContextClass();
        } catch {
            return null;
        }

        return audioContext;
    }

    function playSound() {
        if (!soundEnabled) {
            return;
        }

        const context = ensureAudioContext();

        if (!context) {
            return;
        }

        if (context.state === 'suspended') {
            context.resume().catch(() => {});
        }

        const now = context.currentTime;
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, now);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.08, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.16);

        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start(now);
        oscillator.stop(now + 0.17);
    }

    async function leaveRoom() {
        if (!window.confirm('Leave this room? This cannot be undone.')) {
            return;
        }

        try {
            await axios.post(config.leaveUrl);
            window.location.href = config.homeUrl;
        } catch (error) {
            handleError(error);
        }
    }

    function setComposerDisabled(disabled) {
        if (elements.messageInput) {
            elements.messageInput.disabled = disabled;
        }

        const submit = elements.messageForm?.querySelector('button[type="submit"]');

        if (submit) {
            submit.disabled = disabled;
        }
    }

    function stopPolling() {
        stopped = true;
        clearTimeout(pollTimer);
        clearTimeout(typingPollTimer);
        clearTimeout(typingSendTimer);
    }

    function showExpired() {
        if (expired) {
            return;
        }

        expired = true;
        stopPolling();
        clearTimeout(countdownTimer);

        setComposerDisabled(true);

        if (elements.expiredOverlay) {
            elements.expiredOverlay.classList.remove('hidden');
            elements.expiredOverlay.classList.add('flex');
        }
    }

    function handleError(error) {
        const status = error.response?.status;

        // 410 means the room just expired; 404 means the cleanup command
        // removed it before the client noticed. Both end the conversation.
        if (status === 404 || status === 410) {
            showExpired();
            return;
        }

        if (status === 419) {
            stopPolling();
            showToast('Your session expired. Reloading…', 'error');
            setTimeout(() => window.location.reload(), 1200);
            return;
        }

        if (status === 403) {
            stopPolling();
            showToast('Your session ended. Reloading…', 'error');
            setTimeout(() => window.location.reload(), 1200);
            return;
        }

        if (status === 422) {
            showToast(error.response?.data?.message || 'Please check your input.', 'error');
            return;
        }

        if (status === 429) {
            showToast(error.response?.data?.message || 'Slow down a little.', 'error');
            return;
        }

        // Avoid stacking a toast every two seconds during a network outage.
        if (Date.now() - lastGenericErrorAt > 5000) {
            lastGenericErrorAt = Date.now();
            showToast('Something went wrong. Please try again.', 'error');
        }
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');

        if (!container) {
            return;
        }

        const styles = {
            success: 'border-tertiary/30 bg-tertiary/10 text-tertiary',
            error: 'border-danger/30 bg-danger/10 text-danger',
            info: 'border-secondary/30 bg-surface text-primary',
        };

        const toast = document.createElement('div');
        toast.className = `rounded-md border px-4 py-3 text-sm ${styles[type] || styles.info}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 4000);
    }
}
