<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
require_once __DIR__ . '/config.php';

function h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function posted(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
function back(): never { header('Location: orque.php', true, 303); exit; }
function icon(string $name): string {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'edit' => '<path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/>',
        'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 14h12l1-14M10 10v6M14 10v6"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'logout' => '<path d="M9 4H4v16h5M13 8l4 4-4 4M8 12h13"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 14v3"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'layers' => '<path d="m12 3 10 5-10 5L2 8zM2 12l10 5 10-5M2 16l10 5 10-5"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
$flash = $_SESSION['project_flash'] ?? null;
unset($_SESSION['project_flash']);
$notice = $flash['message'] ?? '';
$noticeType = $flash['type'] ?? 'success';
$reopen = $flash['values'] ?? null;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = posted('action');
$csrfValid = !$isPost || (is_string($_POST['csrf'] ?? null) && hash_equals($csrf, $_POST['csrf']));

if (!$csrfValid) {
    http_response_code(403);
    $notice = 'La session du formulaire a expiré. Actualisez la page et réessayez.';
    $noticeType = 'error';
} elseif ($isPost && $action === 'login') {
    $candidate = is_string($_POST['login_password'] ?? null) ? $_POST['login_password'] : '';
    if (hash_equals((string) $mot_de_passe_admin, $candidate)) {
        session_regenerate_id(true);
        $_SESSION['admin_connecte'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        back();
    }
    http_response_code(401);
    $notice = 'Mot de passe incorrect. Réessayez.';
    $noticeType = 'error';
} elseif ($isPost && $action === 'logout') {
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
    back();
}

$authenticated = ($_SESSION['admin_connecte'] ?? false) === true;
$projects = [];
$databaseError = false;
if ($authenticated) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        if ($isPost && $csrfValid && in_array($action, ['add', 'edit', 'delete'], true)) {
            $id = filter_var(posted('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $values = ['action' => $action, 'id' => $id ?: '', 'titre' => posted('titre'), 'description' => posted('description'), 'lien_projet' => posted('lien_projet'), 'technologie' => posted('technologie'), 'ordre' => posted('ordre'), 'espace_admin' => isset($_POST['espace_admin']) ? 1 : 0];
            $errors = [];
            if ($action !== 'add') {
                $find = $pdo->prepare('SELECT id FROM projets WHERE id = ?');
                $find->execute([$id ?: 0]);
                if (!$find->fetch()) $errors[] = 'Ce projet n’existe plus. Actualisez la liste.';
            }
            if ($action !== 'delete') {
                foreach (['titre' => ['Le titre', 255], 'description' => ['La description', 5000], 'technologie' => ['Les technologies', 100], 'lien_projet' => ['Le lien', 255]] as $key => [$label, $max]) {
                    if ($values[$key] === '' || mb_strlen($values[$key]) > $max) $errors[] = "$label doit contenir entre 1 et $max caractères.";
                }
                $scheme = strtolower((string) parse_url($values['lien_projet'], PHP_URL_SCHEME));
                if (!filter_var($values['lien_projet'], FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) $errors[] = 'Renseignez une adresse complète commençant par http:// ou https://.';
                $order = filter_var($values['ordre'], FILTER_VALIDATE_INT, ['options' => ['min_range' => -2147483648, 'max_range' => 2147483647]]);
                if ($order === false) $errors[] = 'L’ordre doit être un nombre entier.';
            }
            if ($errors) {
                $_SESSION['project_flash'] = ['message' => implode(' ', $errors), 'type' => 'error', 'values' => $action === 'delete' ? null : $values];
                back();
            }
            if ($action === 'delete') {
                $pdo->prepare('DELETE FROM projets WHERE id = ?')->execute([$id]);
                $message = 'Le projet a été supprimé de votre accueil.';
            } elseif ($action === 'add') {
                $pdo->prepare("INSERT INTO projets (titre, description, image_url, lien_projet, technologie, espace_admin, ordre) VALUES (?, ?, '#', ?, ?, ?, ?)")->execute([$values['titre'], $values['description'], $values['lien_projet'], $values['technologie'], $values['espace_admin'], $order]);
                $message = 'Votre nouveau projet a été ajouté.';
            } else {
                $pdo->prepare('UPDATE projets SET titre = ?, description = ?, lien_projet = ?, technologie = ?, espace_admin = ?, ordre = ? WHERE id = ?')->execute([$values['titre'], $values['description'], $values['lien_projet'], $values['technologie'], $values['espace_admin'], $order, $id]);
                $message = 'Les modifications du projet ont été enregistrées.';
            }
            $_SESSION['project_flash'] = ['message' => $message, 'type' => 'success'];
            back();
        }
        $projects = $pdo->query('SELECT * FROM projets ORDER BY ordre ASC, id ASC')->fetchAll();
    } catch (PDOException $exception) {
        error_log('Administration des projets : ' . $exception->getMessage());
        http_response_code(503);
        $databaseError = true;
        $notice = 'Impossible de charger ou d’enregistrer les projets pour le moment. Réessayez dans un instant.';
        $noticeType = 'error';
    }
}
$adminCount = count(array_filter($projects, fn($p) => (int) $p['espace_admin'] === 1));
$technologies = [];
foreach ($projects as $project) {
    foreach (preg_split('/[·,|]+/u', $project['technologie']) as $technology) {
        if (trim($technology) !== '') $technologies[mb_strtolower(trim($technology))] = true;
    }
}
if ($authenticated && !$reopen && isset($_GET['edit'])) {
    foreach ($projects as $project) {
        if ((string) $project['id'] === (string) $_GET['edit']) $reopen = ['action' => 'edit'] + $project;
    }
}
$nextOrder = $projects ? max(array_map(fn($p) => (int) $p['ordre'], $projects)) + 1 : 1;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1329">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $authenticated ? 'Gestion des projets' : 'Connexion' ?> — Orque &amp; Banquise</title>
    <link rel="icon" type="image/png" href="logo_orque.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-projects.css?v=20261022">
    <script src="assets/admin-projects.js?v=20261022" defer></script>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="./" aria-label="Orque et Banquise, accueil"><img src="logo_orque.png" alt="" width="46" height="46"><span>Orque <span class="brand-amp">&amp;</span> Banquise<small>ESPACE ADMINISTRATION</small></span></a>
        <div class="header-actions">
            <a class="button button-quiet" href="./" <?= $authenticated ? 'target="_blank" rel="noopener"' : '' ?>><?= icon('arrow') ?><span><?= $authenticated ? 'Voir le site' : 'Retour au site' ?></span></a>
            <?php if ($authenticated): ?><form method="post" action="orque.php"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="logout"><button class="button button-quiet logout" type="submit" aria-label="Déconnexion"><?= icon('logout') ?><span>Déconnexion</span></button></form><?php endif; ?>
        </div>
    </div>
</header>
<?php if (!$authenticated): ?>
<main class="login-shell container">
    <section class="login-intro"><span class="eyebrow"><span class="status-dot"></span> VOTRE ESPACE PRIVÉ</span><h1>Les projets,<br>côté <em>coulisses.</em></h1><p>Un endroit pour organiser vos projets, soigner leur présentation et faire évoluer votre vitrine.</p><a class="text-link" href="./">Découvrir l’accueil <?= icon('arrow') ?></a></section>
    <section class="login-card" aria-labelledby="login-title"><div class="icon-tile"><?= icon('lock') ?></div><h2 id="login-title">Bienvenue à bord.</h2><p>Connectez-vous pour gérer vos projets.</p>
        <?php if ($notice): ?><div class="notice notice-<?= h($noticeType) ?>" role="alert"><?= h($notice) ?></div><?php endif; ?>
        <form method="post" action="orque.php" class="login-form"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="login"><label for="login-password">Mot de passe</label><input id="login-password" type="password" name="login_password" autocomplete="current-password" placeholder="Votre mot de passe" required autofocus><button class="button button-primary" type="submit">Se connecter <?= icon('arrow') ?></button></form>
        <div class="login-footnote"><?= icon('lock') ?> Accès réservé à l’administration</div>
    </section>
</main>
<?php else: ?>
<main class="container dashboard">
    <section class="page-heading"><div><span class="eyebrow"><span class="status-dot"></span> TABLEAU DE BORD</span><h1>Gérez vos <em>projets.</em></h1><p>Une vue d’ensemble claire. Une vitrine toujours à jour.</p></div><button class="button button-primary" type="button" data-add <?= $databaseError ? 'disabled' : '' ?>><?= icon('plus') ?> Ajouter un projet</button></section>
    <?php if ($notice): ?><div class="notice notice-<?= h($noticeType) ?>" role="<?= $noticeType === 'error' ? 'alert' : 'status' ?>"><?= icon($noticeType === 'error' ? 'close' : 'check') ?><span><?= h($notice) ?></span></div><?php endif; ?>
    <section class="stats" aria-label="Vue d’ensemble"><div class="stat"><span class="stat-icon"><?= icon('grid') ?></span><div><span class="stat-label">Projets</span><strong><?= str_pad((string) count($projects), 2, '0', STR_PAD_LEFT) ?></strong></div><span class="stat-note">sur votre accueil</span></div><div class="stat"><span class="stat-icon"><?= icon('lock') ?></span><div><span class="stat-label">Avec espace admin</span><strong><?= str_pad((string) $adminCount, 2, '0', STR_PAD_LEFT) ?></strong></div><span class="stat-note">projets administrables</span></div><div class="stat"><span class="stat-icon"><?= icon('layers') ?></span><div><span class="stat-label">Technologies</span><strong><?= str_pad((string) count($technologies), 2, '0', STR_PAD_LEFT) ?></strong></div><span class="stat-note">dans votre stack</span></div></section>
    <section class="project-panel" aria-labelledby="projects-title">
        <div class="panel-toolbar"><div><h2 id="projects-title">Tous les projets <span class="count-badge"><?= count($projects) ?></span></h2><p>Les projets apparaissent sur le site dans l’ordre indiqué.</p></div><label class="search-field"><?= icon('search') ?><span class="sr-only">Rechercher un projet</span><input id="project-search" type="search" placeholder="Rechercher un projet…" autocomplete="off"></label></div>
        <div class="table-scroll" role="region" aria-label="Tableau des projets" tabindex="0"><table><thead><tr><th scope="col" class="order-column">Ordre</th><th scope="col">Projet</th><th scope="col">Technologies</th><th scope="col">Espace admin</th><th scope="col" class="actions-column">Actions</th></tr></thead><tbody>
        <?php foreach ($projects as $project): ?>
        <tr data-project-row data-search="<?= h(mb_strtolower($project['titre'] . ' ' . $project['description'] . ' ' . $project['technologie'] . ' ' . $project['lien_projet'])) ?>">
            <td><span class="order-number"><?= h($project['ordre'] ?? 0) ?></span></td>
            <td class="project-cell"><div class="project-name"><span class="project-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($project['titre'], 0, 1))) ?></span><div><strong><?= h($project['titre']) ?></strong><a class="project-url" href="<?= h($project['lien_projet']) ?>" target="_blank" rel="noopener"><?= h(preg_replace('~^https?://~i', '', $project['lien_projet'])) ?> <?= icon('arrow') ?></a></div></div><p class="project-description"><?= h($project['description']) ?></p></td>
            <td class="technology-cell"><div class="technology-tags"><?php foreach (preg_split('/[·,|]+/u', $project['technologie']) as $tech): if (trim($tech) === '') continue; ?><span><?= h(trim($tech)) ?></span><?php endforeach; ?></div></td>
            <td><span class="admin-badge <?= (int) $project['espace_admin'] === 1 ? 'enabled' : '' ?>"><?= icon((int) $project['espace_admin'] === 1 ? 'check' : 'close') ?><?= (int) $project['espace_admin'] === 1 ? 'Disponible' : 'Aucun' ?></span></td>
            <td><div class="row-actions"><button class="button button-edit" type="button" data-edit="<?= (int) $project['id'] ?>" aria-label="Modifier <?= h($project['titre']) ?>"><?= icon('edit') ?><span>Modifier</span></button><button class="icon-button danger" type="button" data-delete="<?= (int) $project['id'] ?>" aria-label="Supprimer <?= h($project['titre']) ?>" title="Supprimer le projet"><?= icon('trash') ?></button></div></td>
        </tr>
        <?php endforeach; ?>
        <tr id="search-empty" <?= $projects ? 'hidden' : '' ?>><td colspan="5"><div class="empty-state"><?= icon('grid') ?><h3><?= $databaseError ? 'Chargement indisponible' : ($projects ? 'Aucun projet trouvé' : 'Votre premier projet vous attend') ?></h3><p><?= $databaseError ? 'Réessayez dans un instant.' : ($projects ? 'Essayez un autre mot-clé ou effacez la recherche.' : 'Ajoutez un projet pour le présenter sur votre accueil.') ?></p><?php if (!$projects && !$databaseError): ?><button class="button button-primary" type="button" data-add><?= icon('plus') ?> Ajouter un projet</button><?php endif; ?></div></td></tr>
        </tbody></table></div><div class="panel-footer"><span id="result-count" aria-live="polite"><?= count($projects) ?> projet<?= count($projects) > 1 ? 's' : '' ?></span><span>Les modifications sont visibles sur l’accueil après enregistrement.</span></div>
    </section>
    <div class="dashboard-bottom"><span>Une vitrine à votre image.</span><a class="text-link" href="./" target="_blank" rel="noopener">Voir le résultat <?= icon('arrow') ?></a></div>
</main>

<dialog id="editor-dialog" class="modal" aria-labelledby="editor-title"><div class="modal-header"><div><span class="eyebrow">VOTRE VITRINE</span><h2 id="editor-title">Ajouter un projet</h2></div><button class="icon-button" type="button" data-close aria-label="Fermer la fenêtre"><?= icon('close') ?></button></div><form method="post" action="orque.php" id="editor-form" class="editor-form"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="id" value=""><div class="modal-body"><p class="modal-intro" id="editor-intro">Présentez votre prochain projet sur votre accueil.</p>
    <div class="form-grid"><div class="field"><label for="project-title">Titre du projet <span>*</span></label><input id="project-title" name="titre" type="text" maxlength="255" placeholder="Ex. Réservoir Rock" required></div><div class="field"><label for="project-order">Ordre <span>*</span></label><input id="project-order" name="ordre" type="number" min="-2147483648" max="2147483647" step="1" value="<?= $nextOrder ?>" required></div></div>
    <div class="field"><label for="project-url">Adresse du site <span>*</span></label><input id="project-url" name="lien_projet" type="url" maxlength="255" placeholder="https://mon-projet.fr" required><small>Le lien ouvert depuis votre accueil.</small></div>
    <div class="field"><label for="project-technologies">Technologies <span>*</span></label><input id="project-technologies" name="technologie" type="text" maxlength="100" placeholder="Next.js · Prisma · Tailwind CSS" required><small>Séparez les technologies par un point médian (·) ou une virgule.</small></div>
    <div class="field"><label for="project-description">Description <span>*</span></label><textarea id="project-description" name="description" rows="4" maxlength="5000" placeholder="Quelques mots pour présenter le projet et ce qui le rend unique." required></textarea><small>Cette présentation apparaît sous le titre du projet sur l’accueil.</small></div>
    <label class="checkbox-field"><input type="checkbox" name="espace_admin" value="1"><span><strong>Ce projet possède un espace d’administration</strong><small>Affiche le repère « Espace admin » sur la vitrine.</small></span></label>
    </div><div class="modal-footer"><button class="button button-secondary" type="button" data-close>Annuler</button><button class="button button-primary" type="submit"><?= icon('check') ?><span id="save-label">Ajouter le projet</span></button></div></form></dialog>

<dialog id="delete-dialog" class="modal modal-small" aria-labelledby="delete-title"><div class="modal-header"><div class="delete-icon"><?= icon('trash') ?></div><button class="icon-button" type="button" data-close aria-label="Fermer la fenêtre"><?= icon('close') ?></button></div><form method="post" action="orque.php" id="delete-form"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value=""><div class="delete-body"><h2 id="delete-title">Supprimer ce projet ?</h2><p><strong id="delete-project-name"></strong> sera retiré de votre page d’accueil. Cette action est définitive.</p></div><div class="modal-footer"><button class="button button-secondary" type="button" data-close autofocus>Annuler</button><button class="button button-danger" type="submit"><?= icon('trash') ?> Supprimer le projet</button></div></form></dialog>
<script type="application/json" id="project-data"><?= json_encode(['projects' => $projects, 'reopen' => $reopen, 'nextOrder' => $nextOrder], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<?php endif; ?>
<footer class="site-footer container"><span>Orque &amp; Banquise</span><span><?= date('Y') ?> · Administration des projets</span></footer>
</body>
</html>
