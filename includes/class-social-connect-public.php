<?php

/**
 * Funcionalidades públicas do plugin.
 */
class Social_Connect_Public {

    /**
     * Inicializa a classe.
     */
    public function __construct() {
        // Constructor
    }
    
    /**
     * Registra os estilos CSS do plugin para o frontend.
     */
    public function enqueue_styles() {
        // Apenas carrega o CSS nas páginas que usam o plugin
        if (is_account_page()) {
            // Carrega Dashicons primeiro para garantir que estejam disponíveis
            wp_enqueue_style('dashicons');
            
            // Força recarregamento do CSS com um timestamp para evitar cache durante desenvolvimento
            $force_refresh = '.' . time();
            
            wp_enqueue_style(
                'social-connect',
                SOCIAL_CONNECT_PLUGIN_URL . 'assets/css/social-connect.css',
                array('dashicons'),
                SOCIAL_CONNECT_VERSION . $force_refresh,
                'all'
            );
            
            // Damos uma prioridade maior ao estilo para garantir que ele sobrescreva o tema
            wp_style_add_data('social-connect', 'rtl', 'replace');
            
            // Estilos críticos - Estes estilos serão injetados diretamente no head com alta prioridade
            $critical_css = '
            .woocommerce-message, .woocommerce-info, .woocommerce-error { 
                position: relative !important; 
                padding: 1em 3em 1em 3.5em !important; 
            }
            .woocommerce-message a.button, .woocommerce-info a.button {
                margin-right: 1em !important;
            }
            .woocommerce-message strong {
                margin-left: 0.25em !important;
            }
            
            /* Correção global para os dashicons */
            .social-connect-dashboard .dashicons, 
            .social-connect-dashboard .dashicons-before:before,
            .social-connect-dashboard i.dashicons {
                width: 20px !important;
                height: 20px !important;
                font-size: 20px !important;
                line-height: 1 !important;
                vertical-align: middle !important;
                display: inline-block !important;
                font-family: dashicons !important;
            }
            
            /* Correção específica para os ícones nos badges de relacionamento */
            .social-connect-dashboard .relationship-badge .dashicons,
            .social-connect-dashboard .relationship-badge i.dashicons {
                width: 16px !important;
                height: 16px !important;
                font-size: 16px !important;
                margin-right: 6px !important;
            }
            
            /* Correção para os ícones na lista de benefícios */
            .social-connect-dashboard .benefit-list li .dashicons,
            .social-connect-dashboard .benefit-list li i.dashicons {
                margin-right: 8px !important;
                color: #2ecc71 !important;
                width: 16px !important;
                height: 16px !important;
                font-size: 16px !important;
            }
            
            /* Estilização dos botões de ação */
            .social-connect-dashboard .social-card-actions {
                padding: 20px 20px 24px !important;
                display: flex !important;
                justify-content: center !important;
            }
            
            .social-connect-dashboard .connect-button, 
            .social-connect-dashboard .disconnect-button {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                padding: 12px 24px !important;
                border-radius: 10px !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                width: 100% !important;
                position: relative !important;
                overflow: hidden !important;
                height: auto !important;
                line-height: 1.5 !important;
                text-align: center !important;
            }
            
            .social-connect-dashboard .connect-button {
                background-color: #444 !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
            }
            
            .social-connect-dashboard .connect-button:before {
                content: "" !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 1px !important;
                background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.4), rgba(255,255,255,0)) !important;
            }
            
            .social-connect-dashboard .connect-button:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
            }
            
            .social-connect-dashboard .disconnect-button {
                background-color: rgba(231, 76, 60, 0.1) !important;
                color: #e74c3c !important;
                border: 1px solid rgba(231, 76, 60, 0.3) !important;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
                margin-bottom: 4px !important;
            }
            
            .social-connect-dashboard .disconnect-button:before {
                content: "" !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 1px !important;
                background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.3), rgba(255,255,255,0)) !important;
            }
            
            .social-connect-dashboard .disconnect-button:hover {
                background-color: rgba(231, 76, 60, 0.15) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2) !important;
                border-color: rgba(231, 76, 60, 0.5) !important;
            }
            
            .social-connect-dashboard .disconnect-button .dashicons,
            .social-connect-dashboard .disconnect-button i.dashicons {
                margin-right: 8px !important;
                transition: transform 0.2s ease !important;
            }
            
            .social-connect-dashboard .disconnect-button:hover .dashicons,
            .social-connect-dashboard .disconnect-button:hover i.dashicons {
                transform: rotate(90deg) !important;
            }
            ';
            wp_add_inline_style('social-connect', $critical_css);
        }
    }

    /**
     * Adiciona o endpoint "Conexões" no menu My Account.
     */
    public function add_connections_endpoint($items) {
        // Insere o item de menu "Conexões" antes de "Sair"
        $logout_position = array_search('customer-logout', array_keys($items));
        
        if ($logout_position !== false) {
            $items_beginning = array_slice($items, 0, $logout_position);
            $items_end = array_slice($items, $logout_position);
            
            $items = $items_beginning + array('connections' => __('Conexões', 'social-connect')) + $items_end;
        } else {
            $items['connections'] = __('Conexões', 'social-connect');
        }
        
        return $items;
    }

    /**
     * Adiciona o endpoint de rewrite para o endpoint "Conexões".
     */
    public function add_connections_rewrite_endpoint() {
        add_rewrite_endpoint('connections', EP_ROOT | EP_PAGES);
        
        // Verifica se é necessário dar flush nas regras de rewrite
        if (get_option('social_connect_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            update_option('social_connect_flush_rewrite_rules', false);
        }
    }

    /**
     * Exibe o conteúdo da página "Conexões".
     */
    public function connections_content() {
        $user_id = get_current_user_id();
        $twitch_connected = get_user_meta($user_id, 'social_connect_twitch_connected', true);
        $twitch_username = get_user_meta($user_id, 'social_connect_twitch_username', true);
        $twitter_connected = get_user_meta($user_id, 'social_connect_twitter_connected', true);
        $twitter_username = get_user_meta($user_id, 'social_connect_twitter_username', true);
        
        // URL para conectar com a Twitch
        $twitch = new Social_Connect_Twitch();
        $twitch_connect_url = $twitch->get_authorization_url();
        
        // URL para conectar com o X (Twitter)
        $twitter = new Social_Connect_Twitter();
        $twitter_connect_url = $twitter->get_authorization_url();
        
        // Verificar dados adicionais da Twitch
        $twitch_profile_image = get_user_meta($user_id, 'social_connect_twitch_profile_image', true);
        $twitch_display_name = get_user_meta($user_id, 'social_connect_twitch_display_name', true);
        $twitch_followers = 0; // Placeholder para futura implementação
        $twitch_following_count = get_user_meta($user_id, 'social_connect_twitch_following_count', true);
        
        // Verificar dados adicionais do Twitter
        $twitter_profile_image = get_user_meta($user_id, 'social_connect_twitter_profile_image', true);
        $twitter_followers_count = get_user_meta($user_id, 'social_connect_twitter_followers_count', true);
        $twitter_following_count = get_user_meta($user_id, 'social_connect_twitter_following_count', true);
        $twitter_tweets_count = get_user_meta($user_id, 'social_connect_twitter_tweet_count', true);
        
        // Verificar status de seguidor para o canal Twitch configurado
        $twitch_follows_channel = false;
        $twitch_is_subscribed = false;
        $twitch_tier = '';
        $broadcaster_id = get_option('social_connect_twitch_broadcaster_id', '');
        
        if ($twitch_connected && !empty($broadcaster_id)) {
            $follows = $twitch->check_if_user_follows_channel($broadcaster_id);
            if (!is_wp_error($follows)) {
                $twitch_follows_channel = $follows;
            }
            
            $subscription = $twitch->check_if_user_subscribed_to_channel($broadcaster_id);
            if (!is_wp_error($subscription) && isset($subscription['is_subscribed'])) {
                $twitch_is_subscribed = $subscription['is_subscribed'];
                if ($twitch_is_subscribed && isset($subscription['tier_name'])) {
                    $twitch_tier = $subscription['tier_name'];
                }
            }
        }
        
        // Verificar se o usuário segue a conta do Twitter configurada
        $twitter_follows_account = false;
        $target_twitter_username = get_option('social_connect_twitter_username', '');
        
        if ($twitter_connected && !empty($target_twitter_username)) {
            $follows = $twitter->check_if_user_follows_target($target_twitter_username);
            if (!is_wp_error($follows)) {
                $twitter_follows_account = $follows;
            }
        }
        
        ?>
        <div class="social-connect-dashboard">
            <div class="social-connect-header">
                <h1><?php _e('Suas Conexões Sociais', 'social-connect'); ?></h1>
                <p class="social-connect-subtitle"><?php _e('Conecte suas contas de redes sociais para uma experiência mais integrada', 'social-connect'); ?></p>
            </div>
            
            <div class="social-connect-grid">
                <!-- Twitch Card -->
                <div class="social-card twitch-card <?php echo $twitch_connected ? 'connected' : 'not-connected'; ?>">
                    <div class="social-card-header">
                        <div class="social-card-platform">
                            <img src="<?php echo SOCIAL_CONNECT_PLUGIN_URL . 'assets/images/twitch-icon.svg'; ?>" alt="Twitch" class="platform-icon">
                            <h3><?php _e('Twitch', 'social-connect'); ?></h3>
                        </div>
                        <div class="connection-badge <?php echo $twitch_connected ? 'connected' : 'not-connected'; ?>">
                            <?php echo $twitch_connected ? __('Conectado', 'social-connect') : __('Não conectado', 'social-connect'); ?>
                        </div>
                    </div>
                    
                    <div class="social-card-content">
                        <?php if ($twitch_connected): ?>
                            <div class="profile-info">
                                <?php if (!empty($twitch_profile_image)): ?>
                                    <img src="<?php echo esc_url($twitch_profile_image); ?>" alt="<?php echo esc_attr($twitch_display_name ?: $twitch_username); ?>" class="profile-avatar">
                                <?php else: ?>
                                    <div class="profile-avatar-placeholder"></div>
                                <?php endif; ?>
                                
                                <div class="profile-details">
                                    <h4 class="profile-name"><?php echo esc_html($twitch_display_name ?: $twitch_username); ?></h4>
                                    <div class="profile-username">@<?php echo esc_html($twitch_username); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($twitch_follows_channel || $twitch_is_subscribed): ?>
                            <div class="relationship-status">
                                <?php if ($twitch_follows_channel): ?>
                                    <div class="relationship-badge following">
                                        <i class="dashicons dashicons-yes"></i>
                                        <?php _e('Seguindo nosso canal', 'social-connect'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($twitch_is_subscribed): ?>
                                    <div class="relationship-badge subscribed">
                                        <i class="dashicons dashicons-star-filled"></i>
                                        <?php echo sprintf(__('Assinante %s', 'social-connect'), $twitch_tier); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="connection-prompt">
                                <div class="prompt-icon">
                                    <i class="dashicons dashicons-twitch"></i>
                                </div>
                                <h4><?php _e('Conecte seu perfil da Twitch', 'social-connect'); ?></h4>
                                <p><?php _e('Conectando sua conta Twitch, você pode receber benefícios exclusivos como assinante e seguidor.', 'social-connect'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-card-benefits">
                        <ul class="benefit-list">
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Acesso a conteúdo exclusivo', 'social-connect'); ?></li>
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Recompensas por assinatura', 'social-connect'); ?></li>
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Notificações personalizadas', 'social-connect'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="social-card-actions">
                        <?php if ($twitch_connected): ?>
                            <a href="#" class="disconnect-button twitch-disconnect" 
                               data-nonce="<?php echo wp_create_nonce('social_connect_twitch_disconnect'); ?>"
                               data-action="social_connect_twitch_disconnect">
                                <i class="dashicons dashicons-no-alt"></i>
                                <?php _e('Desconectar', 'social-connect'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($twitch_connect_url); ?>" class="connect-button twitch-connect">
                                <span class="connect-icon"></span>
                                <?php _e('Conectar com Twitch', 'social-connect'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Twitter Card -->
                <div class="social-card twitter-card <?php echo $twitter_connected ? 'connected' : 'not-connected'; ?>">
                    <div class="social-card-header">
                        <div class="social-card-platform">
                            <div class="twitter-icon">
                                <svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.34 10.47l5.11-6h-4.06l-3.09 3.62-2.62-3.62H3.72l4.42 6.07-5.35 6.26h4.06l3.33-3.88 2.84 3.88h4.95l-4.63-6.33zm-1.88 2.2l-.39-.53-3.12-4.22h2.02l2.51 3.41.39.53 3.27 4.42h-2.02l-2.66-3.61z" fill="#fff"/>
                                </svg>
                            </div>
                            <h3><?php _e('X (Twitter)', 'social-connect'); ?></h3>
                        </div>
                        <div class="connection-badge <?php echo $twitter_connected ? 'connected' : 'not-connected'; ?>">
                            <?php echo $twitter_connected ? __('Conectado', 'social-connect') : __('Não conectado', 'social-connect'); ?>
                        </div>
                    </div>
                    
                    <div class="social-card-content">
                        <?php if ($twitter_connected): ?>
                            <div class="profile-info">
                                <?php if (!empty($twitter_profile_image)): ?>
                                    <img src="<?php echo esc_url($twitter_profile_image); ?>" alt="@<?php echo esc_attr($twitter_username); ?>" class="profile-avatar">
                                <?php else: ?>
                                    <div class="profile-avatar-placeholder"></div>
                                <?php endif; ?>
                                
                                <div class="profile-details">
                                    <h4 class="profile-name"><?php echo esc_html($twitter_username); ?></h4>
                                    <div class="profile-username">@<?php echo esc_html($twitter_username); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($twitter_follows_account && !empty($target_twitter_username)): ?>
                            <div class="relationship-status">
                                <div class="relationship-badge following">
                                    <i class="dashicons dashicons-yes"></i>
                                    <?php echo sprintf(__('Seguindo @%s', 'social-connect'), $target_twitter_username); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="connection-prompt">
                                <div class="prompt-icon">
                                    <i class="dashicons dashicons-twitter"></i>
                                </div>
                                <h4><?php _e('Conecte seu perfil do X (Twitter)', 'social-connect'); ?></h4>
                                <p><?php _e('Conectando sua conta X, você pode compartilhar atualizações e receber conteúdo exclusivo.', 'social-connect'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-card-benefits">
                        <ul class="benefit-list">
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Compartilhamento automático', 'social-connect'); ?></li>
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Notificações personalizadas', 'social-connect'); ?></li>
                            <li><i class="dashicons dashicons-yes-alt"></i> <?php _e('Conteúdo exclusivo para seguidores', 'social-connect'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="social-card-actions">
                        <?php if ($twitter_connected): ?>
                            <a href="#" class="disconnect-button twitter-disconnect" 
                               data-nonce="<?php echo wp_create_nonce('social_connect_twitter_disconnect'); ?>"
                               data-action="social_connect_twitter_disconnect">
                                <i class="dashicons dashicons-no-alt"></i>
                                <?php _e('Desconectar', 'social-connect'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($twitter_connect_url); ?>" class="connect-button twitter-connect">
                                <span class="connect-icon"></span>
                                <?php _e('Conectar com X', 'social-connect'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.disconnect-button').on('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('<?php _e('Tem certeza que deseja desconectar sua conta?', 'social-connect'); ?>')) {
                        var button = $(this);
                        var originalText = button.html();
                        
                        button.html('<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> <?php _e('Desconectando...', 'social-connect'); ?>');
                        button.prop('disabled', true);
                        
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: button.data('action'),
                                nonce: button.data('nonce')
                            },
                            success: function(response) {
                                if (response.success) {
                                    window.location.reload();
                                } else {
                                    alert(response.data.message);
                                    button.html(originalText);
                                    button.prop('disabled', false);
                                }
                            },
                            error: function() {
                                alert('<?php _e('Ocorreu um erro. Tente novamente.', 'social-connect'); ?>');
                                button.html(originalText);
                                button.prop('disabled', false);
                            }
                        });
                    }
                });
                
                // Adicionar CSS para a animação de rotação
                $('head').append('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>');
            });
            </script>
        </div>
        <?php
    }
}