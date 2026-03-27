@props([
    'scope' => 'tenant',
    'resourceType',
    'resourceId' => null,
    'redirectUrl' => '/',
    'readOnlyOnDeny' => true,
])

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const config = {
                scope: @js($scope),
                resource_type: @js($resourceType),
                resource_id: @js($resourceId),
                redirect_url: @js($redirectUrl),
                read_only_on_deny: @js((bool) $readOnlyOnDeny),
                acquire_url: @js(route('page-locks.acquire')),
                heartbeat_url: @js(route('page-locks.heartbeat')),
                release_url: @js(route('page-locks.release')),
                heartbeat_ms: @js((int) config('page_locks.heartbeat_seconds', 15) * 1000),
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            };

            if (!config.resource_type) {
                return;
            }

            let heartbeatTimer = null;
            let isReadOnly = false;
            let lostHandled = false;
            let recoveringMissing = false;
            let isSubmitting = false;

            const holderName = (lock) => lock?.locked_by_user_name || lock?.locked_by_user_email || 'Unknown user';

            const postJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                return response.json();
            };

            const showReadOnlyBanner = (message) => {
                const banner = document.createElement('div');
                banner.className = 'mb-4 rounded-md bg-yellow-100 border border-yellow-300 p-3 text-sm text-yellow-900';
                banner.textContent = message;

                const main = document.querySelector('main .container');
                if (main) {
                    main.insertBefore(banner, main.firstChild);
                }
            };

            const applyReadOnly = () => {
                isReadOnly = true;
                const interactive = document.querySelectorAll('form input, form textarea, form select, form button');
                interactive.forEach((el) => {
                    if (el.type === 'hidden') {
                        return;
                    }
                    el.disabled = true;
                });
                document.querySelectorAll('[wire\\:click], [wire\\:submit]').forEach((el) => {
                    el.style.pointerEvents = 'none';
                    el.style.opacity = '0.6';
                });
            };

            const handleServiceUnavailable = () => {
                const message = @js(__('hiko.page_lock_service_unavailable'));
                if (window.flashError) {
                    window.flashError(message, false);
                }
                if (config.read_only_on_deny) {
                    applyReadOnly();
                    showReadOnlyBanner(message);
                } else {
                    window.location.href = config.redirect_url;
                }
            };

            const handleLost = (payload) => {
                if (lostHandled) {
                    return;
                }
                lostHandled = true;

                if (heartbeatTimer) {
                    clearInterval(heartbeatTimer);
                }

                const message = payload?.lock
                    ? @js(__('hiko.page_lock_lost_redirecting')) + ' ' + holderName(payload.lock)
                    : @js(__('hiko.page_lock_not_owned'));
                if (window.flashError) {
                    window.flashError(message, false);
                }
                alert(message);

                setTimeout(() => {
                    window.location.href = config.redirect_url;
                }, 3000);
            };

            const attemptRecoverFromMissing = async () => {
                if (recoveringMissing || isReadOnly) {
                    return true;
                }

                recoveringMissing = true;

                try {
                    const reacquire = await acquire(false);

                    if (reacquire?.acquired) {
                        return true;
                    }

                    if (reacquire?.status === 'locked') {
                        handleLost(reacquire);
                    }

                    return false;
                } catch (e) {
                    return false;
                } finally {
                    recoveringMissing = false;
                }
            };

            const startHeartbeat = () => {
                if (heartbeatTimer || isReadOnly) {
                    return;
                }

                heartbeatTimer = setInterval(async () => {
                    try {
                        const res = await postJson(config.heartbeat_url, {
                            scope: config.scope,
                            resource_type: config.resource_type,
                            resource_id: config.resource_id,
                        });

                        if (!res.ok) {
                            if (res.status === 'missing') {
                                const recovered = await attemptRecoverFromMissing();
                                if (!recovered && !lostHandled) {
                                    handleServiceUnavailable();
                                }
                                return;
                            }
                            handleLost(res);
                        }
                    } catch (e) {
                        // Keep lock alive locally; next tick may recover.
                    }
                }, config.heartbeat_ms);
            };

            const acquire = async (force = false) => {
                return postJson(config.acquire_url, {
                    scope: config.scope,
                    resource_type: config.resource_type,
                    resource_id: config.resource_id,
                    force,
                });
            };

            const initLock = async () => {
                try {
                    const res = await acquire(false);

                    if (res.acquired) {
                        startHeartbeat();
                        return;
                    }

                     if (res.status !== 'locked') {
                        const message = @js(__('hiko.page_lock_service_unavailable'));
                        if (window.flashError) {
                            window.flashError(message, false);
                        }
                        if (config.read_only_on_deny) {
                            applyReadOnly();
                            showReadOnlyBanner(message);
                        } else {
                            window.location.href = config.redirect_url;
                        }
                        return;
                    }

                    const message = @js(__('hiko.page_lock_takeover_confirm')) + ' ' + holderName(res.lock);
                    const shouldTakeOver = confirm(message);

                    if (shouldTakeOver) {
                        const takeover = await acquire(true);
                        if (takeover.acquired) {
                            if (window.flashInfo) {
                                window.flashInfo(@js(__('hiko.page_lock_takeover_success')));
                            }
                            startHeartbeat();
                            return;
                        }
                    }

                    if (config.read_only_on_deny) {
                        applyReadOnly();
                        showReadOnlyBanner(@js(__('hiko.page_lock_read_only_mode')) + ' ' + holderName(res.lock));
                    } else {
                        window.location.href = config.redirect_url;
                    }
                } catch (e) {
                    // If lock service fails, do not block work.
                }
            };

            const release = () => {
                if (isReadOnly || isSubmitting) {
                    return;
                }
                fetch(config.release_url, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        scope: config.scope,
                        resource_type: config.resource_type,
                        resource_id: config.resource_id,
                    }),
                }).catch(() => {});
            };

            document.addEventListener('submit', (event) => {
                if (event.target instanceof HTMLFormElement) {
                    isSubmitting = true;
                }
            }, true);

            window.addEventListener('pagehide', release);
            window.addEventListener('beforeunload', release);

            initLock();
        });
    </script>
@endpush
