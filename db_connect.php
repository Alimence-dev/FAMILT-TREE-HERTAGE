<?php
/* Kibaza Genealogy System - Database Connection
   Central configuration for InfinityFree MySQL Server
   Version: 1.1 (Security Optimized)
*/

// Database Credentials
$servername = "sql209.infinityfree.com"; 
$username   = "if0_41776903";              
$password   = "p3BHMQdBWRm0C";             
$dbname     = "if0_41776903_kibaza";         

// Disable internal reporting for a cleaner live environment
mysqli_report(MYSQLI_REPORT_OFF);

// Create connection using MySQLi Object-Oriented approach
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    /* Security Note: We log the specific error internally but 
       show a clean message to the user to hide server details.
    */
    error_log("Connection failed: " . $conn->connect_error);
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
            <h2>System Maintenance</h2>
            <p>Unable to connect to the heritage database. Please try again later.</p>
         </div>");
}

/* Set charset to utf8mb4.
   This is critical for Rwanda Heritage System to correctly store 
   Kinyarwanda special characters (like 'î', 'â', etc.)
*/
$conn->set_charset("utf8mb4");

// Global utility variables for the system
$system_name = "Kibaza Rwanda Heritage";
$system_year = date("Y");

?>