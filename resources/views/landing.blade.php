<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Civil — planillas y mediciones para residentes de obra de ingeniería civil.">
    <title>Civil · Planillas para residentes de obra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #13222e;
            --steel: #1f4e79;
            --steel-deep: #0f3d68;
            --paper: #f2f5f8;
            --line: #d5dde5;
            --muted: #5a6b7a;
            --accent: #c56a17;
            --white: #ffffff;
            --hero-overlay: linear-gradient(105deg, rgba(19, 34, 46, .88) 0%, rgba(19, 34, 46, .62) 48%, rgba(19, 34, 46, .28) 100%);
            --font-display: "Syne", sans-serif;
            --font-body: "Figtree", sans-serif;
            --space: clamp(1.25rem, 3vw, 2.5rem);
            --max: 1120px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: var(--font-body);
            color: var(--ink);
            background: var(--paper);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }

        /* Splash de carga */
        .splash {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: end start;
            padding: 8vh 8vw;
            background:
                linear-gradient(160deg, rgba(15, 61, 104, .82), rgba(19, 34, 46, .9)),
                url("https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=1800&q=80") center / cover;
            color: var(--white);
            transition: opacity .6s ease, visibility .6s ease;
        }
        .splash.is-hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .splash-marca {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: .95;
            animation: rise .9s ease both;
        }
        .splash-linea {
            width: 0;
            height: 2px;
            margin-top: 1rem;
            background: var(--accent);
            animation: grow 1.1s .2s ease forwards;
        }

        /* Cabecera */
        .top {
            position: absolute;
            inset: 0 0 auto;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: min(100% - 2.5rem, var(--max));
            margin: 0 auto;
            padding: 1.25rem 0;
            color: var(--white);
        }
        .marca {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .top-link {
            font-size: .92rem;
            font-weight: 600;
            opacity: .9;
            border-bottom: 1px solid transparent;
        }
        .top-link:hover { border-color: currentColor; }

        /* Hero full-bleed */
        .hero {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            align-items: end;
            overflow: hidden;
            color: var(--white);
        }
        .hero-media {
            position: absolute;
            inset: 0;
            background:
                var(--hero-overlay),
                url("https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=2000&q=80") center / cover;
            transform: scale(1.06);
            animation: ken 18s ease-out forwards;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            width: min(100% - 2.5rem, var(--max));
            margin: 0 auto;
            padding: 0 0 clamp(3.5rem, 10vh, 6rem);
            max-width: 38rem;
        }
        .hero h1 {
            margin: 0 0 .9rem;
            font-family: var(--font-display);
            font-size: clamp(2.35rem, 6.2vw, 4.1rem);
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.02;
            animation: rise .8s .15s ease both;
        }
        .hero p {
            margin: 0 0 1.75rem;
            max-width: 32ch;
            font-size: clamp(1.05rem, 2vw, 1.2rem);
            color: rgba(255, 255, 255, .88);
            animation: rise .8s .28s ease both;
        }
        .cta-group {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem 1rem;
            animation: rise .8s .4s ease both;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 3rem;
            padding: .75rem 1.35rem;
            border: 0;
            border-radius: 4px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform .18s ease, background .18s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-principal {
            background: var(--accent);
            color: var(--white);
        }
        .btn-principal:hover { background: #a85612; }
        .btn-fantasma {
            background: transparent;
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, .55);
        }
        .btn-fantasma:hover { background: rgba(255, 255, 255, .08); }

        /* Beneficios */
        .beneficios {
            width: min(100% - 2.5rem, var(--max));
            margin: 0 auto;
            padding: clamp(3.5rem, 9vw, 6rem) 0;
        }
        .beneficios h2 {
            margin: 0 0 .5rem;
            font-family: var(--font-display);
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            letter-spacing: -.02em;
            color: var(--steel-deep);
        }
        .beneficios > p {
            margin: 0 0 2.25rem;
            color: var(--muted);
            max-width: 42ch;
        }
        .lista {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem 1.75rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .lista li {
            padding: 1.25rem 1.35rem 1.35rem;
            border-left: 3px solid var(--steel);
            background: var(--white);
            opacity: 0;
            transform: translateY(14px);
            transition: opacity .55s ease, transform .55s ease;
        }
        .lista li.is-visible {
            opacity: 1;
            transform: none;
        }
        .lista strong {
            display: block;
            margin-bottom: .35rem;
            font-family: var(--font-display);
            font-size: 1.05rem;
            color: var(--steel-deep);
        }
        .lista span {
            color: var(--muted);
            font-size: .98rem;
        }

        /* CTA + formulario */
        .cierre {
            background: var(--steel-deep);
            color: var(--white);
        }
        .cierre-inner {
            width: min(100% - 2.5rem, var(--max));
            margin: 0 auto;
            padding: clamp(3.25rem, 8vw, 5.5rem) 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(2rem, 5vw, 4rem);
            align-items: start;
        }
        .cierre h2 {
            margin: 0 0 .75rem;
            font-family: var(--font-display);
            font-size: clamp(1.7rem, 3.8vw, 2.4rem);
            letter-spacing: -.025em;
            line-height: 1.1;
        }
        .cierre .lead {
            margin: 0 0 1.5rem;
            color: rgba(255, 255, 255, .82);
            max-width: 34ch;
        }
        .formulario {
            display: grid;
            gap: .85rem;
            padding: 1.35rem;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .16);
        }
        .formulario label {
            display: grid;
            gap: .35rem;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .72);
        }
        .formulario input,
        .formulario select,
        .formulario textarea {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 4px;
            padding: .7rem .8rem;
            background: rgba(19, 34, 46, .35);
            color: var(--white);
            font: inherit;
        }
        .formulario textarea { min-height: 5.5rem; resize: vertical; }
        .formulario input:focus,
        .formulario select:focus,
        .formulario textarea:focus {
            outline: 2px solid var(--accent);
            outline-offset: 1px;
        }
        .formulario .btn-principal { width: 100%; margin-top: .25rem; }
        .alerta {
            margin: 0 0 .5rem;
            padding: .75rem .9rem;
            border-radius: 4px;
            background: rgba(197, 106, 23, .2);
            border: 1px solid rgba(197, 106, 23, .45);
            color: #ffd7a8;
            font-size: .95rem;
        }
        .alerta.ok {
            background: rgba(46, 125, 80, .22);
            border-color: rgba(120, 190, 140, .45);
            color: #d4f0dd;
        }
        .errores { margin: 0; padding-left: 1.1rem; color: #ffc9c9; font-size: .9rem; }

        footer {
            padding: 1.25rem 0 2rem;
            text-align: center;
            color: var(--muted);
            font-size: .88rem;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: none; }
        }
        @keyframes grow {
            from { width: 0; }
            to { width: 7rem; }
        }
        @keyframes ken {
            to { transform: scale(1); }
        }

        @media (max-width: 820px) {
            .lista { grid-template-columns: 1fr; }
            .cierre-inner { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
            .hero-media { transform: none; }
            .lista li { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    {{-- Splash: misma foto de obra, refuerza progreso mientras carga --}}
    <div class="splash" id="splash" aria-hidden="true">
        <div>
            <div class="splash-marca">Civil</div>
            <div class="splash-linea"></div>
        </div>
    </div>

    <header class="hero">
        <div class="hero-media" aria-hidden="true"></div>
        <nav class="top">
            <a class="marca" href="{{ route('landing') }}">Civil</a>
            <a class="btn btn-principal" href="{{ route('ingresar') }}">Ingresar a la plataforma</a>
        </nav>
        <div class="hero-content">
            <h1>Control de obra sin perder el ritmo del frente.</h1>
            <p>Planillas, mediciones y avance en un solo lugar. Hecho para el residente de obra.</p>
            <div class="cta-group">
                <a class="btn btn-principal" href="{{ route('ingresar') }}">Ingresar a la plataforma</a>
                <a class="btn btn-fantasma" href="#acceso">Solicitar acceso</a>
            </div>
        </div>
    </header>

    <section class="beneficios" aria-labelledby="titulo-beneficios">
        <h2 id="titulo-beneficios">Lo esencial, en campo y en oficina</h2>
        <p>Menos archivos sueltos. Más claridad para liquidar con respaldo.</p>
        <ul class="lista">
            <li>
                <strong>Mediciones con trazabilidad</strong>
                <span>Registre cantidades por rubro y conserve el respaldo de cada período.</span>
            </li>
            <li>
                <strong>Avance al día</strong>
                <span>Compare lo ejecutado con lo contratado sin cruzar hojas a mano.</span>
            </li>
            <li>
                <strong>Fiscalización lista</strong>
                <span>Comparta planilla, hojas y evidencias con un enlace. Sin cuentas extras.</span>
            </li>
            <li>
                <strong>Equipo alineado</strong>
                <span>Administrador y residente trabajan sobre la misma verdad de obra.</span>
            </li>
        </ul>
    </section>

    <section class="cierre" id="acceso" aria-labelledby="titulo-acceso">
        <div class="cierre-inner">
            <div>
                <h2 id="titulo-acceso">Empiece con su obra en Civil</h2>
                <p class="lead">Cuéntenos su rol y su empresa. Le respondemos con el acceso o una demo breve.</p>
                <a class="btn btn-principal" href="#formulario">Quiero acceso</a>
            </div>
            <form id="formulario" class="formulario" method="post" action="{{ route('landing.contacto') }}">
                @csrf
                @if (session('estado'))
                    <div class="alerta ok">{{ session('estado') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alerta">
                        <ul class="errores">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <label>Nombre
                    <input name="nombre" value="{{ old('nombre') }}" required autocomplete="name">
                </label>
                <label>Correo
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </label>
                <label>Empresa / contratista
                    <input name="empresa" value="{{ old('empresa') }}" required autocomplete="organization">
                </label>
                <label>Rol
                    <select name="rol" required>
                        <option value="">Seleccione</option>
                        <option value="residente" @selected(old('rol') === 'residente')>Residente de obra</option>
                        <option value="contratista" @selected(old('rol') === 'contratista')>Contratista</option>
                        <option value="administrador" @selected(old('rol') === 'administrador')>Administración de obra</option>
                        <option value="otro" @selected(old('rol') === 'otro')>Otro</option>
                    </select>
                </label>
                <label>Mensaje <span style="text-transform:none;letter-spacing:0;opacity:.7">(opcional)</span>
                    <textarea name="mensaje" maxlength="500">{{ old('mensaje') }}</textarea>
                </label>
                <button class="btn btn-principal" type="submit">Enviar solicitud</button>
            </form>
        </div>
    </section>

    <footer>Civil · Planillas de liquidación de obra</footer>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => document.getElementById('splash')?.classList.add('is-hidden'), 700);
        });

        const observar = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('is-visible');
                    observar.unobserve(entrada.target);
                }
            });
        }, { threshold: 0.2 });

        document.querySelectorAll('.lista li').forEach((item, indice) => {
            item.style.transitionDelay = `${indice * 90}ms`;
            observar.observe(item);
        });
    </script>
</body>
</html>
