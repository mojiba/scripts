# Scripts Collection

Esta é uma coleção de scripts utilitários desenvolvidos para facilitar a administração e integração de sistemas web. O repositório contém duas ferramentas principais:

## 🔗 Afiliados - XenForo Addon
Sistema completo de gestão de links de afiliados para XenForo 2.2.17+, com recursos avançados de cache, estatísticas e conectores para múltiplos marketplaces.

**Principais recursos:**
- Gestão automatizada de links de afiliados
- Sistema de cache inteligente (file/Redis)
- Estatísticas detalhadas de cliques
- Conectores para Amazon, MercadoLivre, Shopee
- Integração via BBCode e templates
- Interface administrativa completa

[📖 Documentação Completa](./afiliados/README.md)

## 📊 Cache Monitor - Ferramenta de Monitoramento
Ferramenta standalone em PHP para monitoramento de sistemas de cache (Memcache/OpCache) com interface web administrativa.

**Principais recursos:**
- Monitoramento de Memcache e OpCache
- Interface web com autenticação
- Limpeza e gerenciamento de cache
- Estatísticas de performance em tempo real
- Suporte a múltiplos servidores

[📖 Documentação Completa](./cache_monitor/README.md)

## 🚀 Quick Start

### Afiliados (XenForo)
```bash
# 1. Upload para XenForo
cp -r afiliados/ /path/to/xenforo/src/addons/hardMOB/Afiliados/

# 2. Instalar via Admin CP ou CLI
php cmd.php xf-addon:install hardMOB/Afiliados
```

### Cache Monitor
```bash
# 1. Configurar
cp cache_monitor/config.php.example cache_monitor/config.php
nano cache_monitor/config.php

# 2. Configurar servidor web para apontar para cache_monitor/
# 3. Acessar via navegador e fazer login
```

## 📋 Requisitos

### Afiliados
- XenForo 2.2.17+
- PHP 8.1+
- MySQL/MariaDB
- Redis (opcional, para cache avançado)

### Cache Monitor
- PHP 7.4+
- Extensões: memcache, opcache
- Servidor web (Apache/Nginx)

## 🛠️ Suporte e Troubleshooting

Para problemas comuns e soluções, consulte:
- [Guia de Troubleshooting](./TROUBLESHOOTING.md)
- [Exemplos de Integração](./EXAMPLES.md)
- [Guia de Instalação](./INSTALLATION.md)

## 📞 Suporte

Para dúvidas e suporte:
- 📧 Email: suporte@hardmob.com.br
- 🌐 Website: https://hardmob.com.br
- 📝 Issues: Use as issues deste repositório

## 📄 Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo LICENSE para detalhes.