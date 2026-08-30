<?php
$sessionPath = __DIR__ . '/runtime/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0700, true);
session_save_path($sessionPath);
session_start();
function envValues() {
  $values = [];
  foreach (@file(__DIR__.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2); $values[trim($key)] = trim($value);
  }
  return $values;
}
$env = envValues(); $error = ''; $notice = '';
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  if (hash_equals($env['WP_ADMIN_USER'] ?? 'admin', $_POST['username'] ?? '') && hash_equals($env['WP_ADMIN_PASSWORD'] ?? '', $_POST['password'] ?? '')) { $_SESSION['archive_admin'] = true; $_SESSION['csrf'] = bin2hex(random_bytes(24)); header('Location: admin.php'); exit; }
  $error = 'Those credentials do not match the values in .env.';
}
$loggedIn = !empty($_SESSION['archive_admin']);
$file = __DIR__.'/data/content.json';
$items = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$items = is_array($items) ? $items : [];
function slugify($text) { $text = strtolower(trim($text)); $text = preg_replace('/[^a-z0-9]+/', '-', $text); return trim($text, '-') ?: uniqid('entry-'); }
function cleanBody($html) { return strip_tags($html, '<p><br><h2><h3><strong><em><blockquote><ul><ol><li><a>'); }
function saveItems($file, $items) { return file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), LOCK_EX) !== false; }
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid request token.');
  if (isset($_POST['delete'])) {
    $items = array_values(array_filter($items, fn($i) => ($i['id'] ?? '') !== $_POST['delete']));
    $notice = saveItems($file, $items) ? 'Content deleted.' : 'Could not save changes.';
  } else {
    $id = preg_replace('/[^a-z0-9-]/', '', $_POST['id'] ?? '') ?: uniqid('entry-');
    $image = $_POST['existing_image'] ?? 'assets/images/archive-study.png';
    if (!empty($_FILES['image']['tmp_name'])) {
      $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
      if (isset($allowed[$mime]) && $_FILES['image']['size'] <= 8*1024*1024) {
        $name = slugify(pathinfo($_FILES['image']['name'], PATHINFO_FILENAME)).'-'.substr(bin2hex(random_bytes(4)),0,8).'.'.$allowed[$mime];
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/uploads/'.$name)) $image = 'uploads/'.$name;
      } else $error = 'Please upload a JPG, PNG, WebP, or GIF smaller than 8 MB.';
    }
    $audio = $_POST['existing_audio'] ?? '';
    if (!empty($_POST['remove_audio'])) $audio = '';
    if (!empty($_FILES['audio']['tmp_name'])) {
      $audioTypes = [
        'audio/mpeg'=>'mp3', 'audio/mp3'=>'mp3', 'audio/wav'=>'wav',
        'audio/x-wav'=>'wav', 'audio/ogg'=>'ogg', 'audio/mp4'=>'m4a',
        'audio/x-m4a'=>'m4a', 'video/mp4'=>'m4a', 'audio/webm'=>'webm'
      ];
      $audioMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['audio']['tmp_name']);
      if (isset($audioTypes[$audioMime]) && $_FILES['audio']['size'] <= 30*1024*1024) {
        $audioName = slugify(pathinfo($_FILES['audio']['name'], PATHINFO_FILENAME)).'-'.substr(bin2hex(random_bytes(4)),0,8).'.'.$audioTypes[$audioMime];
        if (move_uploaded_file($_FILES['audio']['tmp_name'], __DIR__.'/uploads/'.$audioName)) $audio = 'uploads/'.$audioName;
      } else $error = 'Please upload an MP3, WAV, OGG, M4A, or WebM audio file smaller than 30 MB.';
    }
    if (!$error) {
      $entry = ['id'=>$id,'slug'=>slugify($_POST['title'] ?? ''),'type'=>($_POST['type'] ?? '') === 'article'?'article':'transliteration','title'=>trim($_POST['title'] ?? ''),'excerpt'=>trim($_POST['excerpt'] ?? ''),'category'=>trim($_POST['category'] ?? ''),'language'=>trim($_POST['language'] ?? ''),'date'=>$_POST['date'] ?? date('Y-m-d'),'image'=>$image,'imageAlt'=>trim($_POST['imageAlt'] ?? ''),'audio'=>$audio,'featured'=>isset($_POST['featured']),'body'=>cleanBody($_POST['body'] ?? '')];
      $found = false; foreach ($items as $key=>$item) if (($item['id']??'') === $id) { $items[$key]=$entry; $found=true; }
      if (!$found) $items[]=$entry;
      $notice = saveItems($file, $items) ? 'Content published successfully.' : 'Could not write to data/content.json.';
    }
  }
}
$edit = null; if ($loggedIn && isset($_GET['edit'])) foreach ($items as $item) if (($item['id']??'') === $_GET['edit']) $edit=$item;
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Content Studio · Fr. Alan Relliquette, MSC</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Libre+Caslon+Display&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/admin.css"><link rel="stylesheet" href="assets/css/audio.css"></head>
<body>
<?php if(!$loggedIn): ?><main class="login"><a class="studio-brand" href="./"><span>AR</span><b>Fr. Alan Relliquette, MSC<small>Content studio</small></b></a><section><p class="eyebrow">Welcome back</p><h1>Sign in to your archive.</h1><p>Use the admin username and password saved in your <code>.env</code> file.</p><?php if($error):?><div class="message error"><?=h($error)?></div><?php endif;?><form method="post"><label>Username<input name="username" required autofocus></label><label>Password<input type="password" name="password" required></label><button name="login">Sign in</button></form></section></main>
<?php else: ?><header><a class="studio-brand" href="./"><span>AR</span><b>Content Studio<small>Fr. Alan Relliquette, MSC</small></b></a><nav><a href="site-settings.php">Site identity</a><a href="contact-settings.php">Contact & socials</a><a href="./" target="_blank">View website ↗</a><a href="?logout=1">Sign out</a></nav></header><main class="studio"><aside><p class="eyebrow">Archive</p><h1>Your content</h1><a class="new <?=!$edit?'active':''?>" href="admin.php">＋ New entry</a><div class="entry-list"><?php foreach(array_reverse($items) as $item):?><a class="<?=($edit['id']??'')===$item['id']?'active':''?>" href="?edit=<?=h($item['id'])?>"><span><?=h($item['type'])?></span><b><?=h($item['title'])?></b><small><?=h($item['date'])?></small></a><?php endforeach;?></div></aside><section class="editor"><div class="editor-head"><div><p class="eyebrow"><?=$edit?'Editing entry':'New entry'?></p><h2><?=$edit?'Update your content':'Publish something new'?></h2></div><?php if($edit):?><form method="post" onsubmit="return confirm('Delete this content permanently?')"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><button class="delete" name="delete" value="<?=h($edit['id'])?>">Delete</button></form><?php endif;?></div><?php if($notice):?><div class="message success"><?=h($notice)?></div><?php endif;?><?php if($error):?><div class="message error"><?=h($error)?></div><?php endif;?><form class="content-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="id" value="<?=h($edit['id']??'')?>"><input type="hidden" name="existing_image" value="<?=h($edit['image']??'')?>"><input type="hidden" name="existing_audio" value="<?=h($edit['audio']??'')?>"><div class="row"><label>Content type<select name="type"><option value="transliteration" <?=($edit['type']??'')==='transliteration'?'selected':''?>>Transliteration</option><option value="article" <?=($edit['type']??'')==='article'?'selected':''?>>Article / essay</option></select></label><label>Publication date<input type="date" name="date" value="<?=h($edit['date']??date('Y-m-d'))?>" required></label></div><label>Title<input name="title" value="<?=h($edit['title']??'')?>" placeholder="Give this entry a clear title" required></label><label>Short introduction<textarea name="excerpt" rows="3" placeholder="A concise summary shown on content cards" required><?=h($edit['excerpt']??'')?></textarea></label><div class="row"><label>Category<input name="category" value="<?=h($edit['category']??'')?>" placeholder="e.g. Methodology"></label><label>Language<input name="language" value="<?=h($edit['language']??'')?>" placeholder="e.g. Latin or Editorial"></label></div><div class="image-field"><div><?php if(!empty($edit['image'])):?><img src="<?=h($edit['image'])?>" alt="Current cover"><?php else:?><span>Picture preview</span><?php endif;?></div><label>Cover picture<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF · up to 8 MB</small></label></div><div class="audio-field"><label>Audio recording<input type="file" name="audio" accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/webm,.m4a"><small>MP3, WAV, OGG, M4A, or WebM · up to 30 MB</small></label><?php if(!empty($edit['audio'])):?><audio controls preload="metadata" src="<?=h($edit['audio'])?>"></audio><label class="check"><input type="checkbox" name="remove_audio"> Remove current audio</label><?php endif;?></div><label>Picture description<input name="imageAlt" value="<?=h($edit['imageAlt']??'')?>" placeholder="Describe the picture for visitors using screen readers"></label><label>Content <small class="hint">Simple HTML is supported: &lt;h2&gt;, &lt;p&gt;, &lt;blockquote&gt;, &lt;strong&gt;</small><textarea class="body-editor" name="body" rows="15" placeholder="<p>Begin your text here...</p>" required><?=h($edit['body']??'')?></textarea></label><label class="check"><input type="checkbox" name="featured" <?=!empty($edit['featured'])?'checked':''?>> Feature this entry</label><button class="publish">Publish content</button></form></section></main><?php endif;?><script>const file=document.querySelector('input[type=file]');file?.addEventListener('change',()=>{const img=document.querySelector('.image-field div img')||document.createElement('img');img.src=URL.createObjectURL(file.files[0]);document.querySelector('.image-field div').replaceChildren(img)});</script></body></html>

