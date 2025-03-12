# Changelog do Social Connect

Todas as alterações notáveis para este projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.5.0] - 2025-03-14

### Adicionado
- Interface de dark mode para a página de conexões sociais
- Design moderno com cards para Twitter e Twitch
- Efeitos visuais sutis nos elementos de interface
- Novos badges para status de relacionamento (seguindo, assinante)
- Integração nativa com Dashicons para melhorar a compatibilidade

### Melhorado
- Redesign completo da interface de conexões sociais
- Experiência mais intuitiva e moderna para o usuário
- Responsividade aprimorada para dispositivos móveis
- Compatibilidade com temas escuros
- Feedback visual mais claro para ações de conexão/desconexão

### Corrigido
- Compatibilidade de Dashicons em diversos temas
- Problemas de exibição de ícones em dispositivos móveis
- Ajuste de botões para evitar clipping e melhorar exibição

## [1.4.0] - 2025-03-13

### Adicionado
- Integração com Steam usando Trade URLs existentes
- Novo menu administrativo para Steam no painel WordPress
- Sistema de configuração para a API da Steam
- Campo personalizável para definir meta onde estão armazenados os Trade URLs
- Estrutura de abas para configurações, contas conectadas e inventário da Steam
- Preparação para futura implementação de análise de inventário

### Melhorado
- Estrutura de documentação com inclusão da nova plataforma
- Organização do código seguindo o padrão das outras integrações

## [1.3.0] - 2025-03-12

### Adicionado
- Botão de atualização individual para dados de usuários Twitch
- Nova coluna "Última Atualização" na tabela de usuários Twitch
- Modal de "Ver detalhes" para visualizar canais seguidos por usuários Twitch
- Interface de grid para visualização de canais seguidos com foto, nome e link
- Endpoint AJAX `social_connect_get_followed_channels` para obter lista de canais seguidos
- Paginação para carregar mais canais seguidos sob demanda
- Animação do botão de atualização durante o processamento
- Métodos de processamento de dados do Twitch no backend

### Melhorado
- Interface administrativa com informações mais detalhadas
- Feedback visual para operações AJAX
- Consistência de estilo entre as seções Twitter e Twitch

## [1.2.0] - 2025-03-10

### Adicionado
- Sistema de recompensas para assinantes da Twitch
- Integração com WooWallet para gerenciar créditos
- Verificação de assinantes e seus tiers
- Página administrativa para gerenciar recompensas
- Processo automatizado via CRON para distribuição de recompensas
- Visualização de histórico de recompensas

### Melhorado
- Interface administrativa para visualização de detalhes de usuários
- Adicionados escopos adicionais para API da Twitch
- Documentação interna e comentários de código

## [1.1.0] - 2025-03-08

### Adicionado
- Métricas de Twitter: contagem de tweets, seguidores, seguindo
- Atualização automática de dados do Twitter via CRON
- Interface para atualização manual de todos os usuários
- Botão individual para atualização de dados do Twitter
- Coluna "Última Atualização" para dados do Twitter
- Endpoint AJAX `social_connect_update_user_twitter_data`

### Melhorado
- Estrutura de dados para armazenar métricas
- Interface administrativa com novas colunas
- Feedback visual para usuários
- Tratamento de erros para API do Twitter

### Corrigido
- Problemas de CSS na exibição de cartões sociais
- Tratamento de tokens expirados

## [1.0.0] - 2025-03-05

### Adicionado
- Implementação inicial do plugin
- Autenticação OAuth para Twitter e Twitch
- Conexão de contas de usuários WordPress com redes sociais
- Página de configurações administrativas
- Endpoint "Conexões" na conta WooCommerce
- Interface de cartões sociais para usuários
- Verificação de seguidores para canal específico da Twitch
- Página administrativa para visualizar conexões