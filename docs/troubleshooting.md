# Troubleshooting

## Undefined variable $entry (show view)
- Symptom: ErrorException at `resources/views/livewire/journal-entries/show.blade.php` complaining about `$entry`.
- Cause: Inline PHP computed `$replacement` before Volt bound `$entry` in some render paths.
- Fix: Compute `$replacement` in the component `with()` and remove inline PHP.
  - File: `resources/views/livewire/journal-entries/show.blade.php`.

## Cache & Recompile
- Clear and recompile views/routes/config after template/component changes:
```powershell
php artisan view:clear
php artisan cache:clear
php artisan route:clear
```
- Restart the server:
```powershell
php artisan serve
```

## Livewire Volt command
- `php artisan livewire:volt:cache` may not exist for your version.
- Use standard Laravel cache clear commands above.

## PDF Export
- If Pandoc is installed, export the main spec to PDF:
```powershell
pandoc docs/journal-entry-system-full-spec.md -o docs/journal-entry-system-full-spec.pdf
```
- If Pandoc errors, ensure it’s installed and on PATH.
