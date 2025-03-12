# Social Connect - WordPress Plugin

Plugin para integração de contas sociais (Twitter/X e Twitch) com WordPress/WooCommerce.

Desenvolvido por [Lucas Batalgia](https://github.com/lucasbataglia).

## Funcionalidades

### Integração Twitter/X
- Autenticação OAuth com Twitter
- Conexão de contas de usuários do WordPress com Twitter
- Exibição de métricas: contagem de tweets, seguidores e seguindo
- Atualização manual e automática (CRON) de métricas
- Interface de administração para gerenciar conexões

### Integração Twitch
- Autenticação OAuth com Twitch
- Conexão de contas de usuários do WordPress com Twitch
- Verificação de seguidores de canal específico
- Verificação de assinantes e tiers de assinatura
- Visualização de canais seguidos pelo usuário
- Sistema de recompensas para assinantes
- Atualização manual e automática (CRON) de métricas

## Integração com WooCommerce

O plugin adiciona uma nova seção "Conexões" na conta do cliente no WooCommerce, permitindo que os usuários conectem suas contas sociais.

## Implementações Recentes

### Atualização Automática de Dados

#### Twitter
- Adicionado CRON job para atualização diária dos dados do Twitter
- Implementada interface para atualização manual de todos os usuários
- Adicionado botão individual para atualização de usuário específico
- Mostrar data da última atualização na tabela de administração

#### Twitch
- Adicionado CRON job para atualização diária dos dados da Twitch
- Implementada interface para atualização manual de todos os usuários
- Adicionado botão individual para atualização de usuário específico
- Mostrar data da última atualização na tabela de administração
- Contagem de canais seguidos para cada usuário

### Visualização de Canais Seguidos

- Implementação de modal para visualizar todos os canais que um usuário segue
- Interface em grid com foto de perfil, nome e link para o canal
- Paginação para carregar mais canais quando necessário
- Implementado endpoint AJAX para buscar os dados dos canais

## Estrutura Técnica

### Arquivos Principais

- `social-connect.php` - Arquivo principal do plugin
- `includes/class-social-connect.php` - Classe principal
- `includes/class-social-connect-admin.php` - Interface de administração
- `includes/class-social-connect-twitter.php` - Integração com Twitter
- `includes/class-social-connect-twitch.php` - Integração com Twitch
- `includes/class-social-connect-loader.php` - Gerenciamento de hooks
- `includes/class-social-connect-public.php` - Interface pública
- `assets/css/social-connect.css` - Estilos front-end
- `assets/css/admin.css` - Estilos administrativos

### Endpoints AJAX

#### Twitter
- `social_connect_twitter_disconnect` - Desconectar conta do Twitter
- `social_connect_update_user_twitter_data` - Atualizar dados de um usuário específico

#### Twitch
- `social_connect_twitch_disconnect` - Desconectar conta da Twitch
- `social_connect_update_user_twitch_data` - Atualizar dados de um usuário específico
- `social_connect_get_followed_channels` - Obter canais seguidos por um usuário

#### Geral
- `social_connect_admin_disconnect_user` - Desconectar usuário pelo admin

### Hooks CRON

- `social_connect_twitter_refresh_data` - Atualização diária de dados do Twitter
- `social_connect_twitch_refresh_data` - Atualização diária de dados da Twitch
- `social_connect_process_rewards` - Processa recompensas para assinantes da Twitch

## Metadados de Usuário

### Twitter
- `social_connect_twitter_access_token` - Token de acesso
- `social_connect_twitter_refresh_token` - Token de atualização
- `social_connect_twitter_expires` - Data de expiração do token
- `social_connect_twitter_user_id` - ID do usuário no Twitter
- `social_connect_twitter_username` - Nome de usuário no Twitter
- `social_connect_twitter_display_name` - Nome de exibição no Twitter
- `social_connect_twitter_profile_image` - URL da imagem de perfil
- `social_connect_twitter_connected` - Status da conexão
- `social_connect_twitter_connected_date` - Data da conexão
- `social_connect_twitter_tweets_count` - Número total de tweets
- `social_connect_twitter_following_count` - Número de contas seguidas
- `social_connect_twitter_followers_count` - Número de seguidores
- `social_connect_twitter_last_update` - Data da última atualização de métricas

### Twitch
- `social_connect_twitch_access_token` - Token de acesso
- `social_connect_twitch_refresh_token` - Token de atualização
- `social_connect_twitch_expires` - Data de expiração do token
- `social_connect_twitch_user_id` - ID do usuário na Twitch
- `social_connect_twitch_username` - Nome de usuário na Twitch
- `social_connect_twitch_display_name` - Nome de exibição na Twitch
- `social_connect_twitch_email` - Email associado à conta Twitch
- `social_connect_twitch_profile_image` - URL da imagem de perfil
- `social_connect_twitch_connected` - Status da conexão
- `social_connect_twitch_connected_date` - Data da conexão
- `social_connect_twitch_account_created_at` - Data de criação da conta Twitch
- `social_connect_twitch_following_count` - Número de canais seguidos
- `social_connect_twitch_subscription_tier` - Tier de assinatura (1000, 2000, 3000)
- `social_connect_twitch_subscription_tier_name` - Nome do tier de assinatura
- `social_connect_twitch_last_update` - Data da última atualização de métricas

## Configurações

O plugin adiciona várias opções ao WordPress:

### Twitter
- `social_connect_twitter_client_id` - Client ID da API do Twitter
- `social_connect_twitter_client_secret` - Client Secret da API do Twitter
- `social_connect_twitter_redirect_uri` - URI de redirecionamento para OAuth

### Twitch
- `social_connect_twitch_client_id` - Client ID da API da Twitch
- `social_connect_twitch_client_secret` - Client Secret da API da Twitch
- `social_connect_twitch_redirect_uri` - URI de redirecionamento para OAuth
- `social_connect_twitch_broadcaster_id` - ID do canal principal para verificar seguidores/assinantes
- `social_connect_twitch_enable_rewards` - Habilitar sistema de recompensas
- `social_connect_twitch_reward_tier1` - Valor da recompensa para Tier 1
- `social_connect_twitch_reward_tier2` - Valor da recompensa para Tier 2
- `social_connect_twitch_reward_tier3` - Valor da recompensa para Tier 3
- `social_connect_twitch_reward_frequency` - Frequência das recompensas (diário, semanal, mensal)

## Dependências

- WordPress 5.0+
- WooCommerce 4.0+
- (Opcional) WooWallet para funcionalidade de recompensas

## Escopos da API

### Twitter
- `tweet.read` - Ler tweets e métricas
- `users.read` - Ler informações de usuário

### Twitch
- `user:read:email` - Ler email do usuário
- `user:read:follows` - Ler canais seguidos
- `user:read:subscriptions` - Verificar assinaturas do usuário
- `channel:read:subscriptions` - Verificar assinantes do canal

## Troubleshooting

### Problemas Comuns

#### Erro de Tokens Expirados
O plugin tentará automaticamente renovar tokens expirados usando o refresh token. Se isso falhar, o usuário precisará reconectar sua conta.

#### Falha na API
Erros da API são registrados quando WP_DEBUG está ativado. Verifique o arquivo debug.log para mais informações.

## Melhorias Futuras

- [ ] Estatísticas históricas de métricas
- [ ] Exportação de dados para CSV/Excel
- [ ] Suporte a mais plataformas sociais (Instagram, Facebook, etc.)
- [ ] Visualização de gráficos de crescimento
- [ ] Integração com sistemas de pontos/recompensas adicionais

## Changelog

### 1.0.0
- Implementação inicial
- Suporte a Twitter e Twitch
- Interface administrativa básica

### 1.1.0 
- Adicionadas métricas do Twitter (tweets, seguidores, seguindo)
- Atualização automática via CRON

### 1.2.0
- Adicionada contagem de canais seguidos no Twitch
- Implementado sistema de recompensas para assinantes

### 1.3.0
- Adicionada atualização manual individual para Twitter e Twitch
- Adicionada visualização de canais seguidos no Twitch
- Melhorias visuais e de usabilidade