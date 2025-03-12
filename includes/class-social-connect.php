<?php

/**
 * Classe principal do plugin
 */
class Social_Connect {

    /**
     * Loader responsável por manter e registrar todos os hooks do plugin.
     */
    protected $loader;

    /**
     * Inicializa o plugin e carrega dependências.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carrega as dependências necessárias para o plugin.
     */
    private function load_dependencies() {
        // Classe Loader para gerenciar hooks
        require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect-loader.php';
        
        // Classe para funcionalidades administrativas
        require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect-admin.php';
        
        // Classe para funcionalidades públicas
        require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect-public.php';
        
        // Classe para integrações com Twitch
        require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect-twitch.php';
        
        // Classe para integrações com X (Twitter)
        require_once SOCIAL_CONNECT_PLUGIN_DIR . 'includes/class-social-connect-twitter.php';

        $this->loader = new Social_Connect_Loader();
    }

    /**
     * Registra todos os hooks relacionados à área administrativa.
     */
    private function define_admin_hooks() {
        $admin = new Social_Connect_Admin();
        
        // Adiciona menu administrativo
        $this->loader->add_action('admin_menu', $admin, 'add_admin_menu');
        
        // Registra configurações
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        
        // Adiciona CSS para admin - usar múltiplos hooks para garantir que seja aplicado
        $this->loader->add_action('admin_head', $admin, 'admin_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'admin_styles');
    }

    /**
     * Registra todos os hooks relacionados à área pública.
     */
    private function define_public_hooks() {
        $public = new Social_Connect_Public();
        $twitch = new Social_Connect_Twitch();
        $twitter = new Social_Connect_Twitter();
        
        // Registra estilo CSS
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        
        // Adiciona a página "Conexões" no My Account do WooCommerce
        $this->loader->add_filter('woocommerce_account_menu_items', $public, 'add_connections_endpoint');
        $this->loader->add_action('init', $public, 'add_connections_rewrite_endpoint');
        $this->loader->add_action('woocommerce_account_connections_endpoint', $public, 'connections_content');
        
        // Processa autenticação Twitch
        $this->loader->add_action('init', $twitch, 'process_oauth_callback');
        $this->loader->add_action('wp_ajax_social_connect_twitch_disconnect', $twitch, 'disconnect');
        
        // Processa autenticação Twitter
        $this->loader->add_action('init', $twitter, 'process_oauth_callback');
        $this->loader->add_action('wp_ajax_social_connect_twitter_disconnect', $twitter, 'disconnect');
        
        // Função para desconectar usuários no admin
        $this->loader->add_action('wp_ajax_social_connect_admin_disconnect_user', $this, 'admin_disconnect_user');
        
        // Função para atualizar dados de um usuário específico
        $this->loader->add_action('wp_ajax_social_connect_update_user_twitter_data', $this, 'ajax_update_user_twitter_data');
        $this->loader->add_action('wp_ajax_social_connect_update_user_twitch_data', $this, 'ajax_update_user_twitch_data');
        $this->loader->add_action('wp_ajax_social_connect_get_followed_channels', $this, 'ajax_get_followed_channels');
        
        // Configurar CRON para recompensas automáticas
        if (!wp_next_scheduled('social_connect_process_rewards')) {
            wp_schedule_event(time(), 'daily', 'social_connect_process_rewards');
        }
        $this->loader->add_action('social_connect_process_rewards', $twitch, 'process_rewards');
    }

    /**
     * Executa o loader para executar todos os hooks.
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Desconecta um usuário de suas redes sociais a partir do painel administrativo.
     */
    public function admin_disconnect_user() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'social-connect')));
        }
        
        // Verificar nonce
        check_ajax_referer('social_connect_admin_disconnect_user', 'nonce');
        
        // Verificar ID do usuário
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('ID de usuário inválido.', 'social-connect')));
        }
        
        // Verificar a plataforma a desconectar
        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'all';
        
        // Desconectar usuário conforme a plataforma especificada
        if ($platform === 'twitch' || $platform === 'all') {
            // Desconecta Twitch
            delete_user_meta($user_id, 'social_connect_twitch_access_token');
            delete_user_meta($user_id, 'social_connect_twitch_refresh_token');
            delete_user_meta($user_id, 'social_connect_twitch_expires');
            delete_user_meta($user_id, 'social_connect_twitch_user_id');
            delete_user_meta($user_id, 'social_connect_twitch_username');
            delete_user_meta($user_id, 'social_connect_twitch_display_name');
            delete_user_meta($user_id, 'social_connect_twitch_email');
            delete_user_meta($user_id, 'social_connect_twitch_profile_image');
            delete_user_meta($user_id, 'social_connect_twitch_connected');
            delete_user_meta($user_id, 'social_connect_twitch_connected_date');
        }
        
        if ($platform === 'twitter' || $platform === 'all') {
            // Desconecta Twitter
            delete_user_meta($user_id, 'social_connect_twitter_access_token');
            delete_user_meta($user_id, 'social_connect_twitter_refresh_token');
            delete_user_meta($user_id, 'social_connect_twitter_expires');
            delete_user_meta($user_id, 'social_connect_twitter_user_id');
            delete_user_meta($user_id, 'social_connect_twitter_username');
            delete_user_meta($user_id, 'social_connect_twitter_display_name');
            delete_user_meta($user_id, 'social_connect_twitter_profile_image');
            delete_user_meta($user_id, 'social_connect_twitter_connected');
            delete_user_meta($user_id, 'social_connect_twitter_connected_date');
        }
        
        wp_send_json_success(array('message' => __('Usuário desconectado com sucesso.', 'social-connect')));
    }
    
    /**
     * Manipula solicitação AJAX para atualizar dados de um único usuário do Twitter
     */
    public function ajax_update_user_twitter_data() {
        // Verificar nonce
        check_ajax_referer('update_twitter_user_data', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'social-connect')));
        }
        
        // Verificar ID do usuário
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('ID de usuário inválido.', 'social-connect')));
        }
        
        // Instanciar a classe Twitter
        $twitter = new Social_Connect_Twitter();
        
        // Atualizar dados do usuário
        $result = $twitter->update_user_twitter_data($user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Dados do Twitter atualizados com sucesso.', 'social-connect')));
    }
    
    /**
     * Manipula solicitação AJAX para atualizar dados de um único usuário da Twitch
     */
    public function ajax_update_user_twitch_data() {
        // Verificar nonce
        check_ajax_referer('update_twitch_user_data', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'social-connect')));
        }
        
        // Verificar ID do usuário
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('ID de usuário inválido.', 'social-connect')));
        }
        
        // Instanciar a classe Twitch
        $twitch = new Social_Connect_Twitch();
        
        // Atualizar dados do usuário
        $result = $twitch->update_user_twitch_data($user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Dados da Twitch atualizados com sucesso.', 'social-connect')));
    }
    
    /**
     * Manipula solicitação AJAX para obter os canais seguidos por um usuário
     */
    public function ajax_get_followed_channels() {
        // Verificar nonce
        check_ajax_referer('view_followed_channels', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'social-connect')));
        }
        
        // Verificar ID do usuário
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('ID de usuário inválido.', 'social-connect')));
        }
        
        // Parâmetros para paginação
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $after = isset($_POST['after']) ? sanitize_text_field($_POST['after']) : null;
        
        // Instanciar a classe Twitch
        $twitch = new Social_Connect_Twitch();
        
        // Obter canais seguidos
        $result = $twitch->get_user_followed_channels($user_id, $limit, $after);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
}