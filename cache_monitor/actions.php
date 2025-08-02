<?php
function handle_memcache_actions($memcache, $post, &$action_message) {
    if (isset($post['flush_all'])) {
        try {
            $result = $memcache->flush();
            $action_message = $result
                ? 'Cache limpo com sucesso! Todas as chaves foram removidas.'
                : 'Erro ao limpar cache. Verifique a conexão com o servidor Memcache.';
        } catch (Exception $e) {
            $action_message = 'Erro crítico ao limpar cache: ' . $e->getMessage() . '. Verifique se o servidor Memcache está funcionando.';
        }
    }
    if (isset($post['flush_key']) && !empty($post['key'])) {
        $key = htmlspecialchars($post['key']);
        try {
            $result = $memcache->delete($key);
            $action_message = $result
                ? "Chave '$key' removida com sucesso!"
                : "Erro ao remover '$key'. A chave pode não existir ou houve problema na conexão.";
        } catch (Exception $e) {
            $action_message = "Erro ao remover '$key': " . $e->getMessage();
        }
    }
    if (isset($post['set_key']) && !empty($post['new_key'])) {
        $key = htmlspecialchars($post['new_key']);
        $value = $post['value'] ?? '';
        $exp = (int)($post['expiry'] ?? 0);
        try {
            $result = $memcache->set($key, $value, 0, $exp);
            $action_message = $result
                ? "Chave '$key' definida com sucesso!" . ($exp > 0 ? " (expira em $exp segundos)" : " (sem expiração)")
                : "Erro ao definir '$key'. Verifique se o valor é válido e se há espaço disponível no cache.";
        } catch (Exception $e) {
            $action_message = "Erro ao definir '$key': " . $e->getMessage();
        }
    }
}

function handle_opcache_actions($post, &$opcache_message) {
    if (isset($post['reset_opcache'])) {
        if (!function_exists('opcache_reset')) {
            $opcache_message = 'OPcache não está disponível neste servidor. Verifique se a extensão OPcache está instalada e habilitada.';
            return;
        }
        try {
            $result = opcache_reset();
            $opcache_message = $result
                ? 'OPcache reiniciado com sucesso! Todos os arquivos em cache foram invalidados.'
                : 'Falha ao reiniciar OPcache. Verifique as permissões e configurações do OPcache.';
        } catch (Exception $e) {
            $opcache_message = 'Erro ao reiniciar OPcache: ' . $e->getMessage();
        }
    }
    if (isset($post['invalidate_file']) && !empty($post['file_path'])) {
        $file = $post['file_path'];
        if (!function_exists('opcache_invalidate')) {
            $opcache_message = 'Função opcache_invalidate não está disponível. Verifique a configuração do OPcache.';
            return;
        }
        if (!file_exists($file)) {
            $opcache_message = "Arquivo '$file' não encontrado. Verifique se o caminho está correto.";
            return;
        }
        try {
            $result = opcache_invalidate($file, true);
            $opcache_message = $result
                ? "Arquivo '$file' invalidado com sucesso!"
                : "Falha ao invalidar '$file'. O arquivo pode não estar em cache ou você não tem permissões suficientes.";
        } catch (Exception $e) {
            $opcache_message = "Erro ao invalidar '$file': " . $e->getMessage();
        }
    }
}