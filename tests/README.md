# hardMOB Afiliados - Test Suite

Este diretório contém os testes automatizados para o sistema de afiliados hardMOB.

## Estrutura dos Testes

```
tests/
├── bootstrap.php              # Framework de testes e mocks do XenForo
├── run_tests.php             # Executor principal dos testes
├── AffiliateGeneratorTest.php # Testes para geração de links de afiliados
├── LinkCacheTest.php         # Testes para sistema de cache
├── AnalyticsTest.php         # Testes para tracking de cliques
├── LinkParserTest.php        # Testes para validação de entrada
└── ConnectorTest.php         # Testes para conectores de lojas
```

## Funcionalidades Testadas

### 1. Geração de Links de Afiliados (AffiliateGeneratorTest)
- ✅ Processamento de placeholders `{{slug:...}}`
- ✅ Detecção automática de lojas por padrões de URL
- ✅ Geração de links públicos para redirecionamento
- ✅ Cache inteligente de links gerados
- ✅ Pré-geração de links em batch

### 2. Sistema de Cache (LinkCacheTest)
- ✅ Operações CRUD (get/set/delete)
- ✅ Suporte para drivers file e Redis
- ✅ Gerenciamento de TTL e expiração
- ✅ Fallback automático Redis → File
- ✅ Estatísticas e limpeza de cache

### 3. Tracking de Cliques (AnalyticsTest)
- ✅ Registro de cliques no banco de dados
- ✅ Integração com Google Analytics
- ✅ Cálculo de taxas de conversão
- ✅ Filtros por período e loja
- ✅ Geração de client IDs únicos

### 4. Validação de Entrada (LinkParserTest)
- ✅ Parsing de URLs Amazon, MercadoLivre, Shopee
- ✅ Detecção de links já afiliados
- ✅ Limpeza de parâmetros de tracking
- ✅ Validação de URLs e tratamento de erros
- ✅ Extração de informações de produtos

### 5. Conectores de Lojas (ConnectorTest)
- ✅ Validação de ASINs Amazon
- ✅ Geração de URLs com códigos de afiliado
- ✅ Compliance com interfaces definidas
- ✅ Tratamento de edge cases
- ✅ Performance e robustez

## Como Executar

### Execução Completa
```bash
cd /caminho/para/o/projeto
php tests/run_tests.php
```

### Execução Individual
```bash
# Testar apenas geração de links
php -f tests/AffiliateGeneratorTest.php

# Testar apenas cache
php -f tests/LinkCacheTest.php
```

## Resultados

O conjunto completo de testes inclui **219 asserções** distribuídas em **5 classes de teste**, cobrindo todas as principais funcionalidades do sistema de afiliados.

### Estatísticas dos Testes
- **AffiliateGeneratorTest**: 21 asserções
- **LinkCacheTest**: 36 asserções  
- **AnalyticsTest**: 50 asserções
- **LinkParserTest**: 86 asserções
- **ConnectorTest**: 56 asserções

## Framework de Testes

Este projeto utiliza um framework de testes customizado e auto-contido que:

- ✅ Não requer dependências externas
- ✅ Mocks completos do XenForo framework
- ✅ Suporte para asserções comuns (assertTrue, assertEquals, etc.)
- ✅ Relatórios detalhados com estatísticas
- ✅ Execução isolada de cada teste

## Configuração do Mock

O arquivo `bootstrap.php` contém mocks completos para:
- Classes do XenForo (XF\App, XF\Service\AbstractService)
- Entidades (Store, Click)
- Repositórios e Entity Manager
- Sistema de cache e banco de dados
- Conectores de lojas

## Integração Contínua

Para integrar com CI/CD, execute:
```bash
php tests/run_tests.php
echo $? # 0 = sucesso, 1 = falha
```

## Contribuindo

Ao adicionar novas funcionalidades:

1. Crie testes correspondentes na classe apropriada
2. Execute `php tests/run_tests.php` para validar
3. Mantenha cobertura de testes acima de 95%
4. Documente casos de edge testados