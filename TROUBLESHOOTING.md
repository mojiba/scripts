# 🔧 Guia de Troubleshooting - Scripts Collection

Este guia aborda os problemas mais comuns e suas soluções para o addon Afiliados e o Cache Monitor.

## 📋 Índice

- [Problemas Gerais](#problemas-gerais)
- [Afiliados - XenForo Addon](#afiliados---xenforo-addon)
- [Cache Monitor](#cache-monitor)
- [Performance e Otimização](#performance-e-otimização)
- [Logs e Debug](#logs-e-debug)
- [Ferramentas de Diagnóstico](#ferramentas-de-diagnóstico)

## 🚨 Problemas Gerais

### Erro: "PHP Version Not Supported"

**Sintomas**: Erro durante instalação sobre versão do PHP
**Causa**: Versão do PHP incompatível
**Solução**:

```bash
# Verificar versão atual
php -v

# Instalar PHP 8.1+ (Ubuntu/Debian)
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-common php8.1-mysql \
                 php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl \
                 php8.1-xml php8.1-bcmath

# Atualizar alternativas
sudo update-alternatives --install /usr/bin/php php /usr/bin/php8.1 81
```

### Erro: "Extension Not Found"

**Sintomas**: Erro sobre extensões PHP ausentes
**Solução**:

```bash
# Verificar extensões instaladas
php -m

# Instalar extensões necessárias
sudo apt install php8.1-memcache php8.1-opcache php8.1-redis

# Reiniciar servidor web
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
```

### Problema de Permissões

**Sintomas**: Erros de acesso negado, falha ao escrever arquivos
**Solução**:

```bash
# Corrigir permissões básicas
sudo chown -R www-data:www-data /var/www/
sudo chmod -R 755 /var/www/
sudo chmod -R 644 /var/www/*.php

# Para XenForo
sudo chown -R www-data:www-data /path/to/xenforo/
sudo chmod -R 755 /path/to/xenforo/internal_data/
sudo chmod -R 755 /path/to/xenforo/data/
```

## 🔗 Afiliados - XenForo Addon

### Instalação e Setup

#### Erro: "Table Already Exists"

**Sintomas**: 
```
Error: Table 'xf_hardmob_affiliate_stores' already exists
```

**Causa**: Instalação anterior incompleta ou conflito de versões
**Solução**:

```sql
-- 1. Verificar tabelas existentes
SHOW TABLES LIKE 'xf_hardmob_affiliate_%';

-- 2. Fazer backup se necessário
CREATE TABLE backup_stores AS SELECT * FROM xf_hardmob_affiliate_stores;
CREATE TABLE backup_clicks AS SELECT * FROM xf_hardmob_affiliate_clicks;
CREATE TABLE backup_cache AS SELECT * FROM xf_hardmob_affiliate_cache;

-- 3. Remover tabelas conflitantes
DROP TABLE IF EXISTS xf_hardmob_affiliate_stores;
DROP TABLE IF EXISTS xf_hardmob_affiliate_clicks;
DROP TABLE IF EXISTS xf_hardmob_affiliate_cache;

-- 4. Reinstalar addon
```

#### Erro: "Route Conflicts"

**Sintomas**:
```
Route with pattern 'affiliate' already exists
```

**Solução**:

```bash
# 1. Limpar cache de rotas
php cmd.php xf:rebuild-caches

# 2. Verificar rotas conflitantes
php cmd.php xf-dev:export --addon=hardMOB/Afiliados routes

# 3. Se necessário, alterar prefixo da rota
# Em _data/routes.xml, mudar 'affiliate' para 'aff' ou 'links'
```

#### Erro: "Class Not Found"

**Sintomas**:
```
Class 'hardMOB\Afiliados\Controller\Admin\Affiliates' not found
```

**Causa**: Autoloader não atualizado ou arquivos ausentes
**Solução**:

```bash
# 1. Verificar estrutura de arquivos
ls -la src/addons/hardMOB/Afiliados/Admin/Controller/

# 2. Reconstruir autoloader
php cmd.php xf-dev:rebuild-autoload

# 3. Limpar cache
php cmd.php xf:rebuild-caches

# 4. Verificar permissões
sudo chown -R www-data:www-data src/addons/hardMOB/
```

### Funcionamento

#### Links Não São Processados

**Sintomas**: Placeholders `{{slug:...}}` aparecem como texto normal
**Diagnóstico**:

```php
// Via console XenForo - teste básico
php cmd.php xf-dev:run-code '
$service = \XF::service("hardMOB\Afiliados:AffiliateGenerator");
$test = $service->processContent("Teste: {{slug:/dp/B08N5WRWNW}}");
echo $test;
'
```

**Soluções**:

1. **Verificar se addon está ativo**:
```bash
php cmd.php xf-addon:list | grep Afiliados
```

2. **Verificar logs de erro**:
```bash
tail -f internal_data/logs/xf.log | grep -i affiliate
```

3. **Testar processamento manual**:
```php
// Adicionar código de debug ao processamento
\XF::logError('Processing affiliate link: ' . $placeholder);
```

4. **Verificar configuração de lojas**:
```sql
SELECT * FROM xf_hardmob_affiliate_stores WHERE status = 'active';
```

#### Cache Não Funciona

**Sintomas**: Links são regenerados a cada acesso
**Diagnóstico**:

```php
// Testar cache manualmente
php cmd.php xf-dev:run-code '
$cache = \XF::app()->cache("affiliate");
$cache->set("test_key", "test_value", 300);
$value = $cache->fetch("test_key");
echo "Cache test: " . ($value === "test_value" ? "OK" : "FAILED");
'
```

**Soluções**:

1. **Para cache de arquivo**:
```bash
# Verificar permissões
ls -la internal_data/caches/
sudo chmod 755 internal_data/caches/
```

2. **Para cache Redis**:
```bash
# Testar conectividade
redis-cli ping

# Verificar config XenForo
grep -A 10 "cache.*redis" config.php
```

3. **Configuração no config.php**:
```php
$config['cache']['context']['affiliate'] = [
    'provider' => 'Redis',
    'config' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ]
];
```

#### Cron Jobs Não Executam

**Sintomas**: Links não são pré-gerados, estatísticas não atualizadas
**Diagnóstico**:

```bash
# Verificar jobs pendentes
php cmd.php xf:job-queue

# Executar manualmente
php cmd.php xf:run-jobs --job=hardMOB\\Afiliados\\Job\\GenerateAffiliateLinks
```

**Soluções**:

1. **Configurar cron do sistema**:
```bash
# Adicionar ao crontab
crontab -e

# Adicionar linha:
*/30 * * * * /usr/bin/php /path/to/xenforo/cmd.php xf:run-jobs > /dev/null 2>&1
```

2. **Verificar configuração no addon**:
```sql
SELECT * FROM xf_option WHERE option_id LIKE '%affiliate%cron%';
```

3. **Executar job específico**:
```bash
php cmd.php xf:run-jobs --manual --job=hardMOB\\Afiliados\\Job\\GenerateAffiliateLinks
```

### Conectores

#### Conector Amazon Não Funciona

**Sintomas**: Links Amazon não são gerados corretamente
**Diagnóstico**:

```php
// Testar conector específico
php cmd.php xf-dev:run-code '
$store = \XF::em()->find("hardMOB\Afiliados:Store", 1);
$connector = \XF::app()->container("affiliate.connector.amazon");
$url = $connector->generateAffiliateUrl($store, "/dp/B08N5WRWNW");
echo "Generated URL: " . $url;
'
```

**Soluções**:

1. **Verificar configuração da loja**:
   - Domínio: `amazon.com.br`
   - Código de afiliado: seu Associate Tag
   - Tipo de conector: `Amazon`

2. **Validar Associate Tag**:
```bash
# Teste manual da URL gerada
curl -I "https://amazon.com.br/dp/B08N5WRWNW?tag=seu-tag"
```

3. **Debug do conector**:
```php
// Adicionar logs ao conector Amazon
\XF::logError('Amazon connector - Store: ' . $store->name . ', Slug: ' . $slug);
```

#### Erro: "Invalid Slug Format"

**Sintomas**: Erro ao processar slugs específicos
**Causa**: Padrão de slug não reconhecido pelo conector
**Solução**:

```php
// Verificar padrões aceitos por cada conector
// Amazon: /dp/ASIN, /gp/product/ASIN
// MercadoLivre: MLB-XXXXXXXX
// Shopee: produto-i.x.y

// Teste de padrões
$patterns = [
    '/dp/B08N5WRWNW',
    'MLB-123456789',
    'smartphone-i.123.456'
];

foreach ($patterns as $pattern) {
    echo "Testing: $pattern\n";
    // Processar cada padrão
}
```

## 📊 Cache Monitor

### Conexão e Autenticação

#### Erro: "Cannot Connect to Memcache"

**Sintomas**: 
```
Warning: Memcache::connect(): Can't connect to localhost:11211
```

**Diagnóstico**:
```bash
# Verificar se Memcache está rodando
sudo systemctl status memcached

# Testar conectividade
telnet localhost 11211
```

**Soluções**:

1. **Iniciar Memcache**:
```bash
sudo systemctl start memcached
sudo systemctl enable memcached
```

2. **Verificar configuração**:
```bash
# Verificar bind address
sudo cat /etc/memcached.conf | grep -E "^-l"

# Se necessário, alterar para:
# -l 0.0.0.0  # ou IP específico
```

3. **Testar com netstat**:
```bash
sudo netstat -tulpn | grep :11211
```

4. **Verificar firewall**:
```bash
sudo ufw status
# Se necessário:
sudo ufw allow 11211
```

#### Loop de Login Infinito

**Sintomas**: Volta sempre para a tela de login
**Causa**: Problemas de sessão ou configuração de autenticação
**Soluções**:

1. **Verificar configuração de sessão**:
```php
// Adicionar ao config.php
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.save_path', '/tmp');
```

2. **Limpar cookies do browser**

3. **Verificar permissões de sessão**:
```bash
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 755 /var/lib/php/sessions
```

4. **Debug de autenticação**:
```php
// Adicionar logs ao auth.php
error_log('Login attempt: ' . $_POST['username']);
error_log('Session ID: ' . session_id());
```

#### Erro: "CSRF Token Invalid"

**Sintomas**: Erro ao tentar executar ações
**Causa**: Token CSRF expirado ou inválido
**Soluções**:

1. **Refresh da página** e tentar novamente

2. **Verificar configuração CSRF**:
```php
// config.php
'security' => [
    'enable_csrf' => true,
]
```

3. **Debug de tokens**:
```php
// Adicionar ao csrf.php
error_log('Generated token: ' . $token);
error_log('Submitted token: ' . $_POST['csrf_token']);
```

### Interface e Funcionalidade

#### OpCache Stats Não Aparecem

**Sintomas**: Aba OpCache vazia ou com erro
**Diagnóstico**:
```bash
# Verificar se OpCache está habilitado
php -m | grep OPcache
php -i | grep opcache
```

**Soluções**:

1. **Habilitar OpCache**:
```ini
; php.ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

2. **Reiniciar servidor web**:
```bash
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

3. **Verificar função disponível**:
```php
// Testar se função existe
if (function_exists('opcache_get_status')) {
    var_dump(opcache_get_status());
} else {
    echo 'OpCache functions not available';
}
```

#### Erro 403 - Forbidden

**Sintomas**: Acesso negado ao cache monitor
**Causa**: Configuração de servidor web ou permissões
**Soluções**:

1. **Apache - verificar .htaccess**:
```apache
# Adicionar ao .htaccess
<RequireAll>
    Require all granted
</RequireAll>
```

2. **Nginx - verificar configuração**:
```nginx
location /cache-monitor {
    allow all;
}
```

3. **Verificar permissões**:
```bash
sudo chown -R www-data:www-data /var/www/cache-monitor
sudo chmod 755 /var/www/cache-monitor
```

## ⚡ Performance e Otimização

### Afiliados - Otimização

#### Lentidão no Processamento de Links

**Sintomas**: Forum fica lento ao processar posts com muitos links
**Soluções**:

1. **Otimizar cache**:
```php
// Aumentar TTL do cache
'cache_ttl' => 7200, // 2 horas
```

2. **Usar Redis para cache**:
```php
$config['cache']['context']['affiliate'] = [
    'provider' => 'Redis',
    'config' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1,
        'persistent' => true
    ]
];
```

3. **Otimizar cron jobs**:
```bash
# Executar com mais frequência
*/15 * * * * php /path/to/xenforo/cmd.php xf:run-jobs
```

#### Alto Uso de Banco de Dados

**Sintomas**: Muitas queries lentas relacionadas a affiliate
**Soluções**:

1. **Adicionar índices**:
```sql
ALTER TABLE xf_hardmob_affiliate_clicks ADD INDEX idx_store_date (store_id, click_date);
ALTER TABLE xf_hardmob_affiliate_cache ADD INDEX idx_key_expires (cache_key, expires_at);
```

2. **Limpar dados antigos**:
```sql
-- Remover cliques antigos (90 dias)
DELETE FROM xf_hardmob_affiliate_clicks 
WHERE click_date < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Remover cache expirado
DELETE FROM xf_hardmob_affiliate_cache 
WHERE expires_at > 0 AND expires_at < UNIX_TIMESTAMP();
```

### Cache Monitor - Otimização

#### Interface Lenta

**Sintomas**: Dashboard demora para carregar
**Soluções**:

1. **Reduzir itens por página**:
```php
'ui' => [
    'items_per_page' => 25, // reduzir de 50
]
```

2. **Implementar paginação**:
```php
$offset = (int)($_GET['page'] ?? 0) * $items_per_page;
$keys = array_slice($all_keys, $offset, $items_per_page);
```

3. **Cache de estatísticas**:
```php
// Cache stats por 30 segundos
$cache_key = 'stats_' . date('His');
if (!$cached_stats = apcu_fetch($cache_key)) {
    $cached_stats = getMemcacheStats();
    apcu_store($cache_key, $cached_stats, 30);
}
```

## 📋 Logs e Debug

### Configuração de Logs

#### XenForo (Afiliados)

```php
// config.php - habilitar debug
$config['debug'] = true;
$config['development']['enabled'] = true;

// Log específico para afiliados
$config['enableClickLogging'] = true;
```

#### Cache Monitor

```php
// Adicionar ao início de config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cache_monitor.log');

// Função de log personalizada
function logDebug($message, $context = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ];
    file_put_contents(
        __DIR__ . '/debug.log',
        json_encode($log) . "\n",
        FILE_APPEND | LOCK_EX
    );
}
```

### Monitoramento de Logs

#### Script de Monitoramento

```bash
#!/bin/bash
# monitor_logs.sh

echo "=== Monitoramento de Logs ==="

# XenForo logs
echo "Logs XenForo (últimas 20 linhas):"
tail -20 /path/to/xenforo/internal_data/logs/xf.log | grep -i affiliate

# Cache Monitor logs
echo "Logs Cache Monitor:"
tail -20 /var/www/cache-monitor/debug.log

# Apache/Nginx logs
echo "Logs do servidor web:"
tail -20 /var/log/apache2/error.log | grep -E "(affiliate|cache-monitor)"

# PHP logs
echo "Logs PHP:"
tail -20 /var/log/php8.1-fpm.log | grep -E "(affiliate|memcache)"
```

#### Alertas Automáticos

```bash
# Script para alertas via email
#!/bin/bash
LOG_FILE="/var/log/scripts_monitor.log"
ERROR_PATTERN="(FATAL|ERROR|CRITICAL)"

# Verificar logs dos últimos 5 minutos
RECENT_ERRORS=$(tail -100 $LOG_FILE | grep -E "$ERROR_PATTERN" | tail -10)

if [ ! -z "$RECENT_ERRORS" ]; then
    echo "Erros encontrados nos scripts:" | mail -s "ALERTA: Scripts Collection" admin@exemplo.com
    echo "$RECENT_ERRORS" | mail -s "Detalhes dos Erros" admin@exemplo.com
fi
```

## 🔍 Ferramentas de Diagnóstico

### Script de Diagnóstico Completo

```bash
#!/bin/bash
# diagnostic.sh

echo "=== Diagnóstico Completo - Scripts Collection ==="

# Informações do sistema
echo "Sistema:"
uname -a
php -v | head -1
mysql --version | head -1

# Extensões PHP
echo -e "\nExtensões PHP relevantes:"
php -m | grep -E "(memcache|opcache|redis|curl|json|mysql)"

# Serviços
echo -e "\nStatus dos serviços:"
systemctl is-active apache2 nginx memcached redis-server mysql

# Conectividade
echo -e "\nTeste de conectividade:"
# Memcache
echo "stats" | timeout 2 nc localhost 11211 >/dev/null 2>&1 && echo "✅ Memcache OK" || echo "❌ Memcache FAIL"

# Redis
redis-cli ping >/dev/null 2>&1 && echo "✅ Redis OK" || echo "❌ Redis FAIL"

# MySQL
mysql -e "SELECT 1;" >/dev/null 2>&1 && echo "✅ MySQL OK" || echo "❌ MySQL FAIL"

# Verificar arquivos
echo -e "\nArquivos de configuração:"
[ -f "/var/www/cache-monitor/config.php" ] && echo "✅ Cache Monitor config" || echo "❌ Cache Monitor config"
[ -f "/path/to/xenforo/src/addons/hardMOB/Afiliados/addon.json" ] && echo "✅ Afiliados addon" || echo "❌ Afiliados addon"

# Permissões
echo -e "\nPermissões:"
ls -ld /var/www/cache-monitor/ | awk '{print $1, $3, $4, $9}'
ls -ld /path/to/xenforo/internal_data/ | awk '{print $1, $3, $4, $9}'

# Logs recentes
echo -e "\nErros recentes:"
grep -i "error\|fatal\|critical" /var/log/apache2/error.log | tail -5
grep -i "error\|fatal\|critical" /var/log/nginx/error.log | tail -5 2>/dev/null

echo "=== Fim do Diagnóstico ==="
```

### Testes de Performance

```bash
#!/bin/bash
# performance_test.sh

echo "=== Teste de Performance ==="

# Teste cache
echo "Testando cache..."
time (
    for i in {1..100}; do
        echo "set test_$i 0 300 5\r\nhello\r\n" | nc localhost 11211 >/dev/null
    done
)

# Teste XenForo
echo "Testando XenForo..."
time php /path/to/xenforo/cmd.php xf-dev:run-code 'echo "XenForo OK\n";'

# Teste HTTP
echo "Testando HTTP..."
time curl -s http://localhost/cache-monitor/ >/dev/null

echo "=== Fim dos Testes ==="
```

---

## 📞 Suporte

Se os problemas persistirem após seguir este guia:

1. **Colete logs** usando os scripts de diagnóstico
2. **Execute testes** de conectividade e performance
3. **Documente** os passos que levaram ao problema
4. **Entre em contato**: suporte@hardmob.com.br

---

**Guia de Troubleshooting - Scripts Collection**  
**Desenvolvido pela equipe hardMOB**