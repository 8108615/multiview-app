<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StreamProxyController extends Controller
{
    public function proxy(Request $request, $path = 'index.m3u8')
    {
        // URL base de tu servidor de origen (sin el archivo final)
        $baseUrl = 'http://190.181.18.82:4111/play/a006/';
        $targetUrl = $baseUrl . $path;

        // Petición desde el backend de Laravel hacia la IP de origen
        $response = Http::withoutVerifying()->get($targetUrl);

        if ($response->failed()) {
            return response('No se pudo conectar al stream', 404);
        }

        $contentType = $response->header('Content-Type') ?? 'application/octet-stream';
        $content = $response->body();

        // Si es el archivo principal de listas (.m3u8), ajustamos los tipos MIME y las rutas internas
        if (str_ends_with($path, '.m3u8')) {
            $contentType = 'application/vnd.apple.mpegurl';
            
            // Si el origen devuelve URLs absolutas con http://, las reescribimos para que apunten a nuestro proxy
            $content = str_replace(
                'http://190.181.18.82:4111/play/a006/', 
                url('/stream-proxy') . '/', 
                $content
            );
        }

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}