<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Fiscalización')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: #f4f6f8; color: #1c2430; }
        header { background: #0f3d68; color: #fff; padding: 16px 20px; }
        header strong { display: block; font-size: 16px; }
        header small { display: block; opacity: .85; margin-top: 4px; }
        main { padding: 16px; max-width: 920px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #d9e0e7; border-radius: 8px; padding: 14px 16px; margin-bottom: 12px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 16px; margin: 0 0 8px; }
        a { color: #0b5cab; }
        .aviso { background: #e8eef5; color: #0f3d68; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; font-size: 13px; }
        .rubro { display: block; text-decoration: none; color: inherit; }
        .rubro:hover { border-color: #0f3d68; }
        .rubro small, .muted { color: #4a5b6d; }
        .cifras { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
        .cifras div { background: #f8fafc; border-radius: 6px; padding: 8px; }
        .cifras b { display: block; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #d5dde5; padding: 6px 8px; }
        th { background: #e8eef5; }
        td.n { text-align: right; }
        .galeria { display: flex; flex-wrap: wrap; gap: 10px; }
        .galeria img { width: 180px; height: 140px; object-fit: cover; border: 1px solid #d5dde5; border-radius: 6px; background: #fff; }
        @media (max-width: 640px) {
            .cifras { grid-template-columns: 1fr; }
            .galeria img { width: 100%; height: 200px; }
        }
    </style>
</head>
<body>
<header>
    <strong>@yield('entidad', 'Fiscalización')</strong>
    <small>Vista de solo lectura</small>
</header>
<main>
    <p class="aviso">Esta vista es para revisar. No se puede modificar la planilla desde aquí.</p>
    @yield('contenido')
</main>
</body>
</html>
