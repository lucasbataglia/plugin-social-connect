<?php

/**
 * Integração com a API da Twitch.
 */
class Social_Connect_Twitch {

    /**
     * Client ID da aplicação Twitch.
     */
    private $client_id;
    
    /**
     * Client Secret da aplicação Twitch.
     */
    private $client_secret;
    
    /**
     * URI de redirecionamento para OAuth.
     */
    private $redirect_uri;
    
    /**
     * URL de autorização da Twitch.
     */
    private $authorization_url = 'https://id.twitch.tv/oauth2/authorize';
    
    /**
     * URL do token da Twitch.
     */
    private $token_url = 'https://id.twitch.tv/oauth2/token';
    
    /**
     * URL da API da Twitch.
     */
    private $api_url = 'https://api.twitch.tv/helix';
    
    /**
     * Inicializa a classe.
     */
    public function __construct() {
        $this->client_id = get_option('social_connect_twitch_client_id', '');
        $this->client_secret = get_option('social_connect_twitch_client_secret', '');
        $this->redirect_uri = get_option('social_connect_twitch_redirect_uri', home_url('wc-auth/twitch'));
        
        // Adiciona o ponto de entrada para a autenticação da Twitch
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'process_oauth_callback'));
        
        // Adiciona uma funcionalidade de debug para administradores
        if (WP_DEBUG) {
            add_action('admin_init', array($this, 'maybe_debug_twitch_connection'));
        }
        
        // Adiciona ações para atualização periódica dos dados da Twitch
        add_action('admin_post_update_twitch_data', array($this, 'handle_manual_update'));
        
        // Registra um hook para atualização periódica via CRON
        if (!wp_next_scheduled('social_connect_twitch_refresh_data')) {
            wp_schedule_event(time(), 'daily', 'social_connect_twitch_refresh_data');
        }
        add_action('social_connect_twitch_refresh_data', array($this, 'refresh_all_users_twitch_data'));
    }
    
    /**
     * Exibe informações de debug para administradores
     */
    public function maybe_debug_twitch_connection() {
        if (!current_user_can('manage_options') || !isset($_GET['social_connect_debug'])) {
            return;
        }
        
        echo '<div class="notice notice-info"><p><strong>Social Connect Debug:</strong></p>';
        echo '<p>Client ID: ' . (empty($this->client_id) ? 'Não configurado' : substr($this->client_id, 0, 5) . '...') . '</p>';
        echo '<p>Client Secret: ' . (empty($this->client_secret) ? 'Não configurado' : 'Configurado (oculto)') . '</p>';
        echo '<p>Redirect URI: ' . esc_html($this->redirect_uri) . '</p>';
        echo '<p>URL de autorização: ' . esc_html($this->get_authorization_url()) . '</p>';
        
        $user_id = get_current_user_id();
        if ($user_id) {
            $connected = get_user_meta($user_id, 'social_connect_twitch_connected', true);
            $username = get_user_meta($user_id, 'social_connect_twitch_username', true);
            
            echo '<p>Status da conexão para o usuário atual: ' . ($connected ? 'Conectado como ' . esc_html($username) : 'Não conectado') . '</p>';
            
            if ($connected) {
                $expires = get_user_meta($user_id, 'social_connect_twitch_expires', true);
                echo '<p>Token expira em: ' . date('Y-m-d H:i:s', $expires) . ' (' . ($expires > time() ? 'Válido' : 'Expirado') . ')</p>';
            }
        }
        
        echo '</div>';
        exit;
    }
    
    /**
     * Adiciona regras de rewrite para o callback OAuth.
     */
    public function add_rewrite_rules() {
        // A regra de rewrite não é estritamente necessária para o callback quando usamos template_redirect
        // para capturar a URL, mas é uma boa prática mantê-la para compatibilidade com diversos plugins.
        add_rewrite_rule('^wc-auth/twitch/?$', 'index.php?social_connect_twitch_oauth=1', 'top');
        add_rewrite_tag('%social_connect_twitch_oauth%', '([0-9]+)');
        
        // Força o flush das regras de rewrite se necessário
        if (get_option('social_connect_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            update_option('social_connect_flush_rewrite_rules', false);
        }
    }
    
    /**
     * Gera a URL de autorização para a Twitch.
     */
    public function get_authorization_url() {
        if (empty($this->client_id)) {
            return '#'; // ID do cliente não configurado
        }
        
        $state = wp_create_nonce('social_connect_twitch_state');
        update_user_meta(get_current_user_id(), 'social_connect_twitch_state', $state);
        
        // Adicionar escopo adicional para obter a lista de canais seguidos
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'user:read:email user:read:follows user:read:subscriptions channel:read:subscriptions',
            'state' => $state
        );
        
        return $this->authorization_url . '?' . http_build_query($params);
    }
    
    /**
     * Processa o callback OAuth da Twitch.
     */
    public function process_oauth_callback() {
        // Verifica se estamos na URL de callback da Twitch
        // Verifica se a URL atual corresponde ao padrão do callback
        if (!isset($_GET['code']) || !isset($_GET['state']) || strpos($_SERVER['REQUEST_URI'], 'wc-auth/twitch') === false) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wc_add_notice(__('Você precisa estar logado para conectar sua conta Twitch.', 'social-connect'), 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        $state = get_user_meta($user_id, 'social_connect_twitch_state', true);
        
        if (empty($state) || $state !== $_GET['state']) {
            wc_add_notice(__('Falha na verificação de segurança. Por favor, tente novamente.', 'social-connect'), 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        // Log para debug
        if (WP_DEBUG) {
            error_log('Social Connect: Processando callback Twitch com código: ' . substr($_GET['code'], 0, 5) . '...');
        }
        
        // Obtém o token de acesso
        $token_response = $this->get_access_token($_GET['code']);
        
        if (is_wp_error($token_response)) {
            $error_message = $token_response->get_error_message();
            if (WP_DEBUG) {
                error_log('Social Connect Error: ' . $error_message);
            }
            wc_add_notice($error_message, 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        // Log para debug
        if (WP_DEBUG) {
            error_log('Social Connect: Token obtido com sucesso. Tipo de "scope": ' . gettype($token_response['scope']));
        }
        
        // Verifica se os escopos necessários foram concedidos
        $has_required_scope = false;
        if (isset($token_response['scope'])) {
            if (is_array($token_response['scope'])) {
                $has_required_scope = in_array('user:read:email', $token_response['scope']);
            } else if (is_string($token_response['scope'])) {
                $has_required_scope = in_array('user:read:email', explode(' ', $token_response['scope']));
            }
        }
        
        if (!$has_required_scope) {
            wc_add_notice(__('A conexão não pôde ser completada. Permissões necessárias não foram concedidas.', 'social-connect'), 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        // Salva o token de acesso e refresh
        update_user_meta($user_id, 'social_connect_twitch_access_token', $token_response['access_token']);
        update_user_meta($user_id, 'social_connect_twitch_refresh_token', $token_response['refresh_token']);
        update_user_meta($user_id, 'social_connect_twitch_expires', time() + $token_response['expires_in']);
        
        // Obtém informações do usuário
        $user_info = $this->get_user_info($token_response['access_token']);
        
        if (is_wp_error($user_info)) {
            wc_add_notice($user_info->get_error_message(), 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        // Verifica se conseguimos obter um e-mail
        if (empty($user_info['email'])) {
            wc_add_notice(__('Não foi possível obter o email da sua conta Twitch. Verifique as permissões concedidas.', 'social-connect'), 'error');
            wp_redirect(wc_get_account_endpoint_url('connections'));
            exit;
        }
        
        // Salva informações do usuário
        update_user_meta($user_id, 'social_connect_twitch_user_id', $user_info['id']);
        update_user_meta($user_id, 'social_connect_twitch_username', $user_info['login']);
        update_user_meta($user_id, 'social_connect_twitch_display_name', $user_info['display_name']);
        update_user_meta($user_id, 'social_connect_twitch_email', $user_info['email']);
        update_user_meta($user_id, 'social_connect_twitch_profile_image', $user_info['profile_image_url']);
        update_user_meta($user_id, 'social_connect_twitch_connected', 'yes');
        
        // Armazena a data exata de conexão
        update_user_meta($user_id, 'social_connect_twitch_connected_date', current_time('mysql'));
        
        // Armazena a data de criação da conta Twitch (se disponível)
        if (!empty($user_info['created_at'])) {
            update_user_meta($user_id, 'social_connect_twitch_account_created_at', $user_info['created_at']);
        }
        
        // Obter e armazenar a contagem de canais seguidos
        $followed_channels = $this->get_user_followed_channels($user_id, 1);
        if (!is_wp_error($followed_channels) && isset($followed_channels['total'])) {
            update_user_meta($user_id, 'social_connect_twitch_following_count', $followed_channels['total']);
        }
        
        // Limpa o estado
        delete_user_meta($user_id, 'social_connect_twitch_state');
        
        // Mensagem mais descritiva com o nome do usuário - corrigindo espaçamento
        if (!empty($user_info['display_name'])) {
            wc_add_notice(
                sprintf(
                    __('Sua conta Twitch foi conectada com sucesso! Conectado como %s.', 'social-connect'), 
                    ' <strong>' . esc_html($user_info['display_name']) . '</strong>'
                ), 
                'success'
            );
        } else {
            wc_add_notice(__('Sua conta Twitch foi conectada com sucesso!', 'social-connect'), 'success');
        }
        wp_redirect(wc_get_account_endpoint_url('connections'));
        exit;
    }
    
    /**
     * Obtém o token de acesso da Twitch.
     */
    private function get_access_token($code) {
        $response = wp_remote_post($this->token_url, array(
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirect_uri
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $message = wp_remote_retrieve_response_message($response);
            return new WP_Error(
                'twitch_token_error', 
                sprintf(__('Erro na resposta da Twitch (código %d): %s', 'social-connect'), $status_code, $message)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!is_array($body)) {
            return new WP_Error('twitch_token_error', __('Resposta inválida da API da Twitch.', 'social-connect'));
        }
        
        if (isset($body['error'])) {
            $error_message = isset($body['error_description']) ? $body['error_description'] : $body['error'];
            return new WP_Error('twitch_token_error', $error_message);
        }
        
        // Verifica se os campos obrigatórios estão presentes
        if (!isset($body['access_token']) || !isset($body['refresh_token']) || !isset($body['expires_in'])) {
            return new WP_Error('twitch_token_error', __('Resposta de token incompleta da Twitch.', 'social-connect'));
        }
        
        return $body;
    }
    
    /**
     * Obtém informações do usuário da Twitch.
     */
    private function get_user_info($access_token) {
        if (WP_DEBUG) {
            error_log('Social Connect: Obtendo informações do usuário da Twitch');
        }
        
        $headers = array(
            'Client-ID' => $this->client_id,
            'Authorization' => 'Bearer ' . $access_token
        );
        
        if (WP_DEBUG) {
            error_log('Social Connect: Headers para API Twitch: ' . print_r($headers, true));
        }
        
        // Solicitar informações adicionais, incluindo created_at (data de criação da conta)
        $response = wp_remote_get($this->api_url . '/users', array(
            'headers' => $headers
        ));
        
        if (is_wp_error($response)) {
            if (WP_DEBUG) {
                error_log('Social Connect Error: ' . $response->get_error_message());
            }
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if (WP_DEBUG) {
            error_log('Social Connect: Código de resposta API Twitch: ' . $status_code);
        }
        
        if ($status_code !== 200) {
            $message = wp_remote_retrieve_response_message($response);
            if (WP_DEBUG) {
                error_log('Social Connect Error: Código ' . $status_code . ' - ' . $message);
                error_log('Social Connect Response: ' . wp_remote_retrieve_body($response));
            }
            return new WP_Error(
                'twitch_api_error', 
                sprintf(__('Erro na resposta da API Twitch (código %d): %s', 'social-connect'), $status_code, $message)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (WP_DEBUG) {
            error_log('Social Connect: Resposta da API Twitch: ' . wp_remote_retrieve_body($response));
        }
        
        if (!is_array($body)) {
            return new WP_Error('twitch_api_error', __('Resposta inválida da API da Twitch.', 'social-connect'));
        }
        
        if (isset($body['error'])) {
            $error_message = isset($body['message']) ? $body['error'] . ': ' . $body['message'] : $body['error'];
            return new WP_Error('twitch_api_error', $error_message);
        }
        
        if (empty($body['data']) || !isset($body['data'][0])) {
            return new WP_Error('twitch_api_error', __('Não foi possível obter informações do usuário.', 'social-connect'));
        }
        
        return $body['data'][0];
    }
    
    /**
     * Atualiza o token de acesso usando o refresh token
     */
    public function refresh_access_token($refresh_token) {
        $response = wp_remote_post($this->token_url, array(
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('twitch_token_error', $body['error_description']);
        }
        
        return $body;
    }
    
    /**
     * Verifica se o usuário segue um canal específico
     * 
     * @param string $broadcaster_id ID do canal a verificar
     * @param string $user_id ID do usuário Twitch (opcional, usa o atual se não especificado)
     * @return bool|WP_Error true se seguir, false se não seguir, WP_Error em caso de erro
     */
    public function check_if_user_follows_channel($broadcaster_id, $user_id = null) {
        if (empty($broadcaster_id)) {
            return new WP_Error('twitch_api_error', __('ID do canal não especificado', 'social-connect'));
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return new WP_Error('twitch_api_error', __('Usuário não está logado', 'social-connect'));
        }
        
        // Se não especificou um ID de usuário Twitch, usa o do usuário conectado
        if (empty($user_id)) {
            $user_id = get_user_meta($current_user_id, 'social_connect_twitch_user_id', true);
            if (empty($user_id)) {
                return new WP_Error('twitch_api_error', __('Usuário não conectou conta Twitch', 'social-connect'));
            }
        }
        
        $access_token = get_user_meta($current_user_id, 'social_connect_twitch_access_token', true);
        $expires = get_user_meta($current_user_id, 'social_connect_twitch_expires', true);
        
        // Verifica se o token está expirado
        if (empty($access_token) || $expires < time()) {
            $refresh_token = get_user_meta($current_user_id, 'social_connect_twitch_refresh_token', true);
            if (empty($refresh_token)) {
                return new WP_Error('twitch_api_error', __('Token expirado e não há refresh token', 'social-connect'));
            }
            
            $refresh_response = $this->refresh_access_token($refresh_token);
            if (is_wp_error($refresh_response)) {
                return $refresh_response;
            }
            
            $access_token = $refresh_response['access_token'];
            update_user_meta($current_user_id, 'social_connect_twitch_access_token', $access_token);
            update_user_meta($current_user_id, 'social_connect_twitch_refresh_token', $refresh_response['refresh_token']);
            update_user_meta($current_user_id, 'social_connect_twitch_expires', time() + $refresh_response['expires_in']);
        }
        
        // Faz a chamada para a API da Twitch para verificar se segue
        $url = add_query_arg(
            array(
                'broadcaster_id' => $broadcaster_id,
                'user_id' => $user_id
            ),
            $this->api_url . '/channels/followers'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Client-ID' => $this->client_id,
                'Authorization' => 'Bearer ' . $access_token
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!is_array($body)) {
            return new WP_Error('twitch_api_error', __('Resposta inválida da API da Twitch', 'social-connect'));
        }
        
        if (isset($body['error'])) {
            return new WP_Error('twitch_api_error', $body['error'] . ': ' . $body['message']);
        }
        
        // Verifica se o usuário segue o canal
        if (isset($body['data']) && count($body['data']) > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário é assinante de um canal específico e retorna detalhes do tier
     * 
     * @param string $broadcaster_id ID do canal a verificar
     * @param string $user_id ID do usuário Twitch (opcional, usa o atual se não especificado)
     * @param int $wp_user_id ID do usuário WordPress (opcional, usa o logado atualmente se não especificado)
     * @return array|WP_Error Array com status de assinatura e tier, ou WP_Error em caso de erro
     */
    public function check_if_user_subscribed_to_channel($broadcaster_id, $user_id = null, $wp_user_id = null) {
        if (empty($broadcaster_id)) {
            return new WP_Error('twitch_api_error', __('ID do canal não especificado', 'social-connect'));
        }
        
        // Se um wp_user_id específico foi fornecido, use-o
        if (!empty($wp_user_id)) {
            $current_user_id = $wp_user_id;
        } else {
            $current_user_id = get_current_user_id();
            if (!$current_user_id) {
                return new WP_Error('twitch_api_error', __('Usuário não está logado', 'social-connect'));
            }
        }
        
        // Se não especificou um ID de usuário Twitch, usa o do usuário conectado
        if (empty($user_id)) {
            $user_id = get_user_meta($current_user_id, 'social_connect_twitch_user_id', true);
            if (empty($user_id)) {
                return new WP_Error('twitch_api_error', __('Usuário não conectou conta Twitch', 'social-connect'));
            }
        }
        
        $access_token = get_user_meta($current_user_id, 'social_connect_twitch_access_token', true);
        $expires = get_user_meta($current_user_id, 'social_connect_twitch_expires', true);
        
        // Verifica se o token está expirado
        if (empty($access_token) || $expires < time()) {
            $refresh_token = get_user_meta($current_user_id, 'social_connect_twitch_refresh_token', true);
            if (empty($refresh_token)) {
                return new WP_Error('twitch_api_error', __('Token expirado e não há refresh token', 'social-connect'));
            }
            
            $refresh_response = $this->refresh_access_token($refresh_token);
            if (is_wp_error($refresh_response)) {
                return $refresh_response;
            }
            
            $access_token = $refresh_response['access_token'];
            update_user_meta($current_user_id, 'social_connect_twitch_access_token', $access_token);
            update_user_meta($current_user_id, 'social_connect_twitch_refresh_token', $refresh_response['refresh_token']);
            update_user_meta($current_user_id, 'social_connect_twitch_expires', time() + $refresh_response['expires_in']);
        }
        
        // Faz a chamada para a API da Twitch para verificar se é assinante
        $url = add_query_arg(
            array(
                'broadcaster_id' => $broadcaster_id,
                'user_id' => $user_id
            ),
            $this->api_url . '/subscriptions/user'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Client-ID' => $this->client_id,
                'Authorization' => 'Bearer ' . $access_token
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!is_array($body)) {
            return new WP_Error('twitch_api_error', __('Resposta inválida da API da Twitch', 'social-connect'));
        }
        
        if (isset($body['error'])) {
            // Código 404 geralmente significa que não é assinante
            if (isset($body['status']) && $body['status'] == 404) {
                return array(
                    'is_subscribed' => false,
                    'tier' => null,
                    'tier_name' => null
                );
            }
            return new WP_Error('twitch_api_error', $body['error'] . ': ' . $body['message']);
        }
        
        // Verifica se o usuário é assinante do canal e obtém o tier
        if (isset($body['data']) && count($body['data']) > 0) {
            // Tier 1000 = Tier 1, 2000 = Tier 2, 3000 = Tier 3
            $tier = isset($body['data'][0]['tier']) ? intval($body['data'][0]['tier']) : 1000;
            $tier_level = $tier / 1000;
            
            // Nome do tier para exibição
            $tier_name = '';
            switch ($tier) {
                case 1000:
                    $tier_name = __('Tier 1', 'social-connect');
                    break;
                case 2000:
                    $tier_name = __('Tier 2', 'social-connect');
                    break;
                case 3000:
                    $tier_name = __('Tier 3', 'social-connect');
                    break;
                default:
                    $tier_name = sprintf(__('Tier %s', 'social-connect'), $tier_level);
            }
            
            // Salva o tier na meta do usuário para acesso rápido
            update_user_meta($current_user_id, 'social_connect_twitch_subscription_tier', $tier);
            update_user_meta($current_user_id, 'social_connect_twitch_subscription_tier_name', $tier_name);
            
            return array(
                'is_subscribed' => true,
                'tier' => $tier,
                'tier_name' => $tier_name
            );
        }
        
        return array(
            'is_subscribed' => false,
            'tier' => null,
            'tier_name' => null
        );
    }
    
    /**
     * Desconecta a conta Twitch do usuário.
     */
    public function disconnect() {
        check_ajax_referer('social_connect_twitch_disconnect', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Você precisa estar logado para realizar esta ação.', 'social-connect')));
        }
        
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
        
        wp_send_json_success(array('message' => __('Sua conta Twitch foi desconectada com sucesso!', 'social-connect')));
    }
    
    /**
     * Processa as recompensas para os assinantes.
     * 
     * @return array Estatísticas sobre as recompensas processadas
     */
    public function process_rewards() {
        // Verificar se as recompensas estão ativadas
        $enable_rewards = get_option('social_connect_twitch_enable_rewards', false);
        
        if (!$enable_rewards) {
            return array(
                'status' => 'error',
                'message' => __('Sistema de recompensas não está ativado.', 'social-connect'),
                'processed' => 0,
                'success' => 0,
                'failed' => 0
            );
        }
        
        // Verificar se o WooWallet está disponível
        if (!function_exists('woo_wallet')) {
            return array(
                'status' => 'error',
                'message' => __('Plugin WooWallet não está instalado ou ativado.', 'social-connect'),
                'processed' => 0,
                'success' => 0,
                'failed' => 0
            );
        }
        
        // Configurações de recompensas
        $broadcaster_id = get_option('social_connect_twitch_broadcaster_id', '');
        $reward_tier1 = get_option('social_connect_twitch_reward_tier1', 5);
        $reward_tier2 = get_option('social_connect_twitch_reward_tier2', 10);
        $reward_tier3 = get_option('social_connect_twitch_reward_tier3', 25);
        $frequency = get_option('social_connect_twitch_reward_frequency', 'monthly');
        
        // Definir o período de recompensas com base na frequência
        $period_key = 'social_connect_last_reward_' . $frequency;
        
        // Obter a data da última recompensa
        $now = current_time('timestamp');
        $last_reward = get_option($period_key, 0);
        
        // Verificar se já é hora de processar recompensas novamente
        $process_rewards = false;
        
        switch ($frequency) {
            case 'daily':
                // Verificar se já passou 24 horas desde a última recompensa
                $process_rewards = ($now - $last_reward) >= (24 * 60 * 60);
                break;
            case 'weekly':
                // Verificar se já passou 7 dias desde a última recompensa
                $process_rewards = ($now - $last_reward) >= (7 * 24 * 60 * 60);
                break;
            case 'monthly':
            default:
                // Verificar se já passou 30 dias desde a última recompensa
                $process_rewards = ($now - $last_reward) >= (30 * 24 * 60 * 60);
                break;
        }
        
        // Se não for hora de processar, retornar
        if (!$process_rewards) {
            return array(
                'status' => 'info',
                'message' => __('Ainda não é hora de processar recompensas.', 'social-connect'),
                'next_reward' => $this->get_next_reward_date($last_reward, $frequency),
                'processed' => 0,
                'success' => 0,
                'failed' => 0
            );
        }
        
        // Obter usuários com contas Twitch conectadas
        $connected_users = $this->get_connected_users();
        
        if (empty($connected_users)) {
            return array(
                'status' => 'info',
                'message' => __('Nenhum usuário com conta Twitch conectada.', 'social-connect'),
                'processed' => 0,
                'success' => 0,
                'failed' => 0
            );
        }
        
        // Estatísticas
        $stats = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => array()
        );
        
        // Processar cada usuário
        foreach ($connected_users as $user_id) {
            $stats['processed']++;
            
            // Verificar se é assinante e qual tier
            $subscription = $this->check_if_user_subscribed_to_channel($broadcaster_id, null, $user_id);
            
            if (is_wp_error($subscription)) {
                $stats['failed']++;
                $stats['details'][$user_id] = array(
                    'status' => 'error',
                    'error' => $subscription->get_error_message()
                );
                continue;
            }
            
            if (!$subscription['is_subscribed']) {
                // Não é assinante, pular
                $stats['details'][$user_id] = array(
                    'status' => 'skipped',
                    'reason' => __('Não é assinante', 'social-connect')
                );
                continue;
            }
            
            // Determinar valor da recompensa com base no tier
            $reward_amount = 0;
            switch ($subscription['tier']) {
                case 1000:
                    $reward_amount = $reward_tier1;
                    break;
                case 2000:
                    $reward_amount = $reward_tier2;
                    break;
                case 3000:
                    $reward_amount = $reward_tier3;
                    break;
                default:
                    $reward_amount = $reward_tier1; // Padrão para Tier 1
            }
            
            // Verificar se o usuário já recebeu recompensa neste período
            $last_user_reward = get_user_meta($user_id, $period_key, true);
            if (!empty($last_user_reward) && $last_user_reward >= $last_reward) {
                // Usuário já recebeu recompensa neste período
                $stats['details'][$user_id] = array(
                    'status' => 'skipped',
                    'reason' => __('Já recebeu recompensa neste período', 'social-connect')
                );
                continue;
            }
            
            // Adicionar saldo ao WooWallet
            $transaction_id = woo_wallet()->wallet->credit(
                $user_id,
                $reward_amount,
                sprintf(__('Recompensa por assinatura Twitch (%s)', 'social-connect'), $subscription['tier_name']),
                array(
                    'for' => 'twitch_sub_reward',
                    'tier' => $subscription['tier'],
                    'tier_name' => $subscription['tier_name']
                )
            );
            
            if ($transaction_id) {
                // Atualizar metadados do usuário
                update_user_meta($user_id, $period_key, $now);
                
                // Salvar informações extras na transação
                update_wallet_transaction_meta($transaction_id, 'twitch_subscription_tier', $subscription['tier'], $user_id);
                update_wallet_transaction_meta($transaction_id, 'social_connect_reward_period', $frequency, $user_id);
                
                $stats['success']++;
                $stats['details'][$user_id] = array(
                    'status' => 'success',
                    'reward' => $reward_amount,
                    'tier' => $subscription['tier_name'],
                    'transaction_id' => $transaction_id
                );
            } else {
                $stats['failed']++;
                $stats['details'][$user_id] = array(
                    'status' => 'error',
                    'error' => __('Falha ao adicionar crédito ao WooWallet', 'social-connect')
                );
            }
        }
        
        // Atualizar timestamp da última recompensa
        update_option($period_key, $now);
        
        return array(
            'status' => 'success',
            'message' => sprintf(__('Recompensas processadas. %d sucesso, %d falha.', 'social-connect'), $stats['success'], $stats['failed']),
            'processed' => $stats['processed'],
            'success' => $stats['success'], 
            'failed' => $stats['failed'],
            'details' => $stats['details'],
            'next_reward' => $this->get_next_reward_date($now, $frequency)
        );
    }
    
    /**
     * Obtém a data da próxima recompensa.
     * 
     * @param int $last_reward Timestamp da última recompensa
     * @param string $frequency Frequência das recompensas
     * @return string Data formatada da próxima recompensa
     */
    private function get_next_reward_date($last_reward, $frequency) {
        $next_reward = 0;
        
        switch ($frequency) {
            case 'daily':
                $next_reward = $last_reward + (24 * 60 * 60);
                break;
            case 'weekly':
                $next_reward = $last_reward + (7 * 24 * 60 * 60);
                break;
            case 'monthly':
            default:
                $next_reward = $last_reward + (30 * 24 * 60 * 60);
                break;
        }
        
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_reward);
    }
    
    /**
     * Obtém todos os usuários com contas Twitch conectadas.
     * 
     * @return array Array com IDs de usuários
     */
    private function get_connected_users() {
        global $wpdb;
        
        $query = "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'social_connect_twitch_connected' AND meta_value = 'yes'";
        $results = $wpdb->get_col($query);
        
        return $results;
    }
    
    /**
     * Obtém todos os usuários conectados com detalhes de assinatura e seguidores.
     * 
     * @param string $broadcaster_id ID do canal para verificar status
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Array com detalhes dos usuários conectados
     */
    public function get_connected_users_with_details($broadcaster_id = '', $limit = 20, $offset = 0) {
        global $wpdb;
        
        // Consulta paginada para obter usuários conectados
        $query = $wpdb->prepare(
            "SELECT user_id, user_id as wp_user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'social_connect_twitch_connected' AND meta_value = 'yes'
             ORDER BY user_id DESC LIMIT %d OFFSET %d",
            $limit, $offset
        );
        
        $users = $wpdb->get_results($query, ARRAY_A);
        
        // Obter o total de usuários para paginação
        $total_query = "SELECT COUNT(user_id) FROM {$wpdb->usermeta} WHERE meta_key = 'social_connect_twitch_connected' AND meta_value = 'yes'";
        $total = $wpdb->get_var($total_query);
        
        if (empty($users)) {
            return array(
                'users' => array(),
                'total' => 0,
                'total_pages' => 0
            );
        }
        
        // Para cada usuário, obter detalhes da sua conta Twitch
        foreach ($users as &$user) {
            // Dados do usuário WordPress
            $user['user_data'] = get_userdata($user['wp_user_id']);
            
            // Dados da conta Twitch
            $user['twitch_user_id'] = get_user_meta($user['wp_user_id'], 'social_connect_twitch_user_id', true);
            $user['twitch_username'] = get_user_meta($user['wp_user_id'], 'social_connect_twitch_username', true);
            $user['twitch_display_name'] = get_user_meta($user['wp_user_id'], 'social_connect_twitch_display_name', true);
            $user['twitch_email'] = get_user_meta($user['wp_user_id'], 'social_connect_twitch_email', true);
            $user['twitch_profile_image'] = get_user_meta($user['wp_user_id'], 'social_connect_twitch_profile_image', true);
            
            // Data de criação da conta Twitch
            $account_created_at = get_user_meta($user['wp_user_id'], 'social_connect_twitch_account_created_at', true);
            if (!empty($account_created_at)) {
                $user['twitch_account_created_at'] = $account_created_at;
            }
            
            // Data em que o usuário conectou a conta
            $connection_date = get_user_meta($user['wp_user_id'], 'social_connect_twitch_connected_date', true);
            
            if (empty($connection_date)) {
                // Fallback para data de registro do usuário se não encontrar data específica
                $user_registered = $user['user_data'] ? $user['user_data']->user_registered : current_time('mysql');
                $user['connected_date'] = strtotime($user_registered);
            } else {
                $user['connected_date'] = strtotime($connection_date);
            }
            
            // Se um broadcaster_id foi fornecido, verificar status de seguidor e assinante
            if (!empty($broadcaster_id)) {
                // Status de seguidor
                $follows = $this->check_if_user_follows_channel($broadcaster_id, null, $user['wp_user_id']);
                if (!is_wp_error($follows)) {
                    $user['follows'] = $follows;
                }
                
                // Status de assinante e tier
                $subscription = $this->check_if_user_subscribed_to_channel($broadcaster_id, null, $user['wp_user_id']);
                if (!is_wp_error($subscription) && is_array($subscription)) {
                    $user['subscription'] = $subscription;
                }
            }
        }
        
        return array(
            'users' => $users,
            'total' => intval($total),
            'total_pages' => ceil($total / $limit)
        );
    }
    
    /**
     * Obtem o histórico de recompensas para um período específico ou todos.
     * 
     * @param string $period_start Data de início (formato Y-m-d)
     * @param string $period_end Data de término (formato Y-m-d)
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Histórico de recompensas
     */
    public function get_rewards_history($period_start = '', $period_end = '', $limit = 50, $offset = 0) {
        global $wpdb;
        
        if (!function_exists('woo_wallet')) {
            return array(
                'status' => 'error',
                'message' => __('Plugin WooWallet não está instalado ou ativado.', 'social-connect'),
                'data' => array()
            );
        }
        
        global $wpdb;
        $wallet_transactions_table = "{$wpdb->base_prefix}woo_wallet_transactions";
        $wallet_transaction_meta_table = "{$wpdb->base_prefix}woo_wallet_transaction_meta";
        
        $where = array("transaction_type = 'credit'");
        $where[] = "details LIKE '%Recompensa por assinatura Twitch%'";
        
        if (!empty($period_start)) {
            $where[] = $wpdb->prepare("date >= %s", $period_start . ' 00:00:00');
        }
        
        if (!empty($period_end)) {
            $where[] = $wpdb->prepare("date <= %s", $period_end . ' 23:59:59');
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT t.*, u.display_name, u.user_email 
                  FROM {$wallet_transactions_table} t
                  LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
                  WHERE {$where_clause}
                  ORDER BY t.date DESC
                  LIMIT %d OFFSET %d";
                  
        $transactions = $wpdb->get_results(
            $wpdb->prepare($query, $limit, $offset),
            ARRAY_A
        );
        
        if (empty($transactions)) {
            return array(
                'status' => 'info',
                'message' => __('Nenhuma recompensa encontrada para o período especificado.', 'social-connect'),
                'data' => array()
            );
        }
        
        // Obter total de registros para paginação
        $total_query = "SELECT COUNT(*) FROM {$wallet_transactions_table} WHERE {$where_clause}";
        $total = $wpdb->get_var($total_query);
        
        // Obter metadados para cada transação
        foreach ($transactions as &$transaction) {
            $meta_query = "SELECT meta_key, meta_value 
                           FROM {$wallet_transaction_meta_table} 
                           WHERE transaction_id = %d";
            $meta_results = $wpdb->get_results(
                $wpdb->prepare($meta_query, $transaction['transaction_id']),
                ARRAY_A
            );
            
            $transaction['meta'] = array();
            if (!empty($meta_results)) {
                foreach ($meta_results as $meta) {
                    $transaction['meta'][$meta['meta_key']] = $meta['meta_value'];
                }
            }
            
            // Obter informações do usuário Twitch
            $twitch_username = get_user_meta($transaction['user_id'], 'social_connect_twitch_username', true);
            $transaction['twitch_username'] = $twitch_username;
        }
        
        return array(
            'status' => 'success',
            'message' => sprintf(__('%d recompensas encontradas.', 'social-connect'), count($transactions)),
            'data' => $transactions,
            'total' => (int) $total,
            'limit' => (int) $limit,
            'offset' => (int) $offset,
            'pages' => ceil($total / $limit)
        );
    }
    
    /**
     * Obtém a lista de canais que o usuário segue e a contagem total
     * 
     * @param int $wp_user_id ID do usuário WordPress
     * @param int $limit Quantidade de canais a retornar (máximo 100 por página)
     * @param string $after Cursor para paginação
     * @return array|WP_Error Array com canais seguidos ou WP_Error em caso de falha
     */
    /**
     * Atualiza os dados do Twitch para um único usuário
     * 
     * @param int $user_id ID do usuário WordPress
     * @return bool|WP_Error True se atualizado com sucesso, WP_Error em caso de falha
     */
    public function update_user_twitch_data($user_id) {
        if (empty($user_id)) {
            return new WP_Error('invalid_user', __('ID de usuário inválido', 'social-connect'));
        }
        
        $connected = get_user_meta($user_id, 'social_connect_twitch_connected', true);
        if (empty($connected) || $connected !== 'yes') {
            return new WP_Error('not_connected', __('Usuário não conectou conta Twitch', 'social-connect'));
        }
        
        // Obter token de acesso
        $access_token = get_user_meta($user_id, 'social_connect_twitch_access_token', true);
        $expires = get_user_meta($user_id, 'social_connect_twitch_expires', true);
        
        // Verificar se token está expirado
        if (empty($access_token) || $expires < time()) {
            $refresh_token = get_user_meta($user_id, 'social_connect_twitch_refresh_token', true);
            if (empty($refresh_token)) {
                return new WP_Error('expired_token', __('Token expirado e não há refresh token', 'social-connect'));
            }
            
            $refresh_response = $this->refresh_access_token($refresh_token);
            if (is_wp_error($refresh_response)) {
                return $refresh_response;
            }
            
            $access_token = $refresh_response['access_token'];
            update_user_meta($user_id, 'social_connect_twitch_access_token', $access_token);
            update_user_meta($user_id, 'social_connect_twitch_refresh_token', $refresh_response['refresh_token']);
            update_user_meta($user_id, 'social_connect_twitch_expires', time() + $refresh_response['expires_in']);
        }
        
        // Obter informações atualizadas do usuário
        $user_info = $this->get_user_info($access_token);
        
        if (is_wp_error($user_info)) {
            return $user_info;
        }
        
        // Atualizar dados do usuário
        update_user_meta($user_id, 'social_connect_twitch_user_id', $user_info['id']);
        update_user_meta($user_id, 'social_connect_twitch_username', $user_info['login']);
        update_user_meta($user_id, 'social_connect_twitch_display_name', $user_info['display_name']);
        
        if (!empty($user_info['profile_image_url'])) {
            update_user_meta($user_id, 'social_connect_twitch_profile_image', $user_info['profile_image_url']);
        }
        
        // Obter e atualizar a contagem de canais seguidos
        $followed_channels = $this->get_user_followed_channels($user_id, 1);
        if (!is_wp_error($followed_channels) && isset($followed_channels['total'])) {
            update_user_meta($user_id, 'social_connect_twitch_following_count', $followed_channels['total']);
        }
        
        // Registrar data da última atualização
        update_user_meta($user_id, 'social_connect_twitch_last_update', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Atualiza os dados da Twitch para todos os usuários conectados
     */
    public function refresh_all_users_twitch_data() {
        global $wpdb;
        
        // Obter todos os usuários conectados
        $users = $wpdb->get_col(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'social_connect_twitch_connected' AND meta_value = 'yes'"
        );
        
        $updated_count = 0;
        $error_count = 0;
        
        foreach ($users as $user_id) {
            $result = $this->update_user_twitch_data($user_id);
            
            if (is_wp_error($result)) {
                $error_count++;
                if (WP_DEBUG) {
                    error_log('Erro ao atualizar dados da Twitch para usuário #' . $user_id . ': ' . $result->get_error_message());
                }
            } else {
                $updated_count++;
            }
            
            // Pausa para evitar exceder os limites da API
            usleep(500000); // 0.5 segundos
        }
        
        // Registrar resultado da atualização
        update_option('social_connect_twitch_last_refresh', array(
            'timestamp' => current_time('timestamp'),
            'updated' => $updated_count,
            'errors' => $error_count
        ));
        
        return array(
            'updated' => $updated_count,
            'errors' => $error_count
        );
    }
    
    /**
     * Manipula solicitação manual para atualizar dados da Twitch
     */
    public function handle_manual_update() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para realizar esta ação.', 'social-connect'));
        }
        
        // Verificar nonce
        check_admin_referer('update_twitch_data');
        
        // Executar atualização
        $result = $this->refresh_all_users_twitch_data();
        
        // Mostrar mensagem de sucesso
        add_settings_error(
            'social_connect_twitch',
            'update_success',
            sprintf(
                __('Dados da Twitch atualizados com sucesso. %d contas atualizadas, %d erros.', 'social-connect'),
                $result['updated'],
                $result['errors']
            ),
            'success'
        );
        
        // Redirecionar de volta
        $redirect_url = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : admin_url('admin.php?page=social-connect-twitch&tab=accounts');
        wp_redirect(add_query_arg('settings-updated', '1', $redirect_url));
        exit;
    }
    
    public function get_user_followed_channels($wp_user_id, $limit = 20, $after = null) {
        if (empty($wp_user_id)) {
            return new WP_Error('invalid_user', __('ID de usuário inválido', 'social-connect'));
        }
        
        // Verificar conexão do usuário
        $connected = get_user_meta($wp_user_id, 'social_connect_twitch_connected', true);
        if (empty($connected) || $connected !== 'yes') {
            return new WP_Error('not_connected', __('Usuário não conectou conta Twitch', 'social-connect'));
        }
        
        $twitch_user_id = get_user_meta($wp_user_id, 'social_connect_twitch_user_id', true);
        if (empty($twitch_user_id)) {
            return new WP_Error('missing_twitch_id', __('ID do usuário Twitch não encontrado', 'social-connect'));
        }
        
        // Obter token de acesso
        $access_token = get_user_meta($wp_user_id, 'social_connect_twitch_access_token', true);
        $expires = get_user_meta($wp_user_id, 'social_connect_twitch_expires', true);
        
        // Verificar se token está expirado
        if (empty($access_token) || $expires < time()) {
            $refresh_token = get_user_meta($wp_user_id, 'social_connect_twitch_refresh_token', true);
            if (empty($refresh_token)) {
                return new WP_Error('expired_token', __('Token expirado e não há refresh token', 'social-connect'));
            }
            
            $refresh_response = $this->refresh_access_token($refresh_token);
            if (is_wp_error($refresh_response)) {
                return $refresh_response;
            }
            
            $access_token = $refresh_response['access_token'];
            update_user_meta($wp_user_id, 'social_connect_twitch_access_token', $access_token);
            update_user_meta($wp_user_id, 'social_connect_twitch_refresh_token', $refresh_response['refresh_token']);
            update_user_meta($wp_user_id, 'social_connect_twitch_expires', time() + $refresh_response['expires_in']);
        }
        
        // Preparar parâmetros da consulta
        $query_params = array(
            'user_id' => $twitch_user_id,
            'first' => min(100, absint($limit))
        );
        
        // Adicionar cursor para paginação, se fornecido
        if (!empty($after)) {
            $query_params['after'] = $after;
        }
        
        // Endpoint para buscar canais seguidos
        $url = add_query_arg($query_params, $this->api_url . '/channels/followed');
        
        // Fazer a requisição
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Client-ID' => $this->client_id,
                'Authorization' => 'Bearer ' . $access_token
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $message = wp_remote_retrieve_response_message($response);
            return new WP_Error(
                'twitch_api_error', 
                sprintf(__('Erro na resposta da API Twitch (código %d): %s', 'social-connect'), $status_code, $message)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!is_array($body)) {
            return new WP_Error('invalid_response', __('Resposta inválida da API da Twitch', 'social-connect'));
        }
        
        if (isset($body['error'])) {
            return new WP_Error('twitch_api_error', $body['error'] . ': ' . $body['message']);
        }
        
        // Salvar a contagem total de canais seguidos nos metadados do usuário
        if (isset($body['total'])) {
            update_user_meta($wp_user_id, 'social_connect_twitch_following_count', intval($body['total']));
        }
        
        // Organizar os dados de retorno
        $result = array(
            'channels' => isset($body['data']) ? $body['data'] : array(),
            'total' => isset($body['total']) ? intval($body['total']) : 0,
            'pagination' => isset($body['pagination']) ? $body['pagination'] : null
        );
        
        return $result;
    }
}