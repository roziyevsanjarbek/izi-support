<script>
document.addEventListener('DOMContentLoaded', function () {
    const queryModal = document.getElementById('queryModal');
    const taskModal = document.getElementById('taskModal');

    const queryOverlay = document.getElementById('queryModalOverlay');
    const taskOverlay = document.getElementById('taskModalOverlay');
    const queryBody = document.getElementById('queryModalBody');

    const taskQueryId = document.getElementById('taskQueryId');
    const taskOperationId = document.getElementById('taskOperationId');
    const taskName = document.getElementById('taskName');
    const taskDescription = document.getElementById('taskDescription');
    const taskAttachments = document.getElementById('taskAttachments');
    const taskAttachmentsList = document.getElementById('taskAttachmentsList');
    const taskEndDate = document.getElementById('taskEndDate');

    const showButtons = document.querySelectorAll('[data-show-query]');
    const taskButtons = document.querySelectorAll('[data-open-task]');
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    const saveTaskBtn = document.getElementById('saveTaskBtn');
    const taskNameError = document.getElementById('taskNameError');
    const taskDescriptionError = document.getElementById('taskDescriptionError');
    const taskEndDateError = document.getElementById('taskEndDate');

    const addTaskUrl = @json(url('tasks/query-tasks'));

    if (!queryModal || !taskModal || !queryOverlay || !taskOverlay || !queryBody) {
        return;
    }

    if (typeof axios !== 'undefined' && document.querySelector('meta[name="csrf-token"]')) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content');

        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    }

    let currentQueryRecord = null;
    let taskAttachmentsBuffer = [];

    flatpickr("#taskEndDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        appendTo: document.body,
        position: "below",
        disableMobile: true
    });

    flatpickr("#taskEndDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        appendTo: document.body,
        position: "auto",
        disableMobile: true,
        static: false
    });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function clearErrors() {
        if (taskNameError) {
            taskNameError.textContent = '';
            taskNameError.classList.add('hidden');
        }

        if (taskDescriptionError) {
            taskDescriptionError.textContent = '';
            taskDescriptionError.classList.add('hidden');
        }

        if (taskName) taskName.classList.remove('border-red-500');
        if (taskDescription) taskDescription.classList.remove('border-red-500');
    }

    function getByPath(obj, path, fallback = '-') {
        if (!obj || !path) return fallback;

        const parts = String(path).split('.');
        let current = obj;

        for (const part of parts) {
            if (current === null || current === undefined) return fallback;

            if (Array.isArray(current) && /^\d+$/.test(part)) {
                current = current[Number(part)];
                continue;
            }

            current = current[part];
        }

        return current === undefined || current === null || current === '' ? fallback : current;
    }

    function formatDate(value) {
        if (!value) return '-';

        const date = new Date(value);
        if (isNaN(date.getTime())) return '-';

        return date.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function normalizeValue(value, fallback = '-') {
        if (value === null || value === undefined || value === '') return fallback;
        return String(value);
    }

    function kv(label, value) {
        const display = value === null || value === undefined || value === ''
            ? '-'
            : escapeHtml(value);

        return `
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 py-2 last:border-b-0 dark:border-gray-800">
                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                    ${escapeHtml(label)}
                </div>
                <div class="max-w-[60%] break-words text-right text-sm text-gray-800 dark:text-gray-100">
                    ${display}
                </div>
            </div>
        `;
    }

    function group(title, itemsHtml) {
        return `
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                    ${escapeHtml(title)}
                </div>
                <div>${itemsHtml}</div>
            </div>
        `;
    }

    function lockScroll() {
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
    }

    function unlockScroll() {
        const queryHidden = queryModal.classList.contains('hidden');
        const taskHidden = taskModal.classList.contains('hidden');

        if (!queryHidden || !taskHidden) return;

        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openModal(modal) {
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex', 'items-start');

        lockScroll();
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        unlockScroll();
    }

    function readRecord(button) {
        const raw = button?.dataset?.record || '';
        if (!raw) return {};

        try {
            return JSON.parse(raw);
        } catch (e) {
            console.error('Invalid record payload:', raw, e);
            return {};
        }
    }

    function getAddresses(record) {
        return Array.isArray(record.addresses) ? record.addresses : [];
    }

    function getFromAddress(record) {
        const addresses = getAddresses(record);
        const from = addresses.find(item => Number(item.is_from) === 1);
        return from?.address || '-';
    }

    function getToAddress(record) {
        const addresses = getAddresses(record);
        const to = addresses.find(item => Number(item.is_from) === 0);
        return to?.address || '-';
    }

    function getOperation(record) {
        const operations = Array.isArray(record.operations) ? record.operations : [];
        return operations[0] || {};
    }

    function getOperationName(record) {
        return getByPath(record, 'operations.0.user.name', '-');
    }

    function getSalesName(record) {
        return getByPath(record, 'sales.name', '-');
    }

    function getCustomerName(record) {
        return getByPath(record, 'customer_view.company_name', '-');
    }

    function getCustomerPhone(record) {
        return getByPath(record, 'customer_view.phone_number', '-');
    }

    function getCustomerTin(record) {
        return getByPath(record, 'customer_view.tin', '-');
    }

    function getPackageText(record) {
        return (
            getByPath(record, 'shipment_type.loading_type.name', '') ||
            getByPath(record, 'shipment_type.loading_type.title', '') ||
            getByPath(record, 'shipment_type.loading_type_id', '-')
        );
    }

    function getDimensionsText(record) {
        const length = getByPath(record, 'shipment_type.length', '');
        const width = getByPath(record, 'shipment_type.width', '');
        const height = getByPath(record, 'shipment_type.height', '');

        if (length !== '' || width !== '' || height !== '') {
            return [length || '-', width || '-', height || '-'].join(' x ');
        }

        const trailerFloorVolume = getByPath(record, 'shipment_type.trailer_floor_volume', '');
        if (trailerFloorVolume !== '') {
            return trailerFloorVolume;
        }

        return '-';
    }

    function getVolumeText(record) {
        return getByPath(record, 'shipment_type.cargo_volume', '-');
    }

    function getWeightText(record) {
        const weight = getByPath(record, 'shipment_type.cargo_weight', '-');
        if (weight === '-' || weight === null || weight === undefined || weight === '') return '-';
        return `${weight} kg`;
    }

    function getStatusText(record) {
        return (
            getByPath(record, 'query_status.label', '') ||
            getByPath(record, 'query_status.name', '') ||
            normalizeValue(record.query_status_id, '-')
        );
    }

    function buildCopyText(record) {
        const lines = [
            `POL: ${getFromAddress(record)}`,
            `POD: ${getToAddress(record)}`,
            `Unit: ${normalizeValue(getByPath(record, 'shipment_type.count_of_cars', '-'))}`,
            `Commodity: ${normalizeValue(record.cargo_name, '-')}`,
            `HS code: ${normalizeValue(getByPath(record, 'shipment_type.code_tnved', '-'))}`,
            `Package: ${normalizeValue(getPackageText(record), '-')}`,
            `Dimensions: ${normalizeValue(getDimensionsText(record), '-')}`,
            `Volume: ${normalizeValue(getVolumeText(record), '-')}`,
            `Weight: ${normalizeValue(getWeightText(record), '-')}`,
            `Status: ${normalizeValue(getStatusText(record), '-')}`,
        ];

        return lines.join('\n');
    }

    async function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            document.execCommand('copy');
        } finally {
            document.body.removeChild(textarea);
        }
    }

    function isImageFile(file) {
        return (file?.type || '').startsWith('image/');
    }

    function isImageAttachment(attachment) {
        return (attachment?.mime_type || '').toLowerCase().startsWith('image/');
    }

    function humanFileSize(bytes) {
        if (!bytes) return '0 B';

        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = Number(bytes);
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
    }

    function fileBadge(file) {
        const mime = (file?.type || '').toLowerCase();
        const ext = (file?.name || '').split('.').pop()?.toLowerCase() || '';

        if (mime.includes('pdf') || ext === 'pdf') return 'PDF';
        if (mime.includes('word') || ext === 'doc' || ext === 'docx') return 'DOC';
        if (mime.includes('excel') || ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'XLS';
        if (mime.includes('zip') || ext === 'zip' || ext === 'rar' || ext === '7z') return 'ZIP';
        if (mime.startsWith('video/')) return 'VID';
        if (mime.startsWith('audio/')) return 'AUD';
        if (ext) return ext.slice(0, 3).toUpperCase();

        return 'FILE';
    }

    function attachmentBadge(attachment) {
        const mime = (attachment?.mime_type || '').toLowerCase();
        const ext = (attachment?.extension || '').toLowerCase();

        if (mime.includes('pdf') || ext === 'pdf') return 'PDF';
        if (mime.includes('word') || ext === 'doc' || ext === 'docx') return 'DOC';
        if (mime.includes('excel') || ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'XLS';
        if (mime.includes('zip') || ext === 'zip' || ext === 'rar' || ext === '7z') return 'ZIP';
        if (mime.startsWith('video/')) return 'VID';
        if (mime.startsWith('audio/')) return 'AUD';
        if (ext) return ext.slice(0, 3).toUpperCase();

        return 'FILE';
    }

    function renderTaskAttachments() {
        if (!taskAttachmentsList) return;

        if (!taskAttachmentsBuffer.length) {
            taskAttachmentsList.innerHTML = '';
            return;
        }

        taskAttachmentsList.innerHTML = taskAttachmentsBuffer.map((file, index) => {
            const badge = isImageFile(file)
                ? 'IMG'
                : fileBadge(file);

            return `
                <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-xs font-bold text-gray-600 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300">
                            ${badge}
                        </div>

                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(file.name)}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${humanFileSize(file.size)}</div>
                        </div>
                    </div>

                    <button type="button" data-remove-task-file="${index}" class="text-sm font-semibold text-red-500 hover:text-red-600">
                        Remove
                    </button>
                </div>
            `;
        }).join('');

        taskAttachmentsList.querySelectorAll('[data-remove-task-file]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const index = Number(btn.dataset.removeTaskFile);
                taskAttachmentsBuffer.splice(index, 1);
                renderTaskAttachments();
                if (taskAttachments) taskAttachments.value = '';
            });
        });
    }

    async function handleCopyQuery() {
        if (!currentQueryRecord) return;

        const button = document.getElementById('copyQueryTextBtn');
        const originalText = button ? button.textContent : 'Copy';

        try {
            await copyTextToClipboard(buildCopyText(currentQueryRecord));

            if (button) {
                button.textContent = 'Copied';
                button.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                button.classList.remove('bg-brand-500', 'hover:bg-brand-600');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                    button.classList.add('bg-brand-500', 'hover:bg-brand-600');
                }, 1200);
            }
        } catch (error) {
            console.error('Copy failed:', error);

            if (button) {
                button.textContent = 'Failed';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 1200);
            }
        }
    }

    function openQueryModal(record) {
        currentQueryRecord = record;
        console.log('Opening query modal for record:', record);

        const fromAddress = getFromAddress(record);
        const toAddress = getToAddress(record);
        const operationName = getOperationName(record);
        const salesName = getSalesName(record);
        const statusText = getStatusText(record);
        const customerName = getCustomerName(record);
        const customerTin = getCustomerTin(record);
        const customerPhone = getCustomerPhone(record);

        const routeHeader = `
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900/60">
                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                    Route
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                    <span>${escapeHtml(fromAddress)}</span>
                    <span class="text-gray-400">→</span>
                    <span>${escapeHtml(toAddress)}</span>
                </div>
            </div>
        `;

        const copyBlock = `
            <div class="flex justify-end">
                <button
                    type="button"
                    id="copyQueryTextBtn"
                    class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white transition hover:bg-brand-600">
                    Copy
                </button>
            </div>
        `;

        const overviewHtml = [
            kv('Cargo', record.cargo_name),
            kv('Status', statusText),
            kv('Created At', formatDate(record.created_at)),
            kv('Loading At', record.loading_at),
        ].join('');

        const customerHtml = [
            kv('Company', customerName),
            kv('TIN', customerTin),
            kv('Phone', customerPhone),
        ].join('');

        const cargoHtml = [
            kv('TNVED Code', getByPath(record, 'shipment_type.code_tnved')),
            kv('Weight', `${getByPath(record, 'shipment_type.cargo_weight')} kg`),
            kv('Volume', getByPath(record, 'shipment_type.cargo_volume')),
            kv('Trailer Floor', getByPath(record, 'shipment_type.trailer_floor_volume')),
            kv('Count of Cars', getByPath(record, 'shipment_type.count_of_cars')),
            kv('Dangerous Load', Number(record.is_dangerous_load) === 1 ? 'Yes' : 'No'),
        ].join('');

        const peopleHtml = [
            kv('Sales', salesName),
            kv('Operation', operationName),
        ].join('');

        queryBody.innerHTML = [
            copyBlock,
            routeHeader,
            group('Overview', overviewHtml),
            group('Customer', customerHtml),
            group('Cargo', cargoHtml),
            group('People', peopleHtml),
        ].join('');

        openModal(queryModal);

        const copyBtn = document.getElementById('copyQueryTextBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', handleCopyQuery, { once: true });
        }
    }

    function openTaskModal(record) {
        const operation = getOperation(record);
        const queryId = record?.id ?? '';
        const operationId = operation?.operation_id ?? operation?.id ?? getByPath(record, 'operations.0.user.id', '');

        if (taskQueryId) taskQueryId.value = queryId;
        if (taskOperationId) taskOperationId.value = operationId;

        if (taskName) taskName.value = '';
        if (taskDescription) taskDescription.value = '';

        taskAttachmentsBuffer = [];
        renderTaskAttachments();

        clearErrors();
        openModal(taskModal);
    }

    async function submitTask() {
        const name = taskName ? taskName.value.trim() : '';
        const description = taskDescription ? taskDescription.value.trim() : '';
        const endDate = taskEndDate ? taskEndDate.value.trim() : '';

        const payload = new FormData();
        payload.append('query_id', taskQueryId ? taskQueryId.value : '');
        payload.append('operation_id', taskOperationId ? taskOperationId.value : '');
        payload.append('name', name);
        payload.append('description', description);
        payload.append('end_date', endDate);
        taskAttachmentsBuffer.forEach((file) => {
            payload.append('attachments[]', file);
        });

        if (saveTaskBtn) {
            saveTaskBtn.disabled = true;
            saveTaskBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        try {
            const response = await axios.post(addTaskUrl, payload, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            closeModal(taskModal);

            if (taskName) taskName.value = '';
            if (taskDescription) taskDescription.value = '';
            taskAttachmentsBuffer = [];
            renderTaskAttachments();

            window.dispatchEvent(new CustomEvent('task-created', {
                detail: response.data,
            }));
        } catch (error) {
            clearErrors();

            const errors = error?.response?.data?.errors || {};

            if (errors.name) {
                taskNameError.textContent = errors.name[0];
                taskNameError.classList.remove('hidden');
                taskName.classList.add('border-red-500');
            }

            if (errors.description) {
                taskDescriptionError.textContent = errors.description[0];
                taskDescriptionError.classList.remove('hidden');
                taskDescription.classList.add('border-red-500');
            }

            if(errors.endDate) {
                taskEndDateError.textContent = errors.endDate[0];
                taskEndDateError.classList.remove('hidden');
                taskEndDate.classList.add('border-red-500');
            }

            if (errors.attachments) {
                taskDescriptionError.textContent = errors.attachments[0];
                taskDescriptionError.classList.remove('hidden');
                taskDescription.classList.add('border-red-500');
            }
        } finally {
            if (saveTaskBtn) {
                saveTaskBtn.disabled = false;
                saveTaskBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    }

    showButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const record = readRecord(button);
            openQueryModal(record);
        });
    });

    taskButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const record = readRecord(button);
            openTaskModal(record);
        });
    });

    if (taskAttachments) {
        taskAttachments.addEventListener('change', (event) => {
            const files = Array.from(event.target.files || []);
            const remaining = Math.max(0, 100 - taskAttachmentsBuffer.length);

            taskAttachmentsBuffer.push(...files.slice(0, remaining));
            renderTaskAttachments();
            taskAttachments.value = '';
        });
    }

    if (queryOverlay) {
        queryOverlay.addEventListener('click', () => closeModal(queryModal));
    }

    if (taskOverlay) {
        taskOverlay.addEventListener('click', () => closeModal(taskModal));
    }

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.dataset.closeModal;
            const modal = document.getElementById(modalId);

            if (modal) {
                closeModal(modal);
            }
        });
    });

    if (saveTaskBtn) {
        saveTaskBtn.addEventListener('click', submitTask);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (!queryModal.classList.contains('hidden')) {
            closeModal(queryModal);
        }

        if (!taskModal.classList.contains('hidden')) {
            closeModal(taskModal);
        }
    });
});
</script>
