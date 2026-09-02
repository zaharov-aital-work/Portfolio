<?php
session_start();
require_once __DIR__ . '/config.php';

if (!empty($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = mysqli_prepare($conn, "SELECT id, first_name, last_name, email FROM users WHERE verification_token = ? AND email_verified = 0");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $upd = mysqli_prepare($conn, "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        mysqli_stmt_bind_param($upd, "i", $user['id']);
        mysqli_stmt_execute($upd);

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['email']      = $user['email'];

        header('Location: account.php');
        exit;
    } else {
        header('Location: login.php?verify_error=1');
        exit;
    }

} elseif (!empty($_GET['resend'])) {
    $email = trim($_GET['resend']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = mysqli_prepare($conn, "SELECT id, first_name FROM users WHERE email = ? AND email_verified = 0");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $upd = mysqli_prepare($conn, "UPDATE users SET verification_token = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $token, $user['id']);
            mysqli_stmt_execute($upd);

            $protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $verify_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/verify.php?token=' . $token;
            $subject     = '=?UTF-8?B?' . base64_encode('Подтвердите ваш email — BASEMOOD') . '?=';
            $body        = "Здравствуйте, {$user['first_name']}!\n\n"
                         . "Для подтверждения email перейдите по ссылке:\n{$verify_link}\n\n"
                         . "Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.";
            $headers     = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n"
                         . "Content-Transfer-Encoding: 8bit";
            mail($email, $subject, $body, $headers);
        }
    }

    // Не раскрываем, есть ли такой email: всегда редиректим
    header('Location: login.php?verify_sent=1');
    exit;

} else {
    header('Location: index.php');
    exit;
}
