<?php
declare(strict_types=1);
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');
$envFile=dirname(__DIR__).'/.env';
if (is_file($envFile)) { foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) { $line=trim($line); if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue; [$key,$value]=explode('=',$line,2); $_ENV[trim($key)]=trim($value," \t\n\r\0\x0B\"'"); } }
function envv(string $key,string $default=''): string { if (array_key_exists($key,$_ENV)) return (string)$_ENV[$key]; $value=getenv($key); return $value===false?$default:$value; }
function db(): PDO { static $pdo; if ($pdo instanceof PDO) return $pdo; $dsn='mysql:host='.envv('DB_HOST','127.0.0.1').';port='.envv('DB_PORT','3306').';dbname='.envv('DB_DATABASE','creative_studio').';charset=utf8mb4'; return $pdo=new PDO($dsn,envv('DB_USERNAME','root'),envv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
function e(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function url(string $path): string { return $path; }
function redirect(string $path): never { header('Location: '.$path); exit; }
function flash(string $key,mixed $value=null): mixed { if ($value!==null) { $_SESSION['_flash'][$key]=$value; return null; } $value=$_SESSION['_flash'][$key]??null; unset($_SESSION['_flash'][$key]); return $value; }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function verify_csrf(): void { $token=(string)($_POST['_csrf']??''); if (!$token || !hash_equals(csrf_token(),$token)) { http_response_code(419); render('419'); exit; } }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function current_user(): ?array { static $loaded=false,$user=null; if($loaded)return $user; $loaded=true; if(!empty($_SESSION['demo_admin'])) return $user=['id'=>0,'name'=>'Admin Demo','email'=>'a','phone'=>null,'role'=>'admin']; if(empty($_SESSION['user_id']))return null; $s=db()->prepare('SELECT id,name,email,phone,role FROM users WHERE id=?'); $s->execute([(int)$_SESSION['user_id']]); return $user=$s->fetch()?:null; }
function require_login(): void { if(!current_user()){ flash('errors',['Silakan masuk untuk melanjutkan.']); redirect('/login'); } }
function require_role(string $role): void { require_login(); if(current_user()['role']!==$role){ http_response_code(403); render('403'); exit; } }
function guest_only(): void { if(current_user()) redirect('/dashboard'); }
function render(string $view,array $data=[]): void { extract($data,EXTR_SKIP); $viewFile=dirname(__DIR__).'/app/views/'.$view.'.php'; if(!is_file($viewFile))$viewFile=dirname(__DIR__).'/app/views/404.php'; require dirname(__DIR__).'/app/views/layout.php'; }
function orders_for_user(PDO $pdo,array $user): array { if($user['role']==='admin')return $pdo->query('SELECT o.*,u.name customer_name,s.name service_name FROM orders o JOIN users u ON u.id=o.user_id JOIN services s ON s.id=o.service_id ORDER BY o.created_at DESC')->fetchAll(); $s=$pdo->prepare('SELECT o.*,s.name service_name FROM orders o JOIN services s ON s.id=o.service_id WHERE o.user_id=? ORDER BY o.created_at DESC'); $s->execute([$user['id']]);return $s->fetchAll(); }
function order_stats(PDO $pdo,array $user): array { $sql=$user['role']==='admin'?'SELECT COUNT(*) total,SUM(status=\'new\') new_count,SUM(status=\'in_progress\') progress_count,SUM(status=\'completed\') completed_count FROM orders':'SELECT COUNT(*) total,SUM(status=\'new\') new_count,SUM(status=\'in_progress\') progress_count,SUM(status=\'completed\') completed_count FROM orders WHERE user_id=?'; $s=$pdo->prepare($sql);$s->execute($user['role']==='admin'?[]:[$user['id']]);return $s->fetch()?:[]; }
function find_order(PDO $pdo,int $id,array $user,bool $admin=false): ?array { $sql='SELECT o.*,s.name service_name,u.name customer_name,u.email customer_email,u.phone customer_phone FROM orders o JOIN services s ON s.id=o.service_id JOIN users u ON u.id=o.user_id WHERE o.id=?'; if(!$admin)$sql.=' AND o.user_id=?'; $s=$pdo->prepare($sql);$s->execute($admin?[$id]:[$id,$user['id']]);return $s->fetch()?:null; }
function money(mixed $amount): string { return 'Rp '.number_format((float)$amount,0,',','.'); }
function status_label(string $status): string { return ['new'=>'Baru','review'=>'Ditinjau','in_progress'=>'Dikerjakan','revision'=>'Revisi','completed'=>'Selesai','cancelled'=>'Dibatalkan'][$status]??$status; }
