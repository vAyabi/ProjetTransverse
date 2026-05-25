package com.businesscare;

import java.io.InputStream;
import java.util.Properties;
import java.util.logging.Level;
import java.util.logging.Logger;


public class Main {
    private static final Logger LOGGER = Logger.getLogger(Main.class.getName());
    private static Properties applicationConfig = new Properties();


    private static boolean loadConfiguration() {
        try (InputStream input = Main.class.getClassLoader().getResourceAsStream("application.properties")) {
            if (input == null) {
                System.err.println("ERREUR: Impossible de trouver le fichier application.properties");
                return false;
            }

            applicationConfig.load(input);
            LOGGER.info("Configuration chargée avec succès");

            // Configurer le niveau de log
            String logLevel = applicationConfig.getProperty("logging.level", "INFO");
            try {
                LOGGER.setLevel(Level.parse(logLevel));
            } catch (IllegalArgumentException e) {
                LOGGER.warning("Niveau de log invalide dans la configuration: " + logLevel);
                LOGGER.setLevel(Level.INFO);
            }

            return true;
        } catch (Exception e) {
            System.err.println("ERREUR lors du chargement de la configuration: " + e.getMessage());
            e.printStackTrace();
            return false;
        }
    }

    /**
     * Méthode principale
     */
    public static void main(String[] args) {
        try {
            // Charger la configuration
            if (!loadConfiguration()) {
                System.err.println("Impossible de démarrer l'application sans configuration");
                return;
            }

            // Assurez-vous que les bibliothèques nécessaires sont chargées
            Class.forName("org.jfree.chart.ChartFactory");
            Class.forName("com.itextpdf.text.Document");
            Class.forName("org.json.JSONObject");

            // Configurer l'API client avec les paramètres du fichier de configuration
            String apiHost = applicationConfig.getProperty("api.host", "http://localhost");
            int apiPort = Integer.parseInt(applicationConfig.getProperty("api.port", "80"));
            String apiPath = applicationConfig.getProperty("api.path", "/business-care/api");

            LOGGER.info(String.format("Configuration de l'API: %s:%d%s", apiHost, apiPort, apiPath));

            System.out.println("Démarrage de l'application Business Care...");

            // Tester la connexion à l'API
            APIClient apiClient = new APIClient();
            if (!apiClient.testConnection()) {
                System.err.println("AVERTISSEMENT: Impossible de se connecter à l'API. Vérifiez que votre serveur est en cours d'exécution.");
                System.err.println("L'application va démarrer, mais certaines fonctionnalités pourraient ne pas fonctionner correctement.");
            }

            // Créer et démarrer l'interface utilisateur
            MainUI mainUI = new MainUI();
            mainUI.startApplication();

        } catch (ClassNotFoundException e) {
            System.err.println("ERREUR: Une bibliothèque nécessaire est manquante!");
            System.err.println("Détails: " + e.getMessage());
            e.printStackTrace();

            System.err.println("\nVeuillez vous assurer que toutes les bibliothèques requises sont dans le classpath:");
            System.err.println("- iText (pour la génération PDF)");
            System.err.println("- JFreeChart (pour les graphiques)");
            System.err.println("- org.json (pour le traitement JSON)");
        } catch (Exception e) {
            System.err.println("Une erreur s'est produite lors du démarrage de l'application: " + e.getMessage());
            e.printStackTrace();
        }
    }

    /**
     * Retourne la configuration de l'application
     */
    public static Properties getConfig() {
        return applicationConfig;
    }
}