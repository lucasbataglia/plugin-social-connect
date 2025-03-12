<?php

/**
 * Funcionalidades administrativas do plugin.
 */
class Social_Connect_Admin {

    /**
     * Inicializa a classe.
     */
    public function __construct() {
        // Adiciona os estilos admin via enqueue
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        
        // Adiciona metabox para mostrar informações do Twitter no perfil do usuário
        add_action('add_meta_boxes', array($this, 'add_twitter_metabox'));
    }
    
    /**
     * Adiciona metabox com informações do Twitter na página de edição de usuário
     */
    public function add_twitter_metabox() {
        add_meta_box(
            'social-connect-twitter-info',
            __('Informações do X (Twitter)', 'social-connect'),
            array($this, 'render_twitter_metabox'),
            'user-edit',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderiza o metabox com informações do Twitter
     */
    public function render_twitter_metabox() {
        global $user_id;
        
        // Verifica se o usuário tem conta Twitter conectada
        $connected = get_user_meta($user_id, 'social_connect_twitter_connected', true);
        
        if (empty($connected) || $connected !== 'yes') {
            echo '<p>' . __('Este usuário não tem uma conta X (Twitter) conectada.', 'social-connect') . '</p>';
            return;
        }
        
        // Dados básicos
        $username = get_user_meta($user_id, 'social_connect_twitter_username', true);
        $display_name = get_user_meta($user_id, 'social_connect_twitter_display_name', true);
        $profile_image = get_user_meta($user_id, 'social_connect_twitter_profile_image', true);
        $connected_date = get_user_meta($user_id, 'social_connect_twitter_connected_date', true);
        $account_created = get_user_meta($user_id, 'social_connect_twitter_account_created_at', true);
        
        // Métricas
        $tweet_count = get_user_meta($user_id, 'social_connect_twitter_tweet_count', true);
        $followers_count = get_user_meta($user_id, 'social_connect_twitter_followers_count', true);
        $following_count = get_user_meta($user_id, 'social_connect_twitter_following_count', true);
        
        // Exibe as informações
        ?>
        <div class="twitter-profile-card">
            <div class="profile-header">
                <?php if (!empty($profile_image)): ?>
                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($username); ?>" class="profile-image">
                <?php endif; ?>
                
                <div class="profile-info">
                    <h3><?php echo esc_html($display_name); ?></h3>
                    <p><a href="https://twitter.com/<?php echo esc_attr($username); ?>" target="_blank">@<?php echo esc_html($username); ?></a></p>
                    
                    <?php if (!empty($connected_date)): ?>
                        <p class="meta-info">
                            <?php 
                            _e('Conectado em: ', 'social-connect');
                            echo date_i18n(get_option('date_format'), strtotime($connected_date));
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($account_created)): ?>
                        <p class="meta-info">
                            <?php 
                            _e('Conta criada em: ', 'social-connect');
                            echo date_i18n(get_option('date_format'), strtotime($account_created));
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="twitter-metrics-wrapper">
                <div class="twitter-metrics">
                    <div class="metric-item">
                        <span class="dashicons dashicons-twitter"></span>
                        <div class="metric-content">
                            <span class="metric-value"><?php echo esc_html(number_format_i18n($tweet_count ?: 0)); ?></span>
                            <span class="metric-label"><?php _e('Tweets', 'social-connect'); ?></span>
                        </div>
                    </div>
                    <div class="metric-item">
                        <span class="dashicons dashicons-groups"></span>
                        <div class="metric-content">
                            <span class="metric-value"><?php echo esc_html(number_format_i18n($followers_count ?: 0)); ?></span>
                            <span class="metric-label"><?php _e('Seguidores', 'social-connect'); ?></span>
                        </div>
                    </div>
                    <div class="metric-item">
                        <span class="dashicons dashicons-admin-users"></span>
                        <div class="metric-content">
                            <span class="metric-value"><?php echo esc_html(number_format_i18n($following_count ?: 0)); ?></span>
                            <span class="metric-label"><?php _e('Seguindo', 'social-connect'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            // Tweets recentes (limitado a 5)
            $twitter = new Social_Connect_Twitter();
            $recent_tweets = $twitter->get_recent_tweets($user_id, 5);
            
            if (!is_wp_error($recent_tweets) && !empty($recent_tweets['tweets'])): ?>
                <div class="twitter-recent-posts">
                    <h4><?php _e('Posts Recentes', 'social-connect'); ?></h4>
                    <ul class="tweets-list">
                        <?php foreach ($recent_tweets['tweets'] as $tweet): ?>
                            <li class="tweet-item">
                                <div class="tweet-content">
                                    <?php echo wpautop(esc_html($tweet['text'])); ?>
                                </div>
                                
                                <?php if (isset($tweet['public_metrics'])): ?>
                                    <div class="tweet-metrics">
                                        <?php if (isset($tweet['public_metrics']['like_count'])): ?>
                                            <span class="tweet-metric">
                                                <span class="dashicons dashicons-heart"></span>
                                                <?php echo esc_html(number_format_i18n($tweet['public_metrics']['like_count'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($tweet['public_metrics']['retweet_count'])): ?>
                                            <span class="tweet-metric">
                                                <span class="dashicons dashicons-controls-repeat"></span>
                                                <?php echo esc_html(number_format_i18n($tweet['public_metrics']['retweet_count'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($tweet['created_at'])): ?>
                                    <div class="tweet-date">
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tweet['created_at'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($tweet['id'])): ?>
                                    <a href="https://twitter.com/<?php echo esc_attr($username); ?>/status/<?php echo esc_attr($tweet['id']); ?>" target="_blank" class="tweet-link">
                                        <?php _e('Ver no X', 'social-connect'); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p><?php _e('Não foi possível carregar os posts recentes.', 'social-connect'); ?></p>
            <?php endif; ?>
            
            <div class="twitter-actions">
                <a href="#" class="button button-primary disconnect-twitter-user" data-user-id="<?php echo esc_attr($user_id); ?>" data-nonce="<?php echo wp_create_nonce('social_connect_admin_disconnect_user'); ?>">
                    <?php _e('Desconectar Conta X', 'social-connect'); ?>
                </a>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.disconnect-twitter-user').on('click', function(e) {
                e.preventDefault();
                
                if (confirm('<?php _e('Tem certeza que deseja desconectar a conta X deste usuário?', 'social-connect'); ?>')) {
                    var button = $(this);
                    button.prop('disabled', true).text('<?php _e('Desconectando...', 'social-connect'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'social_connect_admin_disconnect_user',
                            user_id: button.data('user-id'),
                            platform: 'twitter',
                            nonce: button.data('nonce')
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data.message);
                                button.prop('disabled', false).text('<?php _e('Desconectar Conta X', 'social-connect'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Erro ao desconectar usuário. Tente novamente.', 'social-connect'); ?>');
                            button.prop('disabled', false).text('<?php _e('Desconectar Conta X', 'social-connect'); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin styles properly
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style(
            'social-connect-admin',
            SOCIAL_CONNECT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SOCIAL_CONNECT_VERSION
        );
        
        // Adiciona CSS inline para corrigir o problema específico da tabela Twitter
        $custom_css = "
        #wpbody-content .wrap div.tab-content div.card { 
            width: 100% !important; 
            max-width: 100% !important; 
            box-sizing: border-box !important; 
            padding: 20px !important;
        }
        #wpbody-content .wrap div.tab-content div.card table.wp-list-table.widefat.fixed.striped.users {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        ";
        wp_add_inline_style('social-connect-admin', $custom_css);
    }
    
    /**
     * Adiciona estilos CSS para o admin.
     */
    public function admin_styles() {
        // Verificar se estamos em uma página do plugin
        $screen = get_current_screen();
        // Log do screen ID para debug
        error_log('Current screen ID: ' . ($screen ? $screen->id : 'null'));
        
        // Aceitar todas as páginas do plugin, independentemente do ID da tela
        if (!$screen) {
            return;
        }
        
        // Adicionar estilos CSS inline em todas as páginas admin
        ?>
        <style type="text/css">
            /* Estilo para grupo de input com símbolo de moeda */
            .rewards-input-group {
                position: relative;
                display: inline-block;
            }
            
            .rewards-input-group .currency-symbol {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                font-weight: bold;
                color: #555;
            }
            
            .rewards-input-group input[type="number"] {
                padding-left: 25px;
                width: 80px;
            }
            
            /* Estilos para cartões */
            .tab-content .card {
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                width: 100% !important;
                box-sizing: border-box !important;
                padding: 20px !important;
            }
            
            /* Estilos para tabela de histórico */
            .tab-content table {
                margin-top: 15px;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .tab-content table th {
                font-weight: 600;
            }
            
            /* Estilos para tabelas de usuários */
            .wp-list-table {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Cores para tier */
            .tier-1 { color: #9146FF; }
            .tier-2 { color: #2980b9; }
            .tier-3 { color: #c0392b; }
            
            /* Estilos para subsubsub */
            .subsubsub {
                margin-bottom: 20px;
            }
            
            .subsubsub a.current {
                font-weight: 600;
                color: #000;
            }
            
            /* Estilos para seções de configurações */
            .api-settings, .rewards-settings {
                margin-top: 20px;
                width: 100% !important;
            }
            
            /* Fix para o card na página de contas conectadas */
            .tab-content {
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Fix específico para a tabela de contas do Twitter */
            .page-social-connect-twitter .card {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                padding: 20px !important;
            }
            
            .page-social-connect-twitter .card h3 {
                margin-top: 0 !important;
            }
            
            .page-social-connect-twitter .wp-list-table,
            #social-connect-twitter-accounts table,
            .social-connect-twitter table,
            #wpbody-content .wrap .card table.wp-list-table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
            }
            
            /* Super específico para o card de contas Twitter */
            #wpbody-content .wrap div.card {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                padding: 20px !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        </style>
        <?php
    }

    /**
     * Adiciona o menu administrativo do plugin.
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Social Connect', 'social-connect'),
            __('Social Connect', 'social-connect'),
            'manage_options',
            'social-connect',
            array($this, 'display_admin_page'),
            'dashicons-share',
            30
        );
        
        // Submenu para Twitch - Página com abas internas
        add_submenu_page(
            'social-connect',
            __('Twitch', 'social-connect'),
            __('Twitch', 'social-connect'),
            'manage_options',
            'social-connect-twitch',
            array($this, 'display_twitch_tabs_page')
        );
        
        // Submenu para Twitter - Página com abas internas
        add_submenu_page(
            'social-connect',
            __('Twitter', 'social-connect'),
            __('Twitter', 'social-connect'),
            'manage_options',
            'social-connect-twitter',
            array($this, 'display_twitter_tabs_page')
        );
        
        // Submenu para Steam - Página com abas internas
        add_submenu_page(
            'social-connect',
            __('Steam', 'social-connect'),
            __('Steam', 'social-connect'),
            'manage_options',
            'social-connect-steam',
            array($this, 'display_steam_tabs_page')
        );
    }
    
    /**
     * Registra as configurações do plugin.
     */
    public function register_settings() {
        // Grupo de configurações Twitch
        register_setting('social_connect_twitch', 'social_connect_twitch_client_id');
        register_setting('social_connect_twitch', 'social_connect_twitch_client_secret');
        register_setting('social_connect_twitch', 'social_connect_twitch_redirect_uri');
        register_setting('social_connect_twitch', 'social_connect_twitch_broadcaster_id');
        register_setting('social_connect_twitch', 'social_connect_twitch_enable_rewards', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        register_setting('social_connect_twitch', 'social_connect_twitch_reward_tier1', array(
            'type' => 'number',
            'default' => 5,
            'sanitize_callback' => 'absint',
        ));
        register_setting('social_connect_twitch', 'social_connect_twitch_reward_tier2', array(
            'type' => 'number',
            'default' => 10,
            'sanitize_callback' => 'absint',
        ));
        register_setting('social_connect_twitch', 'social_connect_twitch_reward_tier3', array(
            'type' => 'number',
            'default' => 25,
            'sanitize_callback' => 'absint',
        ));
        register_setting('social_connect_twitch', 'social_connect_twitch_reward_frequency', array(
            'type' => 'string',
            'default' => 'monthly',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        // Grupo de configurações Twitter/X
        register_setting('social_connect_twitter', 'social_connect_twitter_client_id');
        register_setting('social_connect_twitter', 'social_connect_twitter_client_secret');
        register_setting('social_connect_twitter', 'social_connect_twitter_redirect_uri');
        register_setting('social_connect_twitter', 'social_connect_twitter_username');
        
        // Grupo de configurações Steam
        register_setting('social_connect_steam', 'social_connect_steam_api_key');
        register_setting('social_connect_steam', 'social_connect_steam_game_id');
        
        // Seção principal para a Steam
        add_settings_section(
            'social_connect_steam_section',
            __('Integração com Steam', 'social-connect'),
            array($this, 'steam_section_callback'),
            'social_connect_steam'
        );
        
        // Seção para configurações da API da Steam
        add_settings_section(
            'social_connect_steam_api_section',
            __('Configurações da API da Steam', 'social-connect'),
            array($this, 'steam_api_section_callback'),
            'social_connect_steam'
        );
        
        // Campo API Key
        add_settings_field(
            'social_connect_steam_api_key',
            __('API Key', 'social-connect'),
            array($this, 'steam_api_key_callback'),
            'social_connect_steam',
            'social_connect_steam_api_section'
        );
        
        // Campo Game ID
        add_settings_field(
            'social_connect_steam_game_id',
            __('Game ID', 'social-connect'),
            array($this, 'steam_game_id_callback'),
            'social_connect_steam',
            'social_connect_steam_section'
        );
        
        // Campo Trade URL
        register_setting('social_connect_steam', 'social_connect_steam_trade_url_field');
        add_settings_field(
            'social_connect_steam_trade_url_field',
            __('Campo de Trade URL', 'social-connect'),
            array($this, 'steam_trade_url_field_callback'),
            'social_connect_steam',
            'social_connect_steam_section'
        );
        
        // Seção principal para a Twitch
        add_settings_section(
            'social_connect_twitch_section',
            __('Integração com Twitch', 'social-connect'),
            array($this, 'twitch_section_callback'),
            'social_connect_twitch'
        );
        
        // Campo Broadcaster ID (seu canal)
        add_settings_field(
            'social_connect_twitch_broadcaster_id',
            __('ID do Seu Canal', 'social-connect'),
            array($this, 'twitch_broadcaster_id_callback'),
            'social_connect_twitch',
            'social_connect_twitch_section'
        );
        
        // Seção para configurações da API
        add_settings_section(
            'social_connect_twitch_api_section',
            __('Configurações da API', 'social-connect'),
            function() {
                echo '<p>' . __('Configure as credenciais da API Twitch para autenticação.', 'social-connect') . '</p>';
            },
            'social_connect_twitch'
        );
        
        // Campo Client ID
        add_settings_field(
            'social_connect_twitch_client_id',
            __('Client ID', 'social-connect'),
            array($this, 'twitch_client_id_callback'),
            'social_connect_twitch',
            'social_connect_twitch_api_section'
        );
        
        // Campo Client Secret
        add_settings_field(
            'social_connect_twitch_client_secret',
            __('Client Secret', 'social-connect'),
            array($this, 'twitch_client_secret_callback'),
            'social_connect_twitch',
            'social_connect_twitch_api_section'
        );
        
        // Campo Redirect URI
        add_settings_field(
            'social_connect_twitch_redirect_uri',
            __('Redirect URI', 'social-connect'),
            array($this, 'twitch_redirect_uri_callback'),
            'social_connect_twitch',
            'social_connect_twitch_api_section'
        );
        
        // Campo Broadcaster ID (seu canal)
        add_settings_field(
            'social_connect_twitch_broadcaster_id',
            __('ID do Seu Canal', 'social-connect'),
            array($this, 'twitch_broadcaster_id_callback'),
            'social_connect_twitch',
            'social_connect_twitch_api_section'
        );
        
        // Seção para configurações de recompensas
        add_settings_section(
            'social_connect_twitch_rewards_section',
            __('Configurações de Recompensas', 'social-connect'),
            function() {
                echo '<p>' . __('Configure as recompensas para assinantes da Twitch. As recompensas serão adicionadas ao saldo do WooWallet.', 'social-connect') . '</p>';
                
                // Verificar se o WooWallet está ativo
                if (!function_exists('woo_wallet')) {
                    echo '<div class="notice notice-error inline"><p>' . __('O plugin WooWallet não está instalado ou ativado. As recompensas não funcionarão sem ele.', 'social-connect') . '</p></div>';
                }
            },
            'social_connect_twitch'
        );
        
        // Campo para ativar recompensas
        add_settings_field(
            'social_connect_twitch_enable_rewards',
            __('Ativar Recompensas', 'social-connect'),
            array($this, 'twitch_enable_rewards_callback'),
            'social_connect_twitch',
            'social_connect_twitch_rewards_section'
        );
        
        // Campo para recompensa Tier 1
        add_settings_field(
            'social_connect_twitch_reward_tier1',
            __('Recompensa para Tier 1', 'social-connect'),
            array($this, 'twitch_reward_tier1_callback'),
            'social_connect_twitch',
            'social_connect_twitch_rewards_section'
        );
        
        // Campo para recompensa Tier 2
        add_settings_field(
            'social_connect_twitch_reward_tier2',
            __('Recompensa para Tier 2', 'social-connect'),
            array($this, 'twitch_reward_tier2_callback'),
            'social_connect_twitch',
            'social_connect_twitch_rewards_section'
        );
        
        // Campo para recompensa Tier 3
        add_settings_field(
            'social_connect_twitch_reward_tier3',
            __('Recompensa para Tier 3', 'social-connect'),
            array($this, 'twitch_reward_tier3_callback'),
            'social_connect_twitch',
            'social_connect_twitch_rewards_section'
        );
        
        // Campo para frequência de recompensas
        add_settings_field(
            'social_connect_twitch_reward_frequency',
            __('Frequência de Recompensas', 'social-connect'),
            array($this, 'twitch_reward_frequency_callback'),
            'social_connect_twitch',
            'social_connect_twitch_rewards_section'
        );
        
        // Seção principal para o Twitter/X
        add_settings_section(
            'social_connect_twitter_section',
            __('Integração com X (Twitter)', 'social-connect'),
            array($this, 'twitter_section_callback'),
            'social_connect_twitter'
        );
        
        // Campo Username X
        add_settings_field(
            'social_connect_twitter_username',
            __('Nome de Usuário do X', 'social-connect'),
            array($this, 'twitter_username_callback'),
            'social_connect_twitter',
            'social_connect_twitter_section'
        );
        
        // Seção para configurações da API do X
        add_settings_section(
            'social_connect_twitter_api_section',
            __('Configurações da API do X', 'social-connect'),
            array($this, 'twitter_api_section_callback'),
            'social_connect_twitter'
        );
        
        // Campo Client ID do X
        add_settings_field(
            'social_connect_twitter_client_id',
            __('Client ID', 'social-connect'),
            array($this, 'twitter_client_id_callback'),
            'social_connect_twitter',
            'social_connect_twitter_api_section'
        );
        
        // Campo Client Secret do X
        add_settings_field(
            'social_connect_twitter_client_secret',
            __('Client Secret', 'social-connect'),
            array($this, 'twitter_client_secret_callback'),
            'social_connect_twitter',
            'social_connect_twitter_api_section'
        );
        
        // Campo Redirect URI do X
        add_settings_field(
            'social_connect_twitter_redirect_uri',
            __('Redirect URI', 'social-connect'),
            array($this, 'twitter_redirect_uri_callback'),
            'social_connect_twitter',
            'social_connect_twitter_api_section'
        );
    }
    
    /**
     * Callback para seção Twitch.
     */
    public function twitch_section_callback() {
        echo '<p>' . __('Gerencie as integrações e funcionalidades relacionadas à Twitch.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Client ID.
     */
    public function twitch_client_id_callback() {
        $client_id = get_option('social_connect_twitch_client_id');
        echo '<input type="text" id="social_connect_twitch_client_id" name="social_connect_twitch_client_id" value="' . esc_attr($client_id) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe o Client ID da sua aplicação Twitch.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Client Secret.
     */
    public function twitch_client_secret_callback() {
        $client_secret = get_option('social_connect_twitch_client_secret');
        echo '<input type="password" id="social_connect_twitch_client_secret" name="social_connect_twitch_client_secret" value="' . esc_attr($client_secret) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe o Client Secret da sua aplicação Twitch.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Redirect URI.
     */
    public function twitch_redirect_uri_callback() {
        $redirect_uri = get_option('social_connect_twitch_redirect_uri', home_url('wc-auth/twitch'));
        echo '<input type="text" id="social_connect_twitch_redirect_uri" name="social_connect_twitch_redirect_uri" value="' . esc_attr($redirect_uri) . '" class="regular-text">';
        echo '<p class="description">' . __('URI de redirecionamento para o OAuth da Twitch.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Broadcaster ID.
     */
    public function twitch_broadcaster_id_callback() {
        $broadcaster_id = get_option('social_connect_twitch_broadcaster_id', '');
        echo '<input type="text" id="social_connect_twitch_broadcaster_id" name="social_connect_twitch_broadcaster_id" value="' . esc_attr($broadcaster_id) . '" class="regular-text">';
        echo '<p class="description">' . __('ID do seu canal na Twitch. Usado para verificar se os usuários seguem seu canal ou são assinantes.', 'social-connect') . '</p>';
        echo '<p class="description">' . __('Você pode obter seu ID acessando <a href="https://dev.twitch.tv/console/tools/user-id" target="_blank">Twitch Developer Console</a>.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para ativar recompensas.
     */
    public function twitch_enable_rewards_callback() {
        $enable_rewards = get_option('social_connect_twitch_enable_rewards', false);
        echo '<label><input type="checkbox" id="social_connect_twitch_enable_rewards" name="social_connect_twitch_enable_rewards" value="1" ' . checked(1, $enable_rewards, false) . '> ' . __('Ativar sistema de recompensas para assinantes', 'social-connect') . '</label>';
        echo '<p class="description">' . __('Quando ativado, os assinantes receberão créditos em suas carteiras de acordo com o tier de assinatura.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para recompensa Tier 1.
     */
    public function twitch_reward_tier1_callback() {
        $currency_symbol = get_woocommerce_currency_symbol();
        $reward_tier1 = get_option('social_connect_twitch_reward_tier1', 5);
        echo '<div class="rewards-input-group">';
        echo '<span class="currency-symbol">' . esc_html($currency_symbol) . '</span>';
        echo '<input type="number" min="0" step="1" id="social_connect_twitch_reward_tier1" name="social_connect_twitch_reward_tier1" value="' . esc_attr($reward_tier1) . '" class="small-text">';
        echo '</div>';
        echo '<p class="description">' . __('Valor a ser adicionado na carteira para assinantes Tier 1.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para recompensa Tier 2.
     */
    public function twitch_reward_tier2_callback() {
        $currency_symbol = get_woocommerce_currency_symbol();
        $reward_tier2 = get_option('social_connect_twitch_reward_tier2', 10);
        echo '<div class="rewards-input-group">';
        echo '<span class="currency-symbol">' . esc_html($currency_symbol) . '</span>';
        echo '<input type="number" min="0" step="1" id="social_connect_twitch_reward_tier2" name="social_connect_twitch_reward_tier2" value="' . esc_attr($reward_tier2) . '" class="small-text">';
        echo '</div>';
        echo '<p class="description">' . __('Valor a ser adicionado na carteira para assinantes Tier 2.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para recompensa Tier 3.
     */
    public function twitch_reward_tier3_callback() {
        $currency_symbol = get_woocommerce_currency_symbol();
        $reward_tier3 = get_option('social_connect_twitch_reward_tier3', 25);
        echo '<div class="rewards-input-group">';
        echo '<span class="currency-symbol">' . esc_html($currency_symbol) . '</span>';
        echo '<input type="number" min="0" step="1" id="social_connect_twitch_reward_tier3" name="social_connect_twitch_reward_tier3" value="' . esc_attr($reward_tier3) . '" class="small-text">';
        echo '</div>';
        echo '<p class="description">' . __('Valor a ser adicionado na carteira para assinantes Tier 3.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para frequência de recompensas.
     */
    public function twitch_reward_frequency_callback() {
        $frequency = get_option('social_connect_twitch_reward_frequency', 'monthly');
        ?>
        <select id="social_connect_twitch_reward_frequency" name="social_connect_twitch_reward_frequency">
            <option value="daily" <?php selected($frequency, 'daily'); ?>><?php _e('Diária', 'social-connect'); ?></option>
            <option value="weekly" <?php selected($frequency, 'weekly'); ?>><?php _e('Semanal', 'social-connect'); ?></option>
            <option value="monthly" <?php selected($frequency, 'monthly'); ?>><?php _e('Mensal', 'social-connect'); ?></option>
        </select>
        <p class="description"><?php _e('Com que frequência os assinantes receberão as recompensas.', 'social-connect'); ?></p>
        <?php
    }
    
    /**
     * Renderiza a página principal do admin.
     */
    public function display_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="card">
                <h2><?php _e('Bem-vindo ao Social Connect', 'social-connect'); ?></h2>
                <p><?php _e('Este plugin permite que seus usuários conectem suas contas de redes sociais ao seu site WordPress.', 'social-connect'); ?></p>
                <p><?php _e('Use o menu lateral para configurar as plataformas de mídia social disponíveis.', 'social-connect'); ?></p>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h3><?php _e('Plataformas Suportadas', 'social-connect'); ?></h3>
                <ul>
                    <li><span class="dashicons dashicons-yes"></span> Twitch</li>
                    <li><span class="dashicons dashicons-minus"></span> YouTube (em breve)</li>
                    <li><span class="dashicons dashicons-minus"></span> Discord (em breve)</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza a página com abas do Twitch.
     */
    public function display_twitch_tabs_page() {
        // Determina qual aba está ativa
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        
        // URL base da página
        $page_url = admin_url('admin.php?page=social-connect-twitch');
        ?>
        <div class="wrap">
            <h1><?php _e('Twitch - Social Connect', 'social-connect'); ?></h1>
            
            <!-- Abas -->
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url($page_url . '&tab=settings'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Configurações', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=accounts'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'accounts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Contas Conectadas', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=rewards'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'rewards' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Recompensas', 'social-connect'); ?>
                </a>
            </h2>
            
            <!-- Conteúdo das abas -->
            <?php
            if ($active_tab == 'settings') {
                $this->display_twitch_settings_content();
            } elseif ($active_tab == 'accounts') {
                $this->display_twitch_accounts_content();
            } elseif ($active_tab == 'rewards') {
                $this->display_twitch_rewards_content();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de configurações do Twitch.
     */
    private function display_twitch_settings_content() {
        // Determinar qual subseção está ativa
        $current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'api';
        $base_url = admin_url('admin.php?page=social-connect-twitch&tab=settings');
        ?>
        <div class="tab-content">
            <!-- Sub-navegação para configurações -->
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url($base_url . '&section=api'); ?>" class="<?php echo $current_section === 'api' ? 'current' : ''; ?>">
                        <?php _e('API Twitch', 'social-connect'); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url($base_url . '&section=rewards'); ?>" class="<?php echo $current_section === 'rewards' ? 'current' : ''; ?>">
                        <?php _e('Recompensas', 'social-connect'); ?>
                    </a>
                </li>
            </ul>
            <div class="clear"></div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('social_connect_twitch');
                
                // Exibir apenas seções relevantes com base na subseção atual
                if ($current_section === 'api') {
                    // Mostrar seções API e geral
                    echo '<div class="api-settings">';
                    $this->do_specific_sections('social_connect_twitch', ['social_connect_twitch_section', 'social_connect_twitch_api_section']);
                    echo '</div>';
                } else if ($current_section === 'rewards') {
                    // Mostrar apenas seção de recompensas
                    echo '<div class="rewards-settings">';
                    $this->do_specific_sections('social_connect_twitch', ['social_connect_twitch_rewards_section']);
                    echo '</div>';
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza seções específicas de configurações.
     */
    private function do_specific_sections($page, $sections) {
        global $wp_settings_sections, $wp_settings_fields;
        
        if (!isset($wp_settings_sections[$page])) {
            return;
        }
        
        foreach ((array)$wp_settings_sections[$page] as $section) {
            if (!in_array($section['id'], $sections)) {
                continue;
            }
            
            if ($section['title']) {
                echo "<h2>{$section['title']}</h2>\n";
            }
            
            if ($section['callback']) {
                call_user_func($section['callback'], $section);
            }
            
            if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
                continue;
            }
            
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($page, $section['id']);
            echo '</table>';
        }
    }
    
    /**
     * Renderiza o conteúdo da aba de contas conectadas do Twitch.
     */
    private function display_twitch_accounts_content() {
        $twitch = new Social_Connect_Twitch();
        $broadcaster_id = get_option('social_connect_twitch_broadcaster_id', '');
        
        // Paginação
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $start = ($current_page - 1) * $per_page;
        
        // Obter todos os usuários conectados
        $connected_users = $twitch->get_connected_users_with_details($broadcaster_id, $per_page, $start);
        
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Aqui você pode ver todas as contas de usuários que conectaram suas contas Twitch.', 'social-connect'); ?></p>
            </div>
            
            <?php if (empty($broadcaster_id)): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('O ID do canal da Twitch não está configurado. Configure-o em Configurações > API Twitch para visualizar informações de seguidores e assinantes.', 'social-connect'); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (empty($connected_users['users'])): ?>
                <div class="card" style="margin-top: 20px; padding: 15px;">
                    <p><?php _e('Nenhum usuário conectou sua conta Twitch ainda.', 'social-connect'); ?></p>
                </div>
            <?php else: ?>
                <!-- Tabela de usuários conectados -->
                <div class="card" style="margin-top: 20px; padding: 15px;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php _e('Avatar', 'social-connect'); ?></th>
                                <th><?php _e('Usuário WordPress', 'social-connect'); ?></th>
                                <th><?php _e('Usuário Twitch', 'social-connect'); ?></th>
                                <th><?php _e('Segue Canal', 'social-connect'); ?></th>
                                <th><?php _e('Canais Seguidos', 'social-connect'); ?></th>
                                <th><?php _e('Assinante', 'social-connect'); ?></th>
                                <th><?php _e('Nível', 'social-connect'); ?></th>
                                <th><?php _e('Data de Criação da Conta', 'social-connect'); ?></th>
                                <th><?php _e('Data de Conexão', 'social-connect'); ?></th>
                                <th><?php _e('Última Atualização', 'social-connect'); ?></th>
                                <th><?php _e('Ações', 'social-connect'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($connected_users['users'] as $user): ?>
                                <tr>
                                    <!-- Avatar -->
                                    <td>
                                        <?php if (!empty($user['twitch_profile_image'])): ?>
                                            <img src="<?php echo esc_url($user['twitch_profile_image']); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <span class="dashicons dashicons-admin-users"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Usuário WordPress -->
                                    <td>
                                        <?php if (isset($user['user_data']) && $user['user_data']): ?>
                                            <a href="<?php echo esc_url(get_edit_user_link($user['wp_user_id'])); ?>">
                                                <?php echo esc_html($user['user_data']->display_name); ?>
                                                <div class="row-actions">
                                                    <span class="email"><?php echo esc_html($user['user_data']->user_email); ?></span>
                                                </div>
                                            </a>
                                        <?php else: ?>
                                            #<?php echo esc_html($user['wp_user_id']); ?>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Usuário Twitch -->
                                    <td>
                                        <?php if (!empty($user['twitch_username'])): ?>
                                            <strong><?php echo esc_html($user['twitch_display_name']); ?></strong>
                                            <div class="row-actions">
                                                <span class="username">@<?php echo esc_html($user['twitch_username']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Segue Canal -->
                                    <td>
                                        <?php if (empty($broadcaster_id)): ?>
                                            <span class="dashicons dashicons-minus"></span>
                                        <?php elseif (isset($user['follows']) && $user['follows'] === true): ?>
                                            <span class="dashicons dashicons-yes" style="color: #14b866;"></span>
                                            <span style="color: #14b866; font-weight: 500;"><?php _e('Sim', 'social-connect'); ?></span>
                                        <?php elseif (isset($user['follows']) && $user['follows'] === false): ?>
                                            <span class="dashicons dashicons-no" style="color: #ff5252;"></span>
                                            <span style="color: #ff5252;"><?php _e('Não', 'social-connect'); ?></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                                            <span style="color: #ffb900;"><?php _e('Erro', 'social-connect'); ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Canais Seguidos -->
                                    <td>
                                        <?php 
                                        $following_count = get_user_meta($user['wp_user_id'], 'social_connect_twitch_following_count', true);
                                        if (!empty($following_count)): 
                                        ?>
                                            <span class="dashicons dashicons-groups" style="color: #9146FF;"></span>
                                            <strong><?php echo esc_html(number_format_i18n($following_count)); ?></strong>
                                            <div class="row-actions">
                                                <span class="view">
                                                    <a href="#" class="view-followed-channels" 
                                                       data-user-id="<?php echo esc_attr($user['wp_user_id']); ?>"
                                                       data-username="<?php echo esc_attr($user['twitch_username']); ?>"
                                                       data-nonce="<?php echo wp_create_nonce('view_followed_channels'); ?>">
                                                        <?php _e('Ver detalhes', 'social-connect'); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                            <span><?php _e('Desconhecido', 'social-connect'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Assinante -->
                                    <td>
                                        <?php if (empty($broadcaster_id)): ?>
                                            <span class="dashicons dashicons-minus"></span>
                                        <?php elseif (isset($user['subscription']) && is_array($user['subscription']) && $user['subscription']['is_subscribed']): ?>
                                            <span class="dashicons dashicons-yes" style="color: #9146FF;"></span>
                                            <span style="color: #9146FF; font-weight: 500;"><?php _e('Sim', 'social-connect'); ?></span>
                                        <?php elseif (isset($user['subscription']) && is_array($user['subscription']) && !$user['subscription']['is_subscribed']): ?>
                                            <span class="dashicons dashicons-no" style="color: #ff5252;"></span>
                                            <span style="color: #ff5252;"><?php _e('Não', 'social-connect'); ?></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                                            <span style="color: #ffb900;"><?php _e('Erro', 'social-connect'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Nível de Assinatura -->
                                    <td>
                                        <?php 
                                        if (isset($user['subscription']) && is_array($user['subscription']) && $user['subscription']['is_subscribed'] && !empty($user['subscription']['tier_name'])): 
                                            $tier_class = '';
                                            if (strpos($user['subscription']['tier_name'], '1') !== false) {
                                                $tier_class = 'tier-1';
                                            } elseif (strpos($user['subscription']['tier_name'], '2') !== false) {
                                                $tier_class = 'tier-2';
                                            } elseif (strpos($user['subscription']['tier_name'], '3') !== false) {
                                                $tier_class = 'tier-3';
                                            }
                                        ?>
                                            <span class="<?php echo esc_attr($tier_class); ?>" style="font-weight: 600;">
                                                <?php echo esc_html($user['subscription']['tier_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Data de Criação da Conta -->
                                    <td>
                                        <?php 
                                        if (!empty($user['twitch_account_created_at'])):
                                            // Tenta converter a data para timestamp
                                            $created_timestamp = strtotime($user['twitch_account_created_at']);
                                            
                                            if ($created_timestamp !== false):
                                                echo esc_html(date_i18n(get_option('date_format'), $created_timestamp));
                                                
                                                // Adiciona o tempo desde a criação
                                                $time_diff = human_time_diff($created_timestamp, current_time('timestamp'));
                                                echo '<div class="row-actions">';
                                                echo '<span class="time-ago">' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</span>';
                                                echo '</div>';
                                            else:
                                                // Mostra a data bruta se não conseguir converter
                                                echo esc_html($user['twitch_account_created_at']);
                                            endif;
                                        else:
                                            echo '-';
                                        endif;
                                        ?>
                                    </td>
                                    
                                    <!-- Data de Conexão -->
                                    <td>
                                        <?php 
                                        if (!empty($user['connected_date'])):
                                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $user['connected_date']));
                                            
                                            // Adiciona o tempo desde a conexão
                                            $time_diff = human_time_diff($user['connected_date'], current_time('timestamp'));
                                            echo '<div class="row-actions">';
                                            echo '<span class="time-ago">' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</span>';
                                            echo '</div>';
                                        else:
                                            echo '-';
                                        endif;
                                        ?>
                                    </td>
                                    
                                    <!-- Última Atualização -->
                                    <td>
                                        <?php 
                                        $last_update = get_user_meta($user['wp_user_id'], 'social_connect_twitch_last_update', true);
                                        if (!empty($last_update)):
                                            $last_update_timestamp = strtotime($last_update);
                                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update_timestamp));
                                            
                                            // Adiciona o tempo desde a última atualização
                                            $time_diff = human_time_diff($last_update_timestamp, current_time('timestamp'));
                                            echo '<div class="row-actions">';
                                            echo '<span class="time-ago">' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</span>';
                                            echo '</div>';
                                        else:
                                            echo '<span class="dashicons dashicons-warning" style="color: #ffb900; vertical-align: text-bottom;"></span> ';
                                            echo __('Nunca atualizado', 'social-connect');
                                        endif;
                                        ?>
                                    </td>
                                    
                                    <!-- Ações -->
                                    <td>
                                        <button type="button" class="button button-small update-user-twitch-data" 
                                                data-user-id="<?php echo esc_attr($user['wp_user_id']); ?>"
                                                data-username="<?php echo esc_attr($user['twitch_username']); ?>"
                                                data-nonce="<?php echo wp_create_nonce('update_twitch_user_data'); ?>">
                                            <span class="dashicons dashicons-update" style="width: 18px; height: 18px; font-size: 18px; vertical-align: text-bottom;"></span>
                                            <?php _e('Atualizar', 'social-connect'); ?>
                                        </button>
                                        <button type="button" class="button button-small disconnect-user-twitch" 
                                                data-user-id="<?php echo esc_attr($user['wp_user_id']); ?>"
                                                data-user-name="<?php echo esc_attr($user['twitch_display_name']); ?>"
                                                data-nonce="<?php echo wp_create_nonce('social_connect_admin_disconnect_user'); ?>">
                                            <?php _e('Desconectar', 'social-connect'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Paginação -->
                    <?php if ($connected_users['total_pages'] > 1): ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php printf(_n('%s item', '%s itens', $connected_users['total'], 'social-connect'), number_format_i18n($connected_users['total'])); ?>
                                </span>
                                
                                <span class="pagination-links">
                                    <?php
                                    $page_links = paginate_links(array(
                                        'base' => add_query_arg('paged', '%#%'),
                                        'format' => '',
                                        'prev_text' => __('&laquo;'),
                                        'next_text' => __('&raquo;'),
                                        'total' => $connected_users['total_pages'],
                                        'current' => $current_page
                                    ));
                                    
                                    echo $page_links;
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- JavaScript para lidar com a desconexão e atualização -->
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Adicionar CSS para a animação de rotação
                    $('head').append('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>');
                    
                    // Manipular clique no botão de desconexão
                    $('.disconnect-user-twitch').on('click', function() {
                        var userId = $(this).data('user-id');
                        var userName = $(this).data('user-name');
                        var nonce = $(this).data('nonce');
                        
                        if (confirm('<?php _e('Tem certeza que deseja desconectar a conta Twitch de', 'social-connect'); ?> ' + userName + '?')) {
                            var button = $(this);
                            button.prop('disabled', true);
                            
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'social_connect_admin_disconnect_user',
                                    user_id: userId,
                                    nonce: nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Remover a linha da tabela
                                        button.closest('tr').fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Se não houver mais linhas, recarregar a página
                                            if ($('table tbody tr').length === 0) {
                                                location.reload();
                                            }
                                        });
                                    } else {
                                        alert(response.data.message);
                                        button.prop('disabled', false);
                                    }
                                },
                                error: function() {
                                    alert('<?php _e('Ocorreu um erro ao processar sua solicitação.', 'social-connect'); ?>');
                                    button.prop('disabled', false);
                                }
                            });
                        }
                    });
                    
                    // Manipular clique no botão de atualização de dados da Twitch
                    $('.update-user-twitch-data').on('click', function() {
                        var button = $(this);
                        var userId = button.data('user-id');
                        var username = button.data('username');
                        var nonce = button.data('nonce');
                        
                        // Salvar o texto original
                        var originalText = button.html();
                        
                        // Atualizar texto do botão e desabilitar
                        button.html('<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 14px; width: 14px; height: 14px; animation: rotation 2s infinite linear;"></span> <?php _e('Atualizando...', 'social-connect'); ?>');
                        button.prop('disabled', true);
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'social_connect_update_user_twitch_data',
                                user_id: userId,
                                nonce: nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Recarregar a página para mostrar os dados atualizados
                                    location.reload();
                                } else {
                                    alert(response.data.message);
                                    button.html(originalText);
                                    button.prop('disabled', false);
                                }
                            },
                            error: function() {
                                alert('<?php _e('Erro ao atualizar dados do usuário. Tente novamente.', 'social-connect'); ?>');
                                button.html(originalText);
                                button.prop('disabled', false);
                            }
                        });
                    });
                    
                    // Modal para visualizar canais seguidos
                    var followedChannelsModal = $(`
                    <div id="followed-channels-modal" class="modal" style="display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
                        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            <div class="modal-header" style="padding-bottom: 10px; margin-bottom: 15px; border-bottom: 1px solid #eee; position: relative;">
                                <h2 id="followed-channels-title" style="margin: 0; font-size: 1.3em; font-weight: 600;">Canais Seguidos</h2>
                                <span class="close" style="position: absolute; right: 0; top: 0; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                            </div>
                            <div id="followed-channels-list" style="margin-bottom: 20px; max-height: 60vh; overflow-y: auto;">
                                <div class="loading" style="text-align: center; padding: 20px;">
                                    <span class="spinner is-active" style="float: none; width: 20px; height: 20px; margin: 0 10px 0 0;"></span>
                                    Carregando canais...
                                </div>
                            </div>
                            <div id="followed-channels-pagination" style="padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div id="followed-channels-info" style="color: #666;"></div>
                                <div id="followed-channels-buttons">
                                    <button type="button" id="load-more-channels" class="button" style="display: none;">Carregar Mais</button>
                                </div>
                            </div>
                        </div>
                    </div>`);
                    
                    $('body').append(followedChannelsModal);
                    
                    // Fechar o modal
                    followedChannelsModal.find('.close').click(function() {
                        followedChannelsModal.hide();
                    });
                    
                    // Fechar o modal clicando fora dele
                    $(window).click(function(event) {
                        if (event.target == followedChannelsModal[0]) {
                            followedChannelsModal.hide();
                        }
                    });
                    
                    // Objeto para armazenar o estado da paginação
                    var followedChannelsState = {
                        userId: null,
                        username: null,
                        after: null,
                        hasMore: false,
                        loading: false
                    };
                    
                    // Função para renderizar os canais seguidos
                    function renderFollowedChannels(channels) {
                        var html = '';
                        
                        if (channels.length === 0) {
                            html = '<div style="text-align: center; padding: 20px;"><p><?php _e('Nenhum canal seguido encontrado.', 'social-connect'); ?></p></div>';
                        } else {
                            html = '<div class="channels-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
                            
                            channels.forEach(function(channel) {
                                var channelName = channel.broadcaster_name || channel.broadcaster_login;
                                var thumbnail = channel.broadcaster_profile_image_url || 'https://static-cdn.jtvnw.net/user-default-pictures-uv/75305d54-c7cc-40d1-bb9c-91fbe85943c7-profile_image-70x70.png';
                                
                                html += `
                                <div class="channel-card" style="background: #f9f9f9; border-radius: 4px; padding: 10px; display: flex; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                    <img src="${thumbnail}" alt="${channelName}" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                                    <div class="channel-info">
                                        <h4 style="margin: 0 0 5px 0; font-size: 14px;">${channelName}</h4>
                                        <div style="font-size: 12px; color: #9146FF;">
                                            <a href="https://twitch.tv/${channel.broadcaster_login}" target="_blank" style="color: #9146FF; text-decoration: none;">Ver canal</a>
                                        </div>
                                    </div>
                                </div>`;
                            });
                            
                            html += '</div>';
                        }
                        
                        return html;
                    }
                    
                    // Função para carregar canais seguidos
                    function loadFollowedChannels(userId, username, after = null, append = false) {
                        // Atualizar estado
                        followedChannelsState.userId = userId;
                        followedChannelsState.username = username;
                        followedChannelsState.loading = true;
                        
                        // Atualizar título do modal
                        $('#followed-channels-title').text('Canais seguidos por ' + username);
                        
                        // Mostrar carregamento se não estivermos anexando
                        if (!append) {
                            $('#followed-channels-list').html('<div class="loading" style="text-align: center; padding: 20px;"><span class="spinner is-active" style="float: none; width: 20px; height: 20px; margin: 0 10px 0 0;"></span>Carregando canais...</div>');
                        } else {
                            $('#load-more-channels').prop('disabled', true).html('<span class="spinner is-active" style="float: none; width: 20px; height: 20px; margin: 0 5px 0 0; vertical-align: middle;"></span> Carregando...');
                        }
                        
                        // Fazer requisição AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'social_connect_get_followed_channels',
                                user_id: userId,
                                after: after,
                                nonce: $('a.view-followed-channels[data-user-id="' + userId + '"]').data('nonce')
                            },
                            success: function(response) {
                                followedChannelsState.loading = false;
                                
                                if (response.success) {
                                    var data = response.data;
                                    
                                    // Atualizar estado da paginação
                                    followedChannelsState.hasMore = data.pagination && data.pagination.cursor;
                                    followedChannelsState.after = data.pagination ? data.pagination.cursor : null;
                                    
                                    // Renderizar canais
                                    var channelsHtml = renderFollowedChannels(data.channels);
                                    
                                    if (append) {
                                        // Remover o loader e anexar novos canais
                                        $('#followed-channels-list .channels-grid').append($(channelsHtml).find('.channel-card'));
                                    } else {
                                        $('#followed-channels-list').html(channelsHtml);
                                    }
                                    
                                    // Atualizar informações de paginação
                                    var infoText = 'Mostrando ' + data.channels.length + ' canais';
                                    if (data.total > 0) {
                                        infoText += ' de ' + data.total + ' no total';
                                    }
                                    $('#followed-channels-info').text(infoText);
                                    
                                    // Mostrar/esconder botão "Carregar Mais"
                                    if (followedChannelsState.hasMore) {
                                        $('#load-more-channels').show().prop('disabled', false).text('Carregar Mais');
                                    } else {
                                        $('#load-more-channels').hide();
                                    }
                                } else {
                                    // Exibir mensagem de erro
                                    $('#followed-channels-list').html('<div class="error" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;"><p>' + response.data.message + '</p></div>');
                                    $('#followed-channels-info').text('');
                                    $('#load-more-channels').hide();
                                }
                            },
                            error: function() {
                                followedChannelsState.loading = false;
                                $('#followed-channels-list').html('<div class="error" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;"><p><?php _e('Erro ao carregar canais seguidos. Tente novamente.', 'social-connect'); ?></p></div>');
                                $('#followed-channels-info').text('');
                                $('#load-more-channels').hide();
                            }
                        });
                    }
                    
                    // Manipular clique no botão "Ver detalhes"
                    $('.view-followed-channels').on('click', function(e) {
                        e.preventDefault();
                        
                        var userId = $(this).data('user-id');
                        var username = $(this).data('username');
                        
                        // Resetar estado
                        followedChannelsState = {
                            userId: userId,
                            username: username,
                            after: null,
                            hasMore: false,
                            loading: false
                        };
                        
                        // Mostrar modal
                        followedChannelsModal.show();
                        
                        // Carregar canais
                        loadFollowedChannels(userId, username);
                    });
                    
                    // Manipular clique no botão "Carregar Mais"
                    $('#load-more-channels').on('click', function() {
                        if (!followedChannelsState.loading && followedChannelsState.hasMore) {
                            loadFollowedChannels(
                                followedChannelsState.userId, 
                                followedChannelsState.username,
                                followedChannelsState.after,
                                true
                            );
                        }
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de recompensas do Twitch.
     */
    private function display_twitch_rewards_content() {
        $twitch = new Social_Connect_Twitch();
        
        // Processar recompensas quando solicitado
        if (isset($_POST['process_rewards']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'social_connect_process_rewards')) {
            $results = $twitch->process_rewards();
            
            if ($results['status'] == 'success') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($results['message']) . '</p></div>';
            } elseif ($results['status'] == 'info') {
                echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($results['message']) . '</p>';
                if (isset($results['next_reward'])) {
                    echo '<p>' . sprintf(__('Próxima distribuição de recompensas: %s', 'social-connect'), $results['next_reward']) . '</p>';
                }
                echo '</div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($results['message']) . '</p></div>';
            }
        }
        
        // Verificar se o WooWallet está instalado
        if (!function_exists('woo_wallet')) {
            echo '<div class="notice notice-error"><p>' . __('O plugin WooWallet não está instalado ou ativado. As recompensas não funcionarão sem ele.', 'social-connect') . '</p></div>';
        }
        
        // Filtros para histórico
        $period_start = isset($_GET['period_start']) ? sanitize_text_field($_GET['period_start']) : '';
        $period_end = isset($_GET['period_end']) ? sanitize_text_field($_GET['period_end']) : '';
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 20;
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($paged - 1) * $limit;
        
        // Obter histórico de recompensas apenas se o WooWallet estiver ativo
        $history = array();
        if (function_exists('woo_wallet')) {
            $history = $twitch->get_rewards_history($period_start, $period_end, $limit, $offset);
        }
        
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Gerencie as recompensas para assinantes da Twitch. As recompensas são baseadas no tier de assinatura e adicionadas ao saldo do WooWallet.', 'social-connect'); ?></p>
            </div>
            
            <!-- Botão para processar recompensas manualmente -->
            <div class="card" style="margin-top: 20px; padding: 15px;">
                <h3><?php _e('Processar Recompensas', 'social-connect'); ?></h3>
                <p><?php _e('Você pode processar recompensas manualmente clicando no botão abaixo. Normalmente, as recompensas são processadas automaticamente de acordo com a frequência configurada.', 'social-connect'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('social_connect_process_rewards'); ?>
                    <input type="submit" name="process_rewards" value="<?php esc_attr_e('Processar Recompensas Agora', 'social-connect'); ?>" class="button button-primary">
                </form>
            </div>
            
            <!-- Histórico de recompensas -->
            <div class="card" style="margin-top: 20px; padding: 15px;">
                <h3><?php _e('Histórico de Recompensas', 'social-connect'); ?></h3>
                
                <!-- Filtros -->
                <form method="get" action="">
                    <input type="hidden" name="page" value="social-connect-twitch">
                    <input type="hidden" name="tab" value="rewards">
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-end;">
                        <div>
                            <label for="period_start"><?php _e('Data Inicial', 'social-connect'); ?></label><br>
                            <input type="date" id="period_start" name="period_start" value="<?php echo esc_attr($period_start); ?>">
                        </div>
                        
                        <div>
                            <label for="period_end"><?php _e('Data Final', 'social-connect'); ?></label><br>
                            <input type="date" id="period_end" name="period_end" value="<?php echo esc_attr($period_end); ?>">
                        </div>
                        
                        <div>
                            <label for="limit"><?php _e('Itens por página', 'social-connect'); ?></label><br>
                            <select id="limit" name="limit">
                                <option value="10" <?php selected($limit, 10); ?>>10</option>
                                <option value="20" <?php selected($limit, 20); ?>>20</option>
                                <option value="50" <?php selected($limit, 50); ?>>50</option>
                                <option value="100" <?php selected($limit, 100); ?>>100</option>
                            </select>
                        </div>
                        
                        <div>
                            <input type="submit" value="<?php esc_attr_e('Filtrar', 'social-connect'); ?>" class="button">
                            <a href="<?php echo admin_url('admin.php?page=social-connect-twitch&tab=rewards'); ?>" class="button"><?php _e('Limpar', 'social-connect'); ?></a>
                        </div>
                    </div>
                </form>
                
                <!-- Tabela de histórico -->
                <?php if (function_exists('woo_wallet')): ?>
                    <?php if (isset($history['data']) && !empty($history['data'])): ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Data', 'social-connect'); ?></th>
                                    <th><?php _e('Usuário', 'social-connect'); ?></th>
                                    <th><?php _e('Usuário Twitch', 'social-connect'); ?></th>
                                    <th><?php _e('Tier', 'social-connect'); ?></th>
                                    <th><?php _e('Valor', 'social-connect'); ?></th>
                                    <th><?php _e('Descrição', 'social-connect'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history['data'] as $transaction): ?>
                                    <tr>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction['date']))); ?></td>
                                        <td><?php echo esc_html($transaction['display_name']); ?> (<?php echo esc_html($transaction['user_email']); ?>)</td>
                                        <td><?php echo esc_html($transaction['twitch_username']); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($transaction['meta']['twitch_subscription_tier'])) {
                                                $tier = intval($transaction['meta']['twitch_subscription_tier']);
                                                if ($tier == 1000) {
                                                    echo __('Tier 1', 'social-connect');
                                                } elseif ($tier == 2000) {
                                                    echo __('Tier 2', 'social-connect');
                                                } elseif ($tier == 3000) {
                                                    echo __('Tier 3', 'social-connect');
                                                } else {
                                                    echo sprintf(__('Tier %s', 'social-connect'), $tier / 1000);
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo function_exists('wc_price') ? wc_price($transaction['amount']) : $transaction['amount']; ?></td>
                                        <td><?php echo esc_html($transaction['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Paginação -->
                        <?php if (isset($history['pages']) && $history['pages'] > 1): ?>
                            <div class="tablenav">
                                <div class="tablenav-pages">
                                    <span class="displaying-num">
                                        <?php echo sprintf(_n('%s item', '%s itens', $history['total'], 'social-connect'), number_format_i18n($history['total'])); ?>
                                    </span>
                                    
                                    <span class="pagination-links">
                                        <?php
                                        $page_links = paginate_links(array(
                                            'base' => add_query_arg('paged', '%#%'),
                                            'format' => '',
                                            'prev_text' => '&laquo;',
                                            'next_text' => '&raquo;',
                                            'total' => $history['pages'],
                                            'current' => $paged
                                        ));
                                        
                                        echo $page_links;
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <p><?php _e('Nenhum histórico de recompensas encontrado para o período especificado.', 'social-connect'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?php _e('Histórico de recompensas estará disponível quando o plugin WooWallet estiver ativo.', 'social-connect'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza a página com abas do Twitter.
     */
    public function display_twitter_tabs_page() {
        // Determina qual aba está ativa
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        
        // URL base da página
        $page_url = admin_url('admin.php?page=social-connect-twitter');
        ?>
        <div class="wrap">
            <h1><?php _e('Twitter - Social Connect', 'social-connect'); ?></h1>
            
            <!-- Abas -->
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url($page_url . '&tab=settings'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Configurações', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=accounts'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'accounts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Contas Conectadas', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=rewards'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'rewards' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Recompensas', 'social-connect'); ?>
                </a>
            </h2>
            
            <!-- Conteúdo das abas -->
            <?php
            if ($active_tab == 'settings') {
                $this->display_twitter_settings_content();
            } elseif ($active_tab == 'accounts') {
                $this->display_twitter_accounts_content();
            } elseif ($active_tab == 'rewards') {
                $this->display_twitter_rewards_content();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de configurações do Twitter.
     */
    private function display_twitter_settings_content() {
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Configure as credenciais da API do X (Twitter) para permitir que os usuários conectem suas contas.', 'social-connect'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('social_connect_twitter');
                do_settings_sections('social_connect_twitter');
                submit_button(__('Salvar Configurações', 'social-connect'));
                ?>
                
                <div class="card">
                    <h3><?php _e('Instruções de Configuração', 'social-connect'); ?></h3>
                    <ol>
                        <li><?php _e('Acesse o <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank">Portal de Desenvolvedores do X</a> e faça login.', 'social-connect'); ?></li>
                        <li><?php _e('Crie um novo projeto e uma aplicação do tipo "Web App"', 'social-connect'); ?></li>
                        <li><?php _e('Configure o "Callback URL" para: ', 'social-connect'); ?><code><?php echo home_url('wc-auth/twitter'); ?></code></li>
                        <li><?php _e('Na seção "User authentication settings", ative OAuth 2.0 e permita "Sign in with Twitter".', 'social-connect'); ?></li>
                        <li><?php _e('Configure os Scopes para incluir: "user.read", "follows.read", "tweet.read"', 'social-connect'); ?></li>
                        <li><?php _e('Copie o "Client ID" e o "Client Secret" para os campos acima.', 'social-connect'); ?></li>
                    </ol>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de contas conectadas do Twitter.
     */
    private function display_twitter_accounts_content() {
        // Instancia a classe Twitter
        $twitter = new Social_Connect_Twitter();
        $username = get_option('social_connect_twitter_username', '');
        
        // Configurações para paginação
        $per_page = 20;
        $current_page = isset($_GET['twitter_page']) ? intval($_GET['twitter_page']) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Obtém usuários conectados
        $connected_users = $twitter->get_connected_users_with_details($username, $per_page, $offset);
        
        // Obter informações sobre a última atualização
        $last_refresh = get_option('social_connect_twitter_last_refresh');
        
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Aqui você pode ver todas as contas de usuários que conectaram suas contas do X (Twitter).', 'social-connect'); ?></p>
                
                <?php if (!empty($last_refresh)): ?>
                    <p>
                        <?php
                        printf(
                            __('Última atualização: %s', 'social-connect'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_refresh['timestamp'])
                        );
                        echo ' - ';
                        printf(
                            __('%d contas atualizadas, %d erros.', 'social-connect'),
                            $last_refresh['updated'],
                            $last_refresh['errors']
                        );
                        ?>
                    </p>
                <?php endif; ?>
                
                <p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                        <input type="hidden" name="action" value="update_twitter_data">
                        <?php wp_nonce_field('update_twitter_data'); ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                            <?php _e('Atualizar Dados do Twitter', 'social-connect'); ?>
                        </button>
                    </form>
                </p>
            </div>
            
            <?php if (empty($connected_users['users'])): ?>
                <div class="card">
                    <p><?php _e('Nenhum usuário conectou uma conta do X ainda.', 'social-connect'); ?></p>
                </div>
            <?php else: ?>
                <div class="card">
                    <h3><?php printf(__('Contas Conectadas (%d usuários no total)', 'social-connect'), $connected_users['total']); ?></h3>
                    
                    <table class="wp-list-table widefat fixed striped users">
                        <thead>
                            <tr>
                                <th scope="col" class="column-avatar"><?php _e('Avatar', 'social-connect'); ?></th>
                                <th scope="col" class="column-wp-username"><?php _e('Usuário WordPress', 'social-connect'); ?></th>
                                <th scope="col" class="column-twitter-username"><?php _e('Usuário X', 'social-connect'); ?></th>
                                <th scope="col" class="column-metrics"><?php _e('Métricas', 'social-connect'); ?></th>
                                <th scope="col" class="column-follows"><?php _e('Segue', 'social-connect'); ?></th>
                                <th scope="col" class="column-account-created"><?php _e('Data de Criação da Conta', 'social-connect'); ?></th>
                                <th scope="col" class="column-connection-date"><?php _e('Data de Conexão', 'social-connect'); ?></th>
                                <th scope="col" class="column-last-update"><?php _e('Última Atualização', 'social-connect'); ?></th>
                                <th scope="col" class="column-actions"><?php _e('Ações', 'social-connect'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($connected_users['users'] as $user): ?>
                                <tr>
                                    <td class="column-avatar">
                                        <?php if (!empty($user['twitter_profile_image'])): ?>
                                            <img src="<?php echo esc_url($user['twitter_profile_image']); ?>" alt="<?php echo esc_attr($user['twitter_username']); ?>" width="48" height="48" style="border-radius: 50%;">
                                        <?php else: ?>
                                            <?php echo get_avatar($user['wp_user_id'], 48); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-wp-username">
                                        <?php 
                                        if ($user['user_data']) {
                                            echo '<a href="' . esc_url(get_edit_user_link($user['wp_user_id'])) . '">' . esc_html($user['user_data']->user_login) . '</a>';
                                            echo '<br><small>' . esc_html($user['user_data']->user_email) . '</small>';
                                        } else {
                                            _e('Usuário excluído', 'social-connect');
                                        }
                                        ?>
                                    </td>
                                    <td class="column-twitter-username">
                                        <?php if (!empty($user['twitter_username'])): ?>
                                            <a href="https://twitter.com/<?php echo esc_attr($user['twitter_username']); ?>" target="_blank">
                                                @<?php echo esc_html($user['twitter_username']); ?>
                                            </a>
                                            <?php if (!empty($user['twitter_display_name']) && $user['twitter_display_name'] !== $user['twitter_username']): ?>
                                                <br><small><?php echo esc_html($user['twitter_display_name']); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-metrics">
                                        <?php if (!empty($user['tweet_count']) || !empty($user['followers_count']) || !empty($user['following_count'])): ?>
                                            <div class="twitter-metrics">
                                                <div class="metric-item">
                                                    <span class="dashicons dashicons-twitter"></span>
                                                    <div class="metric-content">
                                                        <span class="metric-value"><?php echo esc_html(number_format_i18n($user['tweet_count'] ?: 0)); ?></span>
                                                        <span class="metric-label"><?php _e('Tweets', 'social-connect'); ?></span>
                                                    </div>
                                                </div>
                                                <div class="metric-item">
                                                    <span class="dashicons dashicons-groups"></span>
                                                    <div class="metric-content">
                                                        <span class="metric-value"><?php echo esc_html(number_format_i18n($user['followers_count'] ?: 0)); ?></span>
                                                        <span class="metric-label"><?php _e('Seguidores', 'social-connect'); ?></span>
                                                    </div>
                                                </div>
                                                <div class="metric-item">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                    <div class="metric-content">
                                                        <span class="metric-value"><?php echo esc_html(number_format_i18n($user['following_count'] ?: 0)); ?></span>
                                                        <span class="metric-label"><?php _e('Seguindo', 'social-connect'); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                            <?php _e('Informações não disponíveis', 'social-connect'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-follows">
                                        <?php if (isset($user['follows'])): ?>
                                            <?php if ($user['follows'] === true): ?>
                                                <span class="dashicons dashicons-yes" style="color: green;"></span> 
                                                <?php _e('Sim', 'social-connect'); ?>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-no" style="color: red;"></span> 
                                                <?php _e('Não', 'social-connect'); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                            <?php _e('Desconhecido', 'social-connect'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-account-created">
                                        <?php 
                                        if (!empty($user['twitter_account_created_at'])):
                                            // Tenta converter a data para timestamp
                                            $created_timestamp = strtotime($user['twitter_account_created_at']);
                                            
                                            if ($created_timestamp !== false):
                                                echo esc_html(date_i18n(get_option('date_format'), $created_timestamp));
                                                
                                                // Adiciona o tempo desde a criação
                                                $time_diff = human_time_diff($created_timestamp, current_time('timestamp'));
                                                echo '<div class="row-actions">';
                                                echo '<span class="time-ago">' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</span>';
                                                echo '</div>';
                                            else:
                                                // Mostra a data bruta se não conseguir converter
                                                echo esc_html($user['twitter_account_created_at']);
                                            endif;
                                        else:
                                            echo '-';
                                        endif;
                                        ?>
                                    </td>
                                    <td class="column-connection-date">
                                        <?php 
                                        if (!empty($user['connected_date'])) {
                                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $user['connected_date']);
                                            
                                            // Adiciona o tempo desde a conexão
                                            $time_diff = human_time_diff($user['connected_date'], current_time('timestamp'));
                                            echo '<br><small>' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</small>';
                                        } else {
                                            _e('Desconhecida', 'social-connect');
                                        }
                                        ?>
                                    </td>
                                    <td class="column-last-update">
                                        <?php 
                                        $last_update = get_user_meta($user['wp_user_id'], 'social_connect_twitter_last_update', true);
                                        if (!empty($last_update)) {
                                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update));
                                            
                                            // Adiciona o tempo desde a última atualização
                                            $update_time = strtotime($last_update);
                                            $time_diff = human_time_diff($update_time, current_time('timestamp'));
                                            echo '<br><small>' . sprintf(__('Há %s atrás', 'social-connect'), $time_diff) . '</small>';
                                            
                                            // Indicador visual se a atualização é recente ou antiga
                                            $hours_ago = (current_time('timestamp') - $update_time) / HOUR_IN_SECONDS;
                                            if ($hours_ago < 24) {
                                                echo '<span class="dashicons dashicons-yes-alt" style="color: green;" title="' . __('Dados atualizados recentemente', 'social-connect') . '"></span>';
                                            } else if ($hours_ago > 72) {
                                                echo '<span class="dashicons dashicons-warning" style="color: orange;" title="' . __('Dados podem estar desatualizados', 'social-connect') . '"></span>';
                                            }
                                        } else {
                                            _e('Nunca atualizado', 'social-connect');
                                        }
                                        ?>
                                    </td>
                                    <td class="column-actions">
                                        <div class="row-actions">
                                            <button 
                                                type="button" 
                                                class="button button-small update-user-twitter-data" 
                                                data-user-id="<?php echo esc_attr($user['wp_user_id']); ?>"
                                                data-username="<?php echo esc_attr($user['twitter_username']); ?>"
                                                data-nonce="<?php echo wp_create_nonce('update_twitter_user_data'); ?>"
                                            >
                                                <span class="dashicons dashicons-update" style="vertical-align: middle; font-size: 14px; width: 14px; height: 14px;"></span>
                                                <?php _e('Atualizar', 'social-connect'); ?>
                                            </button>
                                            
                                            <button 
                                                type="button" 
                                                class="button button-small disconnect-user" 
                                                data-user-id="<?php echo esc_attr($user['wp_user_id']); ?>"
                                                data-platform="twitter"
                                                data-username="<?php echo esc_attr($user['twitter_username']); ?>"
                                                data-nonce="<?php echo wp_create_nonce('social_connect_admin_disconnect_user'); ?>"
                                            >
                                                <?php _e('Desconectar', 'social-connect'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($connected_users['total_pages'] > 1): ?>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php printf(_n('%s item', '%s itens', $connected_users['total'], 'social-connect'), number_format_i18n($connected_users['total'])); ?>
                                </span>
                                <span class="pagination-links">
                                    <?php
                                    // Links de paginação
                                    $base_url = add_query_arg('tab', 'accounts', remove_query_arg('twitter_page', $_SERVER['REQUEST_URI']));
                                    
                                    // Primeira página
                                    if ($current_page > 1) {
                                        echo '<a class="first-page button" href="' . esc_url(add_query_arg('twitter_page', 1, $base_url)) . '"><span aria-hidden="true">&laquo;</span></a>';
                                    } else {
                                        echo '<span class="first-page button disabled"><span aria-hidden="true">&laquo;</span></span>';
                                    }
                                    
                                    // Página anterior
                                    if ($current_page > 1) {
                                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg('twitter_page', $current_page - 1, $base_url)) . '"><span aria-hidden="true">&lsaquo;</span></a>';
                                    } else {
                                        echo '<span class="prev-page button disabled"><span aria-hidden="true">&lsaquo;</span></span>';
                                    }
                                    
                                    // Contagem de páginas
                                    echo '<span class="paging-input">' . $current_page . ' de <span class="total-pages">' . $connected_users['total_pages'] . '</span></span>';
                                    
                                    // Próxima página
                                    if ($current_page < $connected_users['total_pages']) {
                                        echo '<a class="next-page button" href="' . esc_url(add_query_arg('twitter_page', $current_page + 1, $base_url)) . '"><span aria-hidden="true">&rsaquo;</span></a>';
                                    } else {
                                        echo '<span class="next-page button disabled"><span aria-hidden="true">&rsaquo;</span></span>';
                                    }
                                    
                                    // Última página
                                    if ($current_page < $connected_users['total_pages']) {
                                        echo '<a class="last-page button" href="' . esc_url(add_query_arg('twitter_page', $connected_users['total_pages'], $base_url)) . '"><span aria-hidden="true">&raquo;</span></a>';
                                    } else {
                                        echo '<span class="last-page button disabled"><span aria-hidden="true">&raquo;</span></span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Manipular clique no botão de desconexão
            $('.disconnect-user').on('click', function() {
                var button = $(this);
                var userId = button.data('user-id');
                var platform = button.data('platform');
                var username = button.data('username');
                var nonce = button.data('nonce');
                
                if (confirm('<?php _e('Tem certeza que deseja desconectar a conta do X de @', 'social-connect'); ?>' + username + '?')) {
                    button.prop('disabled', true).text('<?php _e('Desconectando...', 'social-connect'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'social_connect_admin_disconnect_user',
                            user_id: userId,
                            platform: platform,
                            nonce: nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert(response.data.message);
                                button.prop('disabled', false).text('<?php _e('Desconectar', 'social-connect'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Erro ao desconectar usuário. Tente novamente.', 'social-connect'); ?>');
                            button.prop('disabled', false).text('<?php _e('Desconectar', 'social-connect'); ?>');
                        }
                    });
                }
            });
            
            // Manipular clique no botão de atualização de dados do Twitter
            $('.update-user-twitter-data').on('click', function() {
                var button = $(this);
                var userId = button.data('user-id');
                var username = button.data('username');
                var nonce = button.data('nonce');
                
                // Salvar o texto original
                var originalText = button.html();
                
                // Atualizar texto do botão e desabilitar
                button.html('<span class="dashicons dashicons-update" style="vertical-align: middle; font-size: 14px; width: 14px; height: 14px; animation: rotation 2s infinite linear;"></span> <?php _e('Atualizando...', 'social-connect'); ?>');
                button.prop('disabled', true);
                
                // Adicionar CSS para a animação
                $('head').append('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'social_connect_update_user_twitter_data',
                        user_id: userId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Recarregar a página para mostrar os dados atualizados
                            location.reload();
                        } else {
                            alert(response.data.message);
                            button.html(originalText);
                            button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Erro ao atualizar dados do usuário. Tente novamente.', 'social-connect'); ?>');
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de recompensas do Twitter.
     */
    private function display_twitter_rewards_content() {
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Configure recompensas para usuários com base em seus status no Twitter.', 'social-connect'); ?></p>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <p><?php _e('Esta seção será implementada em breve.', 'social-connect'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Callback para seção Twitter.
     */
    public function twitter_section_callback() {
        echo '<p>' . __('Gerencie as integrações e funcionalidades relacionadas ao X (antigo Twitter).', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para seção da API do Twitter.
     */
    public function twitter_api_section_callback() {
        echo '<p>' . __('Configure as credenciais da API do X para permitir a autenticação dos usuários.', 'social-connect') . '</p>';
        echo '<p>' . __('Você precisa criar um aplicativo no <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank">Portal de Desenvolvedores do X</a>.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Client ID do Twitter.
     */
    public function twitter_client_id_callback() {
        $client_id = get_option('social_connect_twitter_client_id');
        echo '<input type="text" id="social_connect_twitter_client_id" name="social_connect_twitter_client_id" value="' . esc_attr($client_id) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe o Client ID da sua aplicação X.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Client Secret do Twitter.
     */
    public function twitter_client_secret_callback() {
        $client_secret = get_option('social_connect_twitter_client_secret');
        echo '<input type="password" id="social_connect_twitter_client_secret" name="social_connect_twitter_client_secret" value="' . esc_attr($client_secret) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe o Client Secret da sua aplicação X.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Redirect URI do Twitter.
     */
    public function twitter_redirect_uri_callback() {
        $redirect_uri = get_option('social_connect_twitter_redirect_uri', home_url('wc-auth/twitter'));
        echo '<input type="text" id="social_connect_twitter_redirect_uri" name="social_connect_twitter_redirect_uri" value="' . esc_attr($redirect_uri) . '" class="regular-text">';
        echo '<p class="description">' . __('URI para onde o X redirecionará o usuário após a autenticação.', 'social-connect') . '</p>';
        echo '<p class="description">' . __('Adicione esse URL à lista de Callback URLs na configuração do seu aplicativo X.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Username do Twitter.
     */
    public function twitter_username_callback() {
        $username = get_option('social_connect_twitter_username');
        echo '<input type="text" id="social_connect_twitter_username" name="social_connect_twitter_username" value="' . esc_attr($username) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe seu nome de usuário no X (sem o @).', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para seção Steam.
     */
    public function steam_section_callback() {
        echo '<p>' . __('Gerencie as integrações e funcionalidades relacionadas à Steam.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para seção da API da Steam.
     */
    public function steam_api_section_callback() {
        echo '<p>' . __('Configure as credenciais da API da Steam.', 'social-connect') . '</p>';
        echo '<p>' . __('Você precisa registrar uma chave API no <a href="https://steamcommunity.com/dev/apikey" target="_blank">Portal de Desenvolvedores da Steam</a>.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo API Key da Steam.
     */
    public function steam_api_key_callback() {
        $api_key = get_option('social_connect_steam_api_key');
        echo '<input type="text" id="social_connect_steam_api_key" name="social_connect_steam_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe a Steam Web API Key da sua aplicação.', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo Game ID da Steam.
     */
    public function steam_game_id_callback() {
        $game_id = get_option('social_connect_steam_game_id');
        echo '<input type="text" id="social_connect_steam_game_id" name="social_connect_steam_game_id" value="' . esc_attr($game_id) . '" class="regular-text">';
        echo '<p class="description">' . __('Informe o ID do jogo principal na Steam (opcional).', 'social-connect') . '</p>';
    }
    
    /**
     * Callback para campo de Trade URL da Steam.
     */
    public function steam_trade_url_field_callback() {
        $field = get_option('social_connect_steam_trade_url_field', 'steam_trade_url');
        echo '<input type="text" id="social_connect_steam_trade_url_field" name="social_connect_steam_trade_url_field" value="' . esc_attr($field) . '" class="regular-text">';
        echo '<p class="description">' . __('Nome do campo em que os usuários já cadastraram seus Trade URLs (por padrão, "steam_trade_url").', 'social-connect') . '</p>';
        echo '<p class="description">' . __('Este plugin usará este campo para obter o Trade URL dos usuários em vez de pedir que eles conectem novamente.', 'social-connect') . '</p>';
    }
    
    /**
     * Renderiza a página com abas da Steam.
     */
    public function display_steam_tabs_page() {
        // Determina qual aba está ativa
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        
        // URL base da página
        $page_url = admin_url('admin.php?page=social-connect-steam');
        ?>
        <div class="wrap">
            <h1><?php _e('Steam - Social Connect', 'social-connect'); ?></h1>
            
            <!-- Abas -->
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url($page_url . '&tab=settings'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Configurações', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=accounts'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'accounts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Contas Conectadas', 'social-connect'); ?>
                </a>
                <a href="<?php echo esc_url($page_url . '&tab=inventory'); ?>" 
                   class="nav-tab <?php echo $active_tab == 'inventory' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Inventário', 'social-connect'); ?>
                </a>
            </h2>
            
            <!-- Conteúdo das abas -->
            <?php
            if ($active_tab == 'settings') {
                $this->display_steam_settings_content();
            } elseif ($active_tab == 'accounts') {
                $this->display_steam_accounts_content();
            } elseif ($active_tab == 'inventory') {
                $this->display_steam_inventory_content();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de configurações da Steam.
     */
    private function display_steam_settings_content() {
        ?>
        <div class="tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('social_connect_steam');
                do_settings_sections('social_connect_steam');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de contas conectadas da Steam.
     */
    private function display_steam_accounts_content() {
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Aqui você pode ver todas as contas de usuários que conectaram suas contas Steam via Trade URL.', 'social-connect'); ?></p>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 15px;">
                <p><?php _e('Esta seção será implementada em breve.', 'social-connect'); ?></p>
                <p><?php _e('Aqui você poderá visualizar todos os usuários que conectaram suas contas Steam, bem como seus níveis e estatísticas.', 'social-connect'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba de inventário da Steam.
     */
    private function display_steam_inventory_content() {
        ?>
        <div class="tab-content">
            <div class="notice notice-info">
                <p><?php _e('Visualize informações sobre inventários de usuários na Steam.', 'social-connect'); ?></p>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 15px;">
                <p><?php _e('Esta seção será implementada em breve.', 'social-connect'); ?></p>
                <p><?php _e('Aqui você poderá ver itens de inventário dos usuários que permitiram acesso através de suas Trade URLs.', 'social-connect'); ?></p>
            </div>
        </div>
        <?php
    }
}