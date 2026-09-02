<?php
function defaultContentTypes(): array {
  return [
    ['key'=>'transliteration','label'=>'Transliteration'],
    ['key'=>'article','label'=>'Article / essay']
  ];
}

function normalizeContentTypes(array $settings): array {
  $types = $settings['contentTypes'] ?? defaultContentTypes();
  if (!is_array($types)) $types = defaultContentTypes();
  $normalized = [];
  foreach ($types as $type) {
    $key = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($type['key'] ?? '')));
    $label = trim((string)($type['label'] ?? ''));
    if ($key && $label && !isset($normalized[$key])) $normalized[$key] = ['key'=>$key,'label'=>$label];
  }
  foreach (array_reverse(defaultContentTypes()) as $default) {
    if (!isset($normalized[$default['key']])) $normalized = [$default['key']=>$default] + $normalized;
  }
  return array_values($normalized);
}

function contentTypeMap(array $types): array {
  $map = [];
  foreach ($types as $type) $map[$type['key']] = $type;
  return $map;
}

