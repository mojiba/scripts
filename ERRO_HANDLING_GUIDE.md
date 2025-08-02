# Mensagens de Erro Amigáveis - hardMOB Afiliados

## Visão Geral

Este documento descreve as melhorias implementadas no sistema de tratamento de erros para o addon hardMOB Afiliados, garantindo que falhas em cache, tracking e geração de links sejam reportadas de forma compreensível para usuários e administradores.

## Componentes Implementados

### 1. ErrorHandler Service (`afiliados/Service/ErrorHandler.php`)

Serviço centralizado para gerenciamento de erros com as seguintes funcionalidades:

- **Logging estruturado**: Categoriza erros, warnings e informações
- **Mensagens amigáveis**: Utiliza o sistema de phrases do XenForo
- **Integração com logs**: Registra automaticamente no error_log do XenForo
- **Formatação para usuários**: Métodos para exibir erros de forma amigável

#### Métodos principais:
```php
$errorHandler->logError($type, $message, $params = [], $exception = null)
$errorHandler->logWarning($type, $message, $params = [])
$errorHandler->logInfo($type, $message, $params = [])
```

### 2. Phrases de Erro (afiliados/_data/phrases.xml)

Adicionadas 12 novas phrases para diferentes tipos de erro:

- `hardmob_afiliados_error_cache_redis_connection`: Falhas de conexão Redis
- `hardmob_afiliados_error_cache_redis_operation`: Falhas em operações Redis
- `hardmob_afiliados_error_cache_database`: Erros no cache de banco de dados
- `hardmob_afiliados_error_link_generation_failed`: Falhas na geração de links
- `hardmob_afiliados_error_store_not_found`: Loja não encontrada
- `hardmob_afiliados_error_invalid_slug`: Formato de produto inválido
- `hardmob_afiliados_error_tracking_failed`: Falhas no tracking
- `hardmob_afiliados_error_analytics_failed`: Erros no Google Analytics
- `hardmob_afiliados_warning_cache_unavailable`: Cache indisponível
- `hardmob_afiliados_info_cache_fallback`: Informação sobre fallback
- `hardmob_afiliados_error_connector_missing`: Conector da loja ausente
- `hardmob_afiliados_error_affiliate_code_missing`: Código de afiliado não configurado

### 3. Melhorias por Componente

#### LinkCache (`afiliados/Cache/LinkCache.php`)
- **Tratamento de falhas Redis**: Fallback automático para cache de banco
- **Logging de problemas de conexão**: Mensagens específicas para diferentes tipos de falha
- **Validação de configuração**: Verifica se Redis está properly configurado
- **Recuperação automática**: Sistema resiliente com fallbacks

#### AffiliateGenerator (`afiliados/Service/AffiliateGenerator.php`)
- **Validação de loja**: Verifica se a loja existe e está ativa
- **Verificação de conectores**: Valida se o conector da loja está disponível
- **Validação de código de afiliado**: Confirma se está configurado
- **Handling de exceções**: Retorna slug original em caso de falha

#### Analytics (`afiliados/Service/Analytics.php`)
- **Tracking resiliente**: Continua funcionamento mesmo com falhas de BD
- **Google Analytics opcional**: Falhas de GA não afetam tracking local
- **Logs detalhados**: Registra contexto específico de cada falha

#### Public Controller (`afiliados/Pub/Controller/Affiliate.php`)
- **Validação de parâmetros**: Verifica se store_id e slug são válidos
- **Verificação de conectores**: Confirma disponibilidade antes de gerar link
- **Fallback para 404**: Retorna not found em caso de falha irrecuperável

#### Cache Monitor (`cache_monitor/actions.php` e `index.php`)
- **Mensagens contextuais**: Erros específicos com sugestões de solução
- **Detecção de conexão**: Identifica problemas de conectividade
- **Validação de operações**: Verifica disponibilidade de funções antes do uso

## Como Usar

### Para Desenvolvedores

1. **Obter instância do ErrorHandler**:
```php
$errorHandler = $this->app->service('hardMOB\Afiliados:ErrorHandler');
```

2. **Registrar erro**:
```php
$errorHandler->logError('invalid_slug', 'Validation failed', ['slug' => $slug]);
```

3. **Registrar warning**:
```php
$errorHandler->logWarning('cache_unavailable', 'Redis down');
```

4. **Obter mensagens formatadas**:
```php
$errors = $errorHandler->getFormattedErrors();
$warnings = $errorHandler->getFormattedWarnings();
```

### Para Administradores

1. **Visualizar logs de erro**: Acesse o painel administrativo → hardMOB Afiliados → Ferramentas → Logs de Erro

2. **Testar error handling**: Use a ferramenta de teste em Ferramentas → Teste de Error Handling

3. **Monitorar cache**: O sistema agora reporta problemas específicos de cache com sugestões de solução

## Tipos de Erro e Soluções

### Erros de Cache

**Redis Connection Failed**
- **Mensagem**: "Erro ao conectar com Redis. Usando cache de arquivo como alternativa."
- **Solução**: Verificar se Redis está rodando e configurações estão corretas

**Database Cache Error**
- **Mensagem**: "Erro no banco de dados do cache: {error}"
- **Solução**: Verificar conexão com banco de dados e estrutura de tabelas

### Erros de Geração de Links

**Store Not Found**
- **Mensagem**: "Loja não encontrada para o produto '{slug}'. Verifique se a loja está configurada e ativa."
- **Solução**: Configurar loja apropriada ou ativar loja existente

**Invalid Slug**
- **Mensagem**: "Formato de produto inválido: '{slug}'. Verifique se o link está correto."
- **Solução**: Verificar formato do slug para a loja específica

**Connector Missing**
- **Mensagem**: "Conector da loja '{store}' não encontrado. Contacte o administrador."
- **Solução**: Implementar conector específico para a loja

### Erros de Tracking

**Tracking Failed**
- **Mensagem**: "Falha ao registrar clique: {error}. O redirecionamento funcionará normalmente."
- **Solução**: Verificar conexão com banco e estrutura da tabela de cliques

**Analytics Failed**
- **Mensagem**: "Erro ao enviar dados para Google Analytics: {error}"
- **Solução**: Verificar configuração do GA e conectividade com a API

## Benefícios

1. **Visibilidade**: Administradores podem identificar problemas rapidamente
2. **Resiliência**: Sistema continua funcionando mesmo com falhas parciais
3. **Debugging**: Logs detalhados facilitam identificação de problemas
4. **UX**: Usuários recebem mensagens compreensíveis ao invés de erros técnicos
5. **Manutenção**: Facilita diagnóstico e correção de problemas

## Fallbacks Implementados

- **Redis → Database**: Cache automático para banco quando Redis falha
- **Link Generation → Original Slug**: Retorna slug original se geração falha
- **Tracking Failure → Continue**: Redirecionamento funciona mesmo com tracking falho
- **GA Failure → Local Tracking**: Tracking local continua se GA falha

## Monitoramento

O sistema agora registra todos os erros no log padrão do XenForo com prefixo `[Afiliados]`, facilitando monitoramento e alertas automáticos.