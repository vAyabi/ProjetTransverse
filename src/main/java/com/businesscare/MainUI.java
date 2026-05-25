package com.businesscare;

import com.businesscare.ReportService;
import com.businesscare.PDFGenerator;
import com.businesscare.ClientStatistics;
import com.businesscare.EventStatistics;
import com.businesscare.ServiceStatistics;

import javax.swing.*;
import java.awt.*;
import java.io.File;
import java.sql.SQLException;

/**
 * Interface principale de l'application
 */
public class MainUI extends JFrame {
    private ReportService reportService;
    private PDFGenerator pdfGenerator;

    // Composants UI
    private JComboBox<String> reportTypeComboBox;
    private JTextField outputPathField;
    private JProgressBar progressBar;
    private JLabel statusLabel;

    /**
     * Constructeur
     */
    public MainUI() {
        reportService = new ReportService();
        pdfGenerator = new PDFGenerator();

        // Configuration de la fenêtre
        setTitle("Business Care - Générateur de Rapports");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setSize(450, 250);
        setLocationRelativeTo(null);

        // Créer l'interface
        createUI();
    }

    /**
     * Crée l'interface utilisateur
     */
    private void createUI() {
        // Créer les composants
        JPanel panel = new JPanel();
        panel.setLayout(new GridLayout(5, 1, 10, 10));
        panel.setBorder(BorderFactory.createEmptyBorder(10, 10, 10, 10));

        // Titre
        JLabel titleLabel = new JLabel("Générateur de Rapports Business Care");
        titleLabel.setFont(new Font("Arial", Font.BOLD, 16));
        titleLabel.setHorizontalAlignment(JLabel.CENTER);
        panel.add(titleLabel);

        // Type de rapport
        JPanel typePanel = new JPanel(new FlowLayout(FlowLayout.LEFT));
        typePanel.add(new JLabel("Type de rapport:"));
        reportTypeComboBox = new JComboBox<>(new String[] {
                "Rapport Complet",
                "Statistiques Clients",
                "Statistiques Événements",
                "Statistiques Prestations"
        });
        typePanel.add(reportTypeComboBox);
        panel.add(typePanel);

        // Chemin de sortie
        JPanel pathPanel = new JPanel(new BorderLayout());
        pathPanel.add(new JLabel("Fichier de sortie:"), BorderLayout.WEST);
        outputPathField = new JTextField(System.getProperty("user.home") + "/rapport_business_care.pdf");
        pathPanel.add(outputPathField, BorderLayout.CENTER);
        JButton browseButton = new JButton("...");
        browseButton.addActionListener(e -> {
            JFileChooser fileChooser = new JFileChooser();
            fileChooser.setDialogTitle("Enregistrer le rapport");
            if (fileChooser.showSaveDialog(this) == JFileChooser.APPROVE_OPTION) {
                outputPathField.setText(fileChooser.getSelectedFile().getAbsolutePath());
            }
        });
        pathPanel.add(browseButton, BorderLayout.EAST);
        panel.add(pathPanel);

        // Barre de progression
        progressBar = new JProgressBar(0, 100);
        progressBar.setStringPainted(true);
        panel.add(progressBar);

        // Boutons
        JPanel buttonPanel = new JPanel();
        JButton generateButton = new JButton("Générer Rapport");
        generateButton.addActionListener(e -> generateReport());
        buttonPanel.add(generateButton);

        JButton exitButton = new JButton("Quitter");
        exitButton.addActionListener(e -> System.exit(0));
        buttonPanel.add(exitButton);
        panel.add(buttonPanel);

        // Statut
        statusLabel = new JLabel("Prêt à générer un rapport");
        statusLabel.setHorizontalAlignment(JLabel.CENTER);
        getContentPane().add(statusLabel, BorderLayout.SOUTH);
        getContentPane().add(panel, BorderLayout.CENTER);
    }

    /**
     * Génère le rapport selon le type sélectionné
     */
    private void generateReport() {
        String outputPath = outputPathField.getText();
        String reportType = (String) reportTypeComboBox.getSelectedItem();

        // Désactiver l'interface pendant la génération
        setInterfaceEnabled(false);
        statusLabel.setText("Connexion à la base de données...");
        progressBar.setValue(0);


        new Thread(() -> {
            try {
                // Récupérer les données de la BDD
                progressBar.setValue(10);
                updateStatus("Récupération des statistiques clients...");

                ClientStatistics clientStats = null;
                EventStatistics eventStats = null;
                ServiceStatistics serviceStats = null;

                try {
                    clientStats = reportService.getClientStatistics();
                    progressBar.setValue(30);
                    updateStatus("Récupération des statistiques événements...");

                    eventStats = reportService.getEventStatistics();
                    progressBar.setValue(50);
                    updateStatus("Récupération des statistiques prestations...");

                    serviceStats = reportService.getServiceStatistics();
                    progressBar.setValue(70);
                    updateStatus("Génération du PDF...");
                } catch (SQLException e) {
                    throw new Exception("Erreur de base de données: " + e.getMessage());
                }

                // Générer le PDF
                boolean success = false;

                switch (reportType) {
                    case "Rapport Complet":
                        success = pdfGenerator.generateFullReport(clientStats, eventStats, serviceStats, outputPath);
                        break;
                    case "Statistiques Clients":
                        success = pdfGenerator.generateClientReport(clientStats, outputPath);
                        break;
                    case "Statistiques Événements":
                        success = pdfGenerator.generateEventReport(eventStats, outputPath);
                        break;
                    case "Statistiques Prestations":
                        success = pdfGenerator.generateServiceReport(serviceStats, outputPath);
                        break;
                }

                progressBar.setValue(100);

                // Afficher le résultat
                if (success) {
                    updateStatus("Rapport généré avec succès");
                    SwingUtilities.invokeLater(() -> {
                        JOptionPane.showMessageDialog(this,
                                "Le rapport a été généré avec succès à l'emplacement:\n" + outputPath,
                                "Succès",
                                JOptionPane.INFORMATION_MESSAGE);


                        try {
                            Desktop.getDesktop().open(new File(outputPath));
                        } catch (Exception ex) {

                        }
                    });
                } else {
                    throw new Exception("Échec de la génération du PDF");
                }

            } catch (Exception ex) {
                ex.printStackTrace();
                updateStatus("Erreur: " + ex.getMessage());
                SwingUtilities.invokeLater(() -> {
                    JOptionPane.showMessageDialog(this,
                            "Une erreur est survenue: " + ex.getMessage(),
                            "Erreur",
                            JOptionPane.ERROR_MESSAGE);
                });
            } finally {
                // Réactiver l'interface
                SwingUtilities.invokeLater(() -> setInterfaceEnabled(true));
            }
        }).start();
    }

    /**
     * Met à jour le statut de l'application
     */
    private void updateStatus(String message) {
        SwingUtilities.invokeLater(() -> {
            statusLabel.setText(message);
        });
    }

    /**
     * Active ou désactive les éléments de l'interface
     */
    private void setInterfaceEnabled(boolean enabled) {
        reportTypeComboBox.setEnabled(enabled);
        outputPathField.setEnabled(enabled);
        for (Component comp : getContentPane().getComponents()) {
            if (comp instanceof JButton) {
                comp.setEnabled(enabled);
            }
        }
    }

    /**
     * Démarre l'application
     */
    public void startApplication() {
        setVisible(true);
    }
}