<?php
// Converte bytes em unidades legíveis
function bytesToHuman($bytes, $p = 2) {
    $units = ['B','KB','MB','GB','TB'];
    $pow = $bytes ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $p) . ' ' . $units[$pow];
}

// Formata uptime em dias, horas, minutos, segundos
function formatUptime($u) {
    $d = floor($u/86400);   $h = floor(($u%86400)/3600);
    $m = floor(($u%3600)/60); $s = $u % 60; $r = [];
    $d && $r[] = "$d dias"; $h && $r[] = "$h horas";
    $m && $r[] = "$m minutos"; $s && $r[] = "$s segundos";
    return implode(', ', $r);
}

// Lista itens do Memcache filtrando por prefixo
function getMemcacheItems($mem, $server, $limit = 100, $prefix = '') {
    $items = []; $count = 0;
    $fp = @fsockopen($server['host'], $server['port'], $e1, $e2, 1);
    if (!$fp) return ["error" => "Falha na conexão ($e2)"];
    // obtém slabs
    fwrite($fp, "stats items\r\n");
    $slabs = [];
    while (!feof($fp)) {
        $line = fgets($fp);
        if (strpos($line, 'END') === 0) break;
        if (preg_match('/STAT items:(\d+):/', $line, $m)) {
            $slabs[] = $m[1];
        }
    }
    // para cada slab, faz dump
    foreach ($slabs as $sl) {
        fwrite($fp, "stats cachedump {$sl} {$limit}\r\n");
        while (!feof($fp)) {
            $line = fgets($fp);
            if (strpos($line, 'END') === 0) break;
            if (preg_match('/ITEM (.*?) \[(\d+) b; (\d+) s\]/', $line, $m)) {
                list(, $key, $size, $exp) = $m;
                if ($prefix === '' || strpos($key, $prefix) === 0) {
                    $val = $mem->get($key);
                    $preview = is_string($val)
                        ? (strlen($val) > 100 ? substr($val,0,100).'...' : $val)
                        : (is_array($val) ? 'Array('.count($val).')' : gettype($val));
                    $items[] = [
                        'key'   => $key,
                        'size'  => bytesToHuman($size),
                        'expiry'=> $exp ? date('Y-m-d H:i:s', $exp) : '—',
                        'preview'=> htmlspecialchars($preview)
                    ];
                    if (++$count >= $limit) break 2;
                }
            }
        }
    }
    fclose($fp);
    return $items;
}

// Coleta info de sessões armazenadas no Memcache
function getMemcacheSessionsInfo($host, $port) {
    $m = new Memcache();
    if (!$m->connect($host, $port)) {
        return ['error' => 'Não foi possível conectar para sessões'];
    }
    $all = []; $total = 0; $bytes = 0;
    $slabs = current($m->getExtendedStats('slabs'));
    foreach (array_keys($slabs) as $sl) {
        if (!is_numeric($sl)) continue;
        $dump = $m->getExtendedStats('cachedump', (int)$sl, 1000);
        foreach ((array)$dump as $srv => $ents) {
            foreach ((array)$ents as $k => $info) {
                if (strpos($k, 'sess_') === 0 || strpos($k, 'session:') === 0) {
                    $total++;
                    $bytes += $info[0];
                    if (count($all) < 20) {
                        $all[] = [
                            'id'      => substr(str_replace(['sess_','session:'], '', $k), 0, 12),
                            'size'    => bytesToHuman($info[0]),
                            'expires' => date('Y-m-d H:i:s', $info[1])
                        ];
                    }
                }
            }
        }
    }
    return [
        'total_sessions' => $total,
        'total_size'     => bytesToHuman($bytes),
        'average_size'   => $total ? bytesToHuman($bytes/$total) : '0 B',
        'sample'         => $all
    ];
}