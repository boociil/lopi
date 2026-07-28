<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\OneTap_Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OneTap_Controller extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $pages = OneTap_Page::query()
            ->where('pembuat_username', $user->username)
            ->with([
                'buttons' => fn ($query) => $query->orderBy('urutan'),
            ])
            ->latest('updated_at')
            ->get();

        $trash = OneTap_Page::onlyTrashed()
            ->where('pembuat_username', $user->username)
            ->with([
                'buttons' => fn ($query) => $query->orderBy('urutan'),
            ])
            ->latest('deleted_at')
            ->get();

        return Inertia::render('onetap', [
            'data' => $pages,
            'trash' => $trash,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $this->validatePayload($request);

        try {
            $page = DB::transaction(function () use ($validated, $user) {
                $page = OneTap_Page::create([
                    'id' => (string) Str::uuid(),
                    'pembuat_username' => $user->username,
                    'satuan_kerja_tahun_id' => data_get(
                        $user,
                        'satuan_kerja_tahun_id'
                    ),
                    'judul' => trim($validated['judul']),
                    'slug' => Str::slug($validated['slug']),
                    'deskripsi' => $this->nullableTrim(
                        $validated['deskripsi'] ?? null
                    ),
                    'akses' => $validated['akses'],
                    'tema' => $validated['tema'],
                    'foto_url' => $this->nullableTrim(
                        $validated['foto_url'] ?? null
                    ),
                    'warna_latar' => $this->nullableTrim(
                        $validated['warna_latar'] ?? null
                    ),
                ]);

                $this->syncButtons(
                    $page,
                    $validated['buttons'] ?? []
                );

                return $page->load([
                    'buttons' => fn ($query) => $query->orderBy('urutan'),
                ]);
            });

            return response()->json([
                'message' => 'OneTap berhasil ditambahkan.',
                'data' => $page,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'OneTap gagal ditambahkan.',
            ], 500);
        }
    }

    public function update(
        Request $request,
        string $id
    ): JsonResponse {
        $page = $this->getOwnedPage($request, $id);
        $validated = $this->validatePayload($request, $page);

        try {
            $updatedPage = DB::transaction(function () use (
                $page,
                $validated
            ) {
                $page->update([
                    'judul' => trim($validated['judul']),
                    'slug' => Str::slug($validated['slug']),
                    'deskripsi' => $this->nullableTrim(
                        $validated['deskripsi'] ?? null
                    ),
                    'akses' => $validated['akses'],
                    'tema' => $validated['tema'],
                    'foto_url' => $this->nullableTrim(
                        $validated['foto_url'] ?? null
                    ),
                    'warna_latar' => $this->nullableTrim(
                        $validated['warna_latar'] ?? null
                    ),
                ]);

                $this->syncButtons(
                    $page,
                    $validated['buttons'] ?? []
                );

                return $page->fresh()->load([
                    'buttons' => fn ($query) => $query->orderBy('urutan'),
                ]);
            });

            return response()->json([
                'message' => 'OneTap berhasil diperbarui.',
                'data' => $updatedPage,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'OneTap gagal diperbarui.',
            ], 500);
        }
    }

    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $page = $this->getOwnedPage($request, $id);

        try {
            $page->delete();

            return response()->json([
                'message' => 'OneTap dipindahkan ke sampah.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'OneTap gagal dipindahkan ke sampah.',
            ], 500);
        }
    }

    public function restore(
        Request $request,
        string $id
    ): JsonResponse {
        $page = $this->getOwnedTrashedPage($request, $id);

        try {
            $page->restore();

            $page = $page->fresh()->load([
                'buttons' => fn ($query) => $query->orderBy('urutan'),
            ]);

            return response()->json([
                'message' => 'OneTap berhasil dipulihkan.',
                'data' => $page,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'OneTap gagal dipulihkan.',
            ], 500);
        }
    }

    public function forceDelete(
        Request $request,
        string $id
    ): JsonResponse {
        $page = $this->getOwnedTrashedPage($request, $id);

        try {
            DB::transaction(function () use ($page): void {
                $page->buttons()->delete();
                $page->forceDelete();
            });

            return response()->json([
                'message' => 'OneTap dihapus permanen.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'OneTap gagal dihapus permanen.',
            ], 500);
        }
    }

    public function publicShow(
        Request $request,
        string $slug
    ): View|RedirectResponse {
        $page = OneTap_Page::query()
            ->where('slug', $slug)
            ->with([
                'buttons' => fn ($query) => $query
                    ->where('aktif', true)
                    ->orderBy('urutan'),
            ])
            ->firstOrFail();

        if ($page->akses === 'private' && !$request->user()) {
            return redirect()->guest(route('login'));
        }

        return view('onetap', [
            'page' => $page,
        ]);
    }

    private function validatePayload(
        Request $request,
        ?OneTap_Page $page = null
    ): array {
        $uniqueSlug = Rule::unique(
            'siakip2_one_tap_pages',
            'slug'
        );

        if ($page !== null) {
            $uniqueSlug->ignore($page->id, 'id');
        }

        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                $uniqueSlug,
            ],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'akses' => [
                'required',
                Rule::in(['public', 'private']),
            ],
            'tema' => [
                'required',
                Rule::in(['orange', 'blue', 'green']),
            ],
            'foto_url' => ['nullable', 'string', 'max:2048'],
            'warna_latar' => ['nullable', 'string', 'max:255'],

            'buttons' => ['nullable', 'array', 'max:100'],
            'buttons.*.id' => ['nullable', 'string', 'max:36'],
            'buttons.*.tipe' => [
                'required',
                Rule::in(['link', 'divider']),
            ],
            'buttons.*.judul' => [
                'nullable',
                'string',
                'max:255',
            ],
            'buttons.*.url' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'buttons.*.ikon' => [
                'nullable',
                'string',
                'max:100',
            ],
            'buttons.*.warna' => [
                'nullable',
                'string',
                'max:255',
            ],
            'buttons.*.aktif' => [
                'nullable',
                'boolean',
            ],
            'buttons.*.urutan' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);
    }

    private function syncButtons(
        OneTap_Page $page,
        array $buttons
    ): void {
        $submittedIds = collect($buttons)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (count($submittedIds) > 0) {
            $page->buttons()
                ->whereNotIn('id', $submittedIds)
                ->delete();
        } else {
            $page->buttons()->delete();
        }

        foreach ($buttons as $index => $button) {
            $type = $button['tipe'] ?? 'link';

            $payload = [
                'tipe' => $type,
                'judul' => trim($button['judul'] ?? ''),
                'url' => $type === 'divider'
                    ? null
                    : $this->nullableTrim($button['url'] ?? null),
                'ikon' => $type === 'divider'
                    ? null
                    : $this->nullableTrim($button['ikon'] ?? null),
                'warna' => $type === 'divider'
                    ? null
                    : $this->nullableTrim($button['warna'] ?? null),
                'aktif' => $type === 'divider'
                    ? true
                    : (bool) ($button['aktif'] ?? true),
                'urutan' => $index + 1,
            ];

            if (!empty($button['id'])) {
                $existing = $page->buttons()
                    ->where('id', $button['id'])
                    ->first();

                if ($existing !== null) {
                    $existing->update($payload);
                    continue;
                }
            }

            $page->buttons()->create([
                'id' => (string) Str::uuid(),
                ...$payload,
            ]);
        }
    }

    private function getOwnedPage(
        Request $request,
        string $id
    ): OneTap_Page {
        $user = $request->user();
        abort_unless($user, 401);

        return OneTap_Page::query()
            ->where('id', $id)
            ->where('pembuat_username', $user->username)
            ->firstOrFail();
    }

    private function getOwnedTrashedPage(
        Request $request,
        string $id
    ): OneTap_Page {
        $user = $request->user();
        abort_unless($user, 401);

        return OneTap_Page::onlyTrashed()
            ->where('id', $id)
            ->where('pembuat_username', $user->username)
            ->firstOrFail();
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
