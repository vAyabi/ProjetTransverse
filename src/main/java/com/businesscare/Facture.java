package com.businesscare;

import java.util.Date;

/**
 * Classe représentant une facture

 */
public class Facture {
    private int id;
    private double montantTotal;
    private Date dateEcheance;
    private String statut;
    private Date createdAt;
    private int idEntreprise;
    private int idContrat;


    public Facture() {
    }


    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public double getMontantTotal() {
        return montantTotal;
    }

    public void setMontantTotal(double montantTotal) {
        this.montantTotal = montantTotal;
    }

    public Date getDateEcheance() {
        return dateEcheance;
    }

    public void setDateEcheance(Date dateEcheance) {
        this.dateEcheance = dateEcheance;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public Date getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(Date createdAt) {
        this.createdAt = createdAt;
    }

    public int getIdEntreprise() {
        return idEntreprise;
    }

    public void setIdEntreprise(int idEntreprise) {
        this.idEntreprise = idEntreprise;
    }

    public int getIdContrat() {
        return idContrat;
    }

    public void setIdContrat(int idContrat) {
        this.idContrat = idContrat;
    }

    @Override
    public String toString() {
        return "Facture{" +
                "id=" + id +
                ", montantTotal=" + montantTotal +
                ", statut='" + statut + '\'' +
                '}';
    }
}