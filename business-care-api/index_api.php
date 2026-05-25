<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

echo json_encode([
    "name" => "Business Care API",
    "version" => "1.0.0",
    "description" => "API pour la gestion de Business Care",
    "endpoints" => [
        "entreprises" => [
            "findAll" => "/api/entreprise/findAll.php",
            "findOne" => "/api/entreprise/findOne.php?id={id}",
            "create" => "/api/entreprise/create.php",
            "update" => "/api/entreprise/update.php",
            "delete" => "/api/entreprise/delete.php?id={id}"
        ],
        "salaries" => [
            "findAll" => "/api/salarie/findAll.php",
            "findOne" => "/api/salarie/findOne.php?id={id}",
            "findByEntreprise" => "/api/salarie/findByEntreprise.php?id_entreprise={id}",
            "create" => "/api/salarie/create.php",
            "update" => "/api/salarie/update.php",
            "delete" => "/api/salarie/delete.php?id={id}",
            "updatePassword" => "/api/salarie/updatePassword.php",
            "login" => "/api/salarie/login.php"
        ],
        "prestataires" => [
            "findAll" => "/api/prestataire/findAll.php",
            "findOne" => "/api/prestataire/findOne.php?id={id}",
            "findBySpecialite" => "/api/prestataire/findBySpecialite.php?specialite={specialite}",
            "create" => "/api/prestataire/create.php",
            "update" => "/api/prestataire/update.php",
            "delete" => "/api/prestataire/delete.php?id={id}",
            "updatePassword" => "/api/prestataire/updatePassword.php",
            "login" => "/api/prestataire/login.php"
        ],
        "evenements" => [
            "findAll" => "/api/evenement/findAll.php",
            "findOne" => "/api/evenement/findOne.php?id={id}",
            "findByEntreprise" => "/api/evenement/findByEntreprise.php?id_entreprise={id}",
            "findByPrestataire" => "/api/evenement/findByPrestataire.php?id_prestataire={id}",
            "create" => "/api/evenement/create.php",
            "update" => "/api/evenement/update.php",
            "delete" => "/api/evenement/delete.php?id={id}",
            "getInscriptions" => "/api/evenement/getInscriptions.php?id={id}",
            "inscrireSalarie" => "/api/evenement/inscrireSalarie.php",
            "desinscrireSalarie" => "/api/evenement/desinscrireSalarie.php"
        ],
        "rendez_vous" => [
            "findAll" => "/api/rendez-vous/findAll.php",
            "findOne" => "/api/rendez-vous/findOne.php?id={id}",
            "findBySalarie" => "/api/rendez-vous/findBySalarie.php?id_salarie={id}",
            "findByPrestataire" => "/api/rendez-vous/findByPrestataire.php?id_prestataire={id}",
            "create" => "/api/rendez-vous/create.php",
            "update" => "/api/rendez-vous/update.php",
            "delete" => "/api/rendez-vous/delete.php?id={id}"
        ]
    ]
]);