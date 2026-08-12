<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $messages = [
            403 => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
            404 => 'Data yang dituju tidak ditemukan atau sudah dihapus.',
            409 => 'Tindakan bentrok dengan perubahan lain. Muat ulang lalu coba lagi.',
            419 => 'Sesi Anda kedaluwarsa. Muat ulang halaman lalu coba lagi.',
            429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
            500 => 'Terjadi kesalahan pada server. Tindakan tidak tersimpan.',
            503 => 'Layanan sedang tidak tersedia. Coba lagi beberapa saat.',
        ];

        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) use ($messages) {
            $status = $response->getStatusCode();
            $message = $messages[$status] ?? null;
            if (! $message || ! $request->inertia() || $request->isMethodSafe()) {
                return $response;
            }
            if ($status >= 500 && config('app.debug')) {
                return $response;
            }

            return back(303)->with('error', $message);
        });
    })->create();
