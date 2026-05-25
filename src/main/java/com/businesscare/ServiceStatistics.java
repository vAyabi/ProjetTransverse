package com.businesscare;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Classe représentant les statistiques des services/prestations
 */
public class ServiceStatistics {
    private Map<String, Integer> servicesByType;
    private Map<String, Integer> servicesBySpeciality;
    private Map<String, Integer> servicesByPriceRange;
    private Map<String, Integer> servicesByEventCount;
    private List<TopService> topServices;

    public ServiceStatistics() {
        servicesByType = new HashMap<>();
        servicesBySpeciality = new HashMap<>();
        servicesByPriceRange = new HashMap<>();
        servicesByEventCount = new HashMap<>();
        topServices = new ArrayList<>();
    }

    public Map<String, Integer> getServicesByType() {
        return servicesByType;
    }

    public void setServicesByType(Map<String, Integer> servicesByType) {
        this.servicesByType = servicesByType;
    }

    public Map<String, Integer> getServicesBySpeciality() {
        return servicesBySpeciality;
    }

    public void setServicesBySpeciality(Map<String, Integer> servicesBySpeciality) {
        this.servicesBySpeciality = servicesBySpeciality;
    }

    public Map<String, Integer> getServicesByPriceRange() {
        return servicesByPriceRange;
    }

    public void setServicesByPriceRange(Map<String, Integer> servicesByPriceRange) {
        this.servicesByPriceRange = servicesByPriceRange;
    }

    public Map<String, Integer> getServicesByEventCount() {
        return servicesByEventCount;
    }

    public void setServicesByEventCount(Map<String, Integer> servicesByEventCount) {
        this.servicesByEventCount = servicesByEventCount;
    }

    public List<TopService> getTopServices() {
        return topServices;
    }

    public void setTopServices(List<TopService> topServices) {
        this.topServices = topServices;
    }

    /**
     * Classe interne représentant un service important
     */
    public static class TopService {
        private String providerName;
        private String serviceType;
        private String speciality;
        private double hourlyRate;
        private int eventCount;

        // Constructeur complet
        public TopService(String providerName, String serviceType, String speciality,
                          double hourlyRate, int eventCount) {
            this.providerName = providerName;
            this.serviceType = serviceType;
            this.speciality = speciality;
            this.hourlyRate = hourlyRate;
            this.eventCount = eventCount;
        }

        // Constructeur existant pour compatibilité
        public TopService(String providerName, String serviceType, int eventCount) {
            this(providerName, serviceType, "Non spécifié", 0.0, eventCount);
        }

        public String getProviderName() {
            return providerName;
        }

        public void setProviderName(String providerName) {
            this.providerName = providerName;
        }

        public String getServiceType() {
            return serviceType;
        }

        public void setServiceType(String serviceType) {
            this.serviceType = serviceType;
        }

        public String getSpeciality() {
            return speciality;
        }

        public void setSpeciality(String speciality) {
            this.speciality = speciality;
        }

        public double getHourlyRate() {
            return hourlyRate;
        }

        public void setHourlyRate(double hourlyRate) {
            this.hourlyRate = hourlyRate;
        }

        public int getEventCount() {
            return eventCount;
        }

        public void setEventCount(int eventCount) {
            this.eventCount = eventCount;
        }
    }
}