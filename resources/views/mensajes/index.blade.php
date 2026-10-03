@extends('layout')

@section('titulo', 'Mensajes')

@section('contenido')
    <style>
        body.pagina-mensajes { height: 100vh; height: 100dvh; overflow: hidden; display: flex; flex-direction: column; }
        body.pagina-mensajes main { flex: 1; min-height: 0; display: flex; flex-direction: column; padding: 12px 16px; overflow: hidden; }
        .chat { display: grid; grid-template-columns: 320px minmax(0, 1fr); flex: 1; min-height: 0; background: #fff; border: 1px solid #d5dde5; border-radius: 8px; overflow: hidden; }
        .chat-lista { background: #fff; border-right: 1px solid #e4e8ec; overflow: auto; min-height: 0; }
        .chat-lista h1 { font-size: 18px; margin: 0; padding: 14px 16px; background: #f0f2f5; }
        .chat-lista a.persona { display: flex; gap: 10px; align-items: center; padding: 12px 14px; text-decoration: none; color: inherit; border-bottom: 1px solid #f0f2f5; }
        .chat-lista a.persona.activo, .chat-lista a.persona:hover { background: #f0f2f5; }
        .chat .avatar { width: 42px; height: 42px; border-radius: 50%; background: #0f3d68; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex: none; }
        .chat-lista .dato { min-width: 0; flex: 1; }
        .chat-lista .dato strong, .chat-lista .dato small { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-lista .dato small { color: #667781; font-size: 13px; margin-top: 2px; }
        .chat-lista .dato small.contratos { color: #0f3d68; white-space: normal; }
        .chat-lista .lado { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex: none; }
        .chat-lista .lado time { color: #667781; font-size: 11px; }
        .chat-vacio { padding: 16px; color: #667781; }
        .chat-lado { display: flex; flex-direction: column; background: #efeae2; min-width: 0; min-height: 0; }
        .chat-cabeza { display: flex; gap: 10px; align-items: center; background: #f0f2f5; padding: 10px 14px; border-bottom: 1px solid #e4e8ec; }
        .chat-cabeza strong { display: block; }
        .chat-cabeza small { color: #667781; }
        .chat-cabeza .volver { display: none; color: #0f3d68; text-decoration: none; font-size: 20px; line-height: 1; }
        .hilo { flex: 1; min-height: 0; overflow: auto; padding: 16px 12px; display: flex; flex-direction: column; gap: 6px; }
        .burbuja { max-width: min(78%, 520px); background: #fff; border-radius: 8px; padding: 6px 8px 4px; align-self: flex-start; box-shadow: 0 1px 0.5px rgba(0, 0, 0, .13); }
        .burbuja.mia { align-self: flex-end; background: #d9fdd3; }
        .burbuja p { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; }
        .burbuja time { display: block; text-align: right; color: #667781; font-size: 11px; margin-top: 2px; }
        .chat-forma { display: flex; gap: 8px; align-items: flex-end; padding: 10px; background: #f0f2f5; flex: none; }
        .chat-forma textarea { flex: 1; min-height: 42px; max-height: 120px; resize: none; margin: 0; border-radius: 8px; padding: 10px 12px; background: #fff; }
        .chat-forma button { background: #00a884; border-radius: 50%; width: 42px; height: 42px; padding: 0; font-size: 18px; }
        .chat-espera { flex: 1; display: flex; align-items: center; justify-content: center; color: #667781; padding: 24px; text-align: center; }
        .chat-error { color: #9b1c1c; font-size: 12px; margin: 0 10px 8px; }
        .chat-regreso { margin: 0 0 8px; flex: none; }
        @media (max-width: 800px) {
            .chat { grid-template-columns: 1fr; }
            .chat.abierto .chat-lista { display: none; }
            .chat:not(.abierto) .chat-lado { display: none; }
            .chat-cabeza .volver { display: inline; }
        }
    </style>

    <p class="chat-regreso"><a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
    <div class="chat {{ $abierto ? 'abierto' : '' }}"
        data-chat
        data-abierto="{{ $abierto->id ?? '' }}"
        data-ultimo="{{ $mensajes->last()->id ?? 0 }}"
        data-novedades="{{ route('mensajes.novedades') }}"
        data-enviar="{{ $abierto ? route('mensajes.enviar', $abierto) : '' }}">
        <aside class="chat-lista">
            @php($sinLeerLista = $filas->sum('sin_leer'))
            <h1>Mensajes <span class="aviso" id="avisos-lista" @if ($sinLeerLista < 1) hidden @endif>@if ($sinLeerLista > 0){{ $sinLeerLista > 99 ? '99+' : $sinLeerLista }}@endif</span></h1>
            @forelse ($filas as $fila)
                @php($contacto = $fila['contacto'])
                @php($ultimo = $fila['ultimo'])
                <a class="persona {{ $abierto && $abierto->id === $contacto->id ? 'activo' : '' }}" href="{{ route('mensajes.show', $contacto) }}" data-contacto="{{ $contacto->id }}">
                    <span class="avatar">{{ mb_strtoupper(mb_substr($contacto->name, 0, 1)) }}</span>
                    <span class="dato">
                        <strong>{{ $contacto->name }}</strong>
                        @if ($contacto->esUsuario())
                            <small class="contratos">{{ $contacto->contratos->pluck('codigo_proceso')->filter()->join(', ') ?: 'Sin contratos' }}</small>
                        @endif
                        <small data-preview>{{ $ultimo ? (($ultimo->de_id === auth()->id() ? 'Tú: ' : '').preg_replace('/\s+/', ' ', $ultimo->texto)) : ($contacto->activo ? '' : 'Inactivo') }}</small>
                    </span>
                    <span class="lado">
                        <time data-hora>{{ $ultimo ? $ultimo->created_at->timezone('America/Guayaquil')->format($ultimo->created_at->timezone('America/Guayaquil')->isSameDay(now('America/Guayaquil')) ? 'H:i' : 'd/m H:i') : '' }}</time>
                        <span class="aviso" data-avisos @if ($fila['sin_leer'] < 1) hidden @endif>{{ $fila['sin_leer'] > 99 ? '99+' : $fila['sin_leer'] }}</span>
                    </span>
                </a>
            @empty
                <p class="chat-vacio">{{ $esAdministrador ? 'Todavía no hay usuarios para conversar.' : 'Todavía no hay un administrador para conversar.' }}</p>
            @endforelse
        </aside>
        <section class="chat-lado">
            @if ($abierto)
                <div class="chat-cabeza">
                    <a class="volver" href="{{ route('mensajes.index') }}" aria-label="Volver a las conversaciones">←</a>
                    <span class="avatar">{{ mb_strtoupper(mb_substr($abierto->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $abierto->name }}</strong>
                        <small>{{ $abierto->esUsuario() ? ($abierto->contratos->pluck('codigo_proceso')->filter()->join(', ') ?: 'Sin contratos') : 'Administrador' }}{{ $abierto->activo ? '' : ' · Inactivo' }}</small>
                    </div>
                </div>
                <div class="hilo" data-hilo>
                    @foreach ($mensajes as $mensaje)
                        <div class="burbuja {{ $mensaje->de_id === auth()->id() ? 'mia' : '' }}" data-id="{{ $mensaje->id }}">
                            <p>{{ $mensaje->texto }}</p>
                            <time>{{ $mensaje->created_at->timezone('America/Guayaquil')->format('H:i') }}</time>
                        </div>
                    @endforeach
                </div>
                <p class="chat-error" data-error hidden></p>
                <form class="chat-forma" data-forma>
                    <textarea name="texto" rows="1" maxlength="2000" placeholder="Escriba un mensaje" required></textarea>
                    <button type="submit" aria-label="Enviar">➤</button>
                </form>
            @else
                <div class="chat-espera">Seleccione una conversación para empezar a escribir.</div>
            @endif
        </section>
    </div>

    <script>
        const chat = document.querySelector('[data-chat]');
        const hilo = chat.querySelector('[data-hilo]');
        const forma = chat.querySelector('[data-forma]');
        const error = chat.querySelector('[data-error]');
        const token = document.querySelector('meta[name="csrf-token"]').content;
        let ultimo = Number(chat.dataset.ultimo || 0);
        let consultando = false;

        if (hilo) hilo.scrollTop = hilo.scrollHeight;

        function aviso(nodo, cantidad) {
            if (!nodo) return;
            if (cantidad > 0) {
                nodo.hidden = false;
                nodo.textContent = cantidad > 99 ? '99+' : String(cantidad);
            } else {
                nodo.hidden = true;
                nodo.textContent = '';
            }
        }

        function agregar(mensaje) {
            if (!hilo || hilo.querySelector('[data-id="' + mensaje.id + '"]')) return;
            const cerca = hilo.scrollHeight - hilo.scrollTop - hilo.clientHeight < 80;
            const burbuja = document.createElement('div');
            burbuja.className = 'burbuja' + (mensaje.mio ? ' mia' : '');
            burbuja.dataset.id = mensaje.id;
            const texto = document.createElement('p');
            texto.textContent = mensaje.texto;
            const hora = document.createElement('time');
            hora.textContent = mensaje.hora;
            burbuja.append(texto, hora);
            hilo.append(burbuja);
            if (mensaje.mio || cerca) hilo.scrollTop = hilo.scrollHeight;
            ultimo = Math.max(ultimo, Number(mensaje.id));
        }

        async function novedades() {
            if (consultando) return;
            consultando = true;
            try {
                const url = new URL(chat.dataset.novedades, location.origin);
                url.searchParams.set('despues', String(ultimo));
                if (chat.dataset.abierto) url.searchParams.set('conversacion', chat.dataset.abierto);
                const respuesta = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (respuesta.status === 401 || respuesta.status === 419) {
                    location.reload();
                    return;
                }
                if (!respuesta.ok) return;
                const datos = await respuesta.json();
                datos.mensajes.forEach(agregar);
                aviso(document.getElementById('avisos-mensajes'), datos.sin_leer);
                aviso(document.getElementById('avisos-lista'), datos.sin_leer);
                datos.contactos.forEach((contacto) => {
                    const fila = chat.querySelector('[data-contacto="' + contacto.id + '"]');
                    if (!fila) return;
                    const preview = fila.querySelector('[data-preview]');
                    const hora = fila.querySelector('[data-hora]');
                    if (preview && contacto.preview) preview.textContent = contacto.preview;
                    if (hora) hora.textContent = contacto.hora;
                    aviso(fila.querySelector('[data-avisos]'), contacto.sin_leer);
                });
            } catch (e) {
            } finally {
                consultando = false;
            }
        }

        if (forma) {
            const area = forma.querySelector('textarea');
            area.addEventListener('input', () => {
                area.style.height = 'auto';
                area.style.height = Math.min(area.scrollHeight, 120) + 'px';
            });
            area.addEventListener('keydown', (evento) => {
                if (evento.key === 'Enter' && !evento.shiftKey) {
                    evento.preventDefault();
                    forma.requestSubmit();
                }
            });
            forma.addEventListener('submit', async (evento) => {
                evento.preventDefault();
                const texto = area.value.trim();
                if (!texto) return;
                error.hidden = true;
                const boton = forma.querySelector('button');
                boton.disabled = true;
                try {
                    const respuesta = await fetch(chat.dataset.enviar, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ texto }),
                    });
                    const datos = await respuesta.json();
                    if (!respuesta.ok) {
                        error.hidden = false;
                        error.textContent = datos.mensaje || 'No se pudo enviar el mensaje.';
                        return;
                    }
                    agregar(datos);
                    area.value = '';
                    area.style.height = 'auto';
                    const fila = chat.querySelector('[data-contacto="' + chat.dataset.abierto + '"] [data-preview]');
                    if (fila) fila.textContent = 'Tú: ' + texto.replace(/\s+/g, ' ');
                    const hora = chat.querySelector('[data-contacto="' + chat.dataset.abierto + '"] [data-hora]');
                    if (hora) hora.textContent = datos.hora;
                } catch (e) {
                    error.hidden = false;
                    error.textContent = 'No se pudo enviar el mensaje.';
                } finally {
                    boton.disabled = false;
                    area.focus();
                }
            });
        }

        setInterval(novedades, 3000);
    </script>
@endsection
