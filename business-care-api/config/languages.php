<?php


// Définir les langues disponibles
$languages = [
    'fr' => [
        'name' => 'Français',
        'flag' => '🇫🇷',
        'code' => 'fr_FR'
    ],
    'en' => [
        'name' => 'English',
        'flag' => '🇬🇧',
        'code' => 'en_US'
    ],
    'es' => [
        'name' => 'Español',
        'flag' => '🇪🇸',
        'code' => 'es_ES'
    ],
    'de' => [
        'name' => 'Deutsch',
        'flag' => '🇩🇪',
        'code' => 'de_DE'
    ]
];

// Récupérer la langue actuelle
session_start();
$current_lang = $_SESSION['lang'] ?? 'fr';

// Fonction pour obtenir une traduction
function __($key, $lang = null) {
    global $current_lang, $translations;
    $lang = $lang ?? $current_lang;
    
    return $translations[$lang][$key] ?? $key;
}

// Traductions
$translations = [
    'fr' => [
        // Page d'accueil
        'hero_title' => 'La santé et le bien-être au cœur de votre entreprise',
        'hero_subtitle' => 'Business Care accompagne les entreprises dans l\'amélioration du bien-être de leurs collaborateurs.',
        'discover_services' => 'Découvrir nos services',
        'contact_us' => 'Nous contacter',
        'our_services' => 'Nos Services',
        'services_subtitle' => 'Une approche complète pour le bien-être professionnel',
        'workplace_health' => 'Santé au travail',
        'workplace_health_desc' => 'Consultations médicales, suivi personnalisé et prévention des risques professionnels.',
        'mental_wellbeing' => 'Bien-être mental',
        'mental_wellbeing_desc' => 'Accompagnement psychologique, gestion du stress et développement personnel.',
        'team_cohesion' => 'Cohésion d\'équipe',
        'team_cohesion_desc' => 'Activités de team building, événements et ateliers collaboratifs.',
        
        // Header
        'login' => 'Connexion',
        'register' => 'Inscription',
        
        // Footer
        'footer_description' => 'Améliorer la santé et le bien-être en entreprise depuis 2018.',
        'navigation' => 'Navigation',
        'home' => 'Accueil',
        'news' => 'Actualités',
        'services' => 'Services',
        'about' => 'À propos',
        'contact' => 'Contact',
        'address' => '110 rue de Rivoli, Paris',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@business-care.fr',
        'all_rights_reserved' => 'Tous droits réservés',
        
        // Header/Navigation
        'dashboard' => 'Tableau de bord',
        'contracts' => 'Contrats',
        'request_quote' => 'Demander un devis',
        'logout' => 'Déconnexion',
        
        // Contrat page
        'my_contract' => 'Mon Contrat',
        'no_active_contract' => 'Aucun contrat actif',
        'start_with_quote' => 'Pour souscrire à Business Care, commencez par demander un devis.',
        'current_contract' => 'Contrat en cours',
        'contract_number' => 'N° Contrat',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
        'formula' => 'Formule',
        'amount' => 'Montant',
        'payment_type' => 'Type de paiement',
        'status' => 'Statut',
        'next_due_date' => 'Prochaine échéance',
        'included_services' => 'Services inclus',
        'recent_invoices' => 'Factures récentes',
        'date' => 'Date',
        'actions' => 'Actions',
        'no_invoice_found' => 'Aucune facture trouvée'
    ],
    'en' => [
        // Home page
        'hero_title' => 'Health and well-being at the heart of your company',
        'hero_subtitle' => 'Business Care supports companies in improving the well-being of their employees.',
        'discover_services' => 'Discover our services',
        'contact_us' => 'Contact us',
        'our_services' => 'Our Services',
        'services_subtitle' => 'A comprehensive approach to professional well-being',
        'workplace_health' => 'Workplace Health',
        'workplace_health_desc' => 'Medical consultations, personalized follow-up and prevention of occupational risks.',
        'mental_wellbeing' => 'Mental Well-being',
        'mental_wellbeing_desc' => 'Psychological support, stress management and personal development.',
        'team_cohesion' => 'Team Cohesion',
        'team_cohesion_desc' => 'Team building activities, events and collaborative workshops.',
        
        // Header
        'login' => 'Login',
        'register' => 'Register',
        
        // Footer
        'footer_description' => 'Improving health and well-being in the workplace since 2018.',
        'navigation' => 'Navigation',
        'home' => 'Home',
        'news' => 'News',
        'services' => 'Services',
        'about' => 'About',
        'contact' => 'Contact',
        'address' => '110 rue de Rivoli, Paris',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@business-care.fr',
        'all_rights_reserved' => 'All rights reserved',
        
        // Header/Navigation
        'dashboard' => 'Dashboard',
        'contracts' => 'Contracts',
        'request_quote' => 'Request a quote',
        'logout' => 'Logout',
        
        // Contract page
        'my_contract' => 'My Contract',
        'no_active_contract' => 'No active contract',
        'start_with_quote' => 'To subscribe to Business Care, start by requesting a quote.',
        'current_contract' => 'Current contract',
        'contract_number' => 'Contract No.',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'formula' => 'Formula',
        'amount' => 'Amount',
        'payment_type' => 'Payment type',
        'status' => 'Status',
        'next_due_date' => 'Next due date',
        'included_services' => 'Included services',
        'recent_invoices' => 'Recent invoices',
        'date' => 'Date',
        'actions' => 'Actions',
        'no_invoice_found' => 'No invoice found'
    ],
    'es' => [
        // Página de inicio
        'hero_title' => 'La salud y el bienestar en el corazón de su empresa',
        'hero_subtitle' => 'Business Care acompaña a las empresas en la mejora del bienestar de sus colaboradores.',
        'discover_services' => 'Descubrir nuestros servicios',
        'contact_us' => 'Contáctanos',
        'our_services' => 'Nuestros Servicios',
        'services_subtitle' => 'Un enfoque integral para el bienestar profesional',
        'workplace_health' => 'Salud en el trabajo',
        'workplace_health_desc' => 'Consultas médicas, seguimiento personalizado y prevención de riesgos laborales.',
        'mental_wellbeing' => 'Bienestar mental',
        'mental_wellbeing_desc' => 'Acompañamiento psicológico, gestión del estrés y desarrollo personal.',
        'team_cohesion' => 'Cohesión de equipo',
        'team_cohesion_desc' => 'Actividades de team building, eventos y talleres colaborativos.',
        
        // Footer
        'footer_description' => 'Mejorando la salud y el bienestar en la empresa desde 2018.',
        'navigation' => 'Navegación',
        'home' => 'Inicio',
        'news' => 'Noticias',
        'services' => 'Servicios',
        'about' => 'Acerca de',
        'contact' => 'Contacto',
        'address' => '110 rue de Rivoli, París',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@business-care.fr',
        'all_rights_reserved' => 'Todos los derechos reservados',
        
        // Header/Navigation
        'dashboard' => 'Panel de control',
        'contracts' => 'Contratos',
        'request_quote' => 'Solicitar presupuesto',
        'logout' => 'Cerrar sesión',
        
        // Contract page
        'my_contract' => 'Mi Contrato',
        'no_active_contract' => 'Sin contrato activo',
        'start_with_quote' => 'Para suscribirse a Business Care, comience solicitando un presupuesto.',
        'current_contract' => 'Contrato actual',
        'contract_number' => 'N° Contrato',
        'start_date' => 'Fecha de inicio',
        'end_date' => 'Fecha de finalización',
        'formula' => 'Fórmula',
        'amount' => 'Importe',
        'payment_type' => 'Tipo de pago',
        'status' => 'Estado',
        'next_due_date' => 'Próximo vencimiento',
        'included_services' => 'Servicios incluidos',
        'recent_invoices' => 'Facturas recientes',
        'date' => 'Fecha',
        'actions' => 'Acciones',
        'no_invoice_found' => 'No se encontraron facturas'
    ],
    'de' => [
        // Startseite
        'hero_title' => 'Gesundheit und Wohlbefinden im Herzen Ihres Unternehmens',
        'hero_subtitle' => 'Business Care unterstützt Unternehmen bei der Verbesserung des Wohlbefindens ihrer Mitarbeiter.',
        'discover_services' => 'Unsere Dienstleistungen entdecken',
        'contact_us' => 'Kontaktieren Sie uns',
        'our_services' => 'Unsere Dienstleistungen',
        'services_subtitle' => 'Ein umfassender Ansatz für berufliches Wohlbefinden',
        'workplace_health' => 'Gesundheit am Arbeitsplatz',
        'workplace_health_desc' => 'Medizinische Beratungen, persönliche Betreuung und Prävention von Berufsrisiken.',
        'mental_wellbeing' => 'Psychisches Wohlbefinden',
        'mental_wellbeing_desc' => 'Psychologische Begleitung, Stressbewältigung und persönliche Entwicklung.',
        'team_cohesion' => 'Teamzusammenhalt',
        'team_cohesion_desc' => 'Teambuilding-Aktivitäten, Veranstaltungen und Kooperationsworkshops.',
        
        // Footer
        'footer_description' => 'Verbesserung von Gesundheit und Wohlbefinden im Unternehmen seit 2018.',
        'navigation' => 'Navigation',
        'home' => 'Startseite',
        'news' => 'Nachrichten',
        'services' => 'Dienstleistungen',
        'about' => 'Über uns',
        'contact' => 'Kontakt',
        'address' => '110 rue de Rivoli, Paris',
        'phone' => '01 23 45 67 89',
        'email' => 'contact@business-care.fr',
        'all_rights_reserved' => 'Alle Rechte vorbehalten',
        
        // Header/Navigation
        'dashboard' => 'Dashboard',
        'contracts' => 'Verträge',
        'request_quote' => 'Angebot anfordern',
        'logout' => 'Abmelden',
        
        // Contract page
        'my_contract' => 'Mein Vertrag',
        'no_active_contract' => 'Kein aktiver Vertrag',
        'start_with_quote' => 'Um Business Care zu abonnieren, fordern Sie zunächst ein Angebot an.',
        'current_contract' => 'Aktueller Vertrag',
        'contract_number' => 'Vertragsnr.',
        'start_date' => 'Startdatum',
        'end_date' => 'Enddatum',
        'formula' => 'Formel',
        'amount' => 'Betrag',
        'payment_type' => 'Zahlungsart',
        'status' => 'Status',
        'next_due_date' => 'Nächste Fälligkeit',
        'included_services' => 'Enthaltene Leistungen',
        'recent_invoices' => 'Aktuelle Rechnungen',
        'date' => 'Datum',
        'actions' => 'Aktionen',
        'no_invoice_found' => 'Keine Rechnungen gefunden'
    ]
];