<?php
session_start();

// Vérification de la session
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

require_once '../../config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer l'ID du salarié
    $id_salarie = $_SESSION['user_id'];

    // Récupérer l'action (GET ou POST)
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : null);

    // Définir les quotas par formule
    $quotas = [
        'starter' => 6,
        'basic' => 20,
        'premium' => 999999
    ];

    // Fonction pour obtenir le quota
    function getQuota($conn, $id_salarie) {
        global $quotas;
        
        // Récupérer le type de formule
        $stmt = $conn->prepare("SELECT e.type_formule 
                               FROM entreprises e 
                               JOIN salaries s ON e.id_entreprise = s.id_entreprise 
                               WHERE s.id_salarie = ?");
        $stmt->execute([$id_salarie]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return ['error' => 'Entreprise non trouvée'];
        }
        
        $formule = $result['type_formule'];
        $quota_mensuel = $quotas[$formule];
        
        // Récupérer ou créer le quota du mois
        $mois = date('n');
        $annee = date('Y');
        
        $stmt = $conn->prepare("SELECT questions_posees, questions_total 
                               FROM quota_chatbot 
                               WHERE id_salarie = ? AND mois = ? AND annee = ?");
        $stmt->execute([$id_salarie, $mois, $annee]);
        $quota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quota) {
            // Créer un nouveau quota
            $stmt = $conn->prepare("INSERT INTO quota_chatbot 
                                   (id_salarie, mois, annee, questions_posees, questions_total) 
                                   VALUES (?, ?, ?, 0, ?)");
            $stmt->execute([$id_salarie, $mois, $annee, $quota_mensuel]);
            
            return [
                'questions_posees' => 0,
                'questions_total' => $quota_mensuel,
                'reste' => $quota_mensuel,
                'formule' => $formule
            ];
        }
        
        return [
            'questions_posees' => (int)$quota['questions_posees'],
            'questions_total' => (int)$quota['questions_total'],
            'reste' => (int)$quota['questions_total'] - (int)$quota['questions_posees'],
            'formule' => $formule
        ];
    }

    // Gérer les actions
    switch ($action) {
        case 'get_quota':
            header('Content-Type: application/json');
            echo json_encode(getQuota($conn, $id_salarie));
            break;
            
        case 'ask_question':
            // Vérifier le quota
            $quota = getQuota($conn, $id_salarie);
            
            if (isset($quota['error'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $quota['error']]);
                exit();
            }
            
            // Vérifier si quota épuisé
            if ($quota['reste'] <= 0 && $quota['formule'] !== 'premium') {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Quota épuisé',
                    'response' => 'Vous avez épuisé votre quota de questions pour ce mois-ci.'
                ]);
                exit();
            }
            
            // Récupérer la question
            $question = isset($_POST['question']) ? trim($_POST['question']) : '';
            if (empty($question)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Question vide']);
                exit();
            }
            
            // Recherche simple dans la base
            $questionClean = strtolower($question);
            
            // recherche exacte
            $stmt = $conn->prepare("SELECT response FROM chatbot_responses WHERE LOWER(question) = ?");
            $stmt->execute([$questionClean]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // recherche partielle
            if (!$result) {
                $stmt = $conn->prepare("SELECT response FROM chatbot_responses WHERE LOWER(question) LIKE ?");
                $stmt->execute(['%' . $questionClean . '%']);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Méthode 3 : Recherche par mots-clés
            if (!$result) {
                $mots = explode(' ', $questionClean);
                foreach ($mots as $mot) {
                    if (strlen($mot) > 3) {
                        $stmt = $conn->prepare("SELECT response FROM chatbot_responses WHERE LOWER(question) LIKE ?");
                        $stmt->execute(['%' . $mot . '%']);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) break;
                    }
                }
            }
            
            $response = $result ? $result['response'] : "Désolé, je ne comprends pas votre question. Essayez : Comment s'inscrire à un événement ?";
            
            // Mettre à jour le quota si pas premium
            if ($quota['formule'] !== 'premium') {
                $stmt = $conn->prepare("UPDATE quota_chatbot 
                                       SET questions_posees = questions_posees + 1 
                                       WHERE id_salarie = ? AND mois = ? AND annee = ?");
                $stmt->execute([$id_salarie, date('n'), date('Y')]);
            }
            
            // Nouveau quota
            $new_quota = getQuota($conn, $id_salarie);
            
            header('Content-Type: application/json');
            echo json_encode([
                'response' => $response,
                'quota' => $new_quota
            ]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?>