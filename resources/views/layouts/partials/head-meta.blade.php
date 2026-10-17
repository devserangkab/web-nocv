{{-- Meta head bersama: favicon bundle, deskripsi, theme-color, robots, OG/Twitter --}}
<link rel="icon" type="image/png" href="{{ asset('favicon/favicon-32x32.png') }}" sizes="32x32">
<link rel="icon" type="image/png" href="{{ asset('favicon/favicon-16x16.png') }}" sizes="16x16">
<link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">

<meta name="description" content="NOC (Network Operation Center Virtual) — katalog aset, peta topologi, dan dokumentasi jaringan dalam satu panel.">
<meta name="theme-color" content="#ffffff">
<meta name="theme-color" content="#232527" media="(prefers-color-scheme: dark)">

{{-- Panel internal — tidak untuk mesin pencari --}}
<meta name="robots" content="noindex, nofollow">

<meta property="og:title" content="{{ $title ?? 'Dashboard | NOC' }}">
<meta property="og:site_name" content="NOC">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary">
