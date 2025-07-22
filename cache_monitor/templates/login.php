<div class="card mx-auto" style="max-width: 400px;">
  <div class="card-header bg-primary text-white text-center">
    <h4>Login do Monitor de Caches</h4>
  </div>
  <div class="card-body">
    <?php if ($login_error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Usuário</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button name="login" class="btn btn-primary w-100">Entrar</button>
    </form>
  </div>
</div>