# 📦 Guia de Instalação - Scripts Collection

Este guia fornece instruções detalhadas para instalar e configurar tanto o addon Afiliados quanto o Cache Monitor.

## 📋 Índice

- [Pré-requisitos](#pré-requisitos)
- [Instalação do Afiliados (XenForo)](#instalação-do-afiliados-xenforo)
- [Instalação do Cache Monitor](#instalação-do-cache-monitor)
- [Configuração de Ambiente](#configuração-de-ambiente)
- [Verificação de Instalação](#verificação-de-instalação)
- [Configuração Pós-instalação](#configuração-pós-instalação)

## 🔧 Pré-requisitos

### Para Afiliados (XenForo Addon)

- **XenForo**: 2.2.17 ou superior
- **PHP**: 8.1.0 ou superior
- **MySQL/MariaDB**: 5.7+/10.2+
- **Extensões PHP**: curl, json, mbstring, mysql/mysqli
- **Acesso**: Admin CP do XenForo
- **Espaço em Disco**: 10MB mínimo

### Para Cache Monitor

- **PHP**: 7.4 ou superior
- **Servidor Web**: Apache/Nginx
- **Extensões PHP**: memcache, opcache, json, session
- **Cache Systems**: Memcache e/ou OpCache instalados
- **Espaço em Disco**: 5MB mínimo

### Verificação de Requisitos

Execute este script para verificar os requisitos:

```bash
#!/bin/bash
echo "=== Verificação de Requisitos ==="

# Verificar PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

# Verificar extensões PHP
echo "Extensões PHP:"
php -m | grep -E "(curl|json|mbstring|mysql|memcache|opcache)" | sort

# Verificar Memcache
if systemctl is-active --quiet memcached; then
    echo "✅ Memcached está ativo"
else
    echo "❌ Memcached não está ativo"
fi

# Verificar OpCache
if php -m | grep -q OPcache; then
    echo "✅ OpCache está habilitado"
else
    echo "❌ OpCache não está habilitado"
fi

echo "=== Fim da Verificação ==="
```

## 🔗 Instalação do Afiliados (XenForo)

### Método 1: Instalação via Admin CP (Recomendado)

#### Passo 1: Preparar Arquivos

```bash
# 1. Download do repositório
git clone https://github.com/mojiba/scripts.git
cd scripts/afiliados

# 2. Criar arquivo ZIP
zip -r hardmob-afiliados.zip . -x "*.git*" "*.md" "_output/*"
```

#### Passo 2: Upload e Instalação

1. **Faça login** no Admin CP do XenForo
2. **Navegue** para `Add-ons` → `Install from archive`
3. **Selecione** o arquivo `hardmob-afiliados.zip`
4. **Clique** em `Install add-on`
5. **Confirme** a instalação na tela seguinte

#### Passo 3: Verificar Instalação

Após a instalação, verifique:

- ✅ Addon aparece na lista de Add-ons ativos
- ✅ Menu "hardMOB Afiliados" aparece no Admin CP
- ✅ Novas tabelas foram criadas no banco de dados:
  - `xf_hardmob_affiliate_stores`
  - `xf_hardmob_affiliate_clicks`
  - `xf_hardmob_affiliate_cache`

### Método 2: Instalação Manual

#### Passo 1: Upload de Arquivos

```bash
# No servidor XenForo
cd /path/to/xenforo/src/addons

# Criar estrutura de diretórios
mkdir -p hardMOB/Afiliados

# Upload dos arquivos (via FTP, SCP, etc.)
# Estrutura final deve ser:
# src/addons/hardMOB/Afiliados/
#   ├── addon.json
#   ├── Setup.php
#   ├── Admin/
#   ├── Pub/
#   └── ...
```

#### Passo 2: Instalação via CLI

```bash
cd /path/to/xenforo

# Instalar addon
php cmd.php xf-addon:install hardMOB/Afiliados

# Verificar instalação
php cmd.php xf-addon:list | grep Afiliados
```

### Método 3: Instalação via Desenvolvimento

Para desenvolvimento ou teste:

```bash
# 1. Ativar modo de desenvolvimento
echo '$config[\'development\'][\'enabled\'] = true;' >> config.php

# 2. Criar link simbólico (Linux/Mac)
ln -s /path/to/development/hardMOB /path/to/xenforo/src/addons/hardMOB

# 3. Instalar
php cmd.php xf-addon:install hardMOB/Afiliados --dev
```

### Resolução de Problemas na Instalação

#### Erro: "Table already exists"

```sql
-- Verificar tabelas existentes
SHOW TABLES LIKE 'xf_hardmob_affiliate_%';

-- Se necessário, remover tabelas antigas
DROP TABLE IF EXISTS xf_hardmob_affiliate_stores;
DROP TABLE IF EXISTS xf_hardmob_affiliate_clicks;
DROP TABLE IF EXISTS xf_hardmob_affiliate_cache;
```

#### Erro: "Route conflicts"

```bash
# Limpar cache de rotas
php cmd.php xf:rebuild-caches

# Verificar rotas conflitantes
php cmd.php xf-dev:export --addon=hardMOB/Afiliados routes
```

## 📊 Instalação do Cache Monitor

### Método 1: Instalação Standalone

#### Passo 1: Preparar Ambiente

```bash
# 1. Criar diretório
sudo mkdir -p /var/www/cache-monitor
cd /var/www/cache-monitor

# 2. Download dos arquivos
git clone https://github.com/mojiba/scripts.git temp
cp -r temp/cache_monitor/* .
rm -rf temp

# 3. Configurar permissões
sudo chown -R www-data:www-data .
sudo chmod 755 .
sudo chmod 644 *.php
```

#### Passo 2: Configuração Inicial

```bash
# 1. Criar arquivo de configuração
cp config.php.example config.php

# 2. Editar configurações
nano config.php
```

Configuração mínima:

```php
<?php
return [
    'auth' => [
        'username' => 'admin',
        'password' => password_hash('sua_senha_forte', PASSWORD_DEFAULT),
    ],
    'servers' => [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
            'name' => 'Local Memcache'
        ]
    ],
    'security' => [
        'enable_csrf' => true,
        'require_https' => true, // true em produção
    ]
];
```

#### Passo 3: Configurar Servidor Web

**Apache Virtual Host**:

```apache
# /etc/apache2/sites-available/cache-monitor.conf
<VirtualHost *:80>
    ServerName cache-monitor.exemplo.com
    DocumentRoot /var/www/cache-monitor
    
    <Directory /var/www/cache-monitor>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirecionar para HTTPS
    RewriteEngine On
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName cache-monitor.exemplo.com
    DocumentRoot /var/www/cache-monitor
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/cache-monitor.crt
    SSLCertificateKeyFile /etc/ssl/private/cache-monitor.key
    
    <Directory /var/www/cache-monitor>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx Configuration**:

```nginx
# /etc/nginx/sites-available/cache-monitor
server {
    listen 80;
    server_name cache-monitor.exemplo.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cache-monitor.exemplo.com;
    
    root /var/www/cache-monitor;
    index index.php;
    
    ssl_certificate /etc/ssl/certs/cache-monitor.crt;
    ssl_certificate_key /etc/ssl/private/cache-monitor.key;
    
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

#### Passo 4: Ativar Site

```bash
# Apache
sudo a2ensite cache-monitor
sudo systemctl reload apache2

# Nginx
sudo ln -s /etc/nginx/sites-available/cache-monitor /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Método 2: Instalação como Subdiretório

Para instalar como subdiretório de um site existente:

```bash
# 1. Criar subdiretório
cd /var/www/html
sudo mkdir cache-monitor

# 2. Copiar arquivos
sudo cp -r /path/to/scripts/cache_monitor/* cache-monitor/

# 3. Configurar permissões
sudo chown -R www-data:www-data cache-monitor/
```

Acessível em: `https://seusite.com/cache-monitor/`

## 🌐 Configuração de Ambiente

### Configuração PHP

#### php.ini Recomendado

```ini
; Configurações gerais
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M

; Sessões
session.cookie_lifetime = 3600
session.gc_maxlifetime = 3600
session.save_path = "/var/lib/php/sessions"

; OpCache
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2

; Memcache
extension = memcache.so
```

### Configuração Memcache

#### /etc/memcached.conf

```ini
# Configuração básica
-d
-m 64
-p 11211
-u memcache
-l 127.0.0.1

# Para produção
-m 512
-c 1024
-I 4m
```

#### Teste de Configuração

```bash
# Verificar status
sudo systemctl status memcached

# Teste de conectividade
echo "stats" | nc localhost 11211

# Teste PHP
php -r "
\$m = new Memcache();
\$m->connect('localhost', 11211);
echo 'Memcache OK: ' . \$m->getVersion();
"
```

### Configuração de Segurança

#### Firewall (UFW)

```bash
# Permitir apenas IPs específicos
sudo ufw allow from 192.168.1.0/24 to any port 80
sudo ufw allow from 192.168.1.0/24 to any port 443

# Bloquear acesso direto ao Memcache
sudo ufw deny 11211
```

#### SSL/TLS

```bash
# Gerar certificado self-signed para teste
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/cache-monitor.key \
    -out /etc/ssl/certs/cache-monitor.crt

# Para produção, use Let's Encrypt
sudo certbot --nginx -d cache-monitor.exemplo.com
```

## ✅ Verificação de Instalação

### Script de Verificação

Crie um script para verificar se tudo está funcionando:

```bash
#!/bin/bash
# verify_installation.sh

echo "=== Verificação de Instalação ==="

# Verificar Afiliados (XenForo)
if [ -f "/path/to/xenforo/src/addons/hardMOB/Afiliados/addon.json" ]; then
    echo "✅ Afiliados: Arquivos encontrados"
    
    # Verificar tabelas
    mysql -u user -p database -e "SHOW TABLES LIKE 'xf_hardmob_affiliate_%';" | grep -q affiliate
    if [ $? -eq 0 ]; then
        echo "✅ Afiliados: Tabelas criadas"
    else
        echo "❌ Afiliados: Tabelas não encontradas"
    fi
else
    echo "❌ Afiliados: Arquivos não encontrados"
fi

# Verificar Cache Monitor
if [ -f "/var/www/cache-monitor/index.php" ]; then
    echo "✅ Cache Monitor: Arquivos encontrados"
    
    # Teste HTTP
    if curl -s "http://localhost/cache-monitor/" | grep -q "Cache Monitor"; then
        echo "✅ Cache Monitor: Acessível via HTTP"
    else
        echo "❌ Cache Monitor: Não acessível via HTTP"
    fi
else
    echo "❌ Cache Monitor: Arquivos não encontrados"
fi

# Verificar Memcache
if systemctl is-active --quiet memcached; then
    echo "✅ Memcached: Serviço ativo"
else
    echo "❌ Memcached: Serviço inativo"
fi

# Verificar OpCache
if php -m | grep -q OPcache; then
    echo "✅ OpCache: Extensão carregada"
else
    echo "❌ OpCache: Extensão não carregada"
fi

echo "=== Fim da Verificação ==="
```

### Testes Funcionais

#### Teste do Afiliados

```php
// Via console XenForo
php cmd.php xf-dev:run-code '
$finder = \XF::finder("hardMOB\Afiliados:Store");
$stores = $finder->fetch();
echo "Lojas encontradas: " . count($stores) . "\n";
foreach ($stores as $store) {
    echo "- " . $store->name . " (" . $store->domain . ")\n";
}
'
```

#### Teste do Cache Monitor

```bash
# Teste de login
curl -c cookies.txt -d "username=admin&password=sua_senha&login=1" \
     "http://localhost/cache-monitor/"

# Teste de stats
curl -b cookies.txt "http://localhost/cache-monitor/actions.php?action=get_stats"
```

## 🔧 Configuração Pós-instalação

### Configuração do Afiliados

1. **Acesse** `Options` → `hardMOB Afiliados`
2. **Configure**:
   - Cache Driver: `file` ou `redis`
   - Cache TTL: `3600` (1 hora)
   - Enable Cron Jobs: `✓`

3. **Adicione lojas** em `hardMOB Afiliados` → `Gerenciar Lojas`

4. **Configure cron jobs**:
```bash
# Adicionar ao crontab
*/30 * * * * php /path/to/xenforo/cmd.php xf:run-jobs > /dev/null 2>&1
```

### Configuração do Cache Monitor

1. **Primeiro acesso**: Use credenciais configuradas no `config.php`
2. **Teste conectividade** com servidores Memcache
3. **Configure alertas** e notificações se necessário
4. **Ajuste filtros** de prefixos conforme sua aplicação

### Backup e Monitoramento

#### Script de Backup

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backups/scripts-$(date +%Y%m%d)"
mkdir -p "$BACKUP_DIR"

# Backup Afiliados
cp -r /path/to/xenforo/src/addons/hardMOB/Afiliados "$BACKUP_DIR/"

# Backup Cache Monitor
cp -r /var/www/cache-monitor "$BACKUP_DIR/"

# Backup banco de dados (Afiliados)
mysqldump -u user -p database \
  --tables xf_hardmob_affiliate_stores xf_hardmob_affiliate_clicks xf_hardmob_affiliate_cache \
  > "$BACKUP_DIR/affiliate_tables.sql"

echo "Backup criado em: $BACKUP_DIR"
```

#### Monitoramento de Logs

```bash
# Monitorar logs do XenForo
tail -f /path/to/xenforo/internal_data/logs/xf.log | grep -i affiliate

# Monitorar logs do Apache/Nginx
tail -f /var/log/apache2/access.log | grep cache-monitor
tail -f /var/log/nginx/access.log | grep cache-monitor
```

---

## 📞 Suporte

Se encontrar problemas durante a instalação:

1. **Verifique os logs** de erro do servidor web e PHP
2. **Execute o script de verificação** incluído neste guia
3. **Consulte a seção Troubleshooting** nos READMEs específicos
4. **Entre em contato**: suporte@hardmob.com.br

---

**Guia de Instalação - Scripts Collection**  
**Desenvolvido pela equipe hardMOB**