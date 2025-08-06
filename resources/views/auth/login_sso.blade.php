<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - SiAKIP</title>

    <link id="favicon" rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">

    <style nonce="{{ csp_nonce() }}">
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-color-theme-50">
    <main class="m-4 bg-white sm:container sm:mx-auto sm:max-w-lg sm:mt-10 shadow-md rounded">
        <form method="GET" action="{{ route('sso') }}">
                               @csrf
               <div class="shadow-2xl rounded-2xl px-8 pt-4 pb-4 mb-4 flex flex-col">
                   <img class="object-cover h-36 md:h-48 w-full rounded" src="public/img/depan.png">
                   <h3 class="text-md md:text-lg text-black text-center uppercase font-semibold m-5">Sistem Informasi Kinerja Pegawai</h3>
                   <div class="mb-3">
                        <label class="block text-black text-sm mb-1" for="tahun">
                            Tahun
                        </label>
                        <select
                            class="shadow appearance-none border border-red rounded w-full py-2 px-3 text-grey-darker text-sm" name="tahun_active"
                            required>
                            @foreach ($TAHUN_ARRAY as $item_tahun)
                                @if ($item_tahun == $TAHUN_DEFAULT)
                                    <option value="{{$item_tahun}}" selected>{{$item_tahun}}</option>
                                @else
                                    <option value="{{$item_tahun}}">{{$item_tahun}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                   <div class="mt-4 mb-4">
                      <button class="bg-color-theme-10a opacity-90 hover:opacity-100 text-white font-bold py-2 px-4 rounded w-full">Login SSO</button>
                   </div>
                   @if ($errors->any())
                        <div class="alert alert-danger gap-y-0.5 -mt-2">
                            @foreach ($errors->all() as $error)
                                <div class="bg-red-100 border border-red-400 text-red-700 px-2 py-1 rounded relative text-xs mb-0.5" role="alert">
                                    <span class="block sm:inline">{{ $error }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
               </div>
       </form>
           </main>
    {{-- <script src="{{ asset('js/app.js') }}" defer></script> --}}
</body>
    <script nonce="{{ csp_nonce() }}">
        localStorage.clear();
    </script>
</html>
