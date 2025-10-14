<?php
$title = "Connexion";
ob_start();
?>

<h3 class="card-title text-center mb-4">Connexion</h3>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">Email ou mot de passe incorrect.</div>
<?php endif; ?>

<form action="/auth/authenticate" method="POST">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">Se Connecter</button>
    </div>
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/centered.php';
?>