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
            wp_enqueue_style(
                'social-connect',
                SOCIAL_CONNECT_PLUGIN_URL . 'assets/css/social-connect.css',
                array(),
                SOCIAL_CONNECT_VERSION,
                'all'
            );
            
            // Damos uma prioridade maior ao estilo para garantir que ele sobrescreva o tema
            wp_style_add_data('social-connect', 'rtl', 'replace');
            
            // Importante: Carregar CSS inline para correções críticas de layout
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
        
        ?>
        <div class="social-connect-connections">
            <h2><?php _e('Suas Conexões com Redes Sociais', 'social-connect'); ?></h2>
            
            <div class="social-connect-account twitch">
                <div class="social-connect-header">
                    <div class="social-connect-icon">
                        <img src="<?php echo SOCIAL_CONNECT_PLUGIN_URL . 'assets/images/twitch-icon.svg'; ?>" alt="Twitch">
                    </div>
                    
                    <div class="social-connect-title">
                        <h3><?php _e('Twitch', 'social-connect'); ?></h3>
                        <p><?php _e('Conecte sua conta para acessar benefícios exclusivos', 'social-connect'); ?></p>
                    </div>
                </div>
                
                <div class="social-connect-content">
                    <div class="social-connect-status">
                        <?php if ($twitch_connected) : ?>
                            <div class="social-connect-connected">
                                <?php _e('Conectado como', 'social-connect'); ?> <strong><?php echo esc_html($twitch_username); ?></strong>
                            </div>
                            
                            <?php
                            // Verificar se o usuário segue e é assinante de um canal específico
                            // Obtém o ID do canal configurado nas opções
                            $broadcaster_id = get_option('social_connect_twitch_broadcaster_id', '');
                            
                            if (!empty($broadcaster_id)) {
                                $twitch = new Social_Connect_Twitch();
                                
                                // Verificar se segue
                                $follows = $twitch->check_if_user_follows_channel($broadcaster_id);
                                if (!is_wp_error($follows)) {
                                    echo '<div class="social-connect-follow-status">';
                                    if ($follows) {
                                        echo '<span class="follows-yes">' . __('Você segue este canal', 'social-connect') . '</span>';
                                    } else {
                                        echo '<span class="follows-no">' . __('Você não segue este canal', 'social-connect') . '</span>';
                                    }
                                    echo '</div>';
                                }
                                
                                // Verificar se é assinante e obter informação do tier
                                $subscription = $twitch->check_if_user_subscribed_to_channel($broadcaster_id);
                                if (!is_wp_error($subscription)) {
                                    echo '<div class="social-connect-sub-status">';
                                    if ($subscription['is_subscribed']) {
                                        echo '<span class="sub-yes">' . 
                                            sprintf(__('Você é assinante deste canal (%s)', 'social-connect'), 
                                                '<strong>' . esc_html($subscription['tier_name']) . '</strong>') . 
                                            '</span>';
                                    } else {
                                        echo '<span class="sub-no">' . __('Você não é assinante deste canal', 'social-connect') . '</span>';
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>
                        <?php else : ?>
                            <div class="social-connect-not-connected">
                                <?php _e('Não conectado', 'social-connect'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-connect-benefits">
                        <h4><?php _e('Benefícios de conectar sua conta Twitch:', 'social-connect'); ?></h4>
                        <ul>
                            <li><?php _e('Compartilhe suas transmissões automaticamente', 'social-connect'); ?></li>
                            <li><?php _e('Receba notificações especiais', 'social-connect'); ?></li>
                            <li><?php _e('Acesse promoções exclusivas para streamers', 'social-connect'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="social-connect-actions">
                        <?php if ($twitch_connected) : ?>
                            <a href="#" class="social-connect-disconnect-button" 
                              data-nonce="<?php echo wp_create_nonce('social_connect_twitch_disconnect'); ?>"
                              data-action="social_connect_twitch_disconnect">
                                <?php _e('Desconectar', 'social-connect'); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($twitch_connect_url); ?>" class="social-connect-button twitch-connect-button">
                                <?php _e('Conectar com Twitch', 'social-connect'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Seção para conexão com o X (Twitter) -->
            <div class="social-connect-account twitter">
                <div class="social-connect-header">
                    <div class="social-connect-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M13.34 10.47l5.11-6h-4.06l-3.09 3.62-2.62-3.62H3.72l4.42 6.07-5.35 6.26h4.06l3.33-3.88 2.84 3.88h4.95l-4.63-6.33zm-1.88 2.2l-.39-.53-3.12-4.22h2.02l2.51 3.41.39.53 3.27 4.42h-2.02l-2.66-3.61z" fill="#1DA1F2"/>
                        </svg>
                    </div>
                    
                    <div class="social-connect-title">
                        <h3><?php _e('X (Twitter)', 'social-connect'); ?></h3>
                        <p><?php _e('Conecte sua conta para compartilhar atualizações', 'social-connect'); ?></p>
                    </div>
                </div>
                
                <div class="social-connect-content">
                    <div class="social-connect-status">
                        <?php if ($twitter_connected) : ?>
                            <div class="social-connect-connected">
                                <?php _e('Conectado como', 'social-connect'); ?> <strong>@<?php echo esc_html($twitter_username); ?></strong>
                            </div>
                            
                            <?php
                            // Verificar se o usuário segue uma conta específica
                            $twitter_username = get_option('social_connect_twitter_username', '');
                            
                            if (!empty($twitter_username)) {
                                $twitter = new Social_Connect_Twitter();
                                
                                // Verificar se segue o perfil configurado
                                $follows = $twitter->check_if_user_follows_target($twitter_username);
                                if (!is_wp_error($follows)) {
                                    echo '<div class="social-connect-follow-status">';
                                    if ($follows) {
                                        echo '<span class="follows-yes">' . __('Você segue @' . esc_html($twitter_username), 'social-connect') . '</span>';
                                    } else {
                                        echo '<span class="follows-no">' . __('Você não segue @' . esc_html($twitter_username), 'social-connect') . '</span>';
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>
                        <?php else : ?>
                            <div class="social-connect-not-connected">
                                <?php _e('Não conectado', 'social-connect'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-connect-benefits">
                        <h4><?php _e('Benefícios de conectar sua conta X:', 'social-connect'); ?></h4>
                        <ul>
                            <li><?php _e('Compartilhe suas atualizações automaticamente', 'social-connect'); ?></li>
                            <li><?php _e('Receba notificações exclusivas', 'social-connect'); ?></li>
                            <li><?php _e('Acesse conteúdo exclusivo para seguidores', 'social-connect'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="social-connect-actions">
                        <?php if ($twitter_connected) : ?>
                            <a href="#" class="social-connect-disconnect-button" 
                              data-nonce="<?php echo wp_create_nonce('social_connect_twitter_disconnect'); ?>"
                              data-action="social_connect_twitter_disconnect">
                                <?php _e('Desconectar', 'social-connect'); ?>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($twitter_connect_url); ?>" class="social-connect-button twitter-connect-button">
                                <?php _e('Conectar com X', 'social-connect'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.social-connect-disconnect-button').on('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('<?php _e('Tem certeza que deseja desconectar sua conta?', 'social-connect'); ?>')) {
                        var button = $(this);
                        
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
                                }
                            }
                        });
                    }
                });
            });
            </script>
        </div>
        <?php
    }
}