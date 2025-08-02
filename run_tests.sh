#!/bin/bash

# hardMOB Afiliados Test Suite Runner
# Executa todos os testes automatizados do sistema

echo "🧪 hardMOB Afiliados - Sistema de Testes Automatizados"
echo "======================================================="
echo ""

# Verifica se PHP está disponível
if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Instale PHP 8.0+ para executar os testes."
    exit 1
fi

# Verifica versão do PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "🐘 PHP Version: $PHP_VERSION"

# Verifica se os arquivos de teste existem
if [ ! -f "tests/run_tests.php" ]; then
    echo "❌ Arquivos de teste não encontrados. Execute este script no diretório raiz do projeto."
    exit 1
fi

echo "📁 Diretório de trabalho: $(pwd)"
echo "📋 Executando testes..."
echo ""

# Executa os testes
php tests/run_tests.php

# Captura o código de saída
TEST_RESULT=$?

echo ""
echo "======================================================="

if [ $TEST_RESULT -eq 0 ]; then
    echo "🎉 TODOS OS TESTES PASSARAM!"
    echo "✅ O sistema de afiliados está funcionando corretamente."
    echo ""
    echo "📊 Cobertura de Testes:"
    echo "   • Geração de links de afiliados"
    echo "   • Sistema de cache (file/Redis)"
    echo "   • Tracking de cliques e analytics"
    echo "   • Validação de entrada"
    echo "   • Conectores de lojas"
else
    echo "⚠️  ALGUNS TESTES FALHARAM!"
    echo "❌ Revise os erros reportados acima."
    echo ""
    echo "🔧 Para depuração:"
    echo "   1. Verifique as mensagens de erro específicas"
    echo "   2. Execute testes individuais se necessário"
    echo "   3. Consulte tests/README.md para mais detalhes"
fi

echo ""
echo "📚 Documentação: tests/README.md"
echo "🚀 Para contribuir, mantenha os testes atualizados!"

exit $TEST_RESULT