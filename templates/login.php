<?php
/**
 * Custom login template - App-like login screen.
 *
 * @package Hotel_Hub_App
 */

if (!defined('ABSPATH')) {
    exit;
}

$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : hha()->auth->get_app_url();
$theme_color = get_option('hha_theme_primary_color', '#2196f3');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="theme-color" content="<?php echo esc_attr($theme_color); ?>">
    <meta name="robots" content="noindex, nofollow">

    <title>Login - <?php echo esc_html(get_bloginfo('name')); ?></title>

    <?php wp_head(); ?>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, <?php echo esc_attr($theme_color); ?> 0%, #667eea 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body > *:not(.hha-login-wrapper) {
            display: none !important;
        }

        .hha-login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .hha-login-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }

        .hha-login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .hha-login-logo img {
            max-width: 120px;
            height: auto;
        }

        .hha-login-logo .dashicons {
            font-size: 80px;
            width: 80px;
            height: 80px;
            color: <?php echo esc_attr($theme_color); ?>;
        }

        .hha-login-title {
            text-align: center;
            margin: 0 0 30px;
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .hha-login-form {
            margin: 0;
        }

        .hha-form-group {
            margin-bottom: 20px;
        }

        .hha-form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }

        .hha-form-group input[type="text"],
        .hha-form-group input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .hha-form-group input[type="text"]:focus,
        .hha-form-group input[type="password"]:focus {
            outline: none;
            border-color: <?php echo esc_attr($theme_color); ?>;
        }

        .hha-form-group input[type="checkbox"] {
            margin-right: 8px;
        }

        .hha-submit-btn {
            width: 100%;
            padding: 14px;
            background: <?php echo esc_attr($theme_color); ?>;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .hha-submit-btn:hover {
            opacity: 0.9;
        }

        .hha-submit-btn:active {
            opacity: 0.8;
        }

        .hha-login-error {
            background: #dc3545;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .hha-login-message {
            background: #28a745;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .hha-login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
        }

        .hha-login-footer a {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body class="hha-login-body">

<div class="hha-login-wrapper">
    <div class="hha-login-box">
        <div class="hha-login-logo">
            <?php
            $icon_url = HHA_PLUGIN_URL . 'assets/icons/icon-192x192.png';
            if (file_exists(HHA_PLUGIN_DIR . 'assets/icons/icon-192x192.png')) :
            ?>
                <img src="<?php echo esc_url($icon_url); ?>" alt="Hotel Hub">
            <?php else : ?>
                <span class="dashicons dashicons-building"></span>
            <?php endif; ?>
        </div>

        <h1 class="hha-login-title">Hotel Hub</h1>

        <?php
        // Show WordPress login errors
        if (isset($_GET['login']) && $_GET['login'] === 'failed') {
            echo '<div class="hha-login-error">Invalid username or password.</div>';
        }

        if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
            echo '<div class="hha-login-message">You have been logged out.</div>';
        }
        ?>

        <form name="loginform" id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post" class="hha-login-form">
            <div class="hha-form-group">
                <label for="user_login">Username or Email</label>
                <input type="text" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off" required>
            </div>

            <div class="hha-form-group">
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" required>
            </div>

            <div class="hha-form-group">
                <label>
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                    Remember Me
                </label>
            </div>

            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
            <input type="hidden" name="testcookie" value="1">

            <button type="submit" name="wp-submit" id="wp-submit" class="hha-submit-btn">
                Log In
            </button>
        </form>
    </div>

    <div class="hha-login-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?></p>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>
