<!doctype html>
<title>@yield('title')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
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
<html>
    <body class="w-auto h-auto">
        <section class="bg-blue-500 relative z-10 py-[120px] w-auto h-auto">
            <div class="container mx-auto">
              <div class="-mx-4 flex">
                <div class="w-full px-4">
                  <div class="mx-auto max-w-[400px] text-center">
                    <h2 class="mb-2 text-[50px] font-bold leading-none text-white sm:text-[80px] md:text-[100px]">
                        @yield('code')
                    </h2>
                    <h4 class="mb-3 text-[22px] font-semibold leading-tight text-white">
                        @yield('message')
                    </h4>
                    <p class="mb-8 text-lg text-white">
                        @yield('message_1')
                    </p>
                    <a
                      href="{{ url('/') }}"
                      class="hover:text-blue-500 inline-block rounded-lg border border-white px-8 py-3 text-center text-base font-semibold text-white transition hover:bg-white">
                      Ke Beranda
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <div class="absolute top-0 left-0 -z-10 flex h-full w-full items-center justify-between space-x-5 md:space-x-8 lg:space-x-14">
              <div class="h-full w-1/3 bg-gradient-to-t from-[#FFFFFF14] to-[#C4C4C400]"></div>
              <div class="flex h-full w-1/3">
                <div class="h-full w-1/2 bg-gradient-to-b from-[#FFFFFF14] to-[#C4C4C400]"></div>
                <div class="h-full w-1/2 bg-gradient-to-t from-[#FFFFFF14] to-[#C4C4C400]"></div>
              </div>
              <div class="h-full w-1/3 bg-gradient-to-b from-[#FFFFFF14] to-[#C4C4C400]"></div>
            </div>
          </section>
    </body>
</html>
