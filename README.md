# Fr. Alan Relliquette, MSC — Modern Content Archive

A lightweight, responsive publishing website for transliterations, language notes, essays, and illustrated archival content. It does not use WordPress or a database.

## Run locally

The site uses PHP 8+. With the included XAMPP installation:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8080 router.php
```

Open `http://localhost:8080`.

## Add or edit content

1. Open `http://localhost:8080/admin.php`.
2. Sign in with `WP_ADMIN_USER` and `WP_ADMIN_PASSWORD` from `.env`.
3. Choose **New entry**, select Article or Transliteration, add the text, and upload a cover picture and optional audio recording.
4. Click **Publish content**. The public archive updates immediately.

Publishing and settings controls are available only after an authenticated admin login. Public visitors do not see content-management links and cannot submit content changes.

Existing entries can be selected in the left sidebar and edited or deleted. Pictures may be JPG, PNG, WebP, or GIF up to 8 MB. Audio may be MP3, WAV, OGG, M4A, or WebM up to 30 MB.

Use **Site identity** in the Content Studio to upload the circular header icon, change the display name and tagline, and select from eleven professionally matched themes. The expanded collection includes classic archival styles and modern editorial, minimal, monochrome, and contemporary presets with curated font pairings.

Use **Contact & socials** to update the parish, address, email, and three phone entries, and to add optional Facebook, Instagram, YouTube, X, TikTok, or LinkedIn profiles. Empty email and social fields remain hidden from the public website.

## Content storage

- `data/content.json` — all published text and metadata
- `data/site.json` — editable header initials, display name, and tagline
- `uploads/` — pictures and audio uploaded through the content studio
- `assets/images/` — permanent design images

Back up `data/content.json` and `uploads/` to preserve all content. The text is plain JSON, so it remains portable and can also be edited directly when needed.

## Before publishing online

- Replace the placeholder password in `.env` with a strong unique password.
- Use HTTPS.
- Configure the web server to deny direct access to `.env` and `data/` (the included `router.php` already does this for the local PHP server).
- Give PHP write permission only to `data/content.json` and `uploads/`.


