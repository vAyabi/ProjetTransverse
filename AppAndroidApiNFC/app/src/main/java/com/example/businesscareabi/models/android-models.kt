package com.example.businesscareabi.models

data class Evenement(
    val id: Int,
    val titre: String,
    val description: String,
    val typeEvenement: String,
    val dateDebut: String,
    val dateFin: String,
    val capaciteMax: Int,
    val statut: String,
    val prestatairNom: String,
    val entrepriseNom: String
)

data class RendezVous(
    val id: Int,
    val salarieId: Int,
    val prestataireId: Int,
    val dateHeure: String,
    val type: String,
    val notes: String,
    val statut: String,
    val horsQuota: Boolean,
    val prestatairNom: String
)
