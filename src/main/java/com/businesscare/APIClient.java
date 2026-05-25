package com.businesscare;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Properties;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.json.JSONObject;
import org.json.JSONArray;

/**
 * Client API pour communiquer avec le backend de Business Care
 */
public class APIClient {
    private static final Logger LOGGER = Logger.getLogger(APIClient.class.getName());

    // Configuration de base de l'API
    private final String apiHost;
    private final int apiPort;
    private final String apiPath;
    private final String apiBaseUrl;

    public APIClient() {
        LOGGER.setLevel(Level.INFO);

        // ici on recuperer les donnéees du fichier application.properties
        Properties config = Main.getConfig();
        this.apiHost = config.getProperty("api.host", "http://localhost");
        this.apiPort = Integer.parseInt(config.getProperty("api.port", "80"));
        this.apiPath = config.getProperty("api.path", "/business-care-api/api");

        this.apiBaseUrl = apiHost + ":" + apiPort + apiPath;
        LOGGER.info("Initialisation du client API avec l'URL de base: " + apiBaseUrl);
    }

    /**
     * Effectue une requête HTTP GET vers l'API
     *
     * @param endpoint Le point de terminaison de l'API
     * @return La réponse JSON sous forme de chaîne de caractères
     * @throws Exception Si une erreur se produit lors de la requête
     */
    public String get(String endpoint) throws Exception {
        String urlString = apiBaseUrl + endpoint;
        LOGGER.info("Requête GET vers: " + urlString);

        URL url = new URL(urlString);
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        connection.setRequestMethod("GET");
        connection.setRequestProperty("Accept", "application/json");
        connection.setConnectTimeout(5000); // 5 secondes timeout
        connection.setReadTimeout(5000);

        int responseCode = connection.getResponseCode();
        LOGGER.info("Code de réponse: " + responseCode);

        BufferedReader in;
        if (responseCode >= 400) {
            in = new BufferedReader(new InputStreamReader(connection.getErrorStream()));
        } else {
            in = new BufferedReader(new InputStreamReader(connection.getInputStream()));
        }

        String inputLine;
        StringBuilder response = new StringBuilder();

        while ((inputLine = in.readLine()) != null) {
            response.append(inputLine);
        }
        in.close();

        String responseString = response.toString();
        // Log complet de la réponse pour le débogage
        LOGGER.fine("Réponse brute reçue: " + responseString);

        if (responseCode != 200) {
            throw new RuntimeException("Échec de la requête HTTP vers " + urlString + " avec le code: " + responseCode + ". Réponse: " + responseString);
        }

        return responseString;
    }

    /**
     * Récupère les données des entreprises
     *
     * @return Un tableau JSON contenant les données des entreprises
     * @throws Exception Si une erreur se produit lors de la requête
     */
    public JSONArray getClientData() throws Exception {
        String response = get("/entreprise/findAll.php");
        try {
            // Transformer la réponse en objet JSON
            JSONObject jsonResponse = new JSONObject(response);

            // Vérifier le statut de la réponse
            if (!jsonResponse.getString("status").equals("success")) {
                throw new Exception("Erreur API: " + jsonResponse.optString("message", "Erreur inconnue"));
            }

            // Extraire le tableau d'entreprises
            return jsonResponse.getJSONObject("data").getJSONArray("entreprises");
        } catch (Exception e) {
            LOGGER.severe("Erreur lors du parsing JSON de la réponse: " + e.getMessage());
            LOGGER.severe("Réponse reçue: " + response);
            throw e;
        }
    }

    /**
     * Récupère les données des événements
     *
     * @return Un tableau JSON contenant les données des événements
     * @throws Exception Si une erreur se produit lors de la requête
     */
    public JSONArray getEventData() throws Exception {
        String response = get("/evenement/findAll.php");
        try {
            // Transformer la réponse en objet JSON
            JSONObject jsonResponse = new JSONObject(response);

            // Vérifier le statut de la réponse
            if (!jsonResponse.getString("status").equals("success")) {
                throw new Exception("Erreur API: " + jsonResponse.optString("message", "Erreur inconnue"));
            }

            // Extraire le tableau d'événements
            return jsonResponse.getJSONObject("data").getJSONArray("evenements");
        } catch (Exception e) {
            LOGGER.severe("Erreur lors du parsing JSON de la réponse: " + e.getMessage());
            LOGGER.severe("Réponse reçue: " + response);
            throw e;
        }
    }

    /**
     * Récupère les données des prestataires
     *
     * @return Un tableau JSON contenant les données des prestataires
     * @throws Exception Si une erreur se produit lors de la requête
     */
    public JSONArray getProviderData() throws Exception {
        String response = get("/prestataire/findAll.php");
        try {
            // Transformer la réponse en objet JSON
            JSONObject jsonResponse = new JSONObject(response);

            // Vérifier le statut de la réponse
            if (!jsonResponse.getString("status").equals("success")) {
                throw new Exception("Erreur API: " + jsonResponse.optString("message", "Erreur inconnue"));
            }

            // Extraire le tableau de prestataires
            return jsonResponse.getJSONObject("data").getJSONArray("prestataires");
        } catch (Exception e) {
            LOGGER.severe("Erreur lors du parsing JSON de la réponse: " + e.getMessage());
            LOGGER.severe("Réponse reçue: " + response);
            throw e;
        }
    }

    /**
     * Teste la connexion à l'API en utilisant un endpoint existant
     *
     * @return true si la connexion est établie avec succès
     */
    public boolean testConnection() {
        try {
            // Essaie de récupérer la liste des entreprises (endpoint qui existe certainement)
            get("/entreprise/findAll.php");
            return true;
        } catch (Exception e) {
            LOGGER.log(Level.WARNING, "Échec du test de connexion à l'API: " + e.getMessage(), e);
            return false;
        }
    }
}