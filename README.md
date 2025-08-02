# scripts

Este repositório contém scripts e sistemas para gerenciamento de cache e afiliados.

## Componentes

### 1. Cache Monitor (`cache_monitor/`)
Sistema de monitoramento de cache Memcache/OPcache com interface web para administração.

**Melhorias Recentes:**
- Mensagens de erro mais detalhadas e amigáveis
- Melhor detecção de problemas de conexão
- Sugestões específicas para resolução de problemas

### 2. Sistema de Afiliados (`afiliados/`)
Addon para XenForo que gerencia links de afiliado com cache, tracking e analytics.

**Melhorias Recentes:**
- Sistema centralizado de tratamento de erros
- Mensagens amigáveis em português
- Fallbacks automáticos para garantir funcionamento
- Logging detalhado para debugging
- Resiliência a falhas de cache e tracking

## Principais Melhorias Implementadas

### ✅ Tratamento de Erros Amigáveis
- **ErrorHandler Service**: Serviço centralizado para gerenciar erros
- **12 novas phrases**: Mensagens amigáveis em português  
- **Logging estruturado**: Integração com sistema de logs do XenForo
- **Fallbacks automáticos**: Sistema continua funcionando mesmo com falhas parciais

### ✅ Cache Resiliente
- **Fallback Redis → Database**: Troca automática quando Redis falha
- **Detecção de problemas**: Identifica e reporta problemas específicos
- **Mensagens contextuais**: Erros com sugestões de solução

### ✅ Geração de Links Robusta
- **Validação completa**: Verifica loja, conector e código de afiliado
- **Handling de exceções**: Retorna slug original em caso de falha
- **Logs detalhados**: Facilita debugging de problemas

### ✅ Tracking Confiável
- **Tracking resiliente**: Funciona mesmo com falhas de banco
- **Google Analytics opcional**: Falhas de GA não afetam sistema principal
- **Contexto de erro**: Logs específicos para cada tipo de falha

## Documentação

- [Guia Completo de Error Handling](ERRO_HANDLING_GUIDE.md) - Documentação detalhada das melhorias

## Como Usar

### Cache Monitor
1. Acesse via navegador: `http://seu-servidor/cache_monitor/`
2. Faça login com credenciais configuradas
3. Monitore cache e execute operações de limpeza

### Sistema de Afiliados
1. Instale o addon no XenForo
2. Configure lojas em Admin → hardMOB Afiliados → Gerenciar Lojas
3. Use `{{slug:/produto/123}}` nos posts para gerar links automaticamente
4. Monitore erros em Ferramentas → Logs de Erro

## Benefícios das Melhorias

- 🛡️ **Maior confiabilidade**: Sistema funciona mesmo com falhas parciais
- 📊 **Melhor visibilidade**: Administradores podem identificar problemas rapidamente  
- 🔧 **Facilita manutenção**: Logs detalhados ajudam no debugging
- 👥 **UX melhorada**: Usuários recebem mensagens compreensíveis
- ⚡ **Performance**: Fallbacks evitam timeout e lentidão