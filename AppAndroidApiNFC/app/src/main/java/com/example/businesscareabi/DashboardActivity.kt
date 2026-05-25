package com.example.businesscareabi

import android.content.Intent
import android.content.SharedPreferences
import android.os.Bundle
import android.view.MenuItem
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.ActionBarDrawerToggle
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.widget.Toolbar
import androidx.core.view.GravityCompat
import androidx.drawerlayout.widget.DrawerLayout
import com.google.android.material.navigation.NavigationView

class DashboardActivity : AppCompatActivity(), NavigationView.OnNavigationItemSelectedListener {

    private lateinit var drawerLayout: DrawerLayout
    private lateinit var navigationView: NavigationView
    private lateinit var welcomeTextView: TextView
    private lateinit var lastLoginInfoTextView: TextView
    private lateinit var logoutButton: Button

    private val PREFS_NAME = "BusinessCarePrefs"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_dashboard)


        val toolbar: Toolbar = findViewById(R.id.toolbar)
        setSupportActionBar(toolbar)

        drawerLayout = findViewById(R.id.drawer_layout)
        navigationView = findViewById(R.id.nav_view)
        welcomeTextView = findViewById(R.id.welcomeTextView)
        lastLoginInfoTextView = findViewById(R.id.lastLoginInfoTextView)
        logoutButton = findViewById(R.id.logoutButton)


        val toggle = ActionBarDrawerToggle(
            this, drawerLayout, toolbar,
            R.string.navigation_drawer_open,
            R.string.navigation_drawer_close
        )
        drawerLayout.addDrawerListener(toggle)
        toggle.syncState()

        navigationView.setNavigationItemSelectedListener(this)

        loadUserInfo()

        logoutButton.setOnClickListener {
            logout()
        }
    }

    private fun loadUserInfo() {
        val prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        val userEmail = prefs.getString("user_email", "Utilisateur")
        val userRole = prefs.getString("user_role", "Accès NFC")

        //dernier scan NFC
        val lastTagId = prefs.getString("lastTagId", "")
        val lastScanTime = prefs.getString("lastScanTime", "")


        welcomeTextView.text = getString(R.string.welcome_message, userEmail)

        // Update last login info
        if (!lastTagId.isNullOrEmpty() && !lastScanTime.isNullOrEmpty()) {
            lastLoginInfoTextView.text = getString(R.string.last_nfc_login_info, lastTagId, lastScanTime)
        } else {
            lastLoginInfoTextView.setText(R.string.no_nfc_scanned)
        }


        val headerView = navigationView.getHeaderView(0)
        val navUsername = headerView.findViewById<TextView>(R.id.nav_header_username)
        val navRole = headerView.findViewById<TextView>(R.id.nav_header_role)

        navUsername?.text = userEmail
        navRole?.text = userRole
    }

    private fun logout() {

        val prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        val editor = prefs.edit()
        editor.clear()
        editor.apply()


        val intent = Intent(this, MainActivity::class.java)
        intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        startActivity(intent)
        finish()
    }

    override fun onNavigationItemSelected(item: MenuItem): Boolean {

        when (item.itemId) {
            R.id.nav_dashboard -> {

            }
            R.id.nav_logout -> {
                logout()
            }
        }

        drawerLayout.closeDrawer(GravityCompat.START)
        return true
    }

    override fun onBackPressed() {
        if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
            drawerLayout.closeDrawer(GravityCompat.START)
        } else {
            super.onBackPressed()
        }
    }
}