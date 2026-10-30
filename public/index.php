<?php
declare(strict_types=1);

session_name('creative_studio_session');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true, 'samesite' => 'Lax',
]);
session_start();

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $pdo = db();
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = rtrim($path, '/') ?: '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $user = current_user();

    if ($method === 'POST') {
        verify_csrf();
        if ($path === '/register') { register_user($pdo); }
        if ($path === '/login') { login_user($pdo); }
        if ($path === '/logout') { logout_user(); }
        if ($path === '/orders') { create_order($pdo); }
        if (preg_match('#^/admin/orders/(\d+)/status$#', $path, $m)) { update_order_status($pdo, (int)$m[1]); }
        http_response_code(404); render('404'); exit;
    }

    if ($path === '/') { render('home', ['services' => $pdo->query('SELECT * FROM services WHERE is_active=1 ORDER BY sort_order')->fetchAll(), 'portfolio' => $pdo->query('SELECT * FROM portfolio_items WHERE is_published=1 ORDER BY sort_order')->fetchAll()]); }
    elseif ($path === '/login') { guest_only(); render('login'); }
    elseif ($path === '/register') { guest_only(); render('register'); }
    elseif ($path === '/services') { render('services', ['services' => $pdo->query('SELECT * FROM services WHERE is_active=1 ORDER BY sort_order')->fetchAll()]); }
    elseif ($path === '/portfolio') { render('portfolio', ['portfolio' => $pdo->query('SELECT * FROM portfolio_items WHERE is_published=1 ORDER BY sort_order')->fetchAll()]); }
    elseif ($path === '/dashboard') { require_login(); $orders = orders_for_user($pdo, $user); render($user['role'] === 'admin' ? 'admin-dashboard' : 'dashboard', ['orders' => $orders, 'stats' => order_stats($pdo, $user)]); }
    elseif ($path === '/orders') { require_login(); render('orders', ['orders' => orders_for_user($pdo, $user)]); }
    elseif ($path === '/orders/new') { require_login(); render('order-form', ['services' => $pdo->query('SELECT * FROM services WHERE is_active=1 ORDER BY sort_order')->fetchAll()]); }
    elseif (preg_match('#^/orders/(\d+)$#', $path, $m)) { require_login(); $order = find_order($pdo, (int)$m[1], $user); if (!$order) { http_response_code(404); render('404'); } else { render('order-detail', ['order' => $order]); } }
    elseif (preg_match('#^/admin/orders/(\d+)$#', $path, $m)) { require_role('admin'); $order = find_order($pdo, (int)$m[1], $user, true); if (!$order) { http_response_code(404); render('404'); } else { render('admin-order', ['order' => $order]); } }
    else { http_response_code(404); render('404'); }
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    render('500');
}

function register_user(PDO $pdo): never {
    $name = trim((string)($_POST['name'] ?? '')); $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? ''); $phone = trim((string)($_POST['phone'] ?? ''));
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Nama harus terdiri dari 2–100 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors[] = 'Alamat email tidak valid.';
    if (strlen($password) < 10 || strlen($password) > 72) $errors[] = 'Kata sandi harus 10–72 karakter.';
    if ($phone !== '' && !preg_match('/^[0-9+() .-]{8,24}$/', $phone)) $errors[] = 'Nomor telepon tidak valid.';
    if (!$errors) { $s=$pdo->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]); if ($s->fetch()) $errors[]='Email sudah terdaftar.'; }
    if ($errors) { flash('errors',$errors); redirect('/register'); }
    $s=$pdo->prepare('INSERT INTO users(name,email,phone,password_hash,role) VALUES(?,?,?,?,\'customer\')');
    $s->execute([$name,$email,$phone ?: null,password_hash($password,PASSWORD_DEFAULT)]);
    session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); flash('success','Akun berhasil dibuat. Selamat datang!'); redirect('/dashboard');
}
function login_user(PDO $pdo): never {
    $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
    // Demo admin credential requested for local preview. Replace with managed auth before production.
    if ($email === 'a' && $password === 'a') {
        session_regenerate_id(true);
        $_SESSION['demo_admin'] = true;
        flash('success','Berhasil masuk sebagai admin demo.');
        redirect('/dashboard');
    }
    $s=$pdo->prepare('SELECT id,password_hash FROM users WHERE email=?'); $s->execute([$email]); $row=$s->fetch();
    if (!$row || !password_verify($password,$row['password_hash'])) { flash('errors',['Email atau kata sandi tidak cocok.']); redirect('/login'); }
    session_regenerate_id(true); $_SESSION['user_id']=(int)$row['id']; flash('success','Berhasil masuk.'); redirect('/dashboard');
}
function logout_user(): never { $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',$p['secure'],$p['httponly']); } session_destroy(); redirect('/'); }
function create_order(PDO $pdo): never {
    require_login(); $user=current_user(); $serviceId=filter_input(INPUT_POST,'service_id',FILTER_VALIDATE_INT); $title=trim((string)($_POST['title']??'')); $brief=trim((string)($_POST['brief']??'')); $deadline=trim((string)($_POST['deadline']??''));
    $errors=[]; if (!$serviceId) $errors[]='Pilih layanan yang tersedia.'; if (mb_strlen($title)<3 || mb_strlen($title)>160) $errors[]='Judul harus terdiri dari 3–160 karakter.'; if (mb_strlen($brief)<20 || mb_strlen($brief)>5000) $errors[]='Brief harus terdiri dari 20–5.000 karakter.';
    if ($deadline!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$deadline) || $deadline < date('Y-m-d'))) $errors[]='Tanggal target tidak valid.';
    $s=$pdo->prepare('SELECT id,price FROM services WHERE id=? AND is_active=1'); $s->execute([$serviceId]); $service=$s->fetch(); if (!$service) $errors[]='Layanan tidak tersedia.';
    if ($errors) { flash('errors',$errors); redirect('/orders/new'); }
    $s=$pdo->prepare('INSERT INTO orders(user_id,service_id,title,brief,deadline,price,status) VALUES(?,?,?,?,?,?,\'new\')'); $s->execute([$user['id'],$serviceId,$title,$brief,$deadline ?: null,$service['price']]);
    flash('success','Pesanan berhasil dikirim.'); redirect('/orders/'.(int)$pdo->lastInsertId());
}
function update_order_status(PDO $pdo,int $id): never {
    require_role('admin'); $status=(string)($_POST['status']??''); $allowed=['new','review','in_progress','revision','completed','cancelled'];
    if (!in_array($status,$allowed,true)) { flash('errors',['Status tidak valid.']); redirect('/admin/orders/'.$id); }
    $s=$pdo->prepare('UPDATE orders SET status=?,updated_at=NOW() WHERE id=?'); $s->execute([$status,$id]); if (!$s->rowCount()) { flash('errors',['Pesanan tidak ditemukan atau status tidak berubah.']); redirect('/orders'); }
    flash('success','Status pesanan diperbarui.'); redirect('/admin/orders/'.$id);
}
