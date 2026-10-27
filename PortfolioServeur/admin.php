<?php
session_start();

require_once 'config.php';

// Nom de ce fichier (admin.php en local, orque.php sur le serveur)
$page_admin = basename($_SERVER['PHP_SELF']);

// --- GESTION DE LA CONNEXION ---
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === $mot_de_passe_admin) {
        $_SESSION['admin_connecte'] = true;
    } else {
        $erreur = "Mot de passe incorrect.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $page_admin);
    exit;
}

// --- SI NON CONNECTÉ : AFFICHER LE LOGIN ---
if (!isset($_SESSION['admin_connecte'])) {
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="robots" content="noindex, nofollow">
        <title>Connexion - Back-office</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-[#0b1329] text-gray-100 flex items-center justify-center h-screen">
        <form method="POST" class="bg-[#111c38] p-8 rounded-lg shadow-lg w-96 border border-[#1e293b]">
            <h1 class="text-2xl mb-6 text-[#38bdf8]">Espace Admin</h1>
            <?php if (isset($erreur)) echo "<p class='text-red-500 mb-4'>$erreur</p>"; ?>
            <label class="block mb-2">Mot de passe</label>
            <input type="password" name="login_password" class="w-full p-2 rounded bg-[#070d1d] border border-[#1e293b] mb-4 text-white focus:outline-none focus:border-[#38bdf8]" required>
            <button type="submit" class="w-full bg-[#38bdf8] text-black font-medium py-2 rounded hover:bg-sky-400 transition-colors">Se connecter</button>
        </form>
    </body>

    </html>
<?php
    exit;
}

// --- SI CONNECTÉ : TRAITEMENT DE LA BDD ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$message = null;

// GESTION DES ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $lien_projet = $_POST['lien_projet'] ?? '';
    $technologie = $_POST['technologie'] ?? '';
    $ordre = $_POST['ordre'] ?? 0;
    $espace_admin = isset($_POST['espace_admin']) ? 1 : 0;

    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO projets (titre, description, image_url, lien_projet, technologie, espace_admin, ordre) VALUES (?, ?, '#', ?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $lien_projet, $technologie, $espace_admin, $ordre]);
        $message = "Le projet a été ajouté avec succès !";
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE projets SET titre=?, description=?, lien_projet=?, technologie=?, espace_admin=?, ordre=? WHERE id=?");
        $stmt->execute([$titre, $description, $lien_projet, $technologie, $espace_admin, $ordre, $id]);
        $message = "Le projet a été modifié avec succès !";
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM projets WHERE id=?");
        $stmt->execute([$id]);
        $message = "Le projet a été supprimé.";
    }
}

// CHARGEMENT POUR ÉDITION
$projet_a_modifier = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $projet_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
}

// RÉCUPÉRATION DE TOUS LES PROJETS
$projets = $pdo->query("SELECT * FROM projets ORDER BY ordre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Administration des projets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#0b1329] text-gray-100 p-8 font-sans">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 border-b border-[#1e293b] pb-4">
            <h1 class="text-3xl font-light text-[#38bdf8]">Gestion des projets</h1>
            <div>
                <a href="index.php" class="text-gray-400 hover:text-white mr-6 transition-colors">Voir le site</a>
                <a href="?logout=1" class="text-red-400 hover:text-red-300 transition-colors">Déconnexion</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-sky-900/50 border border-[#38bdf8] text-sky-200 p-4 rounded mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULAIRE -->
        <form method="POST" action="<?= htmlspecialchars($page_admin) ?>" class="bg-[#111c38] p-6 rounded-lg shadow-lg border border-[#1e293b] mb-12">

            <?php if ($projet_a_modifier): ?>
                <h2 class="text-xl text-[#38bdf8] mb-4">Modifier le projet : <?= htmlspecialchars($projet_a_modifier['titre']) ?></h2>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $projet_a_modifier['id'] ?>">
            <?php else: ?>
                <h2 class="text-xl text-[#38bdf8] mb-4">Ajouter un nouveau projet</h2>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <div class="grid grid-cols-4 gap-4 mb-5">
                <div class="col-span-3">
                    <label class="block mb-2 font-medium">Titre du projet</label>
                    <input type="text" name="titre" value="<?= $projet_a_modifier ? htmlspecialchars($projet_a_modifier['titre']) : '' ?>" placeholder="Ex: Réservoir Rock" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
                </div>
                <div class="col-span-1">
                    <label class="block mb-2 font-medium">Ordre (ex: 1)</label>
                    <input type="number" name="ordre" value="<?= $projet_a_modifier ? htmlspecialchars($projet_a_modifier['ordre']) : '0' ?>" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
                </div>
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Lien du projet (URL)</label>
                <input type="url" name="lien_projet" value="<?= $projet_a_modifier ? htmlspecialchars($projet_a_modifier['lien_projet']) : '' ?>" placeholder="Ex: http://vps-xxx.vps.ovh.net/resrock/" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Technologies utilisées (copier ce point: · )</label>
                <input type="text" name="technologie" value="<?= $projet_a_modifier ? htmlspecialchars($projet_a_modifier['technologie']) : '' ?>" placeholder="Ex: Next.js · Prisma · Tailwind" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Description</label>
                <textarea name="description" rows="3" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors"><?= $projet_a_modifier ? htmlspecialchars($projet_a_modifier['description']) : '' ?></textarea>
            </div>

            <div class="mb-8 flex items-center">
                <input type="checkbox" id="espace_admin" name="espace_admin" value="1" <?= ($projet_a_modifier && $projet_a_modifier['espace_admin'] == 1) ? 'checked' : '' ?> class="w-5 h-5 accent-[#38bdf8] rounded cursor-pointer">
                <label for="espace_admin" class="ml-3 font-medium cursor-pointer">Ce projet possède un espace d'administration</label>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="bg-[#38bdf8] hover:bg-sky-400 text-black font-medium py-2.5 px-6 rounded transition-colors">
                    <?= $projet_a_modifier ? 'Mettre à jour le projet' : 'Enregistrer le projet' ?>
                </button>
                <?php if ($projet_a_modifier): ?>
                    <a href="<?= htmlspecialchars($page_admin) ?>" class="text-gray-400 hover:text-white transition-colors">Annuler la modification</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- LISTE -->
        <h2 class="text-2xl font-light text-[#38bdf8] mb-6">Projets existants (<?= count($projets) ?>)</h2>
        <div class="grid gap-4 mb-16">
            <?php foreach ($projets as $p): ?>
                <div class="bg-[#111c38] p-4 rounded border border-[#1e293b] flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="bg-[#070d1d] border border-[#1e293b] text-gray-400 w-10 h-10 rounded flex items-center justify-center font-medium">
                            #<?= htmlspecialchars($p['ordre']) ?>
                        </div>
                        <div>
                            <h3 class="font-medium text-lg"><?= htmlspecialchars($p['titre']) ?></h3>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($p['technologie']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="?edit=<?= $p['id'] ?>" class="text-[#38bdf8] hover:underline text-sm font-medium">Modifier</a>
                        <form method="POST" action="<?= htmlspecialchars($page_admin) ?>" onsubmit="return confirm('Es-tu sûr(e) de vouloir supprimer définitivement ce projet ?');" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300 hover:underline text-sm font-medium">Supprimer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>