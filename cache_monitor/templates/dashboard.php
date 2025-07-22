<?php
global $mem, $config, $csrf_token, $current_prefix;

$action_message = '';
$opcache_message = '';
handle_memcache_actions($mem, $_POST, $action_message);
handle_opcache_actions($_POST, $opcache_message);

$stats = @$mem->getExtendedStats();

// Coleta itens de todos os servidores
$items = [];
foreach ($config['servers'] as $server) {
    $partial = getMemcacheItems(
        $mem,
        $server,
        $config['items_limit'],
        $current_prefix
    );
    if (is_array($partial) && !isset($partial['error'])) {
        $items = array_merge($items, $partial);
    }
}

$sessions = getMemcacheSessionsInfo(
    $config['memcache_server'],
    $config['memcache_port']
);

$opcache = getOPcacheStatus();
?>

<h2><?= $config['title'] ?></h2>
<p class="text-muted mb-2">
  Última atualização: <?= date('Y-m-d H:i:s') ?>
</p>

<?php if ($action_message): ?>
  <div class="alert alert-info">
    <?= htmlspecialchars($action_message) ?>
  </div>
<?php endif; ?>
<?php if ($opcache_message): ?>
  <div class="alert alert-warning">
    <?= htmlspecialchars($opcache_message) ?>
  </div>
<?php endif; ?>

<!-- Filtro de prefixo -->
<div class="mb-3">
  <label>Prefixo:</label>
  <?php foreach ($config['prefix_filters'] as $prefix => $label): ?>
    <a href="?prefix=<?= $prefix ?>"
       class="btn btn-sm <?= $prefix === $current_prefix
         ? 'btn-primary' : 'btn-outline-secondary' ?> ms-1">
      <?= htmlspecialchars($label) ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Abas -->
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active"
            data-bs-toggle="tab"
            data-bs-target="#stats">
      Estatísticas
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#items">
      Itens
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#sessions">
      Sessões
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#opcache">
      OPcache
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#actions">
      Ações
    </button>
  </li>
</ul>

<div class="tab-content mt-3">
  <!-- Estatísticas -->
  <div class="tab-pane fade show active" id="stats">
    <?php foreach ($config['servers'] as $s):
      $key = "{$s['host']}:{$s['port']}";
      $d   = $stats[$key] ?? null;
    ?>
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">
          <?= $s['name'] ?> (<?= $key ?>)
        </div>
        <div class="card-body">
          <?php if ($d):
            $pct = ($d['bytes'] / $d['limit_maxbytes']) * 100;
          ?>
            <p>
              Status:
              <span class="badge bg-success">ONLINE</span>
            </p>
            <p>
              Uptime: <?= formatUptime($d['uptime']) ?>
            </p>

            <h6>Memória</h6>
            <div class="progress mb-2">
              <div class="progress-bar <?= $pct < 70
                ? 'bg-success'
                : ($pct < 90 ? 'bg-warning' : 'bg-danger') ?>"
                   style="width: <?= number_format($pct, 2) ?>%">
                <?= number_format($pct, 2) ?>%
              </div>
            </div>
            <p>
              <?= bytesToHuman($d['bytes']) ?>
              de <?= bytesToHuman($d['limit_maxbytes']) ?>
            </p>

            <!-- Estatísticas avançadas -->
            <ul class="mt-3">
              <li>
                Itens armazenados:
                <?= number_format($d['curr_items'] ?? 0) ?>
              </li>
              <li>
                Hits:
                <?= number_format($d['get_hits'] ?? 0) ?>
              </li>
              <li>
                Misses:
                <?= number_format($d['get_misses'] ?? 0) ?>
              </li>
              <li>
                Evictions:
                <?= number_format($d['evictions'] ?? 0) ?>
              </li>
              <li>
                Taxa de acerto:
                <?= number_format((
                  ($d['get_hits'] ?? 0)
                  / max(1, $d['cmd_get'] ?? 1)
                ) * 100, 2) ?>%
              </li>
            </ul>
          <?php else: ?>
            <div class="alert alert-danger">
              Não conectado
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Itens -->
  <div class="tab-pane fade" id="items">
    <?php if (isset($items['error'])): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($items['error']) ?>
      </div>
    <?php elseif (empty($items)): ?>
      <div class="alert alert-info">
        Nenhum item com prefixo
        <?= htmlspecialchars($current_prefix) ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th>Chave</th>
              <th>Tamanho</th>
              <th>Expira</th>
              <th>Preview</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['key']) ?></td>
                <td><?= $it['size'] ?></td>
                <td><?= $it['expiry'] ?></td>
                <td><code><?= $it['preview'] ?></code></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Sessões -->
  <div class="tab-pane fade" id="sessions">
    <?php if (isset($sessions['error'])): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($sessions['error']) ?>
      </div>
    <?php else: ?>
      <p>
        Total: <?= number_format($sessions['total_sessions']) ?> |
        Tamanho: <?= $sessions['total_size'] ?> |
        Média: <?= $sessions['average_size'] ?>
      </p>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tamanho</th>
              <th>Expira</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sessions['sample'] as $ss): ?>
              <tr>
                <td><?= htmlspecialchars($ss['id']) ?></td>
                <td><?= $ss['size'] ?></td>
                <td><?= $ss['expires'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- OPcache -->
  <div class="tab-pane fade" id="opcache">
    <?php if (!empty($opcache['memory_usage'])):
      $mu  = $opcache['memory_usage'];
      $pct = ($mu['used_memory'] / $mu['allocated_memory']) * 100;
    ?>
      <h6>Memória</h6>
      <div class="progress mb-2">
        <div class="progress-bar <?= $pct < 70
          ? 'bg-success'
          : ($pct < 90 ? 'bg-warning' : 'bg-danger') ?>"
             style="width: <?= number_format($pct, 2) ?>%">
          <?= number_format($pct, 2) ?>%
        </div>
      </div>
      <p>
        <?= bytesToHuman($mu['used_memory']) ?>
        de <?= bytesToHuman($mu['allocated_memory']) ?>
      </p>
    <?php else: ?>
      <div class="alert alert-warning">
        OPcache indisponível ou desativado.
      </div>
    <?php endif; ?>
  </div>

  <!-- Ações -->
  <div class="tab-pane fade" id="actions">
    <form method="post" class="mb-3">
      <input type="hidden"
             name="csrf_token"
             value="<?= $csrf_token ?>">
      <button name="flush_all"
              class="btn btn-danger"
              onclick="return confirm('Limpar todo o Memcache?')">
        Limpar Memcache
      </button>
    </form>

    <form method="post" class="mb-3">
      <input type="hidden"
             name="csrf_token"
             value="<?= $csrf_token ?>">
      <button name="reset_opcache"
              class="btn btn-warning"
              onclick="return confirm('Reiniciar OPcache?')">
        Reiniciar OPcache
      </button>
    </form>

    <form method="post">
      <input type="hidden"
             name="csrf_token"
             value="<?= $csrf_token ?>">
      <div class="mb-2">
        <label class="form-label">Arquivo para invalidar:</label>
        <input type="text"
               name="file_path"
               class="form-control"
               placeholder="/caminho/para/script.php">
      </div>
      <button name="invalidate_file"
              class="btn btn-outline-secondary">
        Invalidar Script no OPcache
      </button>
    </form>
  </div>
</div>

<!-- Botão de logout -->
<a href="?logout=1" class="btn btn-outline-secondary mt-4">
  Sair
</a>