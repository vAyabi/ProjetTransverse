package com.businesscare;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Classe représentant les statistiques des événements
 */
public class EventStatistics {
    private Map<String, Integer> eventsByType;
    private Map<String, Integer> eventsByStatus;
    private Map<String, Integer> eventsByMonth;
    private Map<String, Integer> eventsByCompany;
    private List<TopEvent> topEvents;

    public EventStatistics() {
        eventsByType = new HashMap<>();
        eventsByStatus = new HashMap<>();
        eventsByMonth = new HashMap<>();
        eventsByCompany = new HashMap<>();
        topEvents = new ArrayList<>();
    }

    public Map<String, Integer> getEventsByType() {
        return eventsByType;
    }

    public void setEventsByType(Map<String, Integer> eventsByType) {
        this.eventsByType = eventsByType;
    }

    public Map<String, Integer> getEventsByStatus() {
        return eventsByStatus;
    }

    public void setEventsByStatus(Map<String, Integer> eventsByStatus) {
        this.eventsByStatus = eventsByStatus;
    }

    public Map<String, Integer> getEventsByMonth() {
        return eventsByMonth;
    }

    public void setEventsByMonth(Map<String, Integer> eventsByMonth) {
        this.eventsByMonth = eventsByMonth;
    }

    public Map<String, Integer> getEventsByCompany() {
        return eventsByCompany;
    }

    public void setEventsByCompany(Map<String, Integer> eventsByCompany) {
        this.eventsByCompany = eventsByCompany;
    }

    public List<TopEvent> getTopEvents() {
        return topEvents;
    }

    public void setTopEvents(List<TopEvent> topEvents) {
        this.topEvents = topEvents;
    }

    /**
     * Classe interne représentant un événement important
     */
    public static class TopEvent {
        private String title;
        private String type;
        private int registrationCount;

        public TopEvent(String title, String type, int registrationCount) {
            this.title = title;
            this.type = type;
            this.registrationCount = registrationCount;
        }

        public String getTitle() {
            return title;
        }

        public void setTitle(String title) {
            this.title = title;
        }

        public String getType() {
            return type;
        }

        public void setType(String type) {
            this.type = type;
        }

        public int getRegistrationCount() {
            return registrationCount;
        }

        public void setRegistrationCount(int registrationCount) {
            this.registrationCount = registrationCount;
        }
    }
}