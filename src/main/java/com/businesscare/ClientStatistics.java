package com.businesscare;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Classe représentant les statistiques des clients
 */
public class ClientStatistics {
    private Map<String, Integer> clientsByFormule;
    private Map<String, Integer> clientsByStatus;
    private Map<String, Integer> clientsByRegistrationMonth;
    private Map<String, Double> clientsByRevenue;
    private List<TopClient> topClients;

    public ClientStatistics() {
        clientsByFormule = new HashMap<>();
        clientsByStatus = new HashMap<>();
        clientsByRegistrationMonth = new HashMap<>();
        clientsByRevenue = new HashMap<>();
        topClients = new ArrayList<>();
    }

    public Map<String, Integer> getClientsByFormule() {
        return clientsByFormule;
    }

    public void setClientsByFormule(Map<String, Integer> clientsByFormule) {
        this.clientsByFormule = clientsByFormule;
    }

    public Map<String, Integer> getClientsByStatus() {
        return clientsByStatus;
    }

    public void setClientsByStatus(Map<String, Integer> clientsByStatus) {
        this.clientsByStatus = clientsByStatus;
    }

    public Map<String, Integer> getClientsByRegistrationMonth() {
        return clientsByRegistrationMonth;
    }

    public void setClientsByRegistrationMonth(Map<String, Integer> clientsByRegistrationMonth) {
        this.clientsByRegistrationMonth = clientsByRegistrationMonth;
    }

    public Map<String, Double> getClientsByRevenue() {
        return clientsByRevenue;
    }

    public void setClientsByRevenue(Map<String, Double> clientsByRevenue) {
        this.clientsByRevenue = clientsByRevenue;
    }

    public List<TopClient> getTopClients() {
        return topClients;
    }

    public void setTopClients(List<TopClient> topClients) {
        this.topClients = topClients;
    }

    /**
     * Classe interne représentant un client
     */
    public static class TopClient {
        private String name;
        private int contractCount;
        private double totalRevenue;

        public TopClient(String name, int contractCount, double totalRevenue) {
            this.name = name;
            this.contractCount = contractCount;
            this.totalRevenue = totalRevenue;
        }

        public String getName() {
            return name;
        }

        public void setName(String name) {
            this.name = name;
        }

        public int getContractCount() {
            return contractCount;
        }

        public void setContractCount(int contractCount) {
            this.contractCount = contractCount;
        }

        public double getTotalRevenue() {
            return totalRevenue;
        }

        public void setTotalRevenue(double totalRevenue) {
            this.totalRevenue = totalRevenue;
        }
    }
}