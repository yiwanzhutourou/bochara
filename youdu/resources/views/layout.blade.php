<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? '有读书房' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta name="applicable-device" content="pc">
    <link rel="apple-touch-icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    @yield('meta')
    @section('styles')
        <link rel="stylesheet" href="{{ mix('/dist/lib/index.css') }}">
    @show
    @yield('css')
</head>
<body>
@yield('header')
<article class="main">
    @yield('content')
</article>
@section('scripts')
    <script src="{{ mix('/dist/lib/index.js') }}"></script>
@show
@yield('script')
</body>
</html>
