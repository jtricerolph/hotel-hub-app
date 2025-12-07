/**
 * Hotel Hub App - Main JavaScript
 *
 * Handles app navigation, module loading, and user interactions.
 *
 * @package Hotel_Hub_App
 */

(function($) {
    'use strict';

    const HotelHubApp = {
        currentHotelId: null,
        currentModuleId: null,

        init: function() {
            this.bindEvents();
            this.loadNavigation();
            this.checkOnlineStatus();
            this.initPWA();
            this.initHeartbeat();
            this.restoreLastModule();
        },

        bindEvents: function() {
            // Menu toggle
            $('.hha-menu-btn').on('click', () => this.toggleSidebar());
            $('.hha-sidebar-close, .hha-sidebar-overlay').on('click', () => this.closeSidebar());

            // Hotel selector
            $('.hha-hotel-selector-btn').on('click', () => this.openHotelSelector());
            $('.hha-modal-close, .hha-modal-overlay').on('click', () => this.closeHotelSelector());
            $(document).on('click', '.hha-hotel-item', (e) => this.selectHotel($(e.currentTarget)));

            // Module navigation
            $(document).on('click', '.hha-nav-module', (e) => this.loadModule($(e.currentTarget)));

            // PWA install
            $('.hha-install-btn').on('click', () => this.installPWA());
            $('.hha-install-dismiss').on('click', () => this.dismissInstallPrompt());

            // Reload app (clear cache)
            $('#hha-reload-app').on('click', () => this.reloadApp());

            // Online/offline events
            window.addEventListener('online', () => this.setOnlineStatus(true));
            window.addEventListener('offline', () => this.setOnlineStatus(false));

            // Check session and trigger heartbeat on wake from standby
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    console.log('[HHA] Page became visible, checking session...');
                    this.checkSession();
                }
            });

            // Fallback for window focus
            window.addEventListener('focus', () => {
                console.log('[HHA] Window gained focus, checking session...');
                this.checkSession();
            });
        },

        toggleSidebar: function() {
            $('.hha-sidebar').toggleClass('open');
        },

        closeSidebar: function() {
            $('.hha-sidebar').removeClass('open');
        },

        openHotelSelector: function() {
            $('.hha-hotel-selector').show();
        },

        closeHotelSelector: function() {
            $('.hha-hotel-selector').hide();
        },

        selectHotel: function($item) {
            const hotelId = $item.data('hotel-id');

            $.post(hhaData.ajaxUrl, {
                action: 'hha_set_current_hotel',
                nonce: hhaData.nonce,
                hotel_id: hotelId
            }, (response) => {
                if (response.success) {
                    this.currentHotelId = hotelId;
                    $('.hha-current-hotel-name').text(response.data.hotel.name);

                    // Update active hotel indicator in modal
                    $('.hha-hotel-item').find('.hha-hotel-active').remove();
                    $item.append('<span class="dashicons dashicons-yes hha-hotel-active"></span>');

                    this.closeHotelSelector();
                    this.loadNavigation();

                    // Save to localStorage as backup
                    localStorage.setItem('hha-hotel-id', hotelId);
                    localStorage.setItem('hha-hotel-name', response.data.hotel.name);

                    // Clear current module and show welcome with instruction
                    this.currentModuleId = null;
                    this.showWelcome(true); // Pass true to indicate hotel is selected
                }
            }).fail((xhr) => {
                // Handle authentication errors
                if (xhr.status === 401 || xhr.status === 403) {
                    console.warn('[HHA] Authentication error while selecting hotel');
                    this.handleSessionExpired();
                } else {
                    this.showError('Failed to select hotel. Please try again.');
                }
            });
        },

        loadNavigation: function() {
            const $nav = $('#hha-navigation');

            $.post(hhaData.ajaxUrl, {
                action: 'hha_get_navigation',
                nonce: hhaData.nonce,
                hotel_id: this.currentHotelId
            }, (response) => {
                if (response.success) {
                    this.renderNavigation(response.data.navigation, $nav);
                }
            }).fail((xhr) => {
                // Handle authentication errors
                if (xhr.status === 401 || xhr.status === 403) {
                    console.warn('[HHA] Authentication error while loading navigation');
                    this.handleSessionExpired();
                }
            });
        },

        renderNavigation: function(navigation, $container) {
            $container.empty();

            if (navigation.length === 0) {
                $container.html('<div style="padding: 20px; text-align: center; color: #999;">No modules available</div>');
                return;
            }

            navigation.forEach(dept => {
                const $dept = $('<div class="hha-nav-department">');
                $dept.append(`<div class="hha-nav-department-header">${dept.label}</div>`);

                dept.modules.forEach(module => {
                    const $module = $(`
                        <div class="hha-nav-module" data-module-id="${module.id}">
                            <span class="hha-nav-module-icon material-symbols-outlined">${module.icon}</span>
                            <span class="hha-nav-module-name">${module.name}</span>
                        </div>
                    `);
                    $dept.append($module);
                });

                $container.append($dept);
            });
        },

        loadModule: function($moduleItem) {
            const moduleId = $moduleItem.data('module-id');

            if (moduleId === this.currentModuleId) {
                this.closeSidebar();
                return;
            }

            // Update active state
            $('.hha-nav-module').removeClass('active');
            $moduleItem.addClass('active');

            // Show loading
            this.showLoading();
            this.closeSidebar();

            $.post(hhaData.ajaxUrl, {
                action: 'hha_load_module',
                nonce: hhaData.nonce,
                module_id: moduleId,
                hotel_id: this.currentHotelId
            }, (response) => {
                this.hideLoading();

                if (response.success) {
                    this.currentModuleId = moduleId;
                    $('.hha-module-container').html(response.data.content);

                    // Trigger custom event for modules that need to re-initialize
                    $(document).trigger('hha-module-loaded', [moduleId]);

                    // Store current module for refresh
                    localStorage.setItem('hha-last-module', moduleId);
                    console.log('[HHA] Module loaded and stored:', moduleId);
                } else {
                    this.showError(response.data.message || 'Failed to load module');
                }
            }).fail((xhr) => {
                this.hideLoading();

                // Handle authentication errors
                if (xhr.status === 401 || xhr.status === 403) {
                    console.warn('[HHA] Authentication error while loading module');
                    this.handleSessionExpired();
                } else {
                    this.showError('Network error. Please try again.');
                }
            });
        },

        restoreLastModule: function() {
            const lastModule = localStorage.getItem('hha-last-module');

            if (!lastModule) {
                console.log('[HHA] No previous module to restore');
                return;
            }

            console.log('[HHA] Restoring last module:', lastModule);

            // Wait for navigation to load, then restore the module
            const checkNav = setInterval(() => {
                const $moduleItem = $(`.hha-nav-module[data-module-id="${lastModule}"]`);

                if ($moduleItem.length > 0) {
                    clearInterval(checkNav);
                    console.log('[HHA] Found module item, loading...');
                    this.loadModule($moduleItem);
                }
            }, 100);

            // Stop checking after 5 seconds
            setTimeout(() => {
                clearInterval(checkNav);
            }, 5000);
        },

        showWelcome: function(hotelSelected = false) {
            let instructionBox = '';

            // Show instruction based on state
            if (hotelSelected) {
                instructionBox = `
                    <div class="hha-instruction-box hha-instruction-left">
                        <div class="hha-instruction-arrow">←</div>
                        <div class="hha-instruction-content">
                            <strong>Step 2:</strong> Choose a module
                        </div>
                    </div>
                `;
            }

            $('.hha-module-container').html(`
                <div class="hha-welcome">
                    ${instructionBox}
                    <div class="hha-welcome-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="60px" viewBox="0 -960 960 960" width="60px" fill="currentColor"><path d="M400-80v-80h520v80H400Zm40-120q0-81 51-141.5T620-416v-25q0-17 11.5-28.5T660-481q17 0 28.5 11.5T700-441v25q77 14 128.5 74.5T880-200H440Zm105-81h228q-19-27-48.5-43.5T660-341q-36 0-66 16.5T545-281Zm114 0ZM40-440v-440h240v58l280-78 320 100v40q0 50-35 85t-85 35h-80v24q0 25-14.5 45.5T628-541L358-440H40Zm80-80h80v-280h-80v280Zm160 0h64l232-85q11-4 17.5-13.5T600-640h-71l-117 38-24-76 125-42h247q9 0 22.5-6.5T796-742l-238-74-278 76v220Z"/></svg>
                    </div>
                    <h2>Welcome to Hotel Hub</h2>
                    <p>Your multi-hotel operations hub</p>
                </div>
            `);
        },

        showLoading: function() {
            $('.hha-loading-overlay').show();
        },

        hideLoading: function() {
            $('.hha-loading-overlay').hide();
        },

        showError: function(message) {
            $('.hha-module-container').html(`
                <div style="max-width: 600px; margin: 60px auto; text-align: center;">
                    <div style="font-size: 60px; color: #f44336; margin-bottom: 20px;">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <h2>Error</h2>
                    <p>${message}</p>
                </div>
            `);
        },

        checkOnlineStatus: function() {
            this.setOnlineStatus(navigator.onLine);
        },

        setOnlineStatus: function(isOnline) {
            if (isOnline) {
                $('.hha-offline-indicator').hide();
            } else {
                $('.hha-offline-indicator').show();
            }
        },

        checkSession: function() {
            // Don't check if offline
            if (!navigator.onLine) {
                console.log('[HHA] Offline, skipping session check');
                return;
            }

            $.post(hhaData.ajaxUrl, {
                action: 'hha_check_session',
                nonce: hhaData.nonce
            }, (response) => {
                if (response.success) {
                    console.log('[HHA] Session valid');

                    // Restore hotel ID from session or localStorage
                    if (response.data.hotel_id) {
                        this.currentHotelId = response.data.hotel_id;
                        console.log('[HHA] Restored hotel ID from session:', this.currentHotelId);

                        // Update UI if hotel name is in localStorage
                        const hotelName = localStorage.getItem('hha-hotel-name');
                        if (hotelName) {
                            $('.hha-current-hotel-name').text(hotelName);
                        }
                    } else if (!this.currentHotelId) {
                        // Try to restore from localStorage as fallback
                        const storedHotelId = localStorage.getItem('hha-hotel-id');
                        const storedHotelName = localStorage.getItem('hha-hotel-name');

                        if (storedHotelId && storedHotelName) {
                            this.currentHotelId = parseInt(storedHotelId);
                            $('.hha-current-hotel-name').text(storedHotelName);
                            console.log('[HHA] Restored hotel from localStorage:', this.currentHotelId);
                        }
                    }

                    // Trigger heartbeat to keep session alive
                    if (typeof wp !== 'undefined' && wp.heartbeat) {
                        wp.heartbeat.connectNow();
                    }
                } else {
                    console.warn('[HHA] Session invalid:', response.data.message);
                    this.handleSessionExpired();
                }
            }).fail((xhr) => {
                // Handle 401/403 as session expired
                if (xhr.status === 401 || xhr.status === 403) {
                    console.warn('[HHA] Session expired (status:', xhr.status + ')');
                    this.handleSessionExpired();
                } else {
                    console.error('[HHA] Session check failed:', xhr.status);
                }
            });
        },

        handleSessionExpired: function() {
            // Show session expired message
            $('.hha-module-container').html(`
                <div style="max-width: 600px; margin: 60px auto; text-align: center;">
                    <div style="font-size: 60px; color: #FF9800; margin-bottom: 20px;">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <h2>Session Expired</h2>
                    <p>Your session has expired for security reasons.</p>
                    <p style="margin-top: 20px;">
                        <button class="hha-button" onclick="window.location.reload()">Login Again</button>
                    </p>
                </div>
            `);

            // Clear local data
            localStorage.removeItem('hha-last-module');
            this.currentHotelId = null;
            this.currentModuleId = null;

            // Redirect to login after showing message briefly
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        },

        initHeartbeat: function() {
            // Wait for heartbeat to be ready
            $(document).on('heartbeat-send', (event, data) => {
                // Add custom data to heartbeat
                data.hha_heartbeat = {
                    hotel_id: this.currentHotelId,
                    module_id: this.currentModuleId
                };
            });

            $(document).on('heartbeat-tick', (event, data) => {
                // Handle heartbeat response
                if (data.hha_heartbeat) {
                    console.log('[HHA] Heartbeat tick received');

                    // Check if session is still valid
                    if (data.hha_heartbeat.session_expired) {
                        console.warn('[HHA] Server reports session expired');
                        this.handleSessionExpired();
                    }
                }
            });

            $(document).on('heartbeat-error', (event, jqXHR, textStatus, error) => {
                console.error('[HHA] Heartbeat error:', textStatus, error);
                // Don't treat heartbeat errors as session expiry unless it's a 401/403
                if (jqXHR && (jqXHR.status === 401 || jqXHR.status === 403)) {
                    this.handleSessionExpired();
                }
            });

            // Configure heartbeat interval (60 seconds)
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                wp.heartbeat.interval(60);
                console.log('[HHA] Heartbeat initialized with 60s interval');
            }
        },

        initPWA: function() {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;

                // Show install prompt (unless previously dismissed)
                if (!localStorage.getItem('hha-install-dismissed')) {
                    setTimeout(() => {
                        $('.hha-install-prompt').show();
                    }, 5000);
                }
            });
        },

        installPWA: function() {
            if (!this.deferredPrompt) {
                console.log('Install prompt not available');
                return;
            }

            this.deferredPrompt.prompt();

            this.deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('PWA installed');
                } else {
                    console.log('PWA installation cancelled');
                }

                this.deferredPrompt = null;
                $('.hha-install-prompt').hide();
            });
        },

        dismissInstallPrompt: function() {
            $('.hha-install-prompt').hide();
            localStorage.setItem('hha-install-dismissed', 'true');
        },

        reloadApp: async function() {
            console.log('[HHA] Reloading app and clearing cache...');

            // Show loading overlay
            $('.hha-loading-overlay').show();

            try {
                // Unregister all service workers
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (let registration of registrations) {
                        await registration.unregister();
                        console.log('[HHA] Service worker unregistered');
                    }
                }

                // Clear all caches
                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(
                        cacheNames.map(cacheName => {
                            console.log('[HHA] Deleting cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                    );
                }

                // Clear local storage (except install dismissed state and last module)
                const installDismissed = localStorage.getItem('hha-install-dismissed');
                const lastModule = localStorage.getItem('hha-last-module');
                localStorage.clear();
                if (installDismissed) {
                    localStorage.setItem('hha-install-dismissed', installDismissed);
                }
                if (lastModule) {
                    localStorage.setItem('hha-last-module', lastModule);
                }

                // Clear session storage
                sessionStorage.clear();

                console.log('[HHA] Cache cleared, reloading...');

                // Force hard reload by adding cache-busting parameter
                // This bypasses browser HTTP cache for all resources
                const url = new URL(window.location.href);
                url.searchParams.set('_refresh', Date.now());
                window.location.href = url.toString();
            } catch (error) {
                console.error('[HHA] Error clearing cache:', error);
                alert('Error clearing cache. Please try clearing your browser data manually.');
                $('.hha-loading-overlay').hide();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        HotelHubApp.init();
    });

})(jQuery);
