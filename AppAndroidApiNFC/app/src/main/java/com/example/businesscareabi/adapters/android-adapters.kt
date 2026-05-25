package com.example.businesscareabi.adapters

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.example.businesscareabi.models.Evenement
import com.example.businesscareabi.models.RendezVous
import com.example.businesscareabi.R
import java.text.SimpleDateFormat
import java.util.*

class EvenementAdapter(
    private val evenements: List<Evenement>,
    private val onItemClick: (Evenement) -> Unit
) : RecyclerView.Adapter<EvenementAdapter.EvenementViewHolder>() {

    class EvenementViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val titleTextView: TextView = itemView.findViewById(R.id.eventTitleTextView)
        val dateTextView: TextView = itemView.findViewById(R.id.eventDateTextView)
        val typeTextView: TextView = itemView.findViewById(R.id.eventTypeTextView)
        val prestatairTextView: TextView = itemView.findViewById(R.id.eventPrestatairTextView)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): EvenementViewHolder {
        val itemView = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_event, parent, false)
        return EvenementViewHolder(itemView)
    }

    override fun onBindViewHolder(holder: EvenementViewHolder, position: Int) {
        val currentItem = evenements[position]
        
        holder.titleTextView.text = currentItem.titre
        
        val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        val outputFormat = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault())
        
        try {
            val dateDebutObj = inputFormat.parse(currentItem.dateDebut)
            val dateFinObj = inputFormat.parse(currentItem.dateFin)
            
            if (dateDebutObj != null && dateFinObj != null) {
                val dateDebutFormatted = outputFormat.format(dateDebutObj)
                val dateFinFormatted = outputFormat.format(dateFinObj)
                holder.dateTextView.text = "Du $dateDebutFormatted au $dateFinFormatted"
            } else {
                holder.dateTextView.text = "Du ${currentItem.dateDebut} au ${currentItem.dateFin}"
            }
        } catch (e: Exception) {
            holder.dateTextView.text = "Du ${currentItem.dateDebut} au ${currentItem.dateFin}"
        }
        
        holder.typeTextView.text = "Type: ${currentItem.typeEvenement}"
        holder.prestatairTextView.text = "Par: ${currentItem.prestatairNom}"
        
        holder.itemView.setOnClickListener {
            onItemClick(currentItem)
        }
    }

    override fun getItemCount() = evenements.size
}

class RendezVousAdapter(
    private val rendezVous: List<RendezVous>,
    private val onItemClick: (RendezVous) -> Unit
) : RecyclerView.Adapter<RendezVousAdapter.RendezVousViewHolder>() {

    class RendezVousViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val dateTextView: TextView = itemView.findViewById(R.id.rdvDateTextView)
        val typeTextView: TextView = itemView.findViewById(R.id.rdvTypeTextView)
        val prestatairTextView: TextView = itemView.findViewById(R.id.rdvPrestatairTextView)
        val statutTextView: TextView = itemView.findViewById(R.id.rdvStatutTextView)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RendezVousViewHolder {
        val itemView = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_rdv, parent, false)
        return RendezVousViewHolder(itemView)
    }

    override fun onBindViewHolder(holder: RendezVousViewHolder, position: Int) {
        val currentItem = rendezVous[position]
        
        val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        val outputFormat = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault())
        
        try {
            val dateHeureObj = inputFormat.parse(currentItem.dateHeure)
            
            if (dateHeureObj != null) {
                val dateHeureFormatted = outputFormat.format(dateHeureObj)
                holder.dateTextView.text = dateHeureFormatted
            } else {
                holder.dateTextView.text = currentItem.dateHeure
            }
        } catch (e: Exception) {
            holder.dateTextView.text = currentItem.dateHeure
        }
        
        holder.typeTextView.text = "Type: ${currentItem.type}"
        holder.prestatairTextView.text = "Avec: ${currentItem.prestatairNom}"
        holder.statutTextView.text = "Statut: ${currentItem.statut}"
        
        holder.itemView.setOnClickListener {
            onItemClick(currentItem)
        }
    }

    override fun getItemCount() = rendezVous.size
}
