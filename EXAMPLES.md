# 📝 Exemplos de Integração - Scripts Collection

Este guia fornece exemplos práticos de como integrar e usar tanto o addon Afiliados quanto o Cache Monitor em diferentes cenários.

## 📋 Índice

- [Afiliados - Exemplos BBCode](#afiliados---exemplos-bbcode)
- [Afiliados - Exemplos Templates](#afiliados---exemplos-templates)
- [Afiliados - Integrações Avançadas](#afiliados---integrações-avançadas)
- [Cache Monitor - Integrações](#cache-monitor---integrações)
- [Casos de Uso Reais](#casos-de-uso-reais)

## 🔗 Afiliados - Exemplos BBCode

### BBCode Básico para Links

#### Exemplo 1: BBCode Simples

**Configuração no Admin CP:**
- **Nome**: `amazon`
- **Replacement**: 
```html
<a href="/affiliate/1/{{ base64_encode({text}) }}" target="_blank" rel="nofollow" class="affiliate-link amazon">
    <i class="fab fa-amazon"></i> Ver na Amazon
</a>
```

**Uso no post:**
```bbcode
Confira este produto incrível: [amazon]/dp/B08N5WRWNW[/amazon]
```

**Resultado:**
```html
<a href="/affiliate/1/L2RwL0IwOE41V1JXTlc=" target="_blank" rel="nofollow" class="affiliate-link amazon">
    <i class="fab fa-amazon"></i> Ver na Amazon
</a>
```

#### Exemplo 2: BBCode com Preview de Produto

**Configuração:**
- **Nome**: `produto`
- **Replacement**:
```html
<div class="product-preview" data-store="{option}" data-slug="{text}">
    <div class="product-loading">Carregando informações do produto...</div>
    <a href="/affiliate/{option}/{{ base64_encode({text}) }}" target="_blank" rel="nofollow" class="product-link">
        Ver Produto
    </a>
</div>
```

**JavaScript associado:**
```javascript
// Adicionar ao template principal
$(document).ready(function() {
    $('.product-preview').each(function() {
        var $preview = $(this);
        var store = $preview.data('store');
        var slug = $preview.data('slug');
        
        // Carregar informações do produto via AJAX
        $.get('/api/product-info', {
            store: store,
            slug: slug
        }).done(function(data) {
            $preview.html(
                '<div class="product-info">' +
                    '<img src="' + data.image + '" alt="' + data.title + '">' +
                    '<h4>' + data.title + '</h4>' +
                    '<p class="price">' + data.price + '</p>' +
                    '<a href="/affiliate/' + store + '/' + btoa(slug) + '" target="_blank" class="buy-button">Comprar Agora</a>' +
                '</div>'
            );
        });
    });
});
```

**Uso:**
```bbcode
[produto=1]/dp/B08N5WRWNW[/produto]
```

### BBCode Avançado com Múltiplas Opções

#### Exemplo 3: Comparador de Preços

**Configuração:**
- **Nome**: `comparar`
- **Replacement**:
```html
<div class="price-comparison" data-products="{text}">
    <h4>Comparar Preços:</h4>
    <div class="comparison-loading">Carregando comparação...</div>
</div>
```

**JavaScript:**
```javascript
$('.price-comparison').each(function() {
    var $comp = $(this);
    var products = $comp.data('products').split('|');
    var html = '<div class="comparison-grid">';
    
    products.forEach(function(product) {
        var parts = product.split(':');
        var store = parts[0];
        var slug = parts[1];
        
        html += '<div class="comparison-item" data-store="' + store + '" data-slug="' + slug + '">';
        html += '<div class="store-logo store-' + store + '"></div>';
        html += '<div class="price-info">Carregando...</div>';
        html += '<a href="/affiliate/' + store + '/' + btoa(slug) + '" target="_blank" class="comparison-link">Ver</a>';
        html += '</div>';
    });
    
    html += '</div>';
    $comp.html(html);
    
    // Carregar preços individuais
    $comp.find('.comparison-item').each(function() {
        var $item = $(this);
        loadPriceInfo($item);
    });
});

function loadPriceInfo($item) {
    var store = $item.data('store');
    var slug = $item.data('slug');
    
    $.get('/api/price-info', {
        store: store,
        slug: slug
    }).done(function(data) {
        $item.find('.price-info').html(
            '<span class="price">' + data.price + '</span>' +
            '<span class="shipping">' + data.shipping + '</span>'
        );
    });
}
```

**Uso:**
```bbcode
[comparar]1:/dp/B08N5WRWNW|2:MLB-123456789|3:produto-i.123.456[/comparar]
```

### CSS para BBCodes

```css
/* Estilos para links de afiliados */
.affiliate-link {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    margin: 5px 0;
}

.affiliate-link.amazon {
    background: linear-gradient(45deg, #ff9900, #ff6600);
    color: white;
}

.affiliate-link.mercadolivre {
    background: linear-gradient(45deg, #ffe600, #ffcc00);
    color: #333;
}

.affiliate-link.shopee {
    background: linear-gradient(45deg, #ee4d2d, #ff6b35);
    color: white;
}

.affiliate-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Product Preview */
.product-preview {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    background: #f9f9f9;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-info img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.product-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.product-info .price {
    font-size: 18px;
    font-weight: bold;
    color: #e74c3c;
}

.buy-button {
    background: #2ecc71;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
}

/* Price Comparison */
.comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.comparison-item {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    background: white;
}

.store-logo {
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
}

.store-1 { background-image: url('/images/amazon-logo.png'); }
.store-2 { background-image: url('/images/ml-logo.png'); }
.store-3 { background-image: url('/images/shopee-logo.png'); }

.price {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #e74c3c;
}

.shipping {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
```

## 🎨 Afiliados - Exemplos Templates

### Template de Widget Lateral

#### Widget de Produtos Recomendados

**Template: `affiliate_widget_recommended`**
```html
<xf:if is="$xf.options.affiliateEnabled">
    <div class="block block--outer">
        <div class="block-container">
            <h3 class="block-header">
                <a href="{{ link('affiliate/recommended') }}">Produtos Recomendados</a>
            </h3>
            <div class="block-body">
                <xf:foreach loop="$affiliateRecommended" value="$product">
                    <div class="affiliate-widget-item">
                        <div class="product-thumb">
                            <img src="{$product.image}" alt="{$product.title}" loading="lazy" />
                        </div>
                        <div class="product-info">
                            <h4 class="product-title">{$product.title}</h4>
                            <div class="product-price">{$product.price}</div>
                            <div class="product-store">
                                <img src="/images/store-{$product.store_id}.png" alt="{$product.store_name}" />
                                {$product.store_name}
                            </div>
                            <a href="{{ link('affiliate/redirect', $product) }}" 
                               target="_blank" 
                               rel="nofollow"
                               class="button button--small button--primary"
                               data-affiliate-click="recommended">
                                Ver Oferta
                            </a>
                        </div>
                    </div>
                </xf:foreach>
            </div>
        </div>
    </div>
</xf:if>
```

**CSS associado:**
```css
.affiliate-widget-item {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.affiliate-widget-item:last-child {
    border-bottom: none;
}

.product-thumb {
    width: 60px;
    height: 60px;
    margin-right: 10px;
    flex-shrink: 0;
}

.product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.product-info {
    flex: 1;
}

.product-title {
    font-size: 14px;
    line-height: 1.3;
    margin: 0 0 5px 0;
    font-weight: normal;
}

.product-price {
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 5px;
}

.product-store {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.product-store img {
    width: 16px;
    height: 16px;
    margin-right: 5px;
}
```

### Template de Post Enhancement

#### Processamento Automático de Links

**Template: `message_body` (modificação)**
```html
<!-- Versão original -->
<div class="message-content js-messageContent">
    {$content|raw}
</div>

<!-- Versão com processamento de afiliados -->
<xf:set var="$processedContent" value="{{ affiliate_process($content, $message.User) }}" />

<div class="message-content js-messageContent">
    <xf:if is="$processedContent !== $content">
        <!-- Indicador de que links foram processados -->
        <div class="affiliate-notice">
            <i class="fas fa-link"></i> Links de afiliados detectados
        </div>
    </xf:if>
    
    {$processedContent|raw}
</div>
```

#### Template de Estatísticas de Usuário

**Template: `affiliate_user_stats`**
```html
<xf:if is="$xf.visitor.is_admin OR $user.user_id == $xf.visitor.user_id">
    <div class="block">
        <div class="block-container">
            <h3 class="block-header">Estatísticas de Afiliados</h3>
            <div class="block-body">
                <dl class="pairs pairs--rows">
                    <dt>Links criados este mês</dt>
                    <dd>{$affiliateStats.links_month|number}</dd>
                    
                    <dt>Cliques recebidos</dt>
                    <dd>{$affiliateStats.clicks_total|number}</dd>
                    
                    <dt>Taxa de clique média</dt>
                    <dd>{$affiliateStats.click_rate}%</dd>
                    
                    <dt>Loja mais popular</dt>
                    <dd>{$affiliateStats.top_store}</dd>
                </dl>
                
                <xf:if is="$affiliateStats.clicks_today > 0">
                    <div class="affiliate-today">
                        <strong>{$affiliateStats.clicks_today}</strong> cliques hoje
                    </div>
                </xf:if>
            </div>
        </div>
    </div>
</xf:if>
```

### Template de Dashboard Admin

**Template: `affiliate_admin_dashboard`**
```html
<xf:title>Dashboard de Afiliados</xf:title>

<div class="affiliate-dashboard">
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total de Cliques</h3>
            <div class="stat-number">{$stats.total_clicks|number}</div>
            <div class="stat-change {$stats.clicks_change >= 0 ? 'positive' : 'negative'}">
                {$stats.clicks_change > 0 ? '+' : ''}{$stats.clicks_change}% este mês
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Links Ativos</h3>
            <div class="stat-number">{$stats.active_links|number}</div>
        </div>
        
        <div class="stat-card">
            <h3>Lojas Configuradas</h3>
            <div class="stat-number">{$stats.total_stores|number}</div>
        </div>
        
        <div class="stat-card">
            <h3>Cache Hit Rate</h3>
            <div class="stat-number">{$stats.cache_hit_rate}%</div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-container">
            <h4>Cliques por Dia (Últimos 30 dias)</h4>
            <canvas id="clicksChart" width="400" height="200"></canvas>
        </div>
        
        <div class="chart-container">
            <h4>Top 5 Lojas</h4>
            <canvas id="storesChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="recent-activity">
        <h4>Atividade Recente</h4>
        <div class="activity-list">
            <xf:foreach loop="$recentClicks" value="$click">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="activity-content">
                        <strong>{$click.User.username}</strong> clicou em 
                        <strong>{$click.Store.name}</strong>
                        <div class="activity-time">
                            <xf:date time="{$click.click_date}" />
                        </div>
                    </div>
                    <div class="activity-meta">
                        <span class="product-slug">{$click.product_slug}</span>
                    </div>
                </div>
            </xf:foreach>
        </div>
    </div>
</div>

<xf:js>
// Chart.js para gráficos
const clicksData = {$clicksChartData|json};
const storesData = {$storesChartData|json};

// Gráfico de cliques
const clicksCtx = document.getElementById('clicksChart').getContext('2d');
new Chart(clicksCtx, {
    type: 'line',
    data: clicksData,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de lojas
const storesCtx = document.getElementById('storesChart').getContext('2d');
new Chart(storesCtx, {
    type: 'doughnut',
    data: storesData,
    options: {
        responsive: true
    }
});
</xf:js>
```

## 🔧 Afiliados - Integrações Avançadas

### Integração com Google Analytics

#### Tracking de Eventos

```javascript
// Adicionar ao template principal
$(document).on('click', 'a[href*="/affiliate/"]', function() {
    var $link = $(this);
    var href = $link.attr('href');
    var matches = href.match(/\/affiliate\/(\d+)\/(.+)/);
    
    if (matches && typeof gtag !== 'undefined') {
        gtag('event', 'affiliate_click', {
            'event_category': 'affiliate',
            'event_label': 'store_' + matches[1],
            'value': 1
        });
    }
});
```

#### Enhanced Ecommerce Tracking

```javascript
// Para tracking de compras (requer implementação adicional)
function trackAffiliateConversion(orderId, amount, storeId) {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'purchase', {
            'transaction_id': orderId,
            'value': amount,
            'currency': 'BRL',
            'custom_parameters': {
                'affiliate_store': storeId
            }
        });
    }
}
```

### Integração com API de Produtos

#### Sincronização Automática de Preços

```php
// Service/ProductSync.php
<?php
namespace hardMOB\Afiliados\Service;

class ProductSync
{
    public function syncAmazonPrices()
    {
        $amazonStores = $this->getStoresByType('amazon');
        
        foreach ($amazonStores as $store) {
            $products = $this->getPopularProducts($store);
            
            foreach ($products as $product) {
                $priceInfo = $this->getAmazonPrice($product['asin']);
                
                if ($priceInfo) {
                    $this->updateProductCache($product['id'], $priceInfo);
                }
            }
        }
    }
    
    private function getAmazonPrice($asin)
    {
        // Implementar integração com Amazon Product Advertising API
        // ou serviço de scraping autorizado
        
        $apiKey = \XF::options()->amazonApiKey;
        $secretKey = \XF::options()->amazonSecretKey;
        
        // Fazer requisição à API
        $response = $this->makeApiRequest($asin, $apiKey, $secretKey);
        
        return [
            'price' => $response['price'],
            'availability' => $response['availability'],
            'title' => $response['title'],
            'image' => $response['image']
        ];
    }
}
```

### Sistema de Notificações

#### Notificações de Promoções

```php
// Listener.php - adicionar listener
public static function processPromotion($message, &$isSpam, &$isApproved)
{
    $content = $message->message;
    
    // Detectar palavras-chave de promoção
    $promoKeywords = ['promoção', 'desconto', 'oferta', 'black friday'];
    
    foreach ($promoKeywords as $keyword) {
        if (stripos($content, $keyword) !== false) {
            // Enviar notificação para usuários interessados
            \XF::service('hardMOB\Afiliados:Notification')
                ->notifyPromotion($message);
            break;
        }
    }
}
```

## 📊 Cache Monitor - Integrações

### Integração com Monitoramento

#### Prometheus Metrics

```php
// metrics.php - endpoint para Prometheus
<?php
require_once 'config.php';
require_once 'memcache_utils.php';

header('Content-Type: text/plain; charset=utf-8');

$stats = getMemcacheStats();
$opcache_stats = opcache_get_status();

// Memcache metrics
echo "# HELP memcache_hits_total Total number of cache hits\n";
echo "# TYPE memcache_hits_total counter\n";
echo "memcache_hits_total " . $stats['get_hits'] . "\n";

echo "# HELP memcache_misses_total Total number of cache misses\n";
echo "# TYPE memcache_misses_total counter\n";
echo "memcache_misses_total " . $stats['get_misses'] . "\n";

echo "# HELP memcache_memory_used_bytes Memory currently used by cache\n";
echo "# TYPE memcache_memory_used_bytes gauge\n";
echo "memcache_memory_used_bytes " . $stats['bytes'] . "\n";

// OpCache metrics
echo "# HELP opcache_memory_used_bytes OpCache memory usage\n";
echo "# TYPE opcache_memory_used_bytes gauge\n";
echo "opcache_memory_used_bytes " . $opcache_stats['memory_usage']['used_memory'] . "\n";

echo "# HELP opcache_hit_rate OpCache hit rate percentage\n";
echo "# TYPE opcache_hit_rate gauge\n";
$hit_rate = $opcache_stats['opcache_statistics']['opcache_hit_rate'];
echo "opcache_hit_rate " . $hit_rate . "\n";
?>
```

#### Grafana Dashboard

```json
{
  "dashboard": {
    "title": "Cache Monitor Dashboard",
    "panels": [
      {
        "title": "Memcache Hit Rate",
        "type": "stat",
        "targets": [
          {
            "expr": "rate(memcache_hits_total[5m]) / (rate(memcache_hits_total[5m]) + rate(memcache_misses_total[5m])) * 100"
          }
        ]
      },
      {
        "title": "Memory Usage",
        "type": "timeseries",
        "targets": [
          {
            "expr": "memcache_memory_used_bytes"
          },
          {
            "expr": "opcache_memory_used_bytes"
          }
        ]
      }
    ]
  }
}
```

### API REST para Cache Monitor

```php
// api.php - API RESTful
<?php
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json');

// Verificar autenticação via token
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!validateApiToken($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($endpoint) {
    case 'stats':
        if ($method === 'GET') {
            echo json_encode([
                'memcache' => getMemcacheStats(),
                'opcache' => opcache_get_status(),
                'timestamp' => time()
            ]);
        }
        break;
        
    case 'clear':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $prefix = $input['prefix'] ?? '';
            
            $result = clearCacheByPrefix($prefix);
            echo json_encode(['success' => $result]);
        }
        break;
        
    case 'keys':
        if ($method === 'GET') {
            $pattern = $_GET['pattern'] ?? '*';
            $keys = getAllKeys($pattern);
            echo json_encode(['keys' => $keys]);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function validateApiToken($token) {
    global $config;
    $validTokens = $config['api_tokens'] ?? [];
    return in_array(str_replace('Bearer ', '', $token), $validTokens);
}
?>
```

### Integração com Slack

#### Alertas via Webhook

```php
// alerts.php
<?php
function sendSlackAlert($message, $severity = 'warning') {
    global $config;
    
    $webhook_url = $config['slack_webhook'];
    if (!$webhook_url) return false;
    
    $color = [
        'info' => '#36a64f',
        'warning' => '#ff9900',
        'critical' => '#ff0000'
    ][$severity] ?? '#ff9900';
    
    $payload = [
        'channel' => '#alerts',
        'username' => 'Cache Monitor',
        'attachments' => [
            [
                'color' => $color,
                'title' => 'Cache Alert',
                'text' => $message,
                'timestamp' => time()
            ]
        ]
    ];
    
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result !== false;
}

// Verificar alertas
$stats = getMemcacheStats();

if ($stats['memory_usage_percent'] > 90) {
    sendSlackAlert(
        "Memcache memory usage is at {$stats['memory_usage_percent']}%", 
        'critical'
    );
}

if ($stats['hit_rate'] < 80) {
    sendSlackAlert(
        "Memcache hit rate dropped to {$stats['hit_rate']}%", 
        'warning'
    );
}
?>
```

## 🎯 Casos de Uso Reais

### Caso 1: Fórum de Tecnologia

**Cenário**: Fórum com reviews de produtos eletrônicos
**Implementação**:

1. **BBCode personalizado para reviews**:
```bbcode
[review produto="1:/dp/B08N5WRWNW" nota="4.5"]
Este produto é excelente para gaming. A qualidade de construção é muito boa.

**Prós:**
- Boa performance
- Preço justo

**Contras:**
- Um pouco barulhento

[comparar]1:/dp/B08N5WRWNW|2:MLB-123456789[/comparar]
[/review]
```

2. **Template de review**:
```html
<div class="tech-review">
    <div class="review-header">
        <div class="product-info" data-product="{$produto}"></div>
        <div class="rating">{$nota}/5 ⭐</div>
    </div>
    <div class="review-content">
        {$content|raw}
    </div>
</div>
```

### Caso 2: Site de Cupons

**Cenário**: Sistema de cupons e ofertas
**Implementação**:

1. **Widget de ofertas quentes**:
```html
<div class="hot-deals">
    <h3>🔥 Ofertas Quentes</h3>
    <div class="deals-grid">
        <xf:foreach loop="$hotDeals" value="$deal">
            <div class="deal-item" data-expires="{$deal.expires}">
                <div class="discount-badge">{$deal.discount}% OFF</div>
                <img src="{$deal.image}" alt="{$deal.title}" />
                <h4>{$deal.title}</h4>
                <div class="prices">
                    <span class="old-price">R$ {$deal.old_price}</span>
                    <span class="new-price">R$ {$deal.new_price}</span>
                </div>
                <a href="/affiliate/{$deal.store_id}/{$deal.encoded_slug}" 
                   class="deal-button"
                   data-deal-id="{$deal.id}">
                    Aproveitar Oferta
                </a>
                <div class="expires">Expira em: <span class="countdown"></span></div>
            </div>
        </xf:foreach>
    </div>
</div>
```

2. **JavaScript para countdown**:
```javascript
$('.deal-item').each(function() {
    var $item = $(this);
    var expires = parseInt($item.data('expires'));
    
    var countdown = setInterval(function() {
        var now = Math.floor(Date.now() / 1000);
        var diff = expires - now;
        
        if (diff <= 0) {
            $item.addClass('expired');
            clearInterval(countdown);
            return;
        }
        
        var hours = Math.floor(diff / 3600);
        var minutes = Math.floor((diff % 3600) / 60);
        var seconds = diff % 60;
        
        $item.find('.countdown').text(
            hours + 'h ' + minutes + 'm ' + seconds + 's'
        );
    }, 1000);
});
```

### Caso 3: Blog de Lifestyle

**Cenário**: Blog com posts sobre produtos de lifestyle
**Implementação**:

1. **Template de "Look do Dia"**:
```html
<div class="outfit-post">
    <div class="outfit-image">
        <img src="{$outfit.image}" alt="Look do Dia" />
        <div class="product-hotspots">
            <xf:foreach loop="$outfit.products" value="$product">
                <div class="hotspot" 
                     style="top: {$product.y}%; left: {$product.x}%;"
                     data-product="{$product.affiliate_link}">
                    <div class="hotspot-popup">
                        <img src="{$product.thumb}" />
                        <div class="product-details">
                            <h5>{$product.name}</h5>
                            <p class="price">{$product.price}</p>
                            <a href="{$product.affiliate_link}" class="shop-button">
                                Comprar
                            </a>
                        </div>
                    </div>
                </div>
            </xf:foreach>
        </div>
    </div>
    
    <div class="outfit-description">
        {$content|raw}
    </div>
    
    <div class="shop-the-look">
        <h4>🛍️ Compre o Look</h4>
        <div class="products-grid">
            <xf:foreach loop="$outfit.products" value="$product">
                <div class="product-card">
                    <img src="{$product.image}" />
                    <h5>{$product.name}</h5>
                    <p class="price">{$product.price}</p>
                    <a href="{$product.affiliate_link}" class="product-link">
                        Ver Produto
                    </a>
                </div>
            </xf:foreach>
        </div>
    </div>
</div>
```

### Caso 4: Cache Monitor para E-commerce

**Cenário**: Monitoramento de cache em loja online
**Configuração**:

```php
// config.php específico para e-commerce
return [
    'prefix_filters' => [
        'products' => 'product_*',
        'categories' => 'cat_*',
        'users' => 'user_session_*',
        'cart' => 'cart_*',
        'search' => 'search_*',
        'all' => '*'
    ],
    
    'alerts' => [
        'memory_threshold' => 85,
        'hit_rate_threshold' => 75,
        'check_interval' => 300, // 5 minutos
    ],
    
    'auto_clear' => [
        'expired_sessions' => true,
        'old_search_cache' => true,
        'schedule' => '0 2 * * *' // 2:00 AM diário
    ]
];
```

---

## 📞 Suporte

Para dúvidas sobre implementação dos exemplos:
- 📧 **Email**: suporte@hardmob.com.br
- 🌐 **Website**: https://hardmob.com.br
- 📝 **Issues**: Use as issues do repositório GitHub

---

**Exemplos de Integração - Scripts Collection**  
**Desenvolvido pela equipe hardMOB**