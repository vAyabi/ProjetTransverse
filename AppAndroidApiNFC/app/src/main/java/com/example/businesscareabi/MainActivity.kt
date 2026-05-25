package com.example.businesscareabi

import android.app.PendingIntent
import android.content.Intent
import android.content.SharedPreferences
import android.graphics.Color
import android.nfc.NfcAdapter
import android.nfc.Tag
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import org.json.JSONObject
import com.android.volley.Request
import com.android.volley.toolbox.JsonObjectRequest
import com.android.volley.toolbox.Volley
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class MainActivity : AppCompatActivity() {

    private lateinit var emailEditText: EditText
    private lateinit var passwordEditText: EditText
    private lateinit var loginButton: Button
    private lateinit var progressBar: ProgressBar

    private var nfcAdapter: NfcAdapter? = null
    private lateinit var nfcStatusTextView: TextView
    private lateinit var nfcResultTextView: TextView
    private lateinit var nfcIdTextView: TextView
    private lateinit var clearButton: Button

    private val PREFS_NAME = "BusinessCarePrefs"
    private val LAST_TAG_ID = "lastTagId"
    private val LAST_SCAN_TIME = "lastScanTime"

    private val API_BASE_URL = "http://10.0.2.2/business-care-api"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        // Email/Password
        emailEditText = findViewById(R.id.emailEditText)
        passwordEditText = findViewById(R.id.passwordEditText)
        loginButton = findViewById(R.id.loginButton)
        progressBar = findViewById(R.id.progressBar)

        // NFC
        nfcStatusTextView = findViewById(R.id.nfcStatusTextView)
        nfcResultTextView = findViewById(R.id.nfcResultTextView)
        nfcIdTextView = findViewById(R.id.nfcIdTextView)
        clearButton = findViewById(R.id.clearButton)

        // verif user
        val sharedPreferences = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        if (sharedPreferences.contains("auth_token")) {
            // dashboard
            val intent = Intent(this, DashboardActivity::class.java)
            startActivity(intent)
            finish()
            return
        }


        loginButton.setOnClickListener {
            loginUser()
        }


        initializeNfc()
    }

    private fun initializeNfc() {

        nfcAdapter = NfcAdapter.getDefaultAdapter(this)


        if (nfcAdapter == null) {
            nfcStatusTextView.setText(R.string.nfc_not_supported)
            nfcStatusTextView.setTextColor(Color.RED)
        } else if (nfcAdapter?.isEnabled == false) {
            nfcStatusTextView.setText(R.string.nfc_disabled)
            nfcStatusTextView.setTextColor(Color.RED)
        } else {
            nfcStatusTextView.setText(R.string.nfc_ready)
            nfcStatusTextView.setTextColor(Color.GREEN)
        }


        clearButton.setOnClickListener {
            clearLastScan()
        }


        loadLastScan()


        if (intent.action != null &&
            (NfcAdapter.ACTION_TECH_DISCOVERED == intent.action ||
                    NfcAdapter.ACTION_TAG_DISCOVERED == intent.action)) {
            processIntent(intent)
        }
    }

    private fun loginUser() {
        val email = emailEditText.text.toString().trim()
        val password = passwordEditText.text.toString().trim()

        if (email.isEmpty()) {
            emailEditText.error = "Veuillez entrer votre email"
            return
        }

        if (password.isEmpty()) {
            passwordEditText.error = "Veuillez entrer votre mot de passe"
            return
        }

        progressBar.visibility = View.VISIBLE
        loginButton.isEnabled = false

        try {
            val params = JSONObject()
            params.put("email", email)
            params.put("password", password)

            val loginUrl = "$API_BASE_URL/api/admin/login.php"
            val request = JsonObjectRequest(
                Request.Method.POST, loginUrl, params,
                { response ->
                    progressBar.visibility = View.GONE
                    loginButton.isEnabled = true

                    try {
                        val success = response.getBoolean("success")
                        if (success) {
                            val data = response.getJSONObject("data")
                            val token = data.getString("token")
                            val userId = data.getInt("id_admin")
                            val userEmail = data.getString("email")
                            val userRole = data.getString("role")


                            val sharedPreferences = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
                            val editor = sharedPreferences.edit()
                            editor.putString("auth_token", token)
                            editor.putInt("user_id", userId)
                            editor.putString("user_email", userEmail)
                            editor.putString("user_role", userRole)
                            editor.apply()


                            val intent = Intent(this, DashboardActivity::class.java)
                            startActivity(intent)
                            finish()
                        } else {
                            val message = response.getString("message")
                            Toast.makeText(this, message, Toast.LENGTH_LONG).show()
                        }
                    } catch (e: Exception) {
                        Toast.makeText(this, "Erreur lors du traitement de la réponse", Toast.LENGTH_LONG).show()
                        e.printStackTrace()
                    }
                },
                { error ->
                    progressBar.visibility = View.GONE
                    loginButton.isEnabled = true


                    val errorMessage = when (error.networkResponse?.statusCode) {
                        401 -> "Identifiants incorrects"
                        404 -> "Email non trouvé"
                        else -> "Erreur de connexion"
                    }

                    Toast.makeText(this, errorMessage, Toast.LENGTH_LONG).show()
                    error.printStackTrace()
                })

            Volley.newRequestQueue(this).add(request)
        } catch (e: Exception) {
            progressBar.visibility = View.GONE
            loginButton.isEnabled = true
            Toast.makeText(this, "Erreur de connexion", Toast.LENGTH_LONG).show()
            e.printStackTrace()
        }
    }


    override fun onResume() {
        super.onResume()
        if (nfcAdapter != null && nfcAdapter!!.isEnabled) {
            setupForegroundDispatch()
        }
    }

    override fun onPause() {
        super.onPause()
        nfcAdapter?.disableForegroundDispatch(this)
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        if (NfcAdapter.ACTION_TECH_DISCOVERED == intent.action ||
            NfcAdapter.ACTION_TAG_DISCOVERED == intent.action) {
            processIntent(intent)
        }
    }

    private fun setupForegroundDispatch() {
        if (nfcAdapter == null) return

        val intent = Intent(this, javaClass).addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)

        val flags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            PendingIntent.FLAG_MUTABLE
        } else {
            0
        }

        val pendingIntent = PendingIntent.getActivity(this, 0, intent, flags)
        nfcAdapter?.enableForegroundDispatch(this, pendingIntent, null, null)
    }

    private fun processIntent(intent: Intent) {
        val tag = intent.getParcelableExtra<Tag>(NfcAdapter.EXTRA_TAG)
        if (tag != null) {
            val tagId = getTagId(tag)


            nfcResultTextView.setText(R.string.access_granted)
            nfcResultTextView.setTextColor(Color.GREEN)


            nfcIdTextView.text = tagId


            saveLastScan(tagId)


            val sharedPreferences = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
            val editor = sharedPreferences.edit()
            editor.putString("auth_token", "nfc_session_" + System.currentTimeMillis())
            editor.putInt("user_id", 1)  // ID fictif
            editor.putString("user_email", "nfc_user@example.com")  // Email fictif
            editor.putString("user_role", "NFC User")  // Rôle fictif
            editor.apply()

            // Notif
            Toast.makeText(this, R.string.nfc_detected, Toast.LENGTH_SHORT).show()

            // Rediriger page principale
            nfcResultTextView.postDelayed({
                val dashboardIntent = Intent(this, DashboardActivity::class.java)
                startActivity(dashboardIntent)
                finish()
            }, 1000)  // Délai de 1 seconde pour montrer le message de réussite
        }
    }

    private fun getTagId(tag: Tag): String {
        val id = tag.id
        val sb = StringBuilder()
        for (b in id) {
            sb.append(String.format("%02X", b))
            sb.append(":")
        }
        if (sb.isNotEmpty()) {
            sb.deleteCharAt(sb.length - 1) // Supprimer le dernier ":"
        }
        return sb.toString()
    }

    private fun saveLastScan(tagId: String) {
        val prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        val editor = prefs.edit()

        // Sauvegarder l'ID tag
        editor.putString(LAST_TAG_ID, tagId)

        // l'heure
        val sdf = SimpleDateFormat("dd/MM/yyyy HH:mm:ss", Locale.getDefault())
        val currentTime = sdf.format(Date())
        editor.putString(LAST_SCAN_TIME, currentTime)

        editor.apply()
    }

    private fun loadLastScan() {
        val prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        val lastTagId = prefs.getString(LAST_TAG_ID, null)
        val lastScanTime = prefs.getString(LAST_SCAN_TIME, null)

        if (lastTagId != null && lastScanTime != null) {
            nfcIdTextView.text = "$lastTagId\n$lastScanTime"
        } else {
            nfcIdTextView.setText(R.string.no_nfc_scanned)
        }
    }

    private fun clearLastScan() {
        val prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        val editor = prefs.edit()
        editor.remove(LAST_TAG_ID)
        editor.remove(LAST_SCAN_TIME)
        editor.apply()

        nfcIdTextView.setText(R.string.no_nfc_scanned)
        nfcResultTextView.setText(R.string.nfc_instruction)
        nfcResultTextView.setTextColor(Color.BLACK)
    }
}