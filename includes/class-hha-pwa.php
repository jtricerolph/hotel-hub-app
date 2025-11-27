<?php
/**
 * PWA functionality - Manifest and Service Worker generation.
 *
 * Handles Progressive Web App features including manifest.json and service worker.
 * Uses online-only caching strategy (app shell cached, data always fresh).
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHA_PWA {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('parse_request', array($this, 'handle_pwa_requests'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Load icon generator
        require_once HHA_PLUGIN_DIR . 'includes/class-hha-icon-generator.php';
    }

    /**
     * Add rewrite rules for manifest and service worker.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^hotel-hub-manifest\.json$', 'index.php?hha_manifest=1', 'top');
        add_rewrite_rule('^hotel-hub-sw\.js$', 'index.php?hha_sw=1', 'top');

        // Add rewrite rules for dynamic icons
        add_rewrite_rule('^assets/icons/icon-([0-9]+)x([0-9]+)\.png$', 'index.php?hha_icon=$matches[1]', 'top');
        add_rewrite_rule('^assets/icons/icon-([0-9]+)x([0-9]+)-maskable\.png$', 'index.php?hha_icon=$matches[1]&hha_maskable=1', 'top');
    }

    /**
     * Add custom query vars.
     *
     * @param array $vars Query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars($vars) {
        $vars[] = 'hha_manifest';
        $vars[] = 'hha_sw';
        $vars[] = 'hha_icon';
        $vars[] = 'hha_maskable';
        return $vars;
    }

    /**
     * Handle PWA requests (manifest, service worker, and icons).
     *
     * @param WP $wp WordPress environment object.
     */
    public function handle_pwa_requests($wp) {
        // Handle manifest.json
        if (isset($wp->query_vars['hha_manifest'])) {
            $this->serve_manifest();
            exit;
        }

        // Handle service-worker.js
        if (isset($wp->query_vars['hha_sw'])) {
            $this->serve_service_worker();
            exit;
        }

        // Handle icon requests
        if (isset($wp->query_vars['hha_icon'])) {
            $size = absint($wp->query_vars['hha_icon']);
            $maskable = isset($wp->query_vars['hha_maskable']) && $wp->query_vars['hha_maskable'] == '1';

            HHA_Icon_Generator::serve_icon($size, $maskable);
            exit;
        }
    }

    /**
     * Serve manifest.json.
     */
    private function serve_manifest() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $manifest = $this->generate_manifest();

        echo wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate manifest data.
     *
     * @return array Manifest data.
     */
    private function generate_manifest() {
        $site_name = get_bloginfo('name');
        $theme_color = get_option('hha_theme_primary_color', '#2196f3');
        $icon_base = home_url('/assets/icons/');

        // Generate unique ID based on site URL
        $unique_id = 'com.hotelhub.' . md5(home_url());

        return array(
            'name'             => $site_name . ' - Hotel Hub',
            'short_name'       => 'Hotel Hub',
            'description'      => 'Multi-hotel operations management',
            'start_url'        => home_url('/hotel-hub/'),
            'scope'            => home_url('/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'theme_color'      => $theme_color,
            'background_color' => '#ffffff',
            'id'               => $unique_id,
            'icons'            => array(
                array(
                    'src'     => $icon_base . 'icon-72x72.png',
                    'sizes'   => '72x72',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-96x96.png',
                    'sizes'   => '96x96',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-128x128.png',
                    'sizes'   => '128x128',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-144x144.png',
                    'sizes'   => '144x144',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-152x152.png',
                    'sizes'   => '152x152',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-192x192.png',
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-384x384.png',
                    'sizes'   => '384x384',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-512x512.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'     => $icon_base . 'icon-192x192-maskable.png',
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ),
                array(
                    'src'     => $icon_base . 'icon-512x512-maskable.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ),
            ),
            'categories'       => array('business', 'productivity'),
            'shortcuts'        => array(),
        );
    }

    /**
     * Serve service worker JavaScript.
     */
    private function serve_service_worker() {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Service-Worker-Allowed: /');

        echo $this->generate_service_worker();
    }

    /**
     * Generate service worker code.
     *
     * @return string Service worker JavaScript code.
     */
    private function generate_service_worker() {
        $version = HHA_VERSION;
        $cache_name = 'hotel-hub-v' . $version;
        $app_url = home_url('/hotel-hub/');

        // Assets to cache for app shell (online-only, minimal caching)
        $cache_assets = array(
            HHA_PLUGIN_URL . 'assets/css/standalone.css',
            HHA_PLUGIN_URL . 'assets/css/themes.css',
            HHA_PLUGIN_URL . 'assets/js/app.js',
        );

        $cache_assets_json = wp_json_encode($cache_assets);

        return <<<JAVASCRIPT
// Hotel Hub Service Worker - Online-Only Mode
// Version: {$version}

const CACHE_NAME = '{$cache_name}';
const APP_SHELL_CACHE = CACHE_NAME + '-shell';

// Minimal assets to cache (app shell only)
const APP_SHELL_ASSETS = {$cache_assets_json};

// Install event - cache app shell
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');

    event.waitUntil(
        caches.open(APP_SHELL_CACHE)
            .then((cache) => {
                console.log('[SW] Caching app shell');
                return cache.addAll(APP_SHELL_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name.startsWith('hotel-hub-') && name !== APP_SHELL_CACHE)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - network-first strategy (online-only)
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome extensions and non-http(s) requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    event.respondWith(
        // Always try network first
        fetch(request)
            .then((response) => {
                // Update cache with fresh response for app shell assets
                if (APP_SHELL_ASSETS.includes(url.href)) {
                    const responseClone = response.clone();
                    caches.open(APP_SHELL_CACHE)
                        .then((cache) => cache.put(request, responseClone));
                }

                return response;
            })
            .catch((error) => {
                // Only fall back to cache for app shell assets
                if (APP_SHELL_ASSETS.includes(url.href)) {
                    return caches.match(request)
                        .then((cachedResponse) => {
                            if (cachedResponse) {
                                console.log('[SW] Serving from cache (offline):', url.href);
                                return cachedResponse;
                            }
                            throw error;
                        });
                }

                // For everything else, show offline message
                throw error;
            })
    );
});

// Listen for messages from the app
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[SW] Service worker loaded');
JAVASCRIPT;
    }

    /**
     * Add manifest link to page head.
     */
    public function add_manifest_link() {
        echo '<link rel="manifest" href="' . esc_url(home_url('/hotel-hub-manifest.json')) . '">' . "\n";
    }

    /**
     * Add PWA meta tags to page head.
     */
    public function add_pwa_meta_tags() {
        $theme_color = get_option('hha_theme_primary_color', '#2196f3');
        $icon_url = home_url('/assets/icons/icon-192x192.png');

        ?>
        <meta name="theme-color" content="<?php echo esc_attr($theme_color); ?>">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Hotel Hub">
        <link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">
        <?php
    }

    /**
     * Register service worker in page footer.
     */
    public function register_service_worker() {
        $sw_url = home_url('/hotel-hub-sw.js');
        ?>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo esc_js($sw_url); ?>', {
                    scope: '<?php echo esc_js(home_url('/')); ?>'
                })
                .then((registration) => {
                    console.log('[HHA] Service Worker registered:', registration.scope);

                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        console.log('[HHA] Service Worker update found');
                    });
                })
                .catch((error) => {
                    console.error('[HHA] Service Worker registration failed:', error);
                });
            });
        }
        </script>
        <?php
    }
}
