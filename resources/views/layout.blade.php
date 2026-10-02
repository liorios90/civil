<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Planilla de liquidación')</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: #f4f6f8; color: #1c2430; }
        header { background: #0f3d68; color: #fff; padding: 16px 24px; }
        header a { color: #fff; text-decoration: none; }
        header small { display: block; opacity: .8; margin-top: 4px; }
        main { padding: 24px; }
        .card { background: #fff; border: 1px solid #d9e0e7; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; }
        th, td { border: 1px solid #d5dde5; padding: 6px 8px; vertical-align: top; }
        th { background: #e8eef5; text-align: center; position: sticky; top: 0; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tr.frente td { background: #123a5f; color: #fff; font-weight: 600; }
        tr.subtotal td { background: #eef6ea; font-weight: 600; }
        tr.cierre td { background: #fff8e8; }
        .scroll { overflow: auto; max-height: calc(100vh - 220px); border: 1px solid #d5dde5; }
        .scroll.ajustada { overflow-x: clip; }
        .meta { display: grid; grid-template-columns: 180px 1fr; gap: 4px 12px; }
        .meta b { color: #4a5b6d; font-weight: 600; }
        a { color: #0b5cab; }
        button, .btn { background: #0f3d68; color: #fff; border: 0; border-radius: 6px; padding: 8px 12px; cursor: pointer; text-decoration: none; display: inline-block; }
        button.secundario, a.secundario { background: #e8eef5; color: #0f3d68; }
        input { border: 1px solid #c5d0db; border-radius: 6px; padding: 6px 8px; width: 100%; }
        .alerta { background: #fff4e5; border: 1px solid #f0d3a2; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }
        .buscar { margin: 0 0 10px; }
        .buscar input { max-width: 420px; }
        [data-sin-rubros] { margin: 0 0 10px; color: #4a5b6d; }
        .danger { background: transparent; color: #9b1c1c; padding: 0; }
        .btn-rojo { background: #9b1c1c; }
        .acciones { display: flex; gap: 8px; align-items: center; }
        textarea { border: 1px solid #c5d0db; border-radius: 6px; padding: 6px 8px; width: 100%; min-height: 70px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid label, .ancho { display: flex; flex-direction: column; gap: 4px; font-size: 13px; }
        .ancho { grid-column: 1 / -1; }
        .fila { display: flex; gap: 12px; align-items: center; justify-content: space-between; }
        .opciones { display: grid; gap: 8px; }
        .opcion { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #d5dde5; border-radius: 8px; text-decoration: none; color: inherit; background: #f8fafc; }
        .opcion:hover { border-color: #0f3d68; background: #eef4fb; }
        .opcion small { color: #4a5b6d; }
        .fila input { flex: 1; }
        .acciones { white-space: nowrap; }
        .acciones form { display: inline; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        h2 { font-size: 16px; margin: 0 0 8px; }
        table.hoja { font-size: 13px; }
        table.hoja th { background: #1f4e79; color: #fff; font-size: 12px; }
        table.hoja td { padding: 0; background: #fff; }
        table.hoja.vista { width: 100%; max-width: 100%; table-layout: fixed; font-size: 12px; }
        table.hoja.vista th { white-space: normal; line-height: 1.2; font-size: 11px; }
        table.hoja.vista td { padding: 4px; }
        table.hoja.vista td .btn-rojo { padding: 4px 8px; }
        table.hoja.vista td:nth-child(2) { white-space: normal; overflow-wrap: anywhere; }
        table.hoja input { border: 0; border-radius: 0; padding: 6px; background: transparent; min-width: 70px; }
        table.hoja input.u { width: 54px; min-width: 54px; text-align: center; }
        table.hoja input.n { text-align: right; width: 90px; }
        table.hoja.vista input { min-width: 0; width: 100%; max-width: 100%; padding: 4px; box-sizing: border-box; }
        table.hoja td.calc { padding: 6px 8px; background: #eef3ea; text-align: right; }
        table.hoja input[readonly] { background: #f4f1e8; }
        table.hoja select { border: 0; background: transparent; width: 110px; padding: 6px; }
        .barra { position: sticky; bottom: 0; background: #fff; padding: 10px 0; }
        .galeria { display: flex; flex-wrap: wrap; gap: 12px; }
        .galeria figure { margin: 0; background: #fff; border: 1px solid #d5dde5; padding: 8px; }
        .galeria img { width: 180px; height: 140px; object-fit: cover; display: block; }
        body.aplicacion main { padding: 12px; }
        body.aplicacion header .btn { float: none; display: inline-block; margin-top: 10px; }
        body.aplicacion .fila { flex-wrap: wrap; }
        body.aplicacion .ficha { background: #fff; border: 1px solid #d5dde5; border-radius: 10px; padding: 12px; margin: 0 0 12px; }
        body.aplicacion .ficha-titulo { display: flex; gap: 8px; align-items: flex-start; }
        body.aplicacion .ficha-titulo a, body.aplicacion .ficha-titulo strong { flex: 1; font-size: 16px; line-height: 1.3; }
        body.aplicacion .ficha-num { background: #1f4e79; color: #fff; border-radius: 6px; min-width: 28px; padding: 2px 8px; text-align: center; font-size: 13px; }
        body.aplicacion .ficha h3 { margin: 14px 0 8px; font-size: 13px; color: #1f4e79; }
        body.aplicacion .pares { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        body.aplicacion .pares label, body.aplicacion .ficha > label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #4a5b6d; }
        body.aplicacion .resultado { background: #eef3ea; border-radius: 6px; padding: 8px; font-size: 13px; }
        body.aplicacion .cierre-ficha { background: #fff8e8; }
        body.aplicacion .cierre-ficha p { display: flex; justify-content: space-between; margin: 6px 0; }
        body.aplicacion input[readonly] { background: #f4f1e8; }
        body.aplicacion .buscar input { max-width: none; }
        body.aplicacion .barra { display: flex; gap: 8px; }
        body.aplicacion .barra button { flex: 1; }
    </style>
</head>
<body @class(['aplicacion' => str_contains((string) request()->userAgent(), 'PlanillasApp')])>
<header>
    <a href="{{ route('inicio') }}">Planillas de liquidación de obra</a>
    <a class="btn" href="{{ route('contratos.create') }}" style="float:right">Nuevo contrato</a>
    <small>GAD del Distrito Metropolitano de Quito</small>
</header>
<main>
    @yield('contenido')
</main>
</body>
</html>
