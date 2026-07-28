<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->judul }}</title>
    <meta name="description" content="{{ $page->deskripsi }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, Arial, sans-serif;
            color: #fff;
            background: {{ $page->warna_latar ?: (
                $page->tema === 'blue'
                    ? 'linear-gradient(135deg, #00adef, #004aad)'
                    : (
                        $page->tema === 'green'
                            ? 'linear-gradient(135deg, #8cc63e, #00bf63)'
                            : 'linear-gradient(135deg, #febd26, #f79039)'
                    )
            ) }};
        }

        .wrapper {
            width: min(100%, 680px);
            margin: 0 auto;
            padding: 48px 20px;
        }

        .profile {
            margin-bottom: 28px;
            text-align: center;
        }

        .avatar {
            width: 104px;
            height: 104px;
            margin: 0 auto 16px;
            border: 4px solid rgba(255,255,255,.85);
            border-radius: 999px;
            object-fit: cover;
            box-shadow: 0 12px 30px rgba(0,0,0,.18);
        }

        .avatar-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.18);
        }

        .avatar-placeholder svg {
            width: 34px;
            height: 34px;
        }

        h1 {
            margin: 0;
            font-size: 30px;
            overflow-wrap: anywhere;
        }

        .description {
            max-width: 560px;
            margin: 10px auto 0;
            color: rgba(255,255,255,.9);
            line-height: 1.65;
            white-space: pre-line;
        }

        .links {
            display: grid;
            gap: 14px;
        }

        .link-button {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 58px;
            padding: 14px 18px;
            border-radius: 16px;
            color: #1f2937;
            text-decoration: none;
            font-weight: 700;
            background: rgba(255,255,255,.95);
            box-shadow: 0 8px 24px rgba(0,0,0,.14);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .link-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0,0,0,.18);
        }

        .link-icon {
            display: inline-flex;
            flex: 0 0 20px;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }

        .link-icon svg {
            width: 18px;
            height: 18px;
            stroke-width: 2;
        }

        .link-title {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .external-icon {
            display: inline-flex;
            flex: 0 0 16px;
            opacity: .6;
        }

        .external-icon svg {
            width: 14px;
            height: 14px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 2px;
            text-transform: uppercase;
            letter-spacing: .12em;
            font-size: 12px;
            font-weight: 800;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.45);
        }

        .divider.is-empty {
            gap: 0;
        }

        .divider.is-empty::after {
            display: none;
        }

        .footer {
            margin-top: 34px;
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,.72);
        }
    </style>
</head>
<body>
    @php
        /*
         * Nilai database disamakan dengan ICON_OPTIONS di onetap.tsx.
         * Nama di sebelah kanan adalah nama ikon Lucide untuk versi browser.
         */
        $iconMap = [
            'link' => 'link',
            'website' => 'globe',
            'instagram' => 'instagram',
            'facebook' => 'facebook',
            'youtube' => 'youtube',
            'tiktok' => 'music-2',
            'x' => 'twitter',
            'whatsapp' => 'message-circle',
            'telegram' => 'send',
            'email' => 'mail',
            'phone' => 'phone',
            'location' => 'map-pin',
            'document' => 'file-text',
            'download' => 'download',
            'building' => 'building-2',
        ];
    @endphp

    <main class="wrapper">
        <section class="profile">
            @if ($page->foto_url)
                <img
                    src="{{ $page->foto_url }}"
                    alt="{{ $page->judul }}"
                    class="avatar"
                >
            @else
                <div class="avatar avatar-placeholder">
                    <i data-lucide="link" aria-hidden="true"></i>
                </div>
            @endif

            <h1>{{ $page->judul }}</h1>

            @if ($page->deskripsi)
                <div class="description">{{ $page->deskripsi }}</div>
            @endif
        </section>

        <section class="links">
            @foreach ($page->buttons as $button)
                @if ($button->tipe === 'divider')
                    @php
                        $dividerTitle = trim((string) $button->judul);
                    @endphp

                    <div class="divider {{ $dividerTitle === '' ? 'is-empty' : '' }}">
                        @if ($dividerTitle !== '')
                            <span>{{ $dividerTitle }}</span>
                        @endif
                    </div>
                @else
                    @php
                        $storedIcon = trim((string) $button->ikon);
                        $lucideIcon = $iconMap[$storedIcon] ?? 'link';
                    @endphp

                    <a
                        href="{{ $button->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="link-button"
                        style="{{ $button->warna ? 'background: '.$button->warna.';' : '' }}"
                    >
                        <span class="link-icon" aria-hidden="true">
                            <i data-lucide="{{ $lucideIcon }}"></i>
                        </span>

                        <span class="link-title">{{ $button->judul }}</span>

                        <span class="external-icon" aria-hidden="true">
                            <i data-lucide="external-link"></i>
                        </span>
                    </a>
                @endif
            @endforeach
        </section>

        <footer class="footer">OneTap</footer>
    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
