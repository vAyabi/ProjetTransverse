<?php
session_start();
if(!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}


$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/business-care-api/config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Vérifier que le paramètre de salle est fourni
if(!isset($_GET['room']) || empty($_GET['room'])) {
    $_SESSION['error'] = "Paramètre de salle manquant.";
    if ($_SESSION['user_type'] === 'salaries') {
        header('Location: /business-care-api/dashboards/salaries/rdv_medicaux/index.php');
    } else {
        header('Location: /business-care-api/dashboards/prestataires/rdv_medicaux.php');
    }
    exit();
}

$room_name = htmlspecialchars($_GET['room']);
$user_name = htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur');
$user_type = $_SESSION['user_type']; // 'salaries' ou 'prestataires'
$user_id = $_SESSION['user_id'];


$sql = "SELECT r.*, s.id_salarie, s.nom AS nom_salarie, p.id_prestataire, p.nom AS nom_prestataire
        FROM rendez_vous_medicaux r
        JOIN salaries s ON r.id_salarie = s.id_salarie
        JOIN prestataires p ON r.id_prestataire = p.id_prestataire
        WHERE r.jitsi_room_name = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$room_name]);
$rdv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rdv) {
    $_SESSION['error'] = "Salle de visioconférence introuvable.";
    if ($user_type === 'salaries') {
        header('Location: /business-care-api/dashboards/salaries/rdv_medicaux/index.php');
    } else {
        header('Location: /business-care-api/dashboards/prestataires/rdv_medicaux.php');
    }
    exit();
}

// Vérifier que l'utilisateur actuel est soit le salarié, soit le prestataire concerné
$user_authorized = false;
$is_salarie = false;

if ($user_type === 'salaries' && $user_id == $rdv['id_salarie']) {
    $user_authorized = true;
    $is_salarie = true;
} else if ($user_type === 'prestataires' && $user_id == $rdv['id_prestataire']) {
    $user_authorized = true;
}

if (!$user_authorized) {
    $_SESSION['error'] = "Vous n'êtes pas autorisé à accéder à cette visioconférence.";
    if ($user_type === 'salaries') {
        header('Location: /business-care-api/dashboards/salaries/rdv_medicaux/index.php');
    } else {
        header('Location: /business-care-api/dashboards/prestataires/rdv_medicaux.php');
    }
    exit();
}


$page_title = "Consultation vidéo";


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Business Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .visio-container {
            height: calc(100vh - 60px);
            width: 100%;
            overflow: hidden;
        }
        .controls {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(255,255,255,0.8);
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="visio-container">
        <div id="jitsi-container" style="height: 100%; width: 100%;"></div>
    </div>
    
    <div class="controls">
        <a href="<?= $is_salarie ? '/business-care-api/dashboards/salaries/rdv_medicaux/index.php' : '/business-care-api/dashboards/prestataires/rdv_medicaux.php' ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Quitter
        </a>
    </div>

    <script src='https://meet.jit.si/external_api.js'></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const domain = 'meet.jit.si';
        const options = {
            roomName: '<?= $room_name ?>',
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#jitsi-container'),
            interfaceConfigOverwrite: {
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                    'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                    'videoquality', 'filmstrip', 'feedback', 'stats', 'shortcuts',
                    'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                    'security'
                ],
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                DEFAULT_REMOTE_DISPLAY_NAME: 'Participant',
                DEFAULT_BACKGROUND: '#f5f5f5',
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                ENABLE_DIAL_OUT: false,
                HIDE_INVITE_MORE_HEADER: true
            },
            configOverwrite: {
                disableDeepLinking: true,
                prejoinPageEnabled: false,
                disableInviteFunctions: true,
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                enableWelcomePage: false,
                enableClosePage: false,
                disableTileView: false,
                hideConferenceSubject: true,
                hideConferenceTimer: false,
                disableThirdPartyRequests: true,
                analytics: {
                    disabled: true,
                    googleAnalyticsTrackingId: ''
                }
            },
            userInfo: {
                displayName: '<?= $user_name ?>'
            }
        };
        
        const api = new JitsiMeetExternalAPI(domain, options);
        
        
        api.addEventListeners({
            readyToClose: function() {
                window.location.href = '<?= $is_salarie ? "/business-care-api/dashboards/salaries/rdv_medicaux/index.php" : "/business-care-api/dashboards/prestataires/rdv_medicaux.php" ?>';
            }
        });
    });
    </script>
</body>
</html>