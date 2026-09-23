<?php
/**
 * Traitement du formulaire de contact — Nurset YAZGOREN Expert Bâtiment
 *
 * À CONFIGURER AVANT MISE EN LIGNE (voir les 3 constantes ci-dessous) :
 *  - DEST_EMAIL         : l'adresse qui recevra les demandes
 *  - TURNSTILE_SECRET   : la clé secrète Cloudflare Turnstile (dashboard.cloudflare.com > Turnstile)
 *  - Le site key correspondant doit être mis dans contact.html (data-sitekey="...")
 */

header('Content-Type: application/json; charset=utf-8');

// ─── Configuration ───
const DEST_EMAIL       = 'contact@yazgoren-expert.fr';
const TURNSTILE_SECRET = 'YOUR_SECRET_KEY'; // à remplacer par la clé secrète Turnstile

function respond(bool $success, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Méthode non autorisée.');
}

// ─── Récupération et nettoyage des champs ───
function clean(string $key): string {
    return trim(filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW) ?? '');
}

$prenom        = clean('prenom');
$nom           = clean('nom');
$telephone     = clean('telephone');
$email         = clean('email');
$typeExpertise = clean('type_expertise');
$adresse       = clean('adresse');
$superficie    = clean('superficie');
$message       = clean('message');
$consentement  = clean('consentement');
$turnstileToken = clean('cf-turnstile-response');

// ─── Validation serveur (ne jamais faire confiance au JS seul) ───
$errors = [];

if ($prenom === '' || $nom === '')                       $errors[] = 'Nom et prénom requis.';
if ($telephone === '')                                    $errors[] = 'Téléphone requis.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
if ($typeExpertise === '')                                 $errors[] = "Type d'expertise requis.";
if ($message === '')                                       $errors[] = 'Description du problème requise.';
if ($consentement === '')                                  $errors[] = 'Consentement RGPD requis.';
if ($turnstileToken === '')                                 $errors[] = 'Vérification anti-robot manquante.';

if (!empty($errors)) {
    respond(false, implode(' ', $errors));
}

// ─── Vérification Cloudflare Turnstile ───
$verify = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => TURNSTILE_SECRET,
            'response' => $turnstileToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
    ],
]));

$verifyResult = json_decode($verify ?: '{}', true);

if (empty($verifyResult['success'])) {
    respond(false, 'Vérification anti-robot échouée, merci de réessayer.');
}

// ─── Construction et envoi de l'email ───
$sujet = "Nouvelle demande d'expertise — $prenom $nom";

$corps = "Nouvelle demande via le site yazgoren-expert.fr\n\n"
       . "Nom : $prenom $nom\n"
       . "Téléphone : $telephone\n"
       . "Email : $email\n"
       . "Type d'expertise : $typeExpertise\n"
       . "Adresse du bien : " . ($adresse !== '' ? $adresse : 'Non renseignée') . "\n"
       . "Superficie estimée : " . ($superficie !== '' ? $superficie : 'Non renseignée') . "\n\n"
       . "Description :\n$message\n";

$headers = "From: Site Web <no-reply@yazgoren-expert.fr>\r\n"
         . "Reply-To: " . $email . "\r\n"
         . "Content-Type: text/plain; charset=UTF-8";

$envoye = mail(DEST_EMAIL, $sujet, $corps, $headers);

if (!$envoye) {
    // Sur beaucoup d'hébergeurs mutualisés, mail() est peu fiable :
    // si ça échoue en prod, remplacer cet appel par un envoi SMTP (PHPMailer) ou un service tiers (Brevo, Postmark...).
    respond(false, "L'envoi a échoué côté serveur. Merci de réessayer ou de contacter directement par téléphone.");
}

respond(true, 'Message envoyé avec succès.');
