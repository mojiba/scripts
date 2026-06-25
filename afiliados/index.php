<?php

/**
 * XenForo 2.2.17 Addon Development Server Demo
 * 
 * This is a development server to demonstrate the hardMOB Afiliados addon structure.
 * In a real XenForo installation, this addon would be installed via the Admin CP.
 */

// Simulate XenForo environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>hardMOB Afiliados - XenForo Addon</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .addon-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .feature {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .feature h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .file-tree {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        .installation-steps {
            background: #e8f5e8;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
        }
        .badge {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        ul li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔗 hardMOB Afiliados</h1>
        <p>Sistema completo de gestão de links de afiliados para XenForo 2.2.17</p>
        <span class="badge">v1.0.0</span>
    </div>

    <div class="addon-info">
        <h2>📋 Sobre o Addon</h2>
        <p>Este é um addon completo para XenForo 2.2.17 que automatiza o gerenciamento de links de afiliados. 
        O sistema detecta e reescreve URLs para inserir códigos de afiliado, gera links via cron jobs, 
        implementa cache inteligente e registra estatísticas detalhadas de cliques.</p>
        
        <h3>🎯 Principais Funcionalidades</h3>
        <ul>
            <li><strong>Gestão de Lojas:</strong> CRUD completo com criação automática de conectores</li>
            <li><strong>Processamento de Links:</strong> Substituição automática de placeholders {{slug:/produtos/123}}</li>
            <li><strong>Sistema de Cache:</strong> Suporte para drivers file e Redis com TTL configurável</li>
            <li><strong>Estatísticas:</strong> Tracking completo de cliques com dashboards no Admin CP</li>
            <li><strong>Cron Jobs:</strong> Geração automática de links e limpeza de dados antigos</li>
            <li><strong>Conectores:</strong> Suporte nativo para Amazon, MercadoLivre, Shopee e lojas customizadas</li>
        </ul>
    </div>

    <div class="features">
        <div class="feature">
            <h3>🏪 Gestão de Lojas</h3>
            <p>Interface administrativa para gerenciar lojas de afiliados:</p>
            <ul>
                <li>Adicionar/editar/remover lojas</li>
                <li>Configurar domínios e códigos de afiliado</li>
                <li>Status ativo/inativo</li>
                <li>Scaffold automático de conectores</li>
            </ul>
        </div>

        <div class="feature">
            <h3>🔄 Processamento de Links</h3>
            <p>Sistema inteligente de reescrita de URLs:</p>
            <ul>
                <li>Detecção automática de placeholders</li>
                <li>Rota pública /affiliate/{store}/{slug}</li>
                <li>Redirect 302 com tracking</li>
                <li>Registro de cliques por usuário</li>
            </ul>
        </div>

        <div class="feature">
            <h3>💾 Sistema de Cache</h3>
            <p>Cache inteligente para performance:</p>
            <ul>
                <li>Drivers: file, Redis</li>
                <li>TTL configurável (0 = permanente)</li>
                <li>Limpeza automática de entradas expiradas</li>
                <li>Interface administrativa para gerenciamento</li>
            </ul>
        </div>

        <div class="feature">
            <h3>📊 Estatísticas</h3>
            <p>Analytics completo de cliques:</p>
            <ul>
                <li>Dashboards com filtros por período</li>
                <li>Estatísticas por loja e produto</li>
                <li>Top produtos mais clicados</li>
                <li>Integração com Google Analytics</li>
            </ul>
        </div>

        <div class="feature">
            <h3>⏰ Cron Jobs</h3>
            <p>Processamento automático em background:</p>
            <ul>
                <li>Geração de links a cada hora</li>
                <li>Limpeza diária de dados antigos</li>
                <li>Configurações administrativas</li>
                <li>Logs de execução</li>
            </ul>
        </div>

        <div class="feature">
            <h3>🔌 Conectores</h3>
            <p>Suporte para múltiplos marketplaces:</p>
            <ul>
                <li>Amazon (ASINs e URLs completas)</li>
                <li>MercadoLivre (IDs MLB)</li>
                <li>Shopee (formato produto-i.x.y)</li>
                <li>Lojas customizadas (genérico)</li>
            </ul>
        </div>
    </div>

    <div class="installation-steps">
        <h3>📦 Como Instalar no XenForo</h3>
        <p><strong>Correções aplicadas:</strong> ❌ Erro de route_class corrigido ✅</p>
        <ol>
            <li>Faça upload de todos os arquivos para <code>src/addons/hardMOB/Afiliados/</code></li>
            <li>No Admin CP, vá em <strong>Add-ons</strong> → <strong>Install from archive</strong></li>
            <li>Ou use o comando CLI: <code>php cmd.php xf-addon:install hardMOB/Afiliados</code></li>
            <li>Configure as opções em <strong>Options</strong> → <strong>hardMOB Afiliados</strong></li>
            <li>Adicione lojas em <strong>hardMOB Afiliados</strong> → <strong>Gerenciar Lojas</strong></li>
        </ol>
        
        <h4>🔧 Problemas corrigidos na v1.0.3:</h4>
        <ul>
            <li>✅ Adicionado <code>route_type</code> nos arquivos XML</li>
            <li>✅ <strong>Removido route_class problemático</strong></li>
            <li>✅ Simplificado configuração de rotas XML</li>
            <li>✅ Criados arquivos de permissões administrativas</li>
            <li>✅ Definições de entidades adicionadas</li>
            <li>✅ Compatibilidade com XenForo 2.2.17+ e PHP 8.1+</li>
            <li>✅ Conflito de tabelas resolvido</li>
            <li>✅ Verificação de tabelas existentes antes da criação</li>
            <li>✅ Limpeza automática de tabelas conflitantes</li>
        </ul>
        
        <div class="alert alert-info">
            <strong>🚨 Se ainda houver erro de conflito:</strong><br>
            1. Desinstale completamente o addon anterior<br>
            2. Verifique se todas as tabelas foram removidas<br>
            3. Instale a versão 1.0.2 limpa<br>
        </div>
    </div>

    <div class="warning">
        <h3>⚠️ Importante</h3>
        <p>Este é um servidor de desenvolvimento que mostra a estrutura do addon. Para usar em produção:</p>
        <ul>
            <li>Instale em uma instalação completa do XenForo 2.2.17+</li>
            <li>Configure PHP 8.1+ com as extensões necessárias</li>
            <li>Ajuste as permissões de arquivos conforme necessário</li>
            <li>Configure o cache Redis se desejado para melhor performance</li>
        </ul>
    </div>

    <div class="addon-info">
        <h3>📁 Estrutura de Arquivos</h3>
        <div class="file-tree">
hardMOB/Afiliados/
├── addon.json                    # Configuração do addon
├── Setup.php                     # Instalação e configuração do banco
├── Listener.php                  # Event listeners
├── Admin/Controller/
│   ├── Affiliates.php           # Gestão de lojas
│   └── Tools.php                # Ferramentas e estatísticas
├── Pub/Controller/
│   └── Affiliate.php            # Controller público para redirects
├── Entity/
│   ├── Store.php                # Entidade de lojas
│   └── Click.php                # Entidade de cliques
├── Repository/
│   ├── Store.php                # Repositório de lojas
│   └── Click.php                # Repositório de cliques
├── Service/
│   ├── AffiliateGenerator.php   # Gerador de links
│   ├── Analytics.php            # Tracking e analytics
│   └── Stats.php                # Estatísticas
├── Cache/
│   └── LinkCache.php            # Sistema de cache
├── Connector/
│   ├── StoreInterface.php       # Interface dos conectores
│   ├── Amazon.php               # Conector Amazon
│   ├── MercadoLivre.php         # Conector MercadoLivre
│   ├── Shopee.php               # Conector Shopee
│   └── CustomStore.php          # Conector genérico
├── Job/
│   └── GenerateAffiliateLinks.php # Job de geração de links
├── Cron/
│   └── GenerateLinks.php        # Cron jobs
├── Helper/
│   └── LinkParser.php           # Parser de URLs
└── _data/                       # Dados XML do addon
    ├── admin_navigation.xml
    ├── code_event_listeners.xml
    ├── cron_entries.xml
    ├── options.xml
    ├── phrases.xml
    ├── routes.xml
    └── templates.xml
        </div>
    </div>

    <div class="addon-info">
        <h3>🔧 Exemplo de Uso</h3>
        <p>No conteúdo de posts do fórum, você pode usar placeholders como:</p>
        <div class="code-block">
            Confira este produto: {{slug:/dp/B08N5WRWNW}}
            <br>
            Ou este da ML: {{slug:MLB-123456789}}
            <br>
            Link Shopee: {{slug:produto-legal-i.123.456}}
        </div>
        <p>O sistema automaticamente:</p>
        <ol>
            <li>Detecta o placeholder no BBCode</li>
            <li>Identifica a loja pelo padrão do slug</li>
            <li>Gera o link público: <code>/affiliate/1/c2x1Zzov...</code></li>
            <li>Registra o clique quando acessado</li>
            <li>Redireciona para a URL final com código de afiliado</li>
        </ol>
    </div>

    <div class="addon-info">
        <h3>⚙️ Configurações Disponíveis</h3>
        <ul>
            <li><strong>Habilitar Cron Jobs:</strong> Ativa/desativa processamento automático</li>
            <li><strong>Driver de Cache:</strong> file ou Redis</li>
            <li><strong>TTL do Cache:</strong> Tempo de vida em segundos (0 = permanente)</li>
            <li><strong>Google Analytics ID:</strong> Para tracking externo</li>
        </ul>
    </div>

    <div class="addon-info">
        <h3>🚀 Próximos Passos</h3>
        <p>Para implementar este addon em seu XenForo:</p>
        <ol>
            <li>Baixe todos os arquivos desta estrutura</li>
            <li>Faça upload para sua instalação XenForo</li>
            <li>Execute a instalação via Admin CP</li>
            <li>Configure suas lojas de afiliados</li>
            <li>Teste com alguns placeholders em posts</li>
            <li>Monitore as estatísticas de cliques</li>
        </ol>
    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #666;">
        <p>© 2025 hardMOB Afiliados - XenForo 2.2.17+ Addon</p>
        <p>Desenvolvido com PHP 8.1+ e compatível com XenForo 2.2.17</p>
    </footer>
</body>
</html>