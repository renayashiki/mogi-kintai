<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠アプリ')</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/guest-common.css') }}">
    @yield('styles')
</head>

<body>
    @include('components.header-guest')
    <main class="main">
        @yield('content')
    </main>
</body>

</html>
