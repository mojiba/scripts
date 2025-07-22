<?php
function handle_memcache_actions($memcache, $post, &$action_message) {
    if (isset($post['flush_all'])) {
        $action_message = $memcache->flush()
            ? 'Cache limpo com sucesso!'
            : 'Erro ao limpar cache.';
    }
    if (isset($post['flush_key']) && !empty($post['key'])) {
        $key = htmlspecialchars($post['key']);
        $action_message = $memcache->delete($key)
            ? "Chave '$key' removida!"
            : "Erro ao remover '$key'.";
    }
    if (isset($post['set_key']) && !empty($post['new_key'])) {
        $key = htmlspecialchars($post['new_key']);
        $value = $post['value'] ?? '';
        $exp = (int)($post['expiry'] ?? 0);
        $action_message = $memcache->set($key, $value, 0, $exp)
            ? "Chave '$key' definida!"
            : "Erro ao definir '$key'.";
    }
}

function handle_opcache_actions($post, &$opcache_message) {
    if (isset($post['reset_opcache']) && function_exists('opcache_reset')) {
        $opcache_message = opcache_reset()
            ? 'OPcache reiniciado!'
            : 'Falha ao reiniciar OPcache.';
    }
    if (isset($post['invalidate_file']) && !empty($post['file_path'])) {
        $file = $post['file_path'];
        $opcache_message = opcache_invalidate($file, true)
            ? "Arquivo '$file' invalidado!"
            : "Falha ao invalidar '$file'.";
    }
}