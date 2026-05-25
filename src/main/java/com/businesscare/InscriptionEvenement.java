package com.businesscare;

import java.util.Date;

/**
 * Classe représentant une inscription à un événement

 */
public class InscriptionEvenement {
    private int idSalarie;
    private int idEvenement;
    private Date dateInscription;
    private String statut;


    public InscriptionEvenement() {
    }


    public int getIdSalarie() {
        return idSalarie;
    }

    public void setIdSalarie(int idSalarie) {
        this.idSalarie = idSalarie;
    }

    public int getIdEvenement() {
        return idEvenement;
    }

    public void setIdEvenement(int idEvenement) {
        this.idEvenement = idEvenement;
    }

    public Date getDateInscription() {
        return dateInscription;
    }

    public void setDateInscription(Date dateInscription) {
        this.dateInscription = dateInscription;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    @Override
    public String toString() {
        return "InscriptionEvenement{" +
                "idSalarie=" + idSalarie +
                ", idEvenement=" + idEvenement +
                ", statut='" + statut + '\'' +
                '}';
    }
}