<?php
require_once "bd.php";

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer le chemin du fichier depuis la base de données
$sql = "SELECT nom_fichier, chemin FROM fichier ORDER BY date_upload DESC LIMIT 1"; // Prend le dernier fichier ajouté
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nom_fichier = $row['nom_fichier'];
    $chemin = $row['chemin'];

    // Vérifier si le fichier existe
    if (file_exists($chemin)) {
        // Envoyer le fichier en téléchargement
        header("Content-Description: File Transfer");
        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=\"" . basename($chemin) . "\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");
        header("Content-Length: " . filesize($chemin));

        readfile($chemin);
        exit;
    } else {
        echo "Fichier introuvable.";
    }
}

$conn->close();
?>
