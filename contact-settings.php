<?php
$sessionPath = __DIR__ . '/runtime/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
session_save_path($sessionPath);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
session_start();
if (empty($_SESSION['archive_admin'])) { header('Location: admin.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$file = __DIR__ . '/data/site.json';
$settings = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$settings = is_array($settings) ? $settings : [];
$defaults = [
  'contactParish'=>'Immaculate Concepcion Parish','contactAddress'=>'Villa Kananga, Butuan City','contactEmail'=>'',
  'contactPhones'=>[['label'=>'TNT','number'=>'0951-880-9712'],['label'=>'SMART','number'=>'0928-870-1387'],['label'=>'GLOBE','number'=>'0966-866-9261']],
  'socialLinks'=>['facebook'=>'','instagram'=>'','youtube'=>'','x'=>'','tiktok'=>'','linkedin'=>'']
];
$settings = array_merge($defaults, $settings);
$settings['socialLinks'] = array_merge($defaults['socialLinks'], $settings['socialLinks'] ?? []);
$notice = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Invalid request token.');
  $email = trim($_POST['contactEmail'] ?? '');
  $phones = [];
  for ($i=0; $i<3; $i++) {
    $label = trim($_POST['phoneLabel'][$i] ?? ''); $number = trim($_POST['phoneNumber'][$i] ?? '');
    if ($label !== '' || $number !== '') $phones[] = ['label'=>$label ?: 'Phone','number'=>$number];
  }
  $networks = ['facebook','instagram','youtube','x','tiktok','linkedin']; $socials = [];
  foreach ($networks as $network) $socials[$network] = trim($_POST['social'][$network] ?? '');
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
  foreach ($socials as $url) {
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if (!$error && $url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http','https'], true))) $error = 'Social media links must be complete HTTP or HTTPS URLs.';
  }
  if (!$error) {
    $settings['contactParish'] = trim($_POST['contactParish'] ?? '');
    $settings['contactAddress'] = trim($_POST['contactAddress'] ?? '');
    $settings['contactEmail'] = $email; $settings['contactPhones'] = $phones; $settings['socialLinks'] = $socials;
    if (file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX) === false) $error = 'Could not save contact settings.';
    else $notice = 'Contact details updated successfully.';
  }
}
function ch($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$phones = array_pad($settings['contactPhones'] ?? [], 3, ['label'=>'','number'=>'']);
$networkLabels = ['facebook'=>'Facebook','instagram'=>'Instagram','youtube'=>'YouTube','x'=>'X / Twitter','tiktok'=>'TikTok','linkedin'=>'LinkedIn'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contact & Socials · Content Studio</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Libre+Caslon+Display&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/admin.css"><link rel="stylesheet" href="assets/css/contact-settings.css"></head>
<body><header><a class="studio-brand" href="./"><span>AR</span><b>Content Studio<small>Contact & socials</small></b></a><nav><a href="admin.php">Content archive</a><a href="site-settings.php">Site identity</a><a href="./" target="_blank">View website ↗</a><a href="admin.php?logout=1">Sign out</a></nav></header><main class="contact-editor"><aside><p class="eyebrow">Public details</p><h1>Stay connected.</h1><p>Update contact information and add social accounts. Any empty email or social field stays hidden from the public website.</p></aside><section><?php if($notice):?><div class="message success"><?=ch($notice)?></div><?php endif;?><?php if($error):?><div class="message error"><?=ch($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=ch($_SESSION['csrf'])?>"><fieldset><legend>Location & email</legend><label>Parish / organization<input name="contactParish" value="<?=ch($settings['contactParish'])?>"></label><label>Address<input name="contactAddress" value="<?=ch($settings['contactAddress'])?>"></label><label>Email address <small>Optional</small><input type="email" name="contactEmail" value="<?=ch($settings['contactEmail'])?>" placeholder="name@example.com"></label></fieldset><fieldset><legend>Phone numbers</legend><p class="field-note">Leave both fields blank to hide a phone entry.</p><?php for($i=0;$i<3;$i++):?><div class="phone-row"><label>Label<input name="phoneLabel[]" value="<?=ch($phones[$i]['label'])?>" placeholder="e.g. TNT"></label><label>Phone number<input name="phoneNumber[]" value="<?=ch($phones[$i]['number'])?>" placeholder="e.g. 0951-880-9712"></label></div><?php endfor;?></fieldset><fieldset><legend>Social media</legend><p class="field-note">Paste full profile links. Accounts appear with icons only when a URL is saved.</p><div class="social-fields"><?php foreach($networkLabels as $key=>$label):?><label><?=ch($label)?> <small>Optional</small><input type="url" name="social[<?=ch($key)?>]" value="<?=ch($settings['socialLinks'][$key])?>" placeholder="https://..."></label><?php endforeach;?></div></fieldset><button>Save contact details</button></form></section></main></body></html>
