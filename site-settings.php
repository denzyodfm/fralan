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
$themes = [
  'heritage'=>['label'=>'Heritage','description'=>'Scholarly, warm, and timeless','colorPrimary'=>'#193d33','colorAccent'=>'#c5a56a','colorBackground'=>'#fbfaf6','colorSurface'=>'#f4f0e7','colorInk'=>'#132722','headingFont'=>'libre-caslon','bodyFont'=>'dm-sans'],
  'coastal'=>['label'=>'Coastal Archive','description'=>'Calm, clear, and approachable','colorPrimary'=>'#183b4e','colorAccent'=>'#d5a04a','colorBackground'=>'#f7f4ec','colorSurface'=>'#e8eef0','colorInk'=>'#102a36','headingFont'=>'lora','bodyFont'=>'source-sans'],
  'burgundy'=>['label'=>'Burgundy Study','description'=>'Formal, literary, and distinguished','colorPrimary'=>'#5a2430','colorAccent'=>'#c79a58','colorBackground'=>'#fbf7f2','colorSurface'=>'#f0e3df','colorInk'=>'#2f1a1e','headingFont'=>'playfair','bodyFont'=>'inter'],
  'earth'=>['label'=>'Earthen Manuscript','description'=>'Organic, grounded, and inviting','colorPrimary'=>'#4b4a32','colorAccent'=>'#b86f47','colorBackground'=>'#f7f2e8','colorSurface'=>'#e7ddcb','colorInk'=>'#2c2c22','headingFont'=>'cormorant','bodyFont'=>'nunito'],
  'midnight'=>['label'=>'Midnight Library','description'=>'Crisp, modern, and authoritative','colorPrimary'=>'#17243f','colorAccent'=>'#d3b46e','colorBackground'=>'#f6f7fa','colorSurface'=>'#e7eaf1','colorInk'=>'#111827','headingFont'=>'libre-caslon','bodyFont'=>'inter']
  ,'modern-editorial'=>['label'=>'Modern Editorial','description'=>'Sophisticated, fresh, and expressive','colorPrimary'=>'#243447','colorAccent'=>'#e07a5f','colorBackground'=>'#fafaf8','colorSurface'=>'#eef1f3','colorInk'=>'#18212b','headingFont'=>'fraunces','bodyFont'=>'manrope']
  ,'minimal-slate'=>['label'=>'Minimal Slate','description'=>'Clean, digital, and confidently modern','colorPrimary'=>'#334155','colorAccent'=>'#3b82f6','colorBackground'=>'#fafafa','colorSurface'=>'#eef2f6','colorInk'=>'#111827','headingFont'=>'space-grotesk','bodyFont'=>'inter']
  ,'sacred-gold'=>['label'=>'Sacred Gold','description'=>'Ceremonial, luminous, and refined','colorPrimary'=>'#3c2f2f','colorAccent'=>'#c99736','colorBackground'=>'#fcf8ef','colorSurface'=>'#f0e7d6','colorInk'=>'#251e1e','headingFont'=>'marcellus','bodyFont'=>'source-sans']
  ,'contemporary-teal'=>['label'=>'Contemporary Teal','description'=>'Vibrant, cultured, and approachable','colorPrimary'=>'#0f4c5c','colorAccent'=>'#e36450','colorBackground'=>'#f8fbfa','colorSurface'=>'#e4f0ee','colorInk'=>'#12333a','headingFont'=>'newsreader','bodyFont'=>'manrope']
  ,'soft-lavender'=>['label'=>'Soft Lavender','description'=>'Gentle, artistic, and distinctive','colorPrimary'=>'#4b4568','colorAccent'=>'#c078b5','colorBackground'=>'#fbf9fc','colorSurface'=>'#eeeaf3','colorInk'=>'#292438','headingFont'=>'dm-serif','bodyFont'=>'outfit']
  ,'modern-monochrome'=>['label'=>'Modern Monochrome','description'=>'Bold, gallery-like, and minimal','colorPrimary'=>'#18181b','colorAccent'=>'#71717a','colorBackground'=>'#ffffff','colorSurface'=>'#f4f4f5','colorInk'=>'#09090b','headingFont'=>'bodoni','bodyFont'=>'work-sans']
];
$defaults = ['initials'=>'AR','logo'=>'','name'=>'Fr. Alan Relliquette, MSC','tagline'=>'Texts, language & scholarship','theme'=>'heritage'] + $themes['heritage'];
$settings = file_exists($file) ? json_decode(file_get_contents($file), true) : $defaults;
$settings = is_array($settings) ? array_merge($defaults, $settings) : $defaults;
$notice = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Invalid request token.');
  $logo = $settings['logo'] ?? '';
  if (!empty($_POST['remove_logo'])) $logo = '';
  if (!empty($_FILES['logo']['tmp_name'])) {
    $logoTypes = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $logoMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);
    if (isset($logoTypes[$logoMime]) && $_FILES['logo']['size'] <= 2*1024*1024) {
      $logoName = 'site-icon-'.substr(bin2hex(random_bytes(5)),0,10).'.'.$logoTypes[$logoMime];
      if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__.'/uploads/'.$logoName)) $logo = 'uploads/'.$logoName;
    } else $error = 'Please upload a JPG, PNG, or WebP icon smaller than 2 MB.';
  }
  $themeKey = $_POST['theme'] ?? 'heritage';
  $selectedTheme = $themes[$themeKey] ?? null;
  $updated = [
    'initials' => $settings['initials'] ?? 'AR',
    'logo' => $logo,
    'name' => trim($_POST['name'] ?? ''),
    'tagline' => trim($_POST['tagline'] ?? ''),
    'theme' => $themeKey,
    'colorPrimary' => $selectedTheme['colorPrimary'] ?? '',
    'colorAccent' => $selectedTheme['colorAccent'] ?? '',
    'colorBackground' => $selectedTheme['colorBackground'] ?? '',
    'colorSurface' => $selectedTheme['colorSurface'] ?? '',
    'colorInk' => $selectedTheme['colorInk'] ?? '',
    'headingFont' => $selectedTheme['headingFont'] ?? '',
    'bodyFont' => $selectedTheme['bodyFont'] ?? ''
  ];
  $updated = array_merge($settings, $updated);
  if (!$error) {
    if ($updated['name'] === '') $error = 'Please enter a display name.';
    elseif ($updated['tagline'] === '') $error = 'Please enter a tagline.';
    elseif (!$selectedTheme) $error = 'Please select one of the available themes.';
    elseif (count(array_filter([$updated['colorPrimary'],$updated['colorAccent'],$updated['colorBackground'],$updated['colorSurface'],$updated['colorInk']], fn($value) => !preg_match('/^#[0-9a-fA-F]{6}$/', $value)))) $error = 'Please choose valid six-digit theme colors.';
    elseif (!in_array($updated['headingFont'], ['libre-caslon','cormorant','playfair','lora','fraunces','marcellus','newsreader','dm-serif','bodoni','space-grotesk'], true) || !in_array($updated['bodyFont'], ['dm-sans','inter','source-sans','nunito','manrope','outfit','work-sans'], true)) $error = 'Please select fonts from the available choices.';
    elseif (file_put_contents($file, json_encode($updated, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX) === false) $error = 'Could not save site settings.';
    else { $settings = $updated; $notice = 'Site identity updated successfully.'; }
  }
}
function sh($value){return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Site Identity · Content Studio</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:wght@400;500;600&family=Cormorant+Garamond:wght@400;500;600&family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600&family=Libre+Caslon+Display&family=Lora:wght@400;500;600&family=Manrope:wght@400;500;600&family=Marcellus&family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&family=Nunito+Sans:wght@400;500;600&family=Outfit:wght@400;500;600&family=Playfair+Display:wght@400;500;600&family=Source+Sans+3:wght@400;500;600&family=Space+Grotesk:wght@400;500;600&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/admin.css"><link rel="stylesheet" href="assets/css/settings.css"><link rel="stylesheet" href="assets/css/theme-settings.css"><link rel="stylesheet" href="assets/css/theme-presets.css"><link rel="stylesheet" href="assets/css/logo.css"></head>
<body><header><a class="studio-brand" href="./"><span><?=sh($settings['initials'])?></span><b>Content Studio<small>Site identity</small></b></a><nav><a href="admin.php">Content archive</a><a href="contact-settings.php">Contact & socials</a><a href="./" target="_blank">View website ↗</a><a href="admin.php?logout=1">Sign out</a></nav></header><main class="settings-page"><div class="settings-copy"><p class="eyebrow">Site identity</p><h1>Change your header.</h1><p>Edit the three parts of the public website identity. Your changes appear immediately after saving.</p><div class="identity-preview" id="theme-preview" style="--preview-primary:<?=sh($settings['colorPrimary'])?>;--preview-accent:<?=sh($settings['colorAccent'])?>;--preview-bg:<?=sh($settings['colorBackground'])?>;--preview-surface:<?=sh($settings['colorSurface'])?>;--preview-ink:<?=sh($settings['colorInk'])?>"><span id="preview-logo"><?php if(!empty($settings['logo'])):?><img src="<?=sh($settings['logo'])?>" alt="Current logo"><?php else:?>AR<?php endif;?></span><div><strong id="preview-name"><?=sh($settings['name'])?></strong><small id="preview-tagline"><?=sh($settings['tagline'])?></small></div></div></div><section class="settings-panel"><?php if($notice):?><div class="message success"><?=sh($notice)?></div><?php endif;?><?php if($error):?><div class="message error"><?=sh($error)?></div><?php endif;?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=sh($_SESSION['csrf'])?>"><div class="logo-upload"><div class="logo-sample" id="logo-sample"><?php if(!empty($settings['logo'])):?><img src="<?=sh($settings['logo'])?>" alt="Current icon"><?php else:?>AR<?php endif;?></div><label>1. Picture / icon<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"><small>Square JPG, PNG, or WebP · up to 2 MB. A transparent PNG works best.</small><?php if(!empty($settings['logo'])):?><span class="check"><input type="checkbox" name="remove_logo"> Remove current icon</span><?php endif;?></label></div><label>2. Display name<input name="name" value="<?=sh($settings['name'])?>" required></label><label>3. Tagline<input name="tagline" value="<?=sh($settings['tagline'])?>" required><small>This appears in uppercase automatically.</small></label><fieldset class="theme-picker"><legend>Choose a complete theme</legend><p class="field-note">Every option includes a balanced color palette and a matched font pair. Select the mood you prefer.</p><div class="theme-grid"><?php foreach($themes as $key=>$theme):?><label class="theme-option"><input type="radio" name="theme" value="<?=sh($key)?>" <?=($settings['theme']??'heritage')===$key?'checked':''?> data-primary="<?=sh($theme['colorPrimary'])?>" data-accent="<?=sh($theme['colorAccent'])?>" data-background="<?=sh($theme['colorBackground'])?>" data-surface="<?=sh($theme['colorSurface'])?>" data-ink="<?=sh($theme['colorInk'])?>" data-heading="<?=sh($theme['headingFont'])?>" data-body="<?=sh($theme['bodyFont'])?>"><span class="theme-card"><span class="swatches"><i style="background:<?=sh($theme['colorPrimary'])?>"></i><i style="background:<?=sh($theme['colorAccent'])?>"></i><i style="background:<?=sh($theme['colorBackground'])?>"></i><i style="background:<?=sh($theme['colorSurface'])?>"></i><i style="background:<?=sh($theme['colorInk'])?>"></i></span><b><?=sh($theme['label'])?></b><small><?=sh($theme['description'])?></small><span class="theme-fonts"><span><?=sh(ucwords(str_replace('-',' ',$theme['headingFont'])))?></span><span><?=sh(ucwords(str_replace('-',' ',$theme['bodyFont'])))?></span></span></span></label><?php endforeach;?></div></fieldset><button>Apply selected theme & save</button></form></section></main><script>const fields={name:document.querySelector('[name=name]'),tagline:document.querySelector('[name=tagline]')};Object.entries(fields).forEach(([key,input])=>input.addEventListener('input',()=>{document.querySelector('#preview-'+key).textContent=input.value}));const logoInput=document.querySelector('[name=logo]');logoInput?.addEventListener('change',()=>{if(!logoInput.files[0])return;const url=URL.createObjectURL(logoInput.files[0]),previewImage=document.createElement('img'),sampleImage=document.createElement('img');previewImage.src=url;sampleImage.src=url;document.querySelector('#preview-logo').replaceChildren(previewImage);document.querySelector('#logo-sample').replaceChildren(sampleImage)});</script><script>const preview=document.querySelector('#theme-preview'),headingFonts={'libre-caslon':"'Libre Caslon Display',serif",cormorant:"'Cormorant Garamond',serif",playfair:"'Playfair Display',serif",lora:"'Lora',serif",fraunces:"'Fraunces',serif",marcellus:"'Marcellus',serif",newsreader:"'Newsreader',serif",'dm-serif':"'DM Serif Display',serif",bodoni:"'Bodoni Moda',serif",'space-grotesk':"'Space Grotesk',sans-serif"},bodyFonts={'dm-sans':"'DM Sans',sans-serif",inter:"'Inter',sans-serif",'source-sans':"'Source Sans 3',sans-serif",nunito:"'Nunito Sans',sans-serif",manrope:"'Manrope',sans-serif",outfit:"'Outfit',sans-serif",'work-sans':"'Work Sans',sans-serif"};document.querySelectorAll('[name=theme]').forEach(option=>option.addEventListener('change',()=>{if(!option.checked)return;preview.style.setProperty('--preview-primary',option.dataset.primary);preview.style.setProperty('--preview-accent',option.dataset.accent);preview.style.setProperty('--preview-bg',option.dataset.background);preview.style.setProperty('--preview-surface',option.dataset.surface);preview.style.setProperty('--preview-ink',option.dataset.ink);preview.style.setProperty('--preview-heading',headingFonts[option.dataset.heading]);preview.style.setProperty('--preview-body',bodyFonts[option.dataset.body])}));</script></body></html>
