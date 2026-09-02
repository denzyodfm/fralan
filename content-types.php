<?php
$sessionPath = __DIR__ . '/runtime/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
session_save_path($sessionPath);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
session_start();
if (empty($_SESSION['archive_admin'])) { header('Location: admin.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
require_once __DIR__.'/content-types-lib.php';

$settingsFile = __DIR__.'/data/site.json';
$contentFile = __DIR__.'/data/content.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$settings = is_array($settings) ? $settings : [];
$items = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$items = is_array($items) ? $items : [];
$types = normalizeContentTypes($settings);
$notice = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Invalid request token.');
  if (isset($_POST['add'])) {
    $label = trim($_POST['label'] ?? '');
    $key = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-');
    if ($label === '') $error = 'Enter a content type name.';
    elseif (mb_strlen($label) > 40) $error = 'Use a name of 40 characters or fewer.';
    elseif ($key === '') $error = 'Use at least one letter or number.';
    elseif (in_array($key, array_column($types, 'key'), true)) $error = 'That content type already exists.';
    else { $types[] = ['key'=>$key,'label'=>$label]; $notice = 'Content type added.'; }
  }
  if (isset($_POST['delete'])) {
    $deleteKey = preg_replace('/[^a-z0-9-]/', '', $_POST['delete']);
    $inUse = count(array_filter($items, fn($item) => ($item['type'] ?? '') === $deleteKey));
    if (in_array($deleteKey, ['transliteration','article'], true)) $error = 'The two default content types cannot be deleted.';
    elseif ($inUse) $error = 'Move or delete entries using this type before removing it.';
    else { $types = array_values(array_filter($types, fn($type) => $type['key'] !== $deleteKey)); $notice = 'Content type removed.'; }
  }
  if (!$error) {
    $settings['contentTypes'] = $types;
    if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX) === false) $error = 'Could not save content types.';
  }
}
function ct($value){return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Content Types · Content Studio</title><link rel="stylesheet" href="assets/css/admin.css"><link rel="stylesheet" href="assets/css/admin-modern.css"><link rel="stylesheet" href="assets/css/content-types.css"></head>
<body><header><a class="studio-brand" href="./"><span>AR</span><b>Content Studio<small>Content types</small></b></a><nav><a href="admin.php">Content archive</a><a href="site-settings.php">Site identity</a><a href="contact-settings.php">Contact & socials</a><a href="manual.php">Help</a><a href="admin.php?logout=1">Sign out</a></nav></header>
<main class="type-manager"><aside><p class="eyebrow">Organization</p><h1>Content types.</h1><p>Create a reusable type whenever the archive grows. Types become choices in the publishing form and filters on the public archive.</p></aside><section><?php if($notice):?><div class="message success"><?=ct($notice)?></div><?php endif;?><?php if($error):?><div class="message error"><?=ct($error)?></div><?php endif;?><div class="type-list"><div class="type-list-head"><div><p class="eyebrow">Available types</p><h2><?=count($types)?> content types</h2></div></div><?php foreach($types as $type): $usage=count(array_filter($items,fn($item)=>($item['type']??'')===$type['key']));?><article><div><strong><?=ct($type['label'])?></strong><small><?=ct($type['key'])?> · <?=$usage?> <?= $usage===1?'entry':'entries' ?></small></div><?php if(!in_array($type['key'],['transliteration','article'],true)):?><form method="post" onsubmit="return confirm('Remove this unused content type?')"><input type="hidden" name="csrf" value="<?=ct($_SESSION['csrf'])?>"><button class="delete" name="delete" value="<?=ct($type['key'])?>" <?=$usage?'disabled':''?>>Remove</button></form><?php else:?><span class="protected">Default</span><?php endif;?></article><?php endforeach;?></div><form class="add-type" method="post"><input type="hidden" name="csrf" value="<?=ct($_SESSION['csrf'])?>"><div><p class="eyebrow">Add a type</p><h2>Create when needed.</h2></div><label>Content type name<input name="label" maxlength="40" placeholder="e.g. Homily, Prayer, Book" required><small>A short URL-safe key is created automatically.</small></label><button name="add">Add content type</button></form></section></main></body></html>

