# Guia para Desenvolvedores - Social Connect

Este documento contém informações técnicas para desenvolvedores que trabalharão com o plugin Social Connect.

## Estrutura do Plugin

O plugin segue uma estrutura modular organizada por classes:

```
social-connect/
├── assets/
│   ├── css/
│   │   ├── admin.css       # Estilos para área administrativa
│   │   └── social-connect.css  # Estilos para frontend
├── includes/
│   ├── class-social-connect.php            # Classe principal
│   ├── class-social-connect-admin.php      # Interface administrativa
│   ├── class-social-connect-loader.php     # Gerenciador de hooks
│   ├── class-social-connect-public.php     # Interface pública
│   ├── class-social-connect-twitter.php    # Integração Twitter
│   └── class-social-connect-twitch.php     # Integração Twitch
├── CHANGELOG.md            # Histórico de alterações
├── DEVELOPER.md            # Este documento
├── README.md               # Documentação geral
└── social-connect.php      # Arquivo principal do plugin
```

## Arquitetura

O plugin usa o padrão de design "Factory" com uma classe de carregador que gerencia todos os hooks do WordPress. Cada componente principal (Admin, Public, Twitter, Twitch) é implementado como uma classe separada para facilitar a manutenção.

### Classe Principal (Social_Connect)

A classe principal inicializa o plugin e carrega as dependências necessárias. Ela funciona como o ponto central de coordenação entre os componentes.

### Sistema de Hooks (Social_Connect_Loader)

Em vez de registrar hooks diretamente com `add_action()` e `add_filter()`, o plugin usa uma classe de carregador. Isso permite melhor organização e potencial para desativar funcionalidades específicas.

### Fluxo de Autenticação OAuth

O fluxo de autenticação segue este padrão:
1. Usuário clica no botão de conexão
2. Redirecionamento para a página de autorização da plataforma
3. Após autorização, redirecionamento para o URL de callback
4. Troca do código de autorização por tokens de acesso e atualização
5. Armazenamento dos tokens nas metadados do usuário
6. Obtenção e armazenamento de informações básicas do usuário

## Endpoints AJAX

### Social Connect Admin

```php
// Desconectar usuário
wp_ajax_social_connect_admin_disconnect_user

// Atualizar dados do Twitter
wp_ajax_social_connect_update_user_twitter_data

// Atualizar dados da Twitch
wp_ajax_social_connect_update_user_twitch_data

// Obter canais seguidos
wp_ajax_social_connect_get_followed_channels
```

### Twitter

```php
// Desconectar conta
wp_ajax_social_connect_twitter_disconnect
```

### Twitch

```php
// Desconectar conta
wp_ajax_social_connect_twitch_disconnect
```

## Metadados de Usuário

Os metadados são usados extensivamente para armazenar informações de conexão e métricas. A convenção de nomenclatura segue o padrão `social_connect_{plataforma}_{tipo_de_dado}`.

## Tarefas CRON

O plugin registra tarefas CRON para atualização automática de dados:

```php
// Twitter: Atualização diária
social_connect_twitter_refresh_data

// Twitch: Atualização diária
social_connect_twitch_refresh_data

// Twitch: Processamento de recompensas
social_connect_process_rewards
```

## APIs Externas

### Twitter API v2

O plugin usa a API v2 do Twitter, que requer autenticação OAuth 2.0. A documentação oficial está disponível em: https://developer.twitter.com/en/docs/twitter-api

### Twitch API

O plugin utiliza a API Helix da Twitch, que requer autenticação OAuth 2.0. A documentação oficial está disponível em: https://dev.twitch.tv/docs/api/

## Adicionando Nova Plataforma

Para adicionar suporte a uma nova plataforma social:

1. Crie uma nova classe `class-social-connect-{plataforma}.php`
2. Implemente os métodos necessários para OAuth e obtenção de dados
3. Adicione opções de configuração em `class-social-connect-admin.php`
4. Inclua a interface do usuário no método `connections_content()` em `class-social-connect-public.php`
5. Registre os hooks necessários em `class-social-connect.php`

## Trabalhando com Dados da Twitch

### Canais Seguidos

A API de canais seguidos retorna os seguintes dados:

```json
{
  "data": [
    {
      "broadcaster_id": "149747285",
      "broadcaster_login": "twitchdev",
      "broadcaster_name": "TwitchDev",
      "followed_at": "2021-04-16T19:37:31Z"
    }
  ],
  "pagination": {
    "cursor": "eyJiIjpudWxsLCJhIjp7Ik9mZnNldCI6MX19"
  },
  "total": 12
}
```

Para navegar por todos os canais seguidos, use o cursor na paginação.

### Assinaturas

A API de assinaturas fornece informações sobre o tier e outros dados relacionados à assinatura.

## Interface JavaScript

O plugin usa jQuery para manipulações DOM e solicitações AJAX. O código principal para o modal de canais seguidos se encontra no método `display_twitch_accounts_content()`.

## Dicas para Desenvolvimento

1. **Depuração**: Habilite `WP_DEBUG` para registrar mensagens detalhadas da API.

2. **Token Refresh**: Implemente sempre lógica para renovar tokens expirados.

3. **Segurança**:
   - Sempre use `nonce` para todas as solicitações AJAX
   - Sanitize todos os inputs de usuário
   - Escape todos os outputs

4. **Cache**: Considere implementar cache para chamadas de API frequentes.

5. **Rate Limiting**: As APIs têm limites de taxa. Implemente lógica para lidar com erros de limite excedido.

## Convenções CSS

A interface administrativa usa as convenções do WordPress para consistência visual. Alguns estilos personalizados são adicionados para os cards de redes sociais e para o modal de canais seguidos.

## Testes

Antes de enviar alterações, teste as seguintes funcionalidades:

1. Conexão de novas contas
2. Desconexão de contas
3. Atualização de dados via AJAX
4. Funcionamento dos modais
5. Atualizações automáticas via CRON
6. Tratamento de erros da API

## Plano de Evolução

Futuras implementações planejadas:

1. Sistema de estatísticas e análises
2. Integração com mais plataformas sociais
3. Melhorias na interface de visualização de dados
4. Sistema de autorização mais granular
5. Exportação de dados