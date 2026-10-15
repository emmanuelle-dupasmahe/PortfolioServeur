<?php
session_start();

// --- CONFIGURATION ---
$mot_de_passe_admin = "orque2026"; // Mot de passe pour accéder à la page
$host = 'localhost';
$dbname = 'portfolio_db';
$username = 'root';
$password = '';

// --- GESTION DE LA CONNEXION ---
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === $mot_de_passe_admin) {
        $_SESSION['admin_connecte'] = true;
    } else {
        $erreur = "Mot de passe incorrect.";
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// --- SI NON CONNECTÉ : AFFICHER LE LOGIN ---
if (!isset($_SESSION['admin_connecte'])) {
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
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

// --- SI CONNECTÉ : TRAITEMENT ET AFFICHAGE DU BACK-OFFICE ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Traitement de l'ajout d'un projet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $lien_projet = $_POST['lien_projet'];
    $technologie = $_POST['technologie'];
    $image_url = '#'; // Valeur par défaut car l'affichage utilise des iframes
    $espace_admin = isset($_POST['espace_admin']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO projets (titre, description, image_url, lien_projet, technologie, espace_admin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titre, $description, $image_url, $lien_projet, $technologie, $espace_admin]);

    $message_succes = "Le projet a été ajouté avec succès !";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Administration des projets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#0b1329] text-gray-100 p-8 font-sans">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 border-b border-[#1e293b] pb-4">
            <h1 class="text-3xl font-light text-[#38bdf8]">Ajouter un projet</h1>
            <div>
                <a href="index.php" class="text-gray-400 hover:text-white mr-6 transition-colors">Voir le site</a>
                <a href="?logout=1" class="text-red-400 hover:text-red-300 transition-colors">Déconnexion</a>
            </div>
        </div>

        <?php if (isset($message_succes)): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 text-emerald-200 p-4 rounded mb-6">
                <?= $message_succes ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-[#111c38] p-6 rounded-lg shadow-lg border border-[#1e293b]">
            <input type="hidden" name="action" value="add">

            <div class="mb-5">
                <label class="block mb-2 font-medium">Titre du projet</label>
                <input type="text" name="titre" placeholder="Ex: Réservoir Rock" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Lien du projet (URL)</label>
                <input type="url" name="lien_projet" placeholder="Ex: http://vps-xxx.vps.ovh.net/resrock/" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Technologies utilisées</label>
                <input type="text" name="technologie" placeholder="Ex: Next.js · Prisma · Tailwind" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors">
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium">Description</label>
                <textarea name="description" rows="3" required class="w-full p-2.5 rounded bg-[#070d1d] border border-[#1e293b] text-white focus:outline-none focus:border-[#38bdf8] transition-colors"></textarea>
            </div>

            <div class="mb-8 flex items-center">
                <input type="checkbox" id="espace_admin" name="espace_admin" value="1" class="w-5 h-5 accent-[#38bdf8] rounded cursor-pointer">
                <label for="espace_admin" class="ml-3 font-medium cursor-pointer">Ce projet possède un espace d'administration</label>
            </div>

            <button type="submit" class="bg-[#38bdf8] hover:bg-sky-400 text-black font-medium py-2.5 px-6 rounded transition-colors">
                Enregistrer le projet
            </button>
        </form>
    </div>
</body>

</html>