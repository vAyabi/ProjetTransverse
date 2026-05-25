package com.businesscare;

import com.businesscare.ClientStatistics;
import com.businesscare.EventStatistics;
import com.businesscare.ServiceStatistics;

import org.jfree.chart.ChartFactory;
import org.jfree.chart.ChartUtils;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;

import com.itextpdf.text.Document;
import com.itextpdf.text.Paragraph;
import com.itextpdf.text.Font;
import com.itextpdf.text.FontFactory;
import com.itextpdf.text.Image;
import com.itextpdf.text.Element;
import com.itextpdf.text.pdf.PdfPTable;
import com.itextpdf.text.pdf.PdfPCell;
import com.itextpdf.text.pdf.PdfWriter;
import com.itextpdf.text.Phrase;

import java.io.File;
import java.io.FileOutputStream;
import java.util.Map;
import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Classe pour générer des rapports PDF avec graphiques
 */
public class PDFGenerator {
    private static final String TEMP_DIR = System.getProperty("java.io.tmpdir");

    /**
     * Génère un rapport PDF complet
     */
    public boolean generateFullReport(
            ClientStatistics clientStats,
            EventStatistics eventStats,
            ServiceStatistics serviceStats,
            String outputPath) {

        try {
            // Créer le document PDF
            Document document = new Document();
            PdfWriter.getInstance(document, new FileOutputStream(outputPath));
            document.open();

            // Page de titre
            Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 20);
            Paragraph title = new Paragraph("Rapport Business Care", titleFont);
            title.setAlignment(Element.ALIGN_CENTER);
            document.add(title);

            // Date du rapport
            SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy HH:mm");
            Paragraph dateP = new Paragraph("Rapport généré le " + dateFormat.format(new Date()),
                    FontFactory.getFont(FontFactory.HELVETICA, 12));
            dateP.setAlignment(Element.ALIGN_CENTER);
            document.add(dateP);
            document.add(new Paragraph(" "));

            // Introduction
            Paragraph intro = new Paragraph(
                    "Ce rapport présente les statistiques de Business Care, "
                            + "incluant des informations sur les clients, les événements et les prestataires. ",
                    FontFactory.getFont(FontFactory.HELVETICA, 12)
            );
            document.add(intro);
            document.add(new Paragraph(" "));

            // Page 1: Clients
            document.newPage();
            document.add(new Paragraph("Statistiques des Clients", titleFont));

            // Graphique 1: Répartition par formule
            if (clientStats.getClientsByFormule() != null && !clientStats.getClientsByFormule().isEmpty()) {
                JFreeChart chart1 = createPieChartInteger("Répartition par formule", clientStats.getClientsByFormule());
                document.add(createImageFromChart(chart1, "clients_by_formule"));
            }

            // Graphique 2: Répartition par statut
            if (clientStats.getClientsByStatus() != null && !clientStats.getClientsByStatus().isEmpty()) {
                JFreeChart chart2 = createPieChartInteger("Répartition par statut", clientStats.getClientsByStatus());
                document.add(createImageFromChart(chart2, "clients_by_status"));
            }

            // Graphique 3: Répartition par mois
            if (clientStats.getClientsByRegistrationMonth() != null && !clientStats.getClientsByRegistrationMonth().isEmpty()) {
                JFreeChart chart3 = createBarChartInteger("Clients par mois d'inscription", clientStats.getClientsByRegistrationMonth());
                document.add(createImageFromChart(chart3, "clients_by_month"));
            }

            // Page 2: Événements
            document.newPage();
            document.add(new Paragraph("Statistiques des Événements", titleFont));

            // Graphique 1: Événements par type
            if (eventStats.getEventsByType() != null && !eventStats.getEventsByType().isEmpty()) {
                JFreeChart chart5 = createPieChartInteger("Événements par type", eventStats.getEventsByType());
                document.add(createImageFromChart(chart5, "events_by_type"));
            }

            // Graphique 2: Événements par statut
            if (eventStats.getEventsByStatus() != null && !eventStats.getEventsByStatus().isEmpty()) {
                JFreeChart chart6 = createPieChartInteger("Événements par statut", eventStats.getEventsByStatus());
                document.add(createImageFromChart(chart6, "events_by_status"));
            }

            // Graphique 3: Événements par mois
            if (eventStats.getEventsByMonth() != null && !eventStats.getEventsByMonth().isEmpty()) {
                JFreeChart chart7 = createBarChartInteger("Événements par mois", eventStats.getEventsByMonth());
                document.add(createImageFromChart(chart7, "events_by_month"));
            }

            // Page 3: Services
            document.newPage();
            document.add(new Paragraph("Statistiques des Prestations", titleFont));

            // Graphique 1: Services par type
            if (serviceStats.getServicesByType() != null && !serviceStats.getServicesByType().isEmpty()) {
                JFreeChart chart9 = createPieChartInteger("Services par type", serviceStats.getServicesByType());
                document.add(createImageFromChart(chart9, "services_by_type"));
            }

            // Graphique 2: Services par spécialité
            if (serviceStats.getServicesBySpeciality() != null && !serviceStats.getServicesBySpeciality().isEmpty()) {
                JFreeChart chart10 = createPieChartInteger("Services par spécialité", serviceStats.getServicesBySpeciality());
                document.add(createImageFromChart(chart10, "services_by_speciality"));
            }

            // Graphique 3: Services par tranche de prix
            if (serviceStats.getServicesByPriceRange() != null && !serviceStats.getServicesByPriceRange().isEmpty()) {
                JFreeChart chart11 = createBarChartInteger("Services par tranche de prix", serviceStats.getServicesByPriceRange());
                document.add(createImageFromChart(chart11, "services_by_price"));
            }

            // Tableau: Top 5 des prestations par tarif horaire (SEUL TOP 5 QU'ON GARDE)
            if (serviceStats.getTopServices() != null && !serviceStats.getTopServices().isEmpty()) {
                document.add(new Paragraph("Top 5 des prestations par tarif horaire",
                        FontFactory.getFont(FontFactory.HELVETICA_BOLD, 14)));

                PdfPTable table = new PdfPTable(4);
                table.setWidthPercentage(100);
                table.setSpacingBefore(10f);
                table.setSpacingAfter(10f);

                // En-têtes
                PdfPCell cell1 = new PdfPCell(new Phrase("Prestataire"));
                cell1.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell1);

                PdfPCell cell2 = new PdfPCell(new Phrase("Type de prestation"));
                cell2.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell2);

                PdfPCell cell3 = new PdfPCell(new Phrase("Spécialité"));
                cell3.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell3);

                PdfPCell cell4 = new PdfPCell(new Phrase("Tarif horaire (€/h)"));
                cell4.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell4);

                // Données
                for (ServiceStatistics.TopService service : serviceStats.getTopServices()) {
                    table.addCell(service.getProviderName());
                    table.addCell(service.getServiceType());
                    table.addCell(service.getSpeciality());
                    table.addCell(String.format("%.2f", service.getHourlyRate()));
                }

                document.add(table);
            }

            document.close();
            return true;

        } catch (Exception e) {
            e.printStackTrace();
            return false;
        }
    }

    /**
     * Génère un rapport PDF pour les clients uniquement
     */
    public boolean generateClientReport(ClientStatistics stats, String outputPath) {
        try {
            Document document = new Document();
            PdfWriter.getInstance(document, new FileOutputStream(outputPath));
            document.open();

            // Titre
            Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 20);
            Paragraph title = new Paragraph("Rapport des Clients", titleFont);
            title.setAlignment(Element.ALIGN_CENTER);
            document.add(title);

            // Date du rapport
            SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy HH:mm");
            Paragraph dateP = new Paragraph("Rapport généré le " + dateFormat.format(new Date()),
                    FontFactory.getFont(FontFactory.HELVETICA, 12));
            dateP.setAlignment(Element.ALIGN_CENTER);
            document.add(dateP);
            document.add(new Paragraph(" "));

            // Graphiques
            if (stats.getClientsByFormule() != null && !stats.getClientsByFormule().isEmpty()) {
                JFreeChart chart1 = createPieChartInteger("Répartition par formule", stats.getClientsByFormule());
                document.add(createImageFromChart(chart1, "clients_by_formule"));
            }

            if (stats.getClientsByStatus() != null && !stats.getClientsByStatus().isEmpty()) {
                JFreeChart chart2 = createPieChartInteger("Répartition par statut", stats.getClientsByStatus());
                document.add(createImageFromChart(chart2, "clients_by_status"));
            }

            if (stats.getClientsByRegistrationMonth() != null && !stats.getClientsByRegistrationMonth().isEmpty()) {
                JFreeChart chart3 = createBarChartInteger("Clients par mois d'inscription", stats.getClientsByRegistrationMonth());
                document.add(createImageFromChart(chart3, "clients_by_month"));
            }

            document.close();
            return true;

        } catch (Exception e) {
            e.printStackTrace();
            return false;
        }
    }

    /**
     * Génère un rapport PDF pour les événements uniquement
     */
    public boolean generateEventReport(EventStatistics stats, String outputPath) {
        try {
            Document document = new Document();
            PdfWriter.getInstance(document, new FileOutputStream(outputPath));
            document.open();

            // Titre
            Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 20);
            Paragraph title = new Paragraph("Rapport des Événements", titleFont);
            title.setAlignment(Element.ALIGN_CENTER);
            document.add(title);

            // Date du rapport
            SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy HH:mm");
            Paragraph dateP = new Paragraph("Rapport généré le " + dateFormat.format(new Date()),
                    FontFactory.getFont(FontFactory.HELVETICA, 12));
            dateP.setAlignment(Element.ALIGN_CENTER);
            document.add(dateP);
            document.add(new Paragraph(" "));

            // Graphiques
            if (stats.getEventsByType() != null && !stats.getEventsByType().isEmpty()) {
                JFreeChart chart1 = createPieChartInteger("Événements par type", stats.getEventsByType());
                document.add(createImageFromChart(chart1, "events_by_type"));
            }

            if (stats.getEventsByStatus() != null && !stats.getEventsByStatus().isEmpty()) {
                JFreeChart chart2 = createPieChartInteger("Événements par statut", stats.getEventsByStatus());
                document.add(createImageFromChart(chart2, "events_by_status"));
            }

            if (stats.getEventsByMonth() != null && !stats.getEventsByMonth().isEmpty()) {
                JFreeChart chart3 = createBarChartInteger("Événements par mois", stats.getEventsByMonth());
                document.add(createImageFromChart(chart3, "events_by_month"));
            }

            document.close();
            return true;

        } catch (Exception e) {
            e.printStackTrace();
            return false;
        }
    }

    /**
     * Génère un rapport PDF pour les services uniquement
     */
    public boolean generateServiceReport(ServiceStatistics stats, String outputPath) {
        try {
            Document document = new Document();
            PdfWriter.getInstance(document, new FileOutputStream(outputPath));
            document.open();

            // Titre
            Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 20);
            Paragraph title = new Paragraph("Rapport des Prestations", titleFont);
            title.setAlignment(Element.ALIGN_CENTER);
            document.add(title);

            // Date du rapport
            SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy HH:mm");
            Paragraph dateP = new Paragraph("Rapport généré le " + dateFormat.format(new Date()),
                    FontFactory.getFont(FontFactory.HELVETICA, 12));
            dateP.setAlignment(Element.ALIGN_CENTER);
            document.add(dateP);
            document.add(new Paragraph(" "));

            // Graphiques
            if (stats.getServicesByType() != null && !stats.getServicesByType().isEmpty()) {
                JFreeChart chart1 = createPieChartInteger("Services par type", stats.getServicesByType());
                document.add(createImageFromChart(chart1, "services_by_type"));
            }

            if (stats.getServicesBySpeciality() != null && !stats.getServicesBySpeciality().isEmpty()) {
                JFreeChart chart2 = createPieChartInteger("Services par spécialité", stats.getServicesBySpeciality());
                document.add(createImageFromChart(chart2, "services_by_speciality"));
            }

            if (stats.getServicesByPriceRange() != null && !stats.getServicesByPriceRange().isEmpty()) {
                JFreeChart chart3 = createBarChartInteger("Services par tranche de prix", stats.getServicesByPriceRange());
                document.add(createImageFromChart(chart3, "services_by_price"));
            }

            // Tableau: Top 5 des prestations par tarif horaire
            if (stats.getTopServices() != null && !stats.getTopServices().isEmpty()) {
                document.add(new Paragraph("Top 5 des prestations par tarif horaire",
                        FontFactory.getFont(FontFactory.HELVETICA_BOLD, 14)));

                PdfPTable table = new PdfPTable(4);
                table.setWidthPercentage(100);
                table.setSpacingBefore(10f);
                table.setSpacingAfter(10f);

                // En-têtes
                PdfPCell cell1 = new PdfPCell(new Phrase("Prestataire"));
                cell1.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell1);

                PdfPCell cell2 = new PdfPCell(new Phrase("Type de prestation"));
                cell2.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell2);

                PdfPCell cell3 = new PdfPCell(new Phrase("Spécialité"));
                cell3.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell3);

                PdfPCell cell4 = new PdfPCell(new Phrase("Tarif horaire (€/h)"));
                cell4.setHorizontalAlignment(Element.ALIGN_CENTER);
                table.addCell(cell4);

                // Données
                for (ServiceStatistics.TopService service : stats.getTopServices()) {
                    table.addCell(service.getProviderName());
                    table.addCell(service.getServiceType());
                    table.addCell(service.getSpeciality());
                    table.addCell(String.format("%.2f", service.getHourlyRate()));
                }

                document.add(table);
            }

            document.close();
            return true;

        } catch (Exception e) {
            e.printStackTrace();
            return false;
        }
    }

    /**
     * Crée un diagramme camembert pour des données entières
     */
    private JFreeChart createPieChartInteger(String title, Map<String, Integer> data) {
        DefaultPieDataset dataset = new DefaultPieDataset();

        if (data != null) {
            for (Map.Entry<String, Integer> entry : data.entrySet()) {
                dataset.setValue(entry.getKey(), entry.getValue());
            }
        }

        return ChartFactory.createPieChart(title, dataset, true, true, false);
    }

    /**
     * Crée un diagramme en barres pour des données entières
     */
    private JFreeChart createBarChartInteger(String title, Map<String, Integer> data) {
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();

        if (data != null) {
            for (Map.Entry<String, Integer> entry : data.entrySet()) {
                dataset.addValue(entry.getValue(), "Valeur", entry.getKey());
            }
        }

        return ChartFactory.createBarChart(
                title,
                "Catégories",
                "Valeurs",
                dataset,
                PlotOrientation.VERTICAL,
                false,
                true,
                false);
    }

    /**
     * Crée un diagramme en barres pour des données décimales
     */
    private JFreeChart createBarChartDouble(String title, Map<String, Double> data) {
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();

        if (data != null) {
            for (Map.Entry<String, Double> entry : data.entrySet()) {
                dataset.addValue(entry.getValue(), "Valeur", entry.getKey());
            }
        }

        return ChartFactory.createBarChart(
                title,
                "Catégories",
                "Valeurs",
                dataset,
                PlotOrientation.VERTICAL,
                false,
                true,
                false);
    }

    /**
     * Crée une image à partir d'un graphique
     */
    private Image createImageFromChart(JFreeChart chart, String fileName) throws Exception {
        // Sauvegarder le graphique dans un fichier temporaire
        File tempFile = new File(TEMP_DIR, fileName + ".png");
        ChartUtils.saveChartAsPNG(tempFile, chart, 500, 300);

        // Charger l'image dans le PDF
        Image image = Image.getInstance(tempFile.getAbsolutePath());
        image.scalePercent(80);
        image.setAlignment(Element.ALIGN_CENTER);

        // Supprimer le fichier temporaire lorsque la JVM se termine
        tempFile.deleteOnExit();

        return image;
    }
}