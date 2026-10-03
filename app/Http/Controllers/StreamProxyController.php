<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamProxyController extends Controller
{
    public function proxy(Request $request)
    {
        $targetUrl = $request->query('url');

        if (!$targetUrl) {
            return response('URL de origen no proporcionada', 400);
        }

        $isPlaylist = str_ends_with($targetUrl, '.m3u8') || str_contains($targetUrl, '.m3u8?');

        if ($isPlaylist) {
            $response = Http::withoutVerifying()->timeout(5)->get($targetUrl);

            if ($response->failed()) {
                return response('No se pudo conectar al stream de origen', 404);
            }

            $content = $response->body();
            $baseUrl = rtrim(substr($targetUrl, 0, strrpos($targetUrl, '/') + 1), '/');

            $lines = explode("\n", $content);
            $processedLines = array_map(function ($line) use ($baseUrl) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    return $line;
                }

                $absoluteSegmentUrl = str_starts_with($line, 'http') ? $line : $baseUrl . '/' . ltrim($line, '/');
                return url('/stream-proxy?url=' . urlencode($absoluteSegmentUrl));
            }, $lines);

            $finalContent = implode("\n", $processedLines);

            return response($finalContent, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        // Para los segmentos de video (.ts), usamos fopen estándar que es 100% compatible con Wasmer
        return new StreamedResponse(function () use ($targetUrl) {
            $stream = @fopen($targetUrl, 'r');
            if ($stream) {
                while (!feof($stream)) {
                    $chunk = fread($stream, 1024 * 8);
                    if ($chunk === false) break;
                    echo $chunk;
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
