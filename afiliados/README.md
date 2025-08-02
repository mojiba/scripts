# 🔗 hardMOB Afiliados - XenForo Addon

Sistema completo de gestão de links de afiliados para XenForo 2.2.17+ com recursos avançados de cache, estatísticas e conectores para múltiplos marketplaces.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Uso Básico](#uso-básico)
- [Integração BBCode](#integração-bbcode)
- [Integração Templates](#integração-templates)
- [Conectores Disponíveis](#conectores-disponíveis)
- [Interface Administrativa](#interface-administrativa)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

## 🎯 Visão Geral

O hardMOB Afiliados é um addon completo que automatiza o gerenciamento de links de afiliados em fóruns XenForo. Ele detecta e reescreve URLs para inserir códigos de afiliado, gera links via cron jobs, implementa cache inteligente e registra estatísticas detalhadas de cliques.

### Principais Funcionalidades

- **🏪 Gestão de Lojas**: CRUD completo com criação automática de conectores
- **🔄 Processamento de Links**: Substituição automática de placeholders `{{slug:/produtos/123}}`
- **💾 Sistema de Cache**: Suporte para drivers file e Redis com TTL configurável
- **📊 Estatísticas**: Tracking completo de cliques com dashboards no Admin CP
- **⏰ Cron Jobs**: Geração automática de links e limpeza de dados antigos
- **🔌 Conectores**: Suporte nativo para Amazon, MercadoLivre, Shopee e lojas customizadas

## 🔧 Requisitos

- **XenForo**: 2.2.17 ou superior
- **PHP**: 8.1.0 ou superior
- **Banco de Dados**: MySQL 5.7+ ou MariaDB 10.2+
- **Extensões PHP**: curl, json, mbstring
- **Redis** (opcional): Para cache avançado

## 📦 Instalação

### Método 1: Via Admin CP (Recomendado)

1. **Faça download** dos arquivos do addon
2. **Comprima** a pasta `hardMOB` em um arquivo ZIP
3. **Acesse** o Admin CP do XenForo
4. **Vá para** `Add-ons` → `Install from archive`
5. **Selecione** o arquivo ZIP e clique em `Install`
6. **Confirme** a instalação

### Método 2: Via CLI

```bash
# Copie os arquivos para o diretório correto
cp -r hardMOB/ /path/to/xenforo/src/addons/

# Instale via linha de comando
cd /path/to/xenforo
php cmd.php xf-addon:install hardMOB/Afiliados
```

### Método 3: Upload Manual

1. **Faça upload** da pasta `hardMOB` para `src/addons/`
2. **Acesse** o Admin CP
3. **Vá para** `Add-ons` → `Install/Upgrade from file`
4. **Selecione** `hardMOB/Afiliados` e instale

## ⚙️ Configuração

### Configurações Básicas

1. **Acesse** `Options` → `hardMOB Afiliados` no Admin CP
2. **Configure** as seguintes opções:

```
Cache Driver: file (ou redis se configurado)
Cache TTL: 3600 (1 hora, 0 = permanente)
Enable Cron Jobs: ✓ Sim
Google Analytics ID: UA-XXXXXX-X (opcional)
```

### Configuração do Redis (Opcional)

Para melhor performance, configure o Redis:

```php
// config.php
$config['cache']['context']['affiliate'] = [
    'provider' => 'Redis',
    'config' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ]
];
```

### Adicionando Lojas

1. **Vá para** `hardMOB Afiliados` → `Gerenciar Lojas`
2. **Clique** em `Adicionar Loja`
3. **Preencha** os dados:

```
Nome: Amazon Brasil
Domínio: amazon.com.br
Código de Afiliado: seu-tag-amazon
Tipo de Conector: Amazon
Status: Ativo
```

## 🚀 Uso Básico

### Placeholders em Posts

Use placeholders nos seus posts para gerar links automáticos:

```bbcode
Confira este produto incrível: {{slug:/dp/B08N5WRWNW}}

Promoção no MercadoLivre: {{slug:MLB-123456789}}

Oferta Shopee: {{slug:produto-legal-i.123.456}}
```

### Como Funciona

1. **Usuário posta** conteúdo com placeholder
2. **Sistema detecta** o placeholder durante o processamento
3. **Identifica a loja** pelo padrão do slug
4. **Gera link público**: `/affiliate/1/c2x1Zzov...`
5. **Usuário clica** no link
6. **Sistema registra** o clique e redireciona

## 🎨 Integração BBCode

### BBCode Personalizado

Crie um BBCode personalizado para facilitar o uso:

**Nome**: `affiliate`
**Replacement**:
```html
<a href="/affiliate/{option}/{text}" target="_blank" rel="nofollow">
    Ver Produto
</a>
```

**Uso**:
```bbcode
[affiliate=amazon]/dp/B08N5WRWNW[/affiliate]
```

### BBCode Avançado com Preview

```html
<div class="affiliate-link">
    <a href="/affiliate/{option}/{text}" target="_blank" rel="nofollow" class="button button--primary">
        <i class="fas fa-external-link-alt"></i> Ver na {option}
    </a>
</div>
```

### CSS para Estilização

Adicione ao seu CSS personalizado:

```css
.affiliate-link {
    margin: 10px 0;
    text-align: center;
}

.affiliate-link .button {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.affiliate-link .button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
```

## 🎨 Integração Templates

### Template de Post

Modifique o template `message_body` para processamento automático:

```html
<xf:set var="$processedContent" value="{$xf.extension.processAffiliateLinks($content)}" />

<div class="message-content js-messageContent">
    <xf:if is="$processedContent">
        {$processedContent|raw}
    <xf:else />
        {$content|raw}
    </xf:if>
</div>
```

### Widget de Produtos Recomendados

Crie um widget personalizado:

```html
<div class="block">
    <div class="block-container">
        <h3 class="block-header">Produtos Recomendados</h3>
        <div class="block-body">
            <xf:foreach loop="$recommendedProducts" value="$product">
                <div class="affiliate-product">
                    <img src="{$product.image}" alt="{$product.title}" />
                    <h4>{$product.title}</h4>
                    <p class="price">{$product.price}</p>
                    <a href="/affiliate/{$product.store_id}/{$product.slug}" 
                       class="button button--primary" target="_blank">
                        Ver Produto
                    </a>
                </div>
            </xf:foreach>
        </div>
    </div>
</div>
```

### Template de Estatísticas

Para exibir estatísticas de cliques:

```html
<xf:if is="$xf.visitor.is_admin">
    <div class="affiliate-stats">
        <h4>Estatísticas de Afiliados</h4>
        <ul>
            <li>Cliques hoje: {$affiliateStats.today}</li>
            <li>Cliques este mês: {$affiliateStats.month}</li>
            <li>Total de cliques: {$affiliateStats.total}</li>
        </ul>
    </div>
</xf:if>
```

## 🔌 Conectores Disponíveis

### Amazon
- **Padrão**: `/dp/ASIN` ou `/gp/product/ASIN`
- **Exemplo**: `{{slug:/dp/B08N5WRWNW}}`
- **Configuração**: Adicione seu Associate Tag

### MercadoLivre
- **Padrão**: `MLB-XXXXXXXX`
- **Exemplo**: `{{slug:MLB-123456789}}`
- **Configuração**: Adicione seu ID de afiliado

### Shopee
- **Padrão**: `produto-i.x.y`
- **Exemplo**: `{{slug:smartphone-i.123.456}}`
- **Configuração**: Configure parâmetros de tracking

### Loja Customizada
- **Padrão**: Configurável
- **Exemplo**: `{{slug:/produtos/categoria/123}}`
- **Configuração**: Defina padrões de URL

## 🏛️ Interface Administrativa

### Dashboard Principal

Acesse `hardMOB Afiliados` no Admin CP para:

- **📊 Visão Geral**: Estatísticas gerais e gráficos
- **🏪 Gerenciar Lojas**: CRUD de lojas afiliadas
- **📈 Relatórios**: Análises detalhadas de performance
- **🔧 Ferramentas**: Limpeza de cache e manutenção
- **⚙️ Configurações**: Ajustes do sistema

### Gerenciamento de Lojas

**Adicionar Nova Loja**:
1. Nome da loja
2. Domínio principal
3. Código/ID de afiliado
4. Tipo de conector
5. Status (ativo/inativo)

**Ferramentas Disponíveis**:
- 🔄 Gerar conectores automaticamente
- 📊 Ver estatísticas por loja
- 🧹 Limpar cache específico
- ⚡ Testar conectividade

### Relatórios e Estatísticas

- **📅 Por Período**: Diário, semanal, mensal, anual
- **🏪 Por Loja**: Performance individual de cada loja
- **👤 Por Usuário**: Top usuários que mais geram cliques
- **📱 Por Produto**: Produtos mais populares
- **🌍 Geolocalização**: Origem dos cliques (se configurado)

## 🔧 Troubleshooting

### Problemas Comuns

#### Links Não São Processados

**Sintomas**: Placeholders aparecem como texto normal
**Soluções**:
1. Verifique se o addon está ativo
2. Confirme se a loja está configurada
3. Verifique logs de erro no XenForo
4. Teste com cache desabilitado

```bash
# Limpar cache via CLI
php cmd.php xf:rebuild-caches
```

#### Erro de Instalação "Table Conflict"

**Sintomas**: Erro durante instalação sobre tabelas existentes
**Soluções**:
1. Desinstale versões anteriores completamente
2. Verifique se tabelas foram removidas:

```sql
SHOW TABLES LIKE 'xf_hardmob_affiliate_%';
```

3. Se existirem, remova manualmente:

```sql
DROP TABLE IF EXISTS xf_hardmob_affiliate_stores;
DROP TABLE IF EXISTS xf_hardmob_affiliate_clicks;
DROP TABLE IF EXISTS xf_hardmob_affiliate_cache;
```

#### Cache Não Funciona

**Sintomas**: Links são gerados a cada acesso
**Soluções**:
1. Verifique configuração do driver de cache
2. Para Redis, teste conectividade:

```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo $redis->ping(); // Deve retornar +PONG
```

3. Verifique permissões para cache de arquivo:

```bash
chmod 755 internal_data/
chmod 644 internal_data/caches/
```

#### Cron Jobs Não Executam

**Sintomas**: Links não são pré-gerados
**Soluções**:
1. Verifique se cron está habilitado nas opções
2. Configure cron do sistema:

```bash
# Adicione ao crontab
*/30 * * * * php /path/to/xenforo/cmd.php xf:run-jobs
```

3. Execute manualmente para teste:

```bash
php cmd.php xf:run-jobs --job=hardMOB\\Afiliados\\Job\\GenerateAffiliateLinks
```

### Debug e Logs

#### Habilitar Debug

Adicione ao `config.php`:

```php
$config['debug'] = true;
$config['development']['enabled'] = true;
```

#### Logs Específicos

O addon gera logs em:
- `internal_data/logs/affiliate_errors.log`
- `internal_data/logs/affiliate_clicks.log`

#### Testando Conectores

```php
// Via console XenForo
$store = \XF::em()->find('hardMOB\Afiliados:Store', 1);
$connector = \XF::app()->container('affiliate.connector.' . $store->connector_type);
$url = $connector->generateAffiliateUrl($store, '/dp/B08N5WRWNW');
var_dump($url);
```

## ❓ FAQ

### Como funciona o sistema de cache?

O sistema mantém URLs geradas em cache para evitar processamento repetitivo. O TTL pode ser configurado nas opções (0 = permanente).

### Posso usar múltiplos códigos de afiliado?

Sim! Cada loja pode ter seu próprio código de afiliado. Configure lojas separadas para diferentes programas.

### O addon afeta a performance do fórum?

Não significativamente. O processamento é otimizado e usa cache. Links são pré-gerados via cron jobs.

### Como migrar de outro sistema?

1. Exporte dados do sistema anterior
2. Use o importador do addon (se disponível)
3. Ou configure manualmente as lojas equivalentes

### Posso personalizar os conectores?

Sim! Crie conectores customizados implementando a interface `StoreInterface`:

```php
<?php
namespace hardMOB\Afiliados\Connector;

class MeuConector implements StoreInterface
{
    public function generateAffiliateUrl($store, $slug)
    {
        return "https://minhaloja.com{$slug}?ref={$store->affiliate_code}";
    }
    
    public function extractSlug($url)
    {
        // Sua lógica de extração
    }
}
```

### Como adicionar suporte a nova loja?

1. **Crie um conector** personalizado
2. **Adicione a loja** no Admin CP
3. **Configure** domínio e código de afiliado
4. **Teste** com alguns links

### O addon funciona com XenForo Cloud?

Não diretamente, pois XenForo Cloud não permite addons customizados. Seria necessário uma solução externa.

---

## 📞 Suporte

Para dúvidas específicas:
- 📧 **Email**: suporte@hardmob.com.br
- 🌐 **Website**: https://hardmob.com.br
- 📝 **Issues**: Use as issues do repositório GitHub

---

**Desenvolvido com ❤️ pela equipe hardMOB**