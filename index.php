<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login    = trim($_POST['login']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, failed_attempts, is_locked 
            FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $username, $hashed_password, $failed_attempts, $is_locked);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();


        if ($is_locked) {
            echo "Account locked due to multiple failed login attempts.";
        } else {
            if (password_verify($password, $hashed_password)) {
                $update = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE id = ?");
                $update->bind_param("i", $id);
                $update->execute();

                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                header("Location: welcome.php");
                exit;
            } else {
                $failed_attempts++;
                if ($failed_attempts >= 3) {
                    $lock = $conn->prepare("UPDATE users SET failed_attempts = ?, is_locked = 1 WHERE id = ?");
                    $lock->bind_param("ii", $failed_attempts, $id);
                    $lock->execute();
                    echo "Too many failed attempts. Account locked.";
                } else {
                    $update = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
                    $update->bind_param("ii", $failed_attempts, $id);
                    $update->execute();
                    echo "Invalid password. Attempt $failed_attempts of 3.";
                }
            }
        }
    } else {
        echo "No user found.";
    }

    $stmt->close();
}
?>

<form method="post" action="">
    <h2>Login</h2>
    <input type="text" name="login" placeholder="Username or Email" required><br><br>
    <input type="password" name="password" placeholder="Enter Password" required><br><br>
    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>