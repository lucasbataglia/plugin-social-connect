<?php
/**
 * Plugin Name: Social Connect
 * Plugin URI: https://example.com/social-connect
 * Description: Conecte suas redes sociais à sua conta WordPress e compartilhe suas informações.
 * Version: 1.5.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: social-connect
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Se este arquivo é chamado diretamente, aborta.
if (!defined('WPINC')) {
    die;
}

define('SOCIAL_CONNECT_VERSION', '1.5.0');
define('SOCIAL_CONNECT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SOCIAL_CONNECT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Função de ativação
function social_connect_activate() {
    // Define flag para atualizar regras de rewrite
    update_option('social_connect_flush_rewrite_rules', true);
}
register_activation_hook(__FILE__, 'social_connect_activate');

// Carrega dependências principais
require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect.php';

// Inicia o plugin
function run_social_connect() {
    $plugin = new Social_Connect();
    $plugin->run();
}
run_social_connect();