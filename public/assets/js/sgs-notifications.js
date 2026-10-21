/**
 * SGS Dynamic Notifications Manager
 * Handles automatic periodic polling (~20s), badge/dropdown DOM sync,
 * floating Toast visual alerts, and soft audio chime playback with browser autoplay unlock.
 */

(function () {
    'use strict';

    let lastMaxId = 0;
    let isPolling = false;
    let pollInterval = null;
    const POLL_FREQ_MS = 20000; // 20 seconds

    // Audio Singleton & Unlock State
    let audioInstance = null;
    let isAudioUnlocked = false;

    function getAudioInstance() {
        if (!audioInstance) {
            audioInstance = new Audio('/assets/sounds/notification.wav');
            audioInstance.preload = 'auto';
        }
        return audioInstance;
    }

    function unlockAudio() {
        if (isAudioUnlocked) return;
        try {
            const audio = getAudioInstance();
            audio.muted = true;
            const promise = audio.play();
            if (promise !== undefined) {
                promise.then(function () {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.muted = false;
                    isAudioUnlocked = true;
                }).catch(function () {
                    if (audio) audio.muted = false;
                });
            }
        } catch (e) {
            // Silence initial unlock attempt errors
        }

        document.removeEventListener('click', unlockAudio);
        document.removeEventListener('keydown', unlockAudio);
        document.removeEventListener('touchstart', unlockAudio);
    }

    function setupAudioUnlock() {
        document.addEventListener('click', unlockAudio, { once: true });
        document.addEventListener('keydown', unlockAudio, { once: true });
        document.addEventListener('touchstart', unlockAudio, { once: true });
    }

    // Audio Chime Playback
    function playChime() {
        try {
            const audio = getAudioInstance();
            audio.currentTime = 0;
            audio.muted = false;
            const promise = audio.play();
            if (promise !== undefined) {
                promise.catch(function (err) {
                    console.warn('[SGS Notifications] Audio playback prevented by browser policy:', err);
                });
            }
        } catch (e) {
            console.warn('[SGS Notifications] Sound playback error:', e);
        }
    }

    // DOM Helpers
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content');
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Toast Alerts
    function ensureToastContainer() {
        let container = document.getElementById('sgs-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'sgs-toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            container.style.maxWidth = '360px';
            container.style.width = '100%';
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(notification) {
        const container = ensureToastContainer();

        // Limit visible toasts to max 3
        const existingToasts = container.querySelectorAll('.sgs-toast');
        if (existingToasts.length >= 3) {
            existingToasts[0].remove();
        }

        const toast = document.createElement('div');
        toast.className = 'sgs-toast alert alert-info alert-dismissible shadow-lg fade show mb-2 p-3 bg-white border border-info rounded-3';
        toast.style.color = '#333';
        toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';

        const headerDiv = document.createElement('div');
        headerDiv.className = 'd-flex align-items-center justify-content-between mb-1';

        const titleSpan = document.createElement('strong');
        titleSpan.className = 'text-primary me-auto';
        titleSpan.textContent = 'Notification SGS';

        const timeSmall = document.createElement('small');
        timeSmall.className = 'text-muted ms-2';
        timeSmall.textContent = 'À l\'instant';

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close ms-2';
        closeBtn.style.padding = '0.25rem';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.onclick = function () {
            toast.remove();
        };

        headerDiv.appendChild(titleSpan);
        headerDiv.appendChild(timeSmall);
        headerDiv.appendChild(closeBtn);

        const bodyDiv = document.createElement('div');
        bodyDiv.className = 'toast-body p-0 mt-1';

        const msgP = document.createElement('p');
        msgP.className = 'mb-1 text-dark small';
        msgP.textContent = notification.message || '';
        bodyDiv.appendChild(msgP);

        if (notification.link) {
            const linkA = document.createElement('a');
            linkA.href = '/notifications/mark-as-read?id=' + encodeURIComponent(notification.id) + '&redirect_to=' + encodeURIComponent(notification.link);
            linkA.className = 'btn btn-xs btn-outline-primary mt-1 text-decoration-none';
            linkA.textContent = 'Consulter';
            bodyDiv.appendChild(linkA);
        }

        toast.appendChild(headerDiv);
        toast.appendChild(bodyDiv);
        container.appendChild(toast);

        // Auto remove after 6 seconds
        setTimeout(function () {
            if (toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }
        }, 6000);
    }

    // UI Updates
    function updateNotificationUI(data) {
        const badgeEl = document.getElementById('notification-badge');
        const count = data.unread_count || 0;

        if (badgeEl) {
            if (count > 0) {
                badgeEl.textContent = count;
                badgeEl.style.display = 'inline-block';
            } else {
                badgeEl.textContent = '0';
                badgeEl.style.display = 'none';
            }
        }

        const listContainer = document.getElementById('notification-dropdown-list');
        if (listContainer) {
            const notifications = data.notifications || [];
            if (notifications.length === 0) {
                listContainer.innerHTML = '<div class="py-3 text-center"><p class="text-muted mb-0">Aucune nouvelle notification</p></div>';
            } else {
                let html = '';
                notifications.forEach(function (notif) {
                    const linkUrl = '/notifications/mark-as-read?id=' + encodeURIComponent(notif.id) + '&redirect_to=' + encodeURIComponent(notif.link || '#');
                    html += '<a href="' + escapeHtml(linkUrl) + '" class="dropdown-item px-3 py-2 border-bottom notification-item" data-id="' + notif.id + '">';
                    html += '  <div class="d-flex align-items-start">';
                    html += '    <div class="flex-shrink-0 me-2 mt-1">';
                    html += '      <i class="ph-duotone ph-warning-circle text-primary fs-5"></i>';
                    html += '    </div>';
                    html += '    <div class="flex-grow-1">';
                    html += '      <p class="mb-0 text-wrap small text-dark">' + escapeHtml(notif.message) + '</p>';
                    html += '      <small class="text-muted" style="font-size: 0.75rem;">' + escapeHtml(notif.created_at || '') + '</small>';
                    html += '    </div>';
                    html += '  </div>';
                    html += '</a>';
                });
                listContainer.innerHTML = html;
            }
        }
    }

    // Ajax Mark As Read Action
    window.sgsMarkNotificationAsRead = function (notificationId, callback) {
        const token = getCsrfToken();
        fetch('/notifications/mark-as-read-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: notificationId, csrf_token: token })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    pollNotifications();
                    if (typeof callback === 'function') callback(data);
                }
            })
            .catch(function (err) {
                console.warn('[SGS Notifications] Error marking notification read:', err);
            });
    };

    window.sgsMarkAllNotificationsAsRead = function (e) {
        if (e) e.preventDefault();
        const token = getCsrfToken();
        fetch('/notifications/mark-all-as-read-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ csrf_token: token })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    updateNotificationUI({ unread_count: 0, notifications: [] });
                } else {
                    // Fallback to standard HTTP redirect link
                    window.location.href = '/notifications/mark-all-as-read';
                }
            })
            .catch(function () {
                window.location.href = '/notifications/mark-all-as-read';
            });
    };

    // Polling Logic
    function pollNotifications() {
        if (isPolling) return;
        isPolling = true;

        const url = '/notifications/unread?since_id=' + encodeURIComponent(lastMaxId);

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (response.status === 401 || response.status === 403) {
                    // User logged out or session expired - stop polling
                    if (pollInterval) clearInterval(pollInterval);
                    return null;
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return null;
                }
                return response.json();
            })
            .then(function (data) {
                isPolling = false;
                if (!data || data.status !== 'success') return;

                updateNotificationUI(data);

                const newItems = data.new_notifications || [];

                if (newItems.length > 0) {
                    lastMaxId = data.max_id || lastMaxId;
                    try {
                        sessionStorage.setItem('sgs_last_notified_id', String(lastMaxId));
                    } catch (e) {}

                    // Display Toasts for new notifications (max 3)
                    const itemsToToast = newItems.slice(0, 3);
                    itemsToToast.forEach(function (notif) {
                        showToast(notif);
                    });

                    // Play audio chime safely
                    playChime();
                } else if (data.max_id > lastMaxId) {
                    lastMaxId = data.max_id;
                    try {
                        sessionStorage.setItem('sgs_last_notified_id', String(lastMaxId));
                    } catch (e) {}
                }
            })
            .catch(function (err) {
                isPolling = false;
                console.warn('[SGS Notifications] Polling network error:', err);
            });
    }

    // Initialize on DOM Ready
    function init() {
        setupAudioUnlock();

        const headerBadge = document.getElementById('notification-badge');
        if (!headerBadge) {
            // Not on an authenticated page with header badge, do not initialize polling or sessionStorage
            return;
        }

        // Persistent tracking across page navigations in the same browser tab
        let storedMaxId = null;
        try {
            storedMaxId = sessionStorage.getItem('sgs_last_notified_id');
        } catch (e) {}

        if (storedMaxId !== null && storedMaxId !== undefined) {
            lastMaxId = parseInt(storedMaxId, 10) || 0;
        } else {
            const initialMaxId = headerBadge.getAttribute('data-max-id');
            if (initialMaxId) {
                lastMaxId = parseInt(initialMaxId, 10) || 0;
            }
            try {
                sessionStorage.setItem('sgs_last_notified_id', String(lastMaxId));
            } catch (e) {}
        }

        // Expose lastMaxId getter/setter for testing environment
        window.sgsGetLastMaxId = function () { return lastMaxId; };
        window.sgsTriggerPoll = function () { pollNotifications(); };

        // Attach event listener for "Tout marquer comme lu" link if present
        const markAllLink = document.getElementById('btn-mark-all-read');
        if (markAllLink) {
            markAllLink.addEventListener('click', window.sgsMarkAllNotificationsAsRead);
        }

        // Initial poll immediately
        pollNotifications();

        // Start periodic polling every 20s
        pollInterval = setInterval(pollNotifications, POLL_FREQ_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
