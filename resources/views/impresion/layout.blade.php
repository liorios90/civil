<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 12px; font-family: Arial, sans-serif; color: #000; font-size: 9px; }
        h1, h2, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 2px 3px; vertical-align: top; }
        th { background: #d9e2f0; text-align: center; font-size: 8px; }
        td.n { text-align: right; white-space: nowrap; }
        tr.frente td { background: #1f4e79; color: #fff; font-weight: 700; }
        tr.sub td { background: #e2efda; font-weight: 700; }
        tr.cierre td { background: #fff2cc; font-weight: 700; }
        .centro { text-align: center; }
        .cabecera { margin-bottom: 8px; }
        .firmas { display: flex; justify-content: space-between; margin-top: 28px; }
        .firmas div { width: 30%; text-align: center; border-top: 1px solid #000; padding-top: 4px; }
        .no-print { margin-bottom: 8px; }
        button { padding: 6px 10px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            @page { size: landscape; margin: 8mm; }
        }
        @yield('extra')
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Imprimir</button></p>
    @yield('contenido')
</body>
</html>
