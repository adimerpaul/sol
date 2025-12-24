<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class PeliculaController extends Controller
{
    private function tmdbGet(string $path, array $params = [])
    {
        $base = rtrim(env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'), '/');
        $lang = env('TMDB_LANGUAGE', 'es-MX');
//        TMDB_API_KEY="eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJlY2NmNGRhNzg5MzIyNjlkZjA2NTQ3MGFmMWI4YzZkOSIsIm5iZiI6MTY5Nzk3MDgwMC43MzIsInN1YiI6IjY1MzRmYTcwMmIyMTA4MDBlMjNjYTUyNiIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.o5edMhhpOPtVQS91djx3jKLVvws2rg0x2aZBuacvG2Y"
//TMDB_BASE_URL=https://api.themoviedb.org/3
//TMDB_LANGUAGE=es-MX
        $url = $base . $path;

        $query = array_merge($params, [
            'language' => $lang,
        ]);

        try {
            $response = Http::withToken(env('TMDB_API_KEY'))
                ->get($url, $query);
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conectar con TMDB',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    // ===========================
    // CRUD BD
    // ===========================
    public function index(Request $request)
    {
        $q = Pelicula::query();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $q->where(function ($qq) use ($s) {
                $qq->where('title', 'like', "%$s%")
                    ->orWhere('original_title', 'like', "%$s%")
                    ->orWhere('tmdb_id', 'like', "%$s%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        return $q->orderByDesc('id')->get();
    }

    public function show(Pelicula $pelicula)
    {
        return $pelicula;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tmdb_id' => ['required', 'integer', 'min:1', 'unique:peliculas,tmdb_id'],
            'title' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'trailer_url' => ['nullable', 'string'],
            'tmdb_data' => ['nullable', 'array'],
            'tmdb_videos' => ['nullable', 'array'],
        ]);

        $data['user_id'] = $request->user()?->id;

        return Pelicula::create($data);
    }

    public function update(Request $request, Pelicula $pelicula)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string'],
            'original_title' => ['nullable', 'string'],
            'original_language' => ['nullable', 'string'],
            'release_date' => ['nullable', 'date'],
            'poster_path' => ['nullable', 'string'],
            'backdrop_path' => ['nullable', 'string'],
            'vote_average' => ['nullable', 'numeric'],
            'vote_count' => ['nullable', 'integer'],
            'popularity' => ['nullable', 'numeric'],
            'status' => ['required', 'string'],
            'trailer_url' => ['nullable', 'string'],
            'tmdb_data' => ['nullable', 'array'],
            'tmdb_videos' => ['nullable', 'array'],
        ]);

        $pelicula->update($data);
        return $pelicula->fresh();
    }

    public function destroy(Pelicula $pelicula)
    {
        $pelicula->delete();
        return response()->json(['message' => 'Película eliminada']);
    }

    // ===========================
    // TMDB PROXY
    // ===========================
    public function tmdbSearch(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'min:1']
        ]);

        $res = $this->tmdbGet('/search/movie', [
            'query' => $request->get('query'),
        ]);

        // si tmdbGet devolvió response() error
        if ($res instanceof \Illuminate\Http\JsonResponse) return $res;

        if (!$res->ok()) {
            return response()->json([
                'message' => 'Error TMDB',
                'detail' => $res->body()
            ], 400);
        }

        return $res->json();
    }

    public function tmdbDetail($tmdbId)
    {
        $res = $this->tmdbGet("/movie/{$tmdbId}");

        if ($res instanceof \Illuminate\Http\JsonResponse) return $res;

        if (!$res->ok()) {
            return response()->json(['message' => 'No se pudo obtener detalle', 'detail' => $res->body()], 400);
        }

        return $res->json();
    }

    public function tmdbVideos($tmdbId)
    {
        $res = $this->tmdbGet("/movie/{$tmdbId}/videos");

        if ($res instanceof \Illuminate\Http\JsonResponse) return $res;

        if (!$res->ok()) {
            return response()->json(['message' => 'No se pudo obtener videos', 'detail' => $res->body()], 400);
        }

        return $res->json();
    }

    // ===========================
    // Guardar desde TMDB (upsert)
    // ===========================
    public function storeFromTmdb(Request $request)
    {
        $request->validate([
            'tmdb_id' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'string']
        ]);

        $tmdbId = (int) $request->tmdb_id;

        $pelicula = Pelicula::withTrashed()->where('tmdb_id', $tmdbId)->first();

        $detailRes = $this->tmdbGet("/movie/{$tmdbId}");
        if ($detailRes instanceof \Illuminate\Http\JsonResponse) return $detailRes;
        if (!$detailRes->ok()) return response()->json(['message' => 'TMDB detalle falló', 'detail' => $detailRes->body()], 400);

//        $videosRes = $this->tmdbGet("/movie/{$tmdbId}/videos");
//        if ($videosRes instanceof \Illuminate\Http\JsonResponse) return $videosRes;
//        if (!$videosRes->ok()) return response()->json(['message' => 'TMDB videos falló', 'detail' => $videosRes->body()], 400);
//
        $detail = $detailRes->json();
//        $videos = $videosRes->json();
//
//        // elegir trailer "mejor"
//        $trailerUrl = null;
//        $results = $videos['results'] ?? [];
//
//        $best = collect($results)
//            ->filter(fn($v) => ($v['site'] ?? '') === 'YouTube')
//            ->sortByDesc(fn($v) => (int)(($v['official'] ?? false) ? 1 : 0))
//            ->values();
//
//        $prefer = $best->first(fn($v) => ($v['iso_3166_1'] ?? '') === 'MX' && ($v['type'] ?? '') === 'Trailer')
//            ?? $best->first(fn($v) => ($v['type'] ?? '') === 'Trailer')
//            ?? $best->first();

//        trailer_key
//
//        if ($prefer && !empty($prefer['key'])) {
//            $trailerUrl = 'https://www.youtube.com/watch?v=' . $prefer['key'];
//        }
        $trailer_key = $request->get('trailer_key', null);
        $trailerUrl = null;
        if ($trailer_key) {
            $trailerUrl = 'https://www.youtube.com/watch?v=' . $trailer_key;
        }
        error_log($trailerUrl);

        $payload = [
            'tmdb_id' => $tmdbId,

            // ✅ usa override si viene, si no usa TMDB
            'title' => $request->filled('title') ? $request->title : ($detail['title'] ?? null),
            'release_date' => $request->filled('release_date') ? $request->release_date : ($detail['release_date'] ?? null),
            'poster_path' => $request->filled('poster_path') ? $request->poster_path : ($detail['poster_path'] ?? null),
            'backdrop_path' => $request->filled('backdrop_path') ? $request->backdrop_path : ($detail['backdrop_path'] ?? null),

            'original_title' => $detail['original_title'] ?? null,
            'original_language' => $detail['original_language'] ?? null,
            'vote_average' => $detail['vote_average'] ?? 0,
            'vote_count' => $detail['vote_count'] ?? 0,
            'popularity' => $detail['popularity'] ?? 0,
            'tmdb_data' => $detail,
            'trailer_url' => $trailerUrl,
            'status' => $request->get('status', 'No Publicado'),
            'user_id' => $request->user()?->id,
        ];

        if ($pelicula) {
            if ($pelicula->trashed()) $pelicula->restore();
            $pelicula->update($payload);
            return $pelicula->fresh();
        }

        return Pelicula::create($payload);
    }
}
