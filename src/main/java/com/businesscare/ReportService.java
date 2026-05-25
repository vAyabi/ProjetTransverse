package com.businesscare;

import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.logging.Level;
import java.util.logging.Logger;
import java.util.stream.Collectors;
import org.json.JSONObject;
import org.json.JSONArray;

/**
 * Service responsable de la récupération et du traitement des données pour les rapports
 */
public class ReportService {
    private static final Logger LOGGER = Logger.getLogger(ReportService.class.getName());
    private APIClient apiClient;

    public ReportService() {
        this.apiClient = new APIClient();
        LOGGER.setLevel(Level.INFO);
    }

    /**
     * Récupère les statistiques des clients
     *
     * @return Un objet ClientStatistics contenant les statistiques des clients
     * @throws SQLException En cas d'erreur de connexion à la base de données
     */
    public ClientStatistics getClientStatistics() throws SQLException {
        ClientStatistics stats = new ClientStatistics();

        try {
            // Récupérer TOUTES les données depuis l'API
            JSONArray entreprisesData = apiClient.getClientData();

            LOGGER.info("Nombre d'entreprises récupérées: " + entreprisesData.length());

            // Maps pour les statistiques
            Map<String, Integer> clientsByFormule = new HashMap<>();
            Map<String, Integer> clientsByStatus = new HashMap<>();
            Map<String, Integer> clientsByRegistrationMonth = new HashMap<>();
            Map<String, Double> clientsByRevenue = new HashMap<>();
            // On ne fait plus le top 5 des clients
            List<ClientStatistics.TopClient> topClients = new ArrayList<>();

            // Mois en français
            String[] moisFrancais = {"Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
                    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"};

            // Traiter chaque entreprise
            for (int i = 0; i < entreprisesData.length(); i++) {
                JSONObject entreprise = entreprisesData.getJSONObject(i);

                int idEntreprise = entreprise.getInt("id_entreprise");
                String nom = entreprise.getString("nom");
                String formule = entreprise.getString("type_formule");

                // Statistiques par formule
                clientsByFormule.put(formule, clientsByFormule.getOrDefault(formule, 0) + 1);

                // Statistiques par statut
                int statut = entreprise.getInt("statut");
                String statutLabel = (statut == 1) ? "Actif" : "Inactif";
                clientsByStatus.put(statutLabel, clientsByStatus.getOrDefault(statutLabel, 0) + 1);

                // Statistiques par mois d'inscription
                String dateInscription = entreprise.optString("date_inscription", "");
                if (!dateInscription.isEmpty()) {
                    try {
                        SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                        Date date = sdf.parse(dateInscription);
                        Calendar cal = Calendar.getInstance();
                        cal.setTime(date);
                        int mois = cal.get(Calendar.MONTH);
                        String moisNom = moisFrancais[mois];
                        clientsByRegistrationMonth.put(moisNom, clientsByRegistrationMonth.getOrDefault(moisNom, 0) + 1);
                    } catch (Exception e) {
                        LOGGER.warning("Erreur lors du parsing de la date: " + dateInscription);
                    }
                }
            }

            // Définir toutes les statistiques
            stats.setClientsByFormule(clientsByFormule);
            stats.setClientsByStatus(clientsByStatus);
            stats.setClientsByRegistrationMonth(clientsByRegistrationMonth);
            stats.setClientsByRevenue(clientsByRevenue);
            // On laisse la liste vide pour le top clients
            stats.setTopClients(topClients);

        } catch (Exception e) {
            e.printStackTrace();
            throw new SQLException("Erreur lors de la récupération des statistiques clients: " + e.getMessage());
        }

        return stats;
    }

    /**
     * Récupère les statistiques des événements
     *
     * @return Un objet EventStatistics contenant les statistiques des événements
     * @throws SQLException En cas d'erreur de connexion à la base de données
     */
    public EventStatistics getEventStatistics() throws SQLException {
        EventStatistics stats = new EventStatistics();

        try {
            // Récupérer toutes les données depuis l'API
            JSONArray eventsData = apiClient.getEventData();

            LOGGER.info("Nombre d'événements récupérés: " + eventsData.length());

            // Maps pour les statistiques
            Map<String, Integer> eventsByType = new HashMap<>();
            Map<String, Integer> eventsByStatus = new HashMap<>();
            Map<String, Integer> eventsByMonth = new HashMap<>();
            Map<String, Integer> eventsByCompany = new HashMap<>();
            // On ne fait plus le top 5 des événements
            List<EventStatistics.TopEvent> topEvents = new ArrayList<>();

            String[] moisFrancais = {"Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
                    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"};

            // Traiter chaque événement
            for (int i = 0; i < eventsData.length(); i++) {
                JSONObject event = eventsData.getJSONObject(i);

                String titre = event.getString("titre");
                String type = event.getString("type_evenement");
                String statut = event.getString("statut");

                // Statistiques par type
                eventsByType.put(type, eventsByType.getOrDefault(type, 0) + 1);

                // Statistiques par statut
                eventsByStatus.put(statut, eventsByStatus.getOrDefault(statut, 0) + 1);

                // Statistiques par mois
                String dateDebut = event.getString("date_debut");
                if (!dateDebut.isEmpty()) {
                    try {
                        SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                        Date date = sdf.parse(dateDebut);
                        Calendar cal = Calendar.getInstance();
                        cal.setTime(date);
                        int mois = cal.get(Calendar.MONTH);
                        String moisNom = moisFrancais[mois];
                        eventsByMonth.put(moisNom, eventsByMonth.getOrDefault(moisNom, 0) + 1);
                    } catch (Exception e) {
                        LOGGER.warning("Erreur lors du parsing de la date: " + dateDebut);
                    }
                }

                // Statistiques par entreprise
                int idEntreprise = event.getInt("id_entreprise");
                eventsByCompany.put("Entreprise " + idEntreprise,
                        eventsByCompany.getOrDefault("Entreprise " + idEntreprise, 0) + 1);
            }

            // Définir toutes les statistiques
            stats.setEventsByType(eventsByType);
            stats.setEventsByStatus(eventsByStatus);
            stats.setEventsByMonth(eventsByMonth);
            stats.setEventsByCompany(eventsByCompany);
            // On laisse la liste vide pour le top events
            stats.setTopEvents(topEvents);

        } catch (Exception e) {
            e.printStackTrace();
            throw new SQLException("Erreur lors de la récupération des statistiques événements: " + e.getMessage());
        }

        return stats;
    }

    /**
     * Récupère les statistiques des services et prestataires
     *
     * @return Un objet ServiceStatistics contenant les statistiques des services
     * @throws SQLException En cas d'erreur de connexion à la base de données
     */
    public ServiceStatistics getServiceStatistics() throws SQLException {
        ServiceStatistics stats = new ServiceStatistics();

        try {
            // Récupérer toutes les données depuis l'API
            JSONArray providersData = apiClient.getProviderData();

            LOGGER.info("Nombre de prestataires récupérés: " + providersData.length());

            // Maps pour les statistiques
            Map<String, Integer> servicesByType = new HashMap<>();
            Map<String, Integer> servicesBySpeciality = new HashMap<>();
            Map<String, Integer> servicesByPriceRange = new HashMap<>();
            Map<String, Integer> servicesByEventCount = new HashMap<>();
            List<ServiceStatistics.TopService> topServices = new ArrayList<>();

            // Traiter chaque prestataire
            for (int i = 0; i < providersData.length(); i++) {
                JSONObject provider = providersData.getJSONObject(i);

                String nom = provider.getString("nom");
                String typePrestation = provider.getString("type_prestation");
                String specialite = provider.optString("specialite", "Non spécifié");
                double tarifHoraire = provider.optDouble("tarif_horaire", 0);

                // Statistiques par type
                servicesByType.put(typePrestation, servicesByType.getOrDefault(typePrestation, 0) + 1);

                // Statistiques par spécialité
                servicesBySpeciality.put(specialite, servicesBySpeciality.getOrDefault(specialite, 0) + 1);

                // Statistiques par tranche de prix
                String tranchePrix;
                if (tarifHoraire < 50) {
                    tranchePrix = "< 50€";
                } else if (tarifHoraire < 100) {
                    tranchePrix = "50€ - 100€";
                } else {
                    tranchePrix = "> 100€";
                }
                servicesByPriceRange.put(tranchePrix, servicesByPriceRange.getOrDefault(tranchePrix, 0) + 1);

                // Pour le nombre d'événements, on simule
                int nbEvenements = (int)(Math.random() * 20) + 1;

                String trancheEvenements;
                if (nbEvenements < 5) {
                    trancheEvenements = "1-5";
                } else if (nbEvenements < 10) {
                    trancheEvenements = "6-10";
                } else if (nbEvenements < 15) {
                    trancheEvenements = "11-15";
                } else {
                    trancheEvenements = "16+";
                }
                servicesByEventCount.put(trancheEvenements, servicesByEventCount.getOrDefault(trancheEvenements, 0) + 1);

                // Créer l'objet TopService
                topServices.add(new ServiceStatistics.TopService(
                        nom,
                        typePrestation,
                        specialite,
                        tarifHoraire,
                        nbEvenements
                ));
            }

            // Trier par tarif horaire et prendre le top 5
            topServices.sort((s1, s2) -> Double.compare(s2.getHourlyRate(), s1.getHourlyRate()));
            topServices = topServices.stream().limit(5).collect(Collectors.toList());

            // Définir toutes les statistiques
            stats.setServicesByType(servicesByType);
            stats.setServicesBySpeciality(servicesBySpeciality);
            stats.setServicesByPriceRange(servicesByPriceRange);
            stats.setServicesByEventCount(servicesByEventCount);
            stats.setTopServices(topServices);

        } catch (Exception e) {
            e.printStackTrace();
            throw new SQLException("Erreur lors de la récupération des statistiques services: " + e.getMessage());
        }

        return stats;
    }
}