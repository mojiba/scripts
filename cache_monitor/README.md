# 📊 Cache Monitor - Ferramenta de Monitoramento

Ferramenta standalone em PHP para monitoramento de sistemas de cache (Memcache/OpCache) com interface web administrativa segura e recursos avançados de análise.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Uso Básico](#uso-básico)
- [Interface Administrativa](#interface-administrativa)
- [Recursos Avançados](#recursos-avançados)
- [Templates e Personalização](#templates-e-personalização)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

## 🎯 Visão Geral

O Cache Monitor é uma ferramenta completa para monitoramento e gerenciamento de sistemas de cache em servidores PHP. Oferece uma interface web segura para visualizar estatísticas, gerenciar entradas de cache e diagnosticar problemas de performance.

### Principais Funcionalidades

- **📊 Monitoramento em Tempo Real**: Estatísticas de Memcache e OpCache
- **🔐 Autenticação Segura**: Sistema de login com proteção CSRF
- **🧹 Gerenciamento de Cache**: Limpeza e flush de caches específicos
- **📈 Análise de Performance**: Gráficos e métricas detalhadas
- **🔧 Ferramentas de Debug**: Inspeção de chaves e valores
- **🌐 Suporte Multi-servidor**: Monitoramento de múltiplos servidores
- **🎨 Interface Responsiva**: Dashboard moderno e intuitivo

## 🔧 Requisitos

### Servidor
- **PHP**: 7.4 ou superior
- **Servidor Web**: Apache, Nginx ou similar
- **Extensões PHP**: memcache, opcache, json, session

### Cache Systems
- **Memcache/Memcached**: Para cache distribuído
- **OpCache**: Para cache de bytecode PHP

### Opcional
- **SSL/HTTPS**: Recomendado para ambientes de produção
- **Firewall**: Para restringir acesso à interface

## 📦 Instalação

### Método 1: Download Direto

```bash
# Clone ou baixe os arquivos
git clone https://github.com/mojiba/scripts.git
cd scripts/cache_monitor

# Configure permissões
chmod 755 .
chmod 644 *.php
chmod 755 templates/
```

### Método 2: Via Composer (Futuro)

```bash
composer require hardmob/cache-monitor
```

### Configuração do Servidor Web

#### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteção de arquivos sensíveis
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "auth.php">
    Order Allow,Deny
    Deny from all
</Files>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name cache-monitor.exemplo.com;
    root /var/www/cache_monitor;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Proteger arquivos sensíveis
    location ~* (config\.php|auth\.php)$ {
        deny all;
    }
}
```

## ⚙️ Configuração

### Arquivo de Configuração Principal

Crie/edite o arquivo `config.php`:

```php
<?php
return [
    // Configurações de autenticação
    'auth' => [
        'username' => 'admin',
        'password' => password_hash('sua_senha_segura', PASSWORD_DEFAULT),
        'session_timeout' => 3600, // 1 hora
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],

    // Servidores Memcache
    'servers' => [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 1,
            'name' => 'Local Server'
        ],
        [
            'host' => '192.168.1.100',
            'port' => 11211,
            'weight' => 2,
            'name' => 'Cache Server 1'
        ]
    ],

    // Prefixos de cache para filtros
    'prefix_filters' => [
        'app' => 'app_*',
        'session' => 'sess_*',
        'user' => 'user_*',
        'page' => 'page_*',
        'all' => '*'
    ],

    // Prefixo padrão
    'default_prefix' => 'all',

    // Configurações de interface
    'ui' => [
        'theme' => 'dark', // dark, light
        'refresh_interval' => 30, // segundos
        'items_per_page' => 50,
        'show_values' => false, // mostrar valores de cache por padrão
    ],

    // Configurações de segurança
    'security' => [
        'enable_csrf' => true,
        'allowed_ips' => [], // vazio = qualquer IP
        'require_https' => false, // true em produção
    ],

    // Configurações OpCache
    'opcache' => [
        'enable_monitoring' => true,
        'show_file_list' => true,
        'enable_reset' => true,
    ]
];
```

### Configuração de Segurança

#### Restringir por IP

```php
'security' => [
    'allowed_ips' => [
        '192.168.1.0/24',
        '10.0.0.0/8',
        '172.16.0.100'
    ]
],
```

#### Habilitar HTTPS

```php
'security' => [
    'require_https' => true,
],
```

### Configuração Multi-ambiente

Crie arquivos específicos por ambiente:

**config_production.php**:
```php
<?php
return array_merge(
    require 'config.php',
    [
        'security' => [
            'require_https' => true,
            'allowed_ips' => ['192.168.1.0/24']
        ],
        'ui' => [
            'show_values' => false
        ]
    ]
);
```

## 🚀 Uso Básico

### Primeiro Acesso

1. **Acesse** a URL onde instalou o cache monitor
2. **Faça login** com as credenciais configuradas
3. **Visualize** o dashboard principal

### Dashboard Principal

O dashboard exibe:

- **📊 Estatísticas Gerais**: Hit rate, miss rate, uptime
- **💾 Uso de Memória**: Gráficos de utilização
- **📈 Performance**: Gráficos de throughput
- **🔍 Inspeção de Chaves**: Lista de entradas ativas

### Filtros de Visualização

Use os filtros para focar em tipos específicos:

```
Filtro "app": Mostra apenas chaves app_*
Filtro "session": Mostra apenas sess_*
Filtro "user": Mostra apenas user_*
```

### Ações Básicas

#### Limpar Cache Específico

```javascript
// Via interface web
$('.clear-prefix-btn').click(function() {
    var prefix = $(this).data('prefix');
    clearCacheByPrefix(prefix);
});
```

#### Flush Completo

```php
// Via ação administrativa
if (isset($_POST['action']) && $_POST['action'] === 'flush_all') {
    if (validate_csrf_token($_POST['csrf_token'])) {
        $mem->flush();
        $success = 'Cache completamente limpo!';
    }
}
```

## 🏛️ Interface Administrativa

### Dashboard de Estatísticas

A interface principal oferece:

#### Memcache Stats
- **Connections**: Conexões ativas/totais
- **Commands**: Get/Set operations por segundo
- **Memory**: Utilização atual vs. limite
- **Hit Rate**: Percentual de cache hits
- **Evictions**: Items removidos por falta de espaço

#### OpCache Stats
- **Memory Usage**: RAM utilizada pelo OpCache
- **File Count**: Arquivos em cache
- **Hit Rate**: Eficiência do cache de bytecode
- **Last Reset**: Último reset do cache

### Ferramentas de Gerenciamento

#### Limpeza Seletiva

```html
<form method="post" class="cache-tools">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" />
    
    <div class="tool-group">
        <label>Limpar por Prefixo:</label>
        <select name="prefix">
            <option value="app_*">Aplicação (app_*)</option>
            <option value="sess_*">Sessões (sess_*)</option>
            <option value="user_*">Usuários (user_*)</option>
        </select>
        <button type="submit" name="action" value="clear_prefix">Limpar</button>
    </div>
    
    <div class="tool-group">
        <label>Ações Gerais:</label>
        <button type="submit" name="action" value="flush_all" 
                onclick="return confirm('Limpar TODO o cache?')">
            Flush Completo
        </button>
        <button type="submit" name="action" value="reset_opcache">
            Reset OpCache
        </button>
    </div>
</form>
```

#### Inspeção de Chaves

```php
// Listar chaves com filtro
if (isset($_GET['inspect'])) {
    $pattern = $_GET['pattern'] ?? '*';
    $keys = getAllKeys($pattern);
    
    foreach ($keys as $key) {
        $value = $mem->get($key);
        $info = [
            'key' => $key,
            'size' => strlen(serialize($value)),
            'type' => gettype($value),
            'expires' => getExpiration($key)
        ];
        echo renderKeyInfo($info);
    }
}
```

## 🔧 Recursos Avançados

### Monitoramento em Tempo Real

#### Auto-refresh com AJAX

```javascript
// Auto-refresh das estatísticas
setInterval(function() {
    $.get('actions.php?action=get_stats', function(data) {
        updateDashboard(data);
    });
}, 30000); // 30 segundos

function updateDashboard(stats) {
    $('#hit-rate').text(stats.hit_rate + '%');
    $('#memory-usage').text(stats.memory_usage);
    $('#connections').text(stats.connections);
    
    // Atualizar gráficos
    updateCharts(stats);
}
```

#### WebSocket para Updates Live

```javascript
// Implementação futura com WebSockets
const ws = new WebSocket('ws://localhost:8080');
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    updateRealTimeStats(data);
};
```

### Alertas e Notificações

#### Sistema de Alertas

```php
class CacheAlerts {
    public function checkThresholds($stats) {
        $alerts = [];
        
        // Memory usage > 90%
        if ($stats['memory_usage_percent'] > 90) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Uso de memória alto: ' . $stats['memory_usage_percent'] . '%'
            ];
        }
        
        // Hit rate < 80%
        if ($stats['hit_rate'] < 80) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Hit rate baixo: ' . $stats['hit_rate'] . '%'
            ];
        }
        
        return $alerts;
    }
}
```

#### Notificações Email

```php
// Envio de alertas críticos
if ($critical_alert) {
    $to = 'admin@exemplo.com';
    $subject = 'ALERTA: Cache Monitor - ' . $alert['message'];
    $body = buildAlertEmail($alert, $stats);
    mail($to, $subject, $body);
}
```

### Exportação de Dados

#### Export JSON

```php
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="cache-stats-' . date('Y-m-d') . '.json"');
    
    $data = [
        'timestamp' => time(),
        'memcache' => getMemcacheStats(),
        'opcache' => getOpCacheStats(),
        'system' => getSystemInfo()
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
```

#### Export CSV

```php
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cache-keys-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Key', 'Size', 'Type', 'Expires']);
    
    foreach ($keys as $key => $info) {
        fputcsv($output, [$key, $info['size'], $info['type'], $info['expires']]);
    }
    
    fclose($output);
    exit;
}
```

## 🎨 Templates e Personalização

### Estrutura de Templates

```
templates/
├── header.php      # Cabeçalho comum
├── footer.php      # Rodapé comum
├── login.php       # Tela de login
└── dashboard.php   # Dashboard principal
```

### Personalização de Temas

#### CSS Personalizado

```css
/* templates/assets/custom.css */
:root {
    --primary-color: #3498db;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --dark-bg: #2c3e50;
    --light-bg: #ecf0f1;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}
```

#### Template Engine Simples

```php
class TemplateEngine {
    private $vars = [];
    
    public function assign($name, $value) {
        $this->vars[$name] = $value;
    }
    
    public function render($template) {
        extract($this->vars);
        ob_start();
        include "templates/{$template}.php";
        return ob_get_clean();
    }
}

// Uso
$tpl = new TemplateEngine();
$tpl->assign('stats', $memcache_stats);
$tpl->assign('alerts', $alerts);
echo $tpl->render('dashboard');
```

### Widgets Personalizados

#### Widget de Status

```php
<!-- templates/widgets/status_widget.php -->
<div class="status-widget">
    <h3>Status dos Serviços</h3>
    <div class="service-list">
        <?php foreach ($services as $service): ?>
        <div class="service-item <?= $service['status'] ?>">
            <span class="service-name"><?= $service['name'] ?></span>
            <span class="service-status"><?= $service['status'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

#### Widget de Gráficos

```javascript
// Chart.js integration
function createMemoryChart(data) {
    const ctx = document.getElementById('memoryChart').getContext('2d');
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Usado', 'Livre'],
            datasets: [{
                data: [data.used, data.free],
                backgroundColor: ['#e74c3c', '#2ecc71']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
```

## 🔧 Troubleshooting

### Problemas Comuns

#### Não Consegue Conectar ao Memcache

**Sintomas**: Erro "Connection refused" ou timeouts
**Soluções**:

1. **Verificar se Memcache está rodando**:
```bash
sudo systemctl status memcached
sudo netstat -tulpn | grep :11211
```

2. **Testar conectividade**:
```bash
telnet localhost 11211
stats
quit
```

3. **Verificar configuração**:
```php
// Teste de conexão
$mem = new Memcache();
if (!$mem->connect('127.0.0.1', 11211)) {
    die('Não foi possível conectar ao Memcache');
}
echo 'Conexão bem-sucedida!';
```

#### Problemas de Autenticação

**Sintomas**: Loop de login ou sessão expira rapidamente
**Soluções**:

1. **Verificar configuração de sessão**:
```php
// Adicionar ao config.php
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);
```

2. **Limpar cookies e cache do browser**

3. **Verificar permissões de sessão**:
```bash
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 755 /var/lib/php/sessions
```

#### OpCache Não Aparece

**Sintomas**: Aba OpCache vazia ou com erros
**Soluções**:

1. **Verificar se OpCache está habilitado**:
```bash
php -m | grep OPcache
```

2. **Verificar configuração PHP**:
```ini
; php.ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

3. **Reiniciar servidor web**:
```bash
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
```

#### Erro de Permissões

**Sintomas**: Erros 403 ou "Permission denied"
**Soluções**:

```bash
# Corrigir permissões
sudo chown -R www-data:www-data /var/www/cache_monitor
sudo chmod -R 755 /var/www/cache_monitor
sudo chmod 644 /var/www/cache_monitor/*.php
```

### Debug e Logs

#### Habilitar Debug

```php
// Adicionar ao início do config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
```

#### Log Personalizado

```php
function logDebug($message, $context = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];
    file_put_contents(
        __DIR__ . '/cache_monitor.log',
        json_encode($log) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

// Uso
logDebug('Tentativa de login', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
```

## ❓ FAQ

### Como adicionar novos servidores Memcache?

Edite o array `servers` no `config.php`:

```php
'servers' => [
    ['host' => '192.168.1.101', 'port' => 11211, 'name' => 'Server 1'],
    ['host' => '192.168.1.102', 'port' => 11211, 'name' => 'Server 2'],
]
```

### Posso monitorar Redis também?

Atualmente só Memcache e OpCache. Suporte a Redis pode ser adicionado criando um adapter:

```php
class RedisAdapter {
    public function getStats() {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        return $redis->info();
    }
}
```

### Como personalizar os filtros de prefixo?

Edite `prefix_filters` no config:

```php
'prefix_filters' => [
    'minhaapp' => 'minhaapp_*',
    'cache_api' => 'api_cache_*',
    'custom' => 'custom_prefix_*'
]
```

### É seguro usar em produção?

Sim, desde que configurado corretamente:
- Use HTTPS
- Restrinja IPs permitidos
- Use senhas fortes
- Configure firewall
- Monitore logs de acesso

### Como fazer backup das configurações?

```bash
# Backup simples
cp config.php config.backup.$(date +%Y%m%d).php

# Backup com criptografia
tar -czf cache_monitor_backup.tar.gz *.php templates/
gpg --cipher-algo AES256 --compress-algo 1 --s2k-mode 3 \
    --s2k-digest-algo SHA512 --s2k-count 65536 --force-mdc \
    --symmetric cache_monitor_backup.tar.gz
```

---

## 📞 Suporte

Para problemas técnicos:
- 📧 **Email**: suporte@hardmob.com.br
- 🌐 **Website**: https://hardmob.com.br
- 📝 **Issues**: Use as issues do repositório GitHub

---

**Cache Monitor - Desenvolvido pela equipe hardMOB**