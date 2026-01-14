import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.tms-app')) {
        return;
    }

    const API_BASE = '/api';
    const state = {
        token: localStorage.getItem('tms_token') || '',
        mode: 'index',
        page: 1,
        lastPage: 1,
        searchParams: {},
        lastExport: null
    };

    const tokenStatus = document.getElementById('tokenStatus');
    const tokenDot = document.getElementById('tokenDot');
    const authMessage = document.getElementById('authMessage');
    const createMessage = document.getElementById('createMessage');
    const updateMessage = document.getElementById('updateMessage');
    const translationsBody = document.getElementById('translationsBody');
    const tableMessage = document.getElementById('tableMessage');
    const pageStatus = document.getElementById('pageStatus');
    const tableCount = document.getElementById('tableCount');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const exportOutput = document.getElementById('exportOutput');
    const downloadExport = document.getElementById('downloadExport');
    const exportForm = document.getElementById('exportForm');
    const resetExport = document.getElementById('resetExport');
    const exportModal = document.getElementById('exportModal');

    const authOnlyButtons = [
        document.getElementById('logoutBtn'),
        document.getElementById('createForm').querySelector('button[type="submit"]'),
        document.getElementById('updateForm').querySelector('button[type="submit"]'),
        document.getElementById('searchForm').querySelector('button[type="submit"]'),
        exportForm.querySelector('button[type="submit"]'),
        resetExport,
        downloadExport
    ];

    function setToken(token) {
        state.token = token;
        if (token) {
            localStorage.setItem('tms_token', token);
        } else {
            localStorage.removeItem('tms_token');
        }
        updateTokenUI();
    }

    function updateTokenUI() {
        if (state.token) {
            tokenStatus.textContent = 'Token active';
            tokenDot.classList.add('online');
        } else {
            tokenStatus.textContent = 'No token saved';
            tokenDot.classList.remove('online');
        }
        authOnlyButtons.forEach((button) => {
            button.disabled = !state.token;
        });
    }

    async function fetchJson(url, options = {}) {
        const headers = options.headers || {};
        headers.Accept = headers.Accept || 'application/json';
        if (state.token) {
            headers.Authorization = `Bearer ${state.token}`;
        }
        if (options.body && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }
        const response = await fetch(url, { ...options, headers });
        const text = await response.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (error) {
            data = text;
        }
        if (!response.ok) {
            const fallback = typeof data === 'string' && data
                ? data.slice(0, 200)
                : response.statusText;
            const message = data && data.message ? data.message : `Request failed (${response.status}). ${fallback}`;
            throw new Error(message);
        }
        return data;
    }

    function showMessage(target, message, tone = 'info') {
        const prefix = tone === 'error' ? 'Error: ' : '';
        target.textContent = message ? `${prefix}${message}` : '';
    }

    function parseTags(raw) {
        if (!raw) {
            return [];
        }
        return raw.split(',').map((tag) => tag.trim()).filter(Boolean);
    }

    async function loadTranslations() {
        if (!state.token) {
            translationsBody.innerHTML = '';
            pageStatus.textContent = 'Page 1';
            tableCount.textContent = 'Showing 0 to 0 of 0 entries';
            prevPage.disabled = true;
            nextPage.disabled = true;
            showMessage(tableMessage, '');
            return;
        }

        const endpoint = state.mode === 'search'
            ? `${API_BASE}/translations/search`
            : `${API_BASE}/translations`;

        const params = new URLSearchParams(state.searchParams);
        params.set('page', state.page);

        try {
            const data = await fetchJson(`${endpoint}?${params.toString()}`);
            renderTable(data.data || []);
            const pagination = data.meta || data;
            state.page = pagination.current_page || 1;
            state.lastPage = pagination.last_page || 1;
            pageStatus.textContent = `Page ${state.page} of ${state.lastPage}`;
            const total = pagination.total ?? 0;
            const from = pagination.from ?? 0;
            const to = pagination.to ?? 0;
            tableCount.textContent = `Showing ${from} to ${to} of ${total} entries`;
            prevPage.disabled = state.page <= 1;
            nextPage.disabled = state.page >= state.lastPage;
            showMessage(tableMessage, '');
        } catch (error) {
            renderTable([]);
            showMessage(tableMessage, error.message, 'error');
            pageStatus.textContent = 'Page 1';
            tableCount.textContent = 'Showing 0 to 0 of 0 entries';
            prevPage.disabled = true;
            nextPage.disabled = true;
        }
    }

    function renderTable(items) {
        translationsBody.innerHTML = '';
        if (!items.length) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" style="color: var(--muted);">No translations found.</td>';
            translationsBody.appendChild(row);
            return;
        }

        items.forEach((item, index) => {
            const row = document.createElement('tr');
            const tagHtml = (item.tags || [])
                .map((tag) => `<span class="tag">${tag.name}</span>`)
                .join('');
            row.style.animation = `fadeUp 0.35s ease ${(index + 1) * 0.03}s both`;
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.key}</td>
                <td>${item.content}</td>
                <td>${item.locale?.code ?? '-'}</td>
                <td>${tagHtml || '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="icon-btn" data-action="edit" data-id="${item.id}" aria-label="Edit">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 20h4l10.5-10.5a1.5 1.5 0 0 0 0-2.1l-1.9-1.9a1.5 1.5 0 0 0-2.1 0L4 16v4z"></path>
                                <path d="M13.5 6.5l4 4"></path>
                            </svg>
                        </button>
                        <button type="button" class="icon-btn danger" data-action="delete" data-id="${item.id}" aria-label="Delete">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 7h12"></path>
                                <path d="M9 7V5h6v2"></path>
                                <path d="M8 7l1 12h6l1-12"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            translationsBody.appendChild(row);
        });
    }

    document.getElementById('authForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(authMessage, '');
        try {
            const payload = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                device_name: document.getElementById('deviceName').value || 'web-console'
            };
            const data = await fetchJson(`${API_BASE}/auth/token`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            setToken(data.token);
            showMessage(authMessage, 'Token saved.');
            state.page = 1;
            state.mode = 'index';
            await loadTranslations();
        } catch (error) {
            showMessage(authMessage, error.message, 'error');
        }
    });

    document.getElementById('logoutBtn').addEventListener('click', async () => {
        if (!state.token) {
            return;
        }
        try {
            await fetchJson(`${API_BASE}/auth/logout`, { method: 'POST' });
        } catch (error) {
            // ignore
        }
        setToken('');
        translationsBody.innerHTML = '';
        showMessage(authMessage, 'Logged out.');
    });

    document.getElementById('createForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(createMessage, '');
        try {
            const payload = {
                key: document.getElementById('createKey').value,
                content: document.getElementById('createContent').value,
                locale: document.getElementById('createLocale').value,
                tags: parseTags(document.getElementById('createTags').value)
            };
            await fetchJson(`${API_BASE}/translations`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            showMessage(createMessage, 'Created.');
            event.target.reset();
            state.page = 1;
            state.mode = 'index';
            await loadTranslations();
        } catch (error) {
            showMessage(createMessage, error.message, 'error');
        }
    });

    document.getElementById('updateForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(updateMessage, '');
        const id = document.getElementById('updateId').value;
        if (!id) {
            showMessage(updateMessage, 'Select a translation to update.', 'error');
            return;
        }
        try {
            const payload = {
                content: document.getElementById('updateContent').value,
                tags: parseTags(document.getElementById('updateTags').value)
            };
            await fetchJson(`${API_BASE}/translations/${id}`, {
                method: 'PUT',
                body: JSON.stringify(payload)
            });
            showMessage(updateMessage, 'Updated.');
            await loadTranslations();
        } catch (error) {
            showMessage(updateMessage, error.message, 'error');
        }
    });

    document.getElementById('clearUpdate').addEventListener('click', () => {
        document.getElementById('updateId').value = '';
        document.getElementById('updateContent').value = '';
        document.getElementById('updateTags').value = '';
        showMessage(updateMessage, '');
    });

    document.getElementById('searchForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        state.mode = 'search';
        state.page = 1;
        state.searchParams = {
            key: document.getElementById('searchKey').value,
            content: document.getElementById('searchContent').value,
            locale: document.getElementById('searchLocale').value,
            tag: document.getElementById('searchTag').value
        };
        await loadTranslations();
    });

    document.getElementById('resetSearch').addEventListener('click', async () => {
        document.getElementById('searchForm').reset();
        state.mode = 'index';
        state.page = 1;
        state.searchParams = {};
        await loadTranslations();
    });

    prevPage.addEventListener('click', async () => {
        if (state.page > 1) {
            state.page -= 1;
            await loadTranslations();
        }
    });

    nextPage.addEventListener('click', async () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            await loadTranslations();
        }
    });

    translationsBody.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }
        const id = button.dataset.id;
        const action = button.dataset.action;
        if (action === 'edit') {
            const row = button.closest('tr');
            document.getElementById('updateId').value = id;
            document.getElementById('updateContent').value = row.children[2].textContent.trim();
            const tags = Array.from(row.children[4].querySelectorAll('.tag'))
                .map((tag) => tag.textContent);
            document.getElementById('updateTags').value = tags.join(', ');
            showMessage(updateMessage, 'Ready to update.');
        }
        if (action === 'delete') {
            if (!confirm('Delete this translation?')) {
                return;
            }
            try {
                await fetchJson(`${API_BASE}/translations/${id}`, { method: 'DELETE' });
                await loadTranslations();
            } catch (error) {
                showMessage(updateMessage, error.message, 'error');
            }
        }
    });

    exportForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const locale = document.getElementById('exportLocale').value;
        const tag = document.getElementById('exportTag').value;
        const params = new URLSearchParams();
        if (tag) {
            params.set('tag', tag);
        }
        try {
            const data = await fetchJson(`${API_BASE}/export/${locale}?${params.toString()}`);
            state.lastExport = data;
            exportOutput.textContent = JSON.stringify(data, null, 2);
            exportModal.classList.add('is-open');
            exportModal.setAttribute('aria-hidden', 'false');
        } catch (error) {
            exportOutput.textContent = JSON.stringify({ error: error.message }, null, 2);
            exportModal.classList.add('is-open');
            exportModal.setAttribute('aria-hidden', 'false');
        }
    });

    resetExport.addEventListener('click', () => {
        resetExportState();
    });

    downloadExport.addEventListener('click', () => {
        if (!state.lastExport) {
            return;
        }
        const blob = new Blob([JSON.stringify(state.lastExport, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'translations.json';
        link.click();
        URL.revokeObjectURL(url);
    });

    function resetExportState() {
        exportForm.reset();
        state.lastExport = null;
        exportOutput.textContent = '{}';
    }

    function closeExportModal() {
        exportModal.classList.remove('is-open');
        exportModal.setAttribute('aria-hidden', 'true');
        resetExportState();
    }

    exportModal.addEventListener('click', (event) => {
        if (event.target.closest('[data-modal-close]')) {
            closeExportModal();
        }
    });

    updateTokenUI();
    if (state.token) {
        loadTranslations();
    }
});
