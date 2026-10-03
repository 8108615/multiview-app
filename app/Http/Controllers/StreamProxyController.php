<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamProxyController extends Controller
{
    public function proxy(Request $request)
    {
        // 1. Obtenemos la URL de origen que nos mandó el cliente y el resto del path interno del stream
        $targetUrl = $request->query('url');

        if (!$targetUrl) {
            return response('URL de origen no proporcionada', 400);
        }

        $isPlaylist = str_ends_with($targetUrl, '.m3u8') || str_contains($targetUrl, '.m3u8?');

        if ($isPlaylist) {
            $response = Http::withoutVerifying()->get($targetUrl);

            if ($response->failed()) {
                return response('No se pudo conectar al stream de origen', 404);
            }

            $content = $response->body();

            // Extraer la ruta base del stream actual (ej: http://190.181.18.82:4111/play/a006/)
            $baseUrl = rtrim(substr($targetUrl, 0, strrpos($targetUrl, '/') + 1), '/');

            // Reescribir las líneas del archivo .m3u8 para que los fragmentos (.ts) pasen también por nuestro proxy
            $lines = explode("\n", $content);
            $processedLines = array_map(function ($line) use ($baseUrl) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    return $line;
                }

                // Si la ruta del segmento es relativa, la convertimos en absoluta usando la base del origen
                $absoluteSegmentUrl = str_starts_with($line, 'http') ? $line : $baseUrl . '/' . ltrim($line, '/');

                // Retornamos la ruta apuntando a nuestro proxy con la URL del segmento codificada
                return url('/stream-proxy?url=' . urlencode($absoluteSegmentUrl));
            }, $lines);

            $finalContent = implode("\n", $processedLines);

            return response($finalContent, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        // 2. Para los segmentos de video binarios (.ts), usamos streaming por bloques dinámico
        return new StreamedResponse(function () use ($targetUrl) {
            $stream = fopen($targetUrl, 'r');
            if ($stream) {
                while (!feof($stream)) {
                    echo fread($stream, 1024 * 8);
                    flush();
                }
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'video/mp2t',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
