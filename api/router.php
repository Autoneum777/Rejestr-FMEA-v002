<?php

declare(strict_types=1);

use App\Database;
use App\Http;
use App\ImportControlPlans;
use App\ImportFmea;
use App\Register;

require __DIR__ . '/vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = Http::path();

if ($method === 'GET' && !str_starts_with($path, '/api')) {
    if (!serveFrontend($path)) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(503);
        echo "Brak zbudowanego interfejsu.\nUruchom npm run dev i otwórz http://127.0.0.1:5173\n";
        exit;
    }
}

$database = Database::open();
$database->init();
$register = new Register($database);

try {
    if ($method === 'POST' && $path === '/api/reminders') {
        try {
            (new App\Notifier($database))->reminders();
        } catch (Throwable $error) {
            // Kolejka poczty nie może zablokować interfejsu.
        }
        Http::json(['ok' => true]);
    }
    route($method, $path, $register);
    Http::json(['error' => 'Nie znaleziono adresu'], 404);
} catch (Throwable $error) {
    Http::json(['error' => $error->getMessage()], 500);
}

function route(string $method, string $path, Register $register): void
{
    $q = mb_strtolower(Http::query('q'));
    $status = Http::query('status');
    $aktywne = Http::query('aktywne', '1');

    if ($method === 'GET' && $path === '/api/fmea') {
        Http::json($register->listFmea($q, $status, $aktywne));
    }
    if ($method === 'POST' && $path === '/api/fmea') {
        Http::json($register->createFmea(Http::jsonBody()), 201);
    }
    if ($method === 'POST' && $path === '/api/fmea/wyczysc') {
        Http::json($register->clearFmea(Http::jsonBody()));
    }
    if ($method === 'GET' && preg_match('#^/api/fmea/(\d+)$#', $path, $match)) {
        Http::json($register->getFmea((int) $match[1]));
    }
    if ($method === 'PUT' && preg_match('#^/api/fmea/(\d+)$#', $path, $match)) {
        Http::json($register->updateFmea((int) $match[1], Http::jsonBody()));
    }
    if ($method === 'PUT' && preg_match('#^/api/fmea/(\d+)/aktywne$#', $path, $match)) {
        Http::json($register->toggleFmea((int) $match[1], Http::jsonBody()));
    }
    if ($method === 'DELETE' && preg_match('#^/api/fmea/(\d+)$#', $path, $match)) {
        Http::json($register->deleteFmea((int) $match[1], Http::jsonBody()));
    }
    if ($method === 'POST' && $path === '/api/fmea/osoba') {
        Http::json($register->assignResponsible('fmea', Http::jsonBody()));
    }
    if ($method === 'POST' && preg_match('#^/api/fmea/(\d+)/reviews$#', $path, $match)) {
        Http::json($register->addFmeaReview((int) $match[1], Http::jsonBody()), 201);
    }
    if ($method === 'GET' && $path === '/api/export') {
        $today = (new DateTimeImmutable())->format('Y-m-d');
        Http::download($register->exportFmea($q, $status, $aktywne), "Rejestr_FMEA_{$today}.xlsx");
    }
    if ($method === 'GET' && $path === '/api/settings') {
        Http::json(Database::open()->settings());
    }
    if ($method === 'PUT' && $path === '/api/settings') {
        Http::json(Database::open()->saveSettings(Http::jsonBody()));
    }
    if ($method === 'POST' && $path === '/api/import') {
        $target = Database::dataDir() . DIRECTORY_SEPARATOR . 'import_fmea.xlsx';
        if (Http::upload($target) === null) {
            Http::json(['error' => 'Wybierz plik Excel z dysku'], 400);
        }
        try {
            Http::json((new ImportFmea())->import($target));
        } catch (Throwable $error) {
            Http::json(['error' => $error->getMessage()], 500);
        }
    }
    if ($method === 'GET' && $path === '/api/cp') {
        Http::json($register->listCp($q, $status, $aktywne));
    }
    if ($method === 'POST' && $path === '/api/cp') {
        Http::json($register->createCp(Http::jsonBody()), 201);
    }
    if ($method === 'POST' && $path === '/api/cp/wyczysc') {
        Http::json($register->clearCp(Http::jsonBody()));
    }
    if ($method === 'POST' && $path === '/api/cp/osoba') {
        Http::json($register->assignResponsible('cp', Http::jsonBody()));
    }
    if ($method === 'GET' && $path === '/api/users') {
        Http::json($register->listUsers());
    }
    if ($method === 'POST' && $path === '/api/users') {
        Http::json($register->saveUser(null, Http::jsonBody()), 201);
    }
    if ($method === 'PUT' && preg_match('#^/api/users/(\d+)$#', $path, $match)) {
        Http::json($register->saveUser((int) $match[1], Http::jsonBody()));
    }
    if ($method === 'DELETE' && preg_match('#^/api/users/(\d+)$#', $path, $match)) {
        Http::json($register->deleteUser((int) $match[1]));
    }
    if ($method === 'POST' && $path === '/api/cp/import') {
        $target = Database::dataDir() . DIRECTORY_SEPARATOR . 'import_cp.xlsx';
        if (Http::upload($target) === null) {
            Http::json(['error' => 'Wybierz plik Excel z dysku'], 400);
        }
        try {
            Http::json((new ImportControlPlans())->import($target));
        } catch (Throwable $error) {
            Http::json(['error' => $error->getMessage()], 500);
        }
    }
    if ($method === 'GET' && $path === '/api/cp/export') {
        $today = (new DateTimeImmutable())->format('Y-m-d');
        Http::download($register->exportCp($q, $status, $aktywne), "Rejestr_planow_kontroli_{$today}.xlsx");
    }
    if ($method === 'GET' && preg_match('#^/api/cp/(\d+)$#', $path, $match)) {
        Http::json($register->getCp((int) $match[1]));
    }
    if ($method === 'PUT' && preg_match('#^/api/cp/(\d+)$#', $path, $match)) {
        Http::json($register->updateCp((int) $match[1], Http::jsonBody()));
    }
    if ($method === 'POST' && preg_match('#^/api/cp/(\d+)/potwierdz-bez-zmian$#', $path, $match)) {
        Http::json($register->confirmCpUnchanged((int) $match[1]));
    }
    if ($method === 'POST' && preg_match('#^/api/cp/(\d+)/reviews$#', $path, $match)) {
        Http::json($register->addCpReview((int) $match[1], Http::jsonBody()));
    }
}

function serveFrontend(string $path): bool
{
    $dist = realpath(dirname(__DIR__) . '/frontend/dist');
    if ($dist === false) {
        return false;
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    if (str_contains($relative, '..')) {
        Http::json(['error' => 'Nie znaleziono pliku'], 404);
    }
    if ($relative !== '') {
        $candidate = $dist . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);
        $prefix = rtrim(strtolower($dist), '\\/') . DIRECTORY_SEPARATOR;
        if (is_string($real) && is_file($real) && str_starts_with(strtolower($real), $prefix)) {
            $extension = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            $types = [
                'css' => 'text/css; charset=utf-8',
                'js' => 'text/javascript; charset=utf-8',
                'mjs' => 'text/javascript; charset=utf-8',
                'png' => 'image/png',
                'svg' => 'image/svg+xml',
                'html' => 'text/html; charset=utf-8',
                'ico' => 'image/x-icon',
                'json' => 'application/json; charset=utf-8',
                'map' => 'application/json; charset=utf-8',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'txt' => 'text/plain; charset=utf-8',
            ];
            Http::file($real, $types[$extension] ?? 'application/octet-stream');
        }
        if (pathinfo($relative, PATHINFO_EXTENSION) !== '') {
            Http::json(['error' => 'Nie znaleziono pliku'], 404);
        }
    }
    $index = $dist . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($index)) {
        return false;
    }
    Http::file($index, 'text/html; charset=utf-8');
}
