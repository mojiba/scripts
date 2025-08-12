# Sistema de Afiliados hardMOB

## Descrição

Sistema completo de gerenciamento de links de afiliados para XenForo com recursos avançados de segurança, análises e performance. Desenvolvido especificamente para o hardMOB, mas pode ser adaptado para qualquer comunidade que precise de um sistema robusto de afiliados.

## Características Principais

### 🔒 Segurança Avançada
- **Proteção CSRF**: Todos os formulários protegidos contra ataques CSRF
- **Validação rigorosa**: URLs e domínios validados contra lista de domínios permitidos
- **Sanitização de dados**: Todos os inputs sanitizados contra XSS
- **Rate limiting**: Proteção contra abuse com limites configuráveis
- **Auditoria completa**: Log de todas as operações para rastreamento de segurança

### 📊 Analytics e Relatórios
- **Rastreamento detalhado**: Cliques, usuários únicos, IPs únicos
- **Integração Google Analytics**: Envio automático de eventos para GA
- **Estatísticas por período**: Dia, semana, mês, ano
- **Relatórios de performance**: Stores com melhor desempenho
- **Dashboard administrativo**: Visão geral com métricas importantes

### ⚡ Performance e Cache
- **Sistema de cache inteligente**: Suporte a File, Redis e Memcached
- **Cache de configurações**: Configurações em cache para melhor performance
- **Invalidação automática**: Cache limpo automaticamente quando necessário
- **Pré-geração de links**: Geração antecipada para links mais utilizados

### 🛠️ Administração Avançada
- **Interface administrativa completa**: Gerenciamento fácil de stores e configurações
- **Operações em lote**: Ativação/desativação de múltiplas stores
- **Sistema de backup**: Backup automático de configurações
- **Limpeza de dados órfãos**: Remoção automática de dados não utilizados

### 🔧 Configuração Flexível
- **Domínios permitidos**: Lista configurável de domínios aceitos
- **Limites de rate**: Configuração de limites por usuário/IP
- **Retenção de logs**: Período configurável de retenção de logs
- **Drivers de cache**: Seleção do sistema de cache preferido

## Requisitos do Sistema

- **XenForo**: 2.2.17 ou superior
- **PHP**: 8.0 ou superior
- **MySQL**: 5.7 ou superior (ou MariaDB equivalente)
- **Extensões PHP**: JSON, PDO, cURL (para analytics)

### Opcionais (para melhor performance)
- **Redis**: Para cache distribuído
- **Memcached**: Cache alternativo
- **APCu**: Cache de opcode

## Instalação

### 1. Instalação via AdminCP

1. Faça download do arquivo ZIP do addon
2. No AdminCP, vá para **Add-ons > Install add-on**
3. Faça upload do arquivo ZIP
4. Confirme a instalação

### 2. Configuração Inicial

1. Vá para **Setup > Options > hardMOB Afiliados**
2. Configure os domínios permitidos
3. Defina as configurações de cache
4. Configure o Google Analytics (opcional)

### 3. Criando Stores

1. Acesse **Afiliados > Gerenciar Stores**
2. Clique em **Adicionar Store**
3. Preencha:
   - Nome da store
   - Domínio (ex: amazon.com.br)
   - Código de afiliado
   - Status (ativo/inativo)

## Configuração

### Opções Principais

#### Segurança
- **Domínios Permitidos**: Lista de domínios aceitos para links de afiliados
- **Rate Limiting**: Ativação e configuração de limites de requests
- **Logging de Segurança**: Ativação de logs de eventos de segurança

#### Performance
- **Driver de Cache**: File, Redis ou Memcached
- **TTL do Cache**: Tempo de vida dos dados em cache (em segundos)
- **Pré-geração**: Ativação da pré-geração de links populares

#### Analytics
- **Google Analytics ID**: ID do GA para tracking automático
- **Ativação de Analytics**: Liga/desliga o sistema de analytics

#### Logs
- **Retenção de Logs**: Período de retenção dos logs de auditoria (em dias)
- **Logs de Segurança**: Ativação de logging detalhado de segurança

### Configuração de Cache

#### File Cache (Padrão)
Utiliza o banco de dados do XenForo. Não requer configuração adicional.

#### Redis Cache
```php
// config.php
$config['cache']['enabled'] = true;
$config['cache']['redis'] = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '', // se necessário
    'database' => 1
];
```

#### Memcached Cache
```php
// config.php
$config['cache']['enabled'] = true;
$config['cache']['memcached'] = [
    'host' => '127.0.0.1',
    'port' => 11211
];
```

## Uso

### Para Administradores

#### Dashboard
- Acesse **Afiliados > Dashboard** para visão geral
- Visualize estatísticas em tempo real
- Monitore performance das stores

#### Gerenciamento de Stores
- **Adicionar**: Criar novas stores de afiliados
- **Editar**: Modificar configurações existentes
- **Desativar**: Desabilitar stores temporariamente
- **Excluir**: Remover stores permanentemente

#### Auditoria
- **Logs de Acesso**: Visualizar tentativas de acesso
- **Logs de Modificação**: Rastrear mudanças nas stores
- **Logs de Segurança**: Monitorar eventos de segurança

### Para Usuários (Moderadores/Editores)

#### Geração de Links
Use o sistema de placeholder nos posts:
```
{{slug:/produto/item-exemplo}}
```

O sistema automaticamente:
1. Detecta a store apropriada
2. Gera o link de afiliado
3. Redireciona através do sistema interno
4. Registra o clique para analytics

## API de Desenvolvimento

### Serviços Disponíveis

#### AffiliateGenerator
```php
$generator = \XF::service('hardMOB\Afiliados:AffiliateGenerator');
$link = $generator->processText($text, $userId);
```

#### Analytics
```php
$analytics = \XF::service('hardMOB\Afiliados:Analytics');
$stats = $analytics->getConversionRate($storeId, 'month');
```

#### Configuration
```php
$config = \XF::service('hardMOB\Afiliados:Configuration');
$value = $config->get('cache_ttl', 3600);
```

### Eventos Disponíveis

- `hardmob_affiliate_link_generated`: Após geração de link
- `hardmob_affiliate_click_tracked`: Após registrar clique
- `hardmob_affiliate_store_created`: Após criar store
- `hardmob_affiliate_store_updated`: Após atualizar store

## Segurança

### Práticas Implementadas

1. **Validação rigorosa**: Todos os inputs validados
2. **Sanitização**: Dados limpos antes do armazenamento
3. **CSRF Protection**: Tokens em todos os formulários
4. **Rate Limiting**: Proteção contra abuse
5. **Audit Logging**: Rastreamento completo de ações
6. **Domain Whitelisting**: Apenas domínios permitidos aceitos

### Configurações de Segurança Recomendadas

1. **Configure domínios permitidos**: Liste apenas stores confiáveis
2. **Ative rate limiting**: Especialmente importante em sites grandes
3. **Configure retenção de logs**: Mantenha histórico adequado
4. **Use HTTPS**: Sempre use conexões seguras
5. **Atualize regularmente**: Mantenha o addon atualizado

## Troubleshooting

### Problemas Comuns

#### Cache não funciona
- Verifique se o Redis/Memcached está ativo
- Confirme as configurações de conexão
- Teste com cache de arquivo primeiro

#### Links não são gerados
- Verifique se a store está ativa
- Confirme se o domínio está na lista permitida
- Teste a validação do slug

#### Performance lenta
- Ative o cache se não estiver ativo
- Configure um cache externo (Redis/Memcached)
- Ative a pré-geração de links

#### Logs não aparecem
- Verifique se o logging está ativado
- Confirme as permissões de escrita
- Teste com logging de arquivo

### Suporte

Para suporte técnico:
- **Issues**: https://github.com/mojiba/scripts/issues
- **Wiki**: https://github.com/mojiba/scripts/wiki
- **Documentação**: Este arquivo README

### Contribuição

Contribuições são bem-vindas! Por favor:
1. Fork o repositório
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Abra um Pull Request

## Changelog

### v1.0.13 (Atual)
- ✨ Sistema de segurança completo
- ✨ Audit logging avançado
- ✨ Cache inteligente melhorado
- ✨ Dashboard administrativo
- ✨ Backup e restore de configurações
- 🔒 Proteção CSRF em todos os formulários
- 🔒 Validação rigorosa de URLs e domínios
- 🔒 Rate limiting configurável
- 📊 Analytics aprimorado
- 🚀 Performance otimizada

### v1.0.12 (Anterior)
- Sistema básico de afiliados
- Cache simples
- Analytics básico

## Licença

Este projeto está licenciado sob a MIT License - veja o arquivo LICENSE para detalhes.

## Créditos

Desenvolvido por **Mojiba** para a comunidade **hardMOB**.

---

**Nota**: Este sistema foi desenvolvido especificamente para o hardMOB mas pode ser adaptado para outras comunidades. Para customizações específicas, entre em contato através dos canais de suporte.