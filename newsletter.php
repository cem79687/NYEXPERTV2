<?php
/**
 * Inscription newsletter — Nurset YAZGOREN Expert Bâtiment
 *
 * Stocke les emails dans newsletter-subscribers.csv (à créer avec des droits
 * d'écriture pour le serveur web, en dehors du dossier public si possible)
 * et notifie l'admin. Pas de CAPTCHA ici : un simple email n'a pas le même
 * enjeu que le formulaire de contact, mais on limite les doublons et le spam basique.
 *
 * À CONFIGURER : ADMIN_EMAIL, et le chemin du fichier CSV si besoin.
 */

header('Content-Type: application/json; charset=utf-8');

const ADMIN_EMAIL = 'contact@yazgoren-expert.fr';
const CSV_PATH     = __DIR__ . '/newsletter-subscribers.csv';

function respond(bool $success, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Méthode non autorisée.');
}

$email = trim(filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW) ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Adresse email invalide.');
}

// Anti-doublon : vérifie si l'email est déjà inscrit
if (file_exists(CSV_PATH)) {
    $existing = file(CSV_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($existing as $line) {
        [$existingEmail] = str_getcsv($line);
        if (strcasecmp($existingEmail, $email) === 0) {
            respond(true, 'Vous êtes déjà inscrit(e).');
        }
    }
}

// Enregistrement
$fp = fopen(CSV_PATH, 'a');
if ($fp === false) {
    respond(false, "Erreur serveur, merci de réessayer plus tard.");
}
fputcsv($fp, [$email, date('Y-m-d H:i:s')]);
fclose($fp);

// Notification à l'admin (facultatif mais utile pour suivre les inscriptions)
@mail(
    ADMIN_EMAIL,
    'Nouvelle inscription newsletter',
    "Nouvel email inscrit à la newsletter : $email",
    "From: Site Web <no-reply@yazgoren-expert.fr>"
);

respond(true, 'Inscription réussie.');
