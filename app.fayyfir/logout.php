<?php  
session_start();  
require "config.php";  

if (isset($_SESSION["user_id"])) {  
  $user_id = $_SESSION["user_id"];  
  
  $stmt = $conn->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE id = ?");  
  $stmt->bind_param("i", $user_id);  
  $stmt->execute();  
  $stmt->close();  

} elseif (isset($_GET["email"])) {
  $email = $_GET["email"];

  $conn1 = get_conn1();
  if ($conn1 && !$conn1->connect_error) {
      $stmt = $conn1->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE email = ? OR phone = ?");
      if ($stmt) {
          $stmt->bind_param("ss", $email, $email);
          $stmt->execute();
          $stmt->close();
      }
  }

  $conn2 = get_conn2();
  if ($conn2 && !$conn2->connect_error) {
      $stmt = $conn2->prepare("UPDATE users SET is_online = 0, session_token = NULL, token_expiry = NULL WHERE email = ? OR phone = ?");
      if ($stmt) {
          $stmt->bind_param("ss", $email, $email);
          $stmt->execute();
          $stmt->close();
      }
  }
}

session_unset();  
session_destroy();  
header("Location: login.php?message=logout_success");  
exit();  
?>