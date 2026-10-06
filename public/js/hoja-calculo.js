(function () {
    const redondo = (numero) => Math.round((numero + Number.EPSILON) * 100) / 100;
    const texto = (numero) => (Math.round((numero + Number.EPSILON) * 100) / 100).toFixed(2);
    const numero = (valor) => {
        if (valor == null) return null;
        const limpio = String(valor).trim();
        if (limpio === '' || Number.isNaN(Number(limpio))) return null;
        return Number(limpio);
    };

    const letra = (indice) => {
        let n = indice + 1;
        let salida = '';
        while (n > 0) {
            n -= 1;
            salida = String.fromCharCode(65 + (n % 26)) + salida;
            n = Math.floor(n / 26);
        }
        return salida;
    };

    const indiceLetra = (letras) => {
        let n = 0;
        for (const caracter of letras.toUpperCase()) {
            if (caracter < 'A' || caracter > 'Z') return -1;
            n = n * 26 + (caracter.charCodeAt(0) - 64);
        }
        return n - 1;
    };

    const dimensiones = (fila, tipo) => {
        const base1 = fila.base1 ?? null;
        const base2 = fila.base2 ?? null;
        const altura = fila.altura ?? null;
        const veces = fila.numero ?? null;
        const factor = (tipo === 'm3km' || tipo === 'kg') ? 1 : (veces == null ? 1 : veces);
        const longitud = base1 == null ? null : redondo(base1 * factor);
        let area = null;
        if (base1 != null && base2 != null) area = redondo(base1 * base2 * factor);
        else if (base1 != null && altura != null) area = redondo(base1 * altura * factor);
        const volumen = (base1 != null && base2 != null && altura != null) ? redondo(base1 * base2 * altura * factor) : null;
        return { longitud, area, volumen };
    };

    const totalUnidad = (tipo, fila, dims) => {
        const longitud = fila.longitud != null ? fila.longitud : dims.longitud;
        const area = fila.area != null ? fila.area : dims.area;
        const volumen = fila.volumen != null ? fila.volumen : dims.volumen;
        const veces = fila.numero ?? null;
        let valor = null;
        if (tipo === 'longitud') valor = longitud;
        else if (tipo === 'area') valor = area;
        else if (tipo === 'volumen') valor = volumen;
        else if (tipo === 'm3km') valor = (volumen == null || veces == null) ? null : redondo(volumen * veces);
        else valor = veces;
        return valor == null ? null : redondo(valor);
    };

    const automatico = (clave, fila, tipo) => {
        const dims = dimensiones(fila, tipo);
        if (clave === 'longitud' || clave === 'area' || clave === 'volumen') return dims[clave];
        if (clave === 'total') return totalUnidad(tipo, fila, dims);
        return null;
    };

    function Motor(crudas, claves, val, tipo) {
        this.crudas = crudas;
        this.claves = claves;
        this.val = val;
        this.tipo = tipo;
        this.src = '';
        this.i = 0;
        this.pila = {};
        this.memo = {};
        this.ciclo = false;
    }

    Motor.prototype.valorDe = function (fila, col) {
        this.pila = {};
        this.memo = {};
        this.ciclo = false;
        return this.leer(fila, col, false);
    };

    Motor.prototype.leer = function (fila, col, enFormula) {
        if (this.ciclo) return null;
        const marca = fila + ':' + col;
        if (Object.prototype.hasOwnProperty.call(this.memo, marca)) {
            const listo = this.memo[marca];
            return enFormula ? (listo == null ? 0 : listo) : listo;
        }
        if (this.pila[marca]) {
            this.ciclo = true;
            return null;
        }
        const clave = this.claves[col];
        if (!clave || clave === 'descripcion') return enFormula ? 0 : null;
        const texto = String(this.crudas[fila][clave] || '').trim();
        if (texto.startsWith('=')) {
            this.pila[marca] = true;
            const src = this.src;
            const pos = this.i;
            this.src = texto.slice(1);
            this.i = 0;
            let valor = this.expresion();
            this.espacios();
            if (this.ciclo || this.i < this.src.length) valor = null;
            this.src = src;
            this.i = pos;
            delete this.pila[marca];
            this.memo[marca] = valor;
            return enFormula ? (valor == null ? 0 : valor) : valor;
        }
        if (texto !== '') {
            const valor = numero(texto);
            this.memo[marca] = valor;
            return enFormula ? (valor == null ? 0 : valor) : valor;
        }
        const actual = this.val[fila][clave];
        if (actual != null) return actual;
        const auto = automatico(clave, this.val[fila] || {}, this.tipo);
        if (auto != null) return auto;
        return enFormula ? 0 : null;
    };

    Motor.prototype.espacios = function () {
        while (this.src[this.i] === ' ') this.i += 1;
    };

    Motor.prototype.expresion = function () {
        if (this.ciclo) return null;
        let izq = this.termino();
        if (izq == null) return null;
        while (true) {
            this.espacios();
            const op = this.src[this.i] || '';
            if (op !== '+' && op !== '-') break;
            this.i += 1;
            const der = this.termino();
            if (der == null) return null;
            izq = op === '+' ? izq + der : izq - der;
        }
        return izq;
    };

    Motor.prototype.termino = function () {
        let izq = this.factor();
        if (izq == null) return null;
        while (true) {
            this.espacios();
            const op = this.src[this.i] || '';
            if (op !== '*' && op !== '/') break;
            this.i += 1;
            const der = this.factor();
            if (der == null || (op === '/' && der === 0)) return null;
            izq = op === '*' ? izq * der : izq / der;
        }
        return izq;
    };

    Motor.prototype.factor = function () {
        this.espacios();
        const op = this.src[this.i] || '';
        if (op === '+' || op === '-') {
            this.i += 1;
            const valor = this.factor();
            return valor == null ? null : (op === '-' ? -valor : valor);
        }
        return this.primario();
    };

    Motor.prototype.primario = function () {
        this.espacios();
        const caracter = this.src[this.i] || '';
        if (caracter === '(') {
            this.i += 1;
            const valor = this.expresion();
            this.espacios();
            if ((this.src[this.i] || '') !== ')') return null;
            this.i += 1;
            return valor;
        }
        const resto = this.src.slice(this.i);
        const num = resto.match(/^(\d+(?:\.\d+)?)/);
        if (num) {
            this.i += num[1].length;
            return Number(num[1]);
        }
        const nombre = resto.match(/^([A-Za-z]+)/);
        if (!nombre) return null;
        this.i += nombre[1].length;
        const despues = this.src.slice(this.i);
        const fila = despues.match(/^(\d+)/);
        if (fila && (this.src[this.i + fila[1].length] || '') !== '(') {
            this.i += fila[1].length;
            const col = indiceLetra(nombre[1]);
            const r = Number(fila[1]) - 1;
            if (col < 0 || col >= this.claves.length || r < 0 || r >= this.crudas.length) return 0;
            const referida = this.leer(r, col, true);
            if (this.ciclo) return null;
            return referida == null ? 0 : referida;
        }
        this.espacios();
        if ((this.src[this.i] || '') !== '(') return null;
        this.i += 1;
        const args = this.argumentos();
        this.espacios();
        if (!args || (this.src[this.i] || '') !== ')') return null;
        this.i += 1;
        return this.funcion(nombre[1].toUpperCase(), args);
    };

    Motor.prototype.argumentos = function () {
        const numeros = [];
        this.espacios();
        if ((this.src[this.i] || '') === ')') return numeros;
        while (true) {
            const bloque = this.rangoOExpresion();
            if (!bloque) return null;
            numeros.push(...bloque);
            this.espacios();
            const sep = this.src[this.i] || '';
            if (sep === ',' || sep === ';') {
                this.i += 1;
                continue;
            }
            break;
        }
        return numeros;
    };

    Motor.prototype.rangoOExpresion = function () {
        this.espacios();
        const inicio = this.src.slice(this.i).match(/^([A-Za-z]+)(\d+)\s*:/);
        if (inicio) {
            const c1 = indiceLetra(inicio[1]);
            const r1 = Number(inicio[2]) - 1;
            this.i += inicio[0].length;
            this.espacios();
            const fin = this.src.slice(this.i).match(/^([A-Za-z]+)(\d+)/);
            if (!fin) return null;
            this.i += fin[0].length;
            const c2 = indiceLetra(fin[1]);
            const r2 = Number(fin[2]) - 1;
            const numeros = [];
            for (let r = Math.min(r1, r2); r <= Math.max(r1, r2); r += 1) {
                for (let c = Math.min(c1, c2); c <= Math.max(c1, c2); c += 1) {
                    if (r < 0 || c < 0 || r >= this.crudas.length || c >= this.claves.length) {
                        numeros.push(0);
                        continue;
                    }
                    const referida = this.leer(r, c, true);
                    if (this.ciclo) return null;
                    numeros.push(referida == null ? 0 : referida);
                }
            }
            return numeros;
        }
        const valor = this.expresion();
        return valor == null ? null : [valor];
    };

    Motor.prototype.funcion = function (nombre, args) {
        if (nombre === 'SUMA' || nombre === 'SUM') return redondo(args.reduce((a, b) => a + b, 0));
        if (nombre === 'PROMEDIO' || nombre === 'PROM' || nombre === 'AVERAGE') {
            return args.length ? redondo(args.reduce((a, b) => a + b, 0) / args.length) : null;
        }
        if (nombre === 'MIN') return args.length ? Math.min(...args) : null;
        if (nombre === 'MAX') return args.length ? Math.max(...args) : null;
        if (nombre === 'ABS') return args.length ? Math.abs(args[0]) : null;
        if (nombre === 'REDONDEAR' || nombre === 'ROUND') return args.length ? redondo(Number(args[0].toFixed(args[1] == null ? 0 : args[1]))) : null;
        return null;
    };

    const resolver = (crudas, claves, tipo) => {
        let val = crudas.map(() => Object.fromEntries(claves.map((clave) => [clave, null])));
        const motor = new Motor(crudas, claves, val, tipo);
        for (let pasada = 0; pasada < 12; pasada += 1) {
            const antes = JSON.stringify(val);
            const siguiente = val.map((fila) => ({ ...fila }));
            crudas.forEach((fila, r) => {
                claves.forEach((clave, c) => {
                    if (clave === 'descripcion') return;
                    const calculado = motor.valorDe(r, c);
                    siguiente[r][clave] = calculado == null ? null : redondo(calculado);
                });
            });
            val = siguiente;
            motor.val = val;
            if (JSON.stringify(val) === antes) break;
        }
        return val;
    };

    const columnasDe = (tabla) => [...tabla.querySelectorAll('thead th')].map((th) => th.dataset.clave);
    const crudasDe = (tabla) => [...tabla.tBodies[0].rows].map((tr) => {
        const fila = {};
        tr.querySelectorAll('td').forEach((td) => {
            fila[td.dataset.clave] = td.querySelector('.celda')?.dataset.raw || '';
        });
        return fila;
    });

    const pintar = (input, raw, valor) => {
        if (document.activeElement === input || input.dataset.texto) return;
        if (raw.startsWith('=')) {
            const mala = valor == null || Number.isNaN(valor);
            input.value = mala ? raw : texto(valor);
            input.classList.toggle('malo', mala);
            return;
        }
        input.classList.remove('malo');
        if (raw !== '') {
            input.value = raw;
            return;
        }
        input.value = valor == null ? '' : texto(valor);
    };

    const recalcular = (tabla, conAutomaticos) => {
        const claves = columnasDe(tabla);
        const crudas = crudasDe(tabla);
        const valores = resolver(crudas, claves, tabla.dataset.tipo || 'numero');
        [...tabla.tBodies[0].rows].forEach((tr, r) => {
            tr.querySelectorAll('td').forEach((td) => {
                const input = td.querySelector('.celda');
                if (!input || input.dataset.texto) return;
                const raw = input.dataset.raw || '';
                if (!conAutomaticos && !raw.startsWith('=')) return;
                pintar(input, raw, valores[r][td.dataset.clave]);
            });
        });
    };

    const letras = (tabla) => {
        [...tabla.querySelectorAll('thead th')].forEach((th, indice) => {
            const marca = th.querySelector('.letra');
            if (marca) marca.textContent = letra(indice);
        });
    };

    const crearCelda = (clave, indice) => {
        const td = document.createElement('td');
        td.dataset.clave = clave;
        const input = document.createElement('input');
        input.className = clave === 'descripcion' ? 'celda' : 'n celda';
        if (clave === 'descripcion') input.dataset.texto = '1';
        input.dataset.raw = '';
        input.autocomplete = 'off';
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.className = 'crudo';
        hidden.name = 'lineas[' + indice + '][celdas][' + clave + ']';
        td.append(input, hidden);
        return td;
    };

    const crearEncabezado = (clave, etiqueta) => {
        const th = document.createElement('th');
        th.dataset.clave = clave;
        const orden = document.createElement('input');
        orden.type = 'hidden';
        orden.name = 'orden_columnas[]';
        orden.value = clave;
        const marca = document.createElement('span');
        marca.className = 'letra';
        const nombre = document.createElement('input');
        nombre.className = 'etiqueta';
        nombre.name = 'etiquetas[' + clave + ']';
        nombre.value = etiqueta;
        nombre.maxLength = 40;
        nombre.autocomplete = 'off';
        th.append(orden, marca, nombre);
        if (clave !== 'total') {
            const quitar = document.createElement('button');
            quitar.type = 'button';
            quitar.className = 'quitar-col';
            quitar.title = 'Quitar columna';
            quitar.textContent = '×';
            th.append(quitar);
        }
        return th;
    };

    const reindexar = (tabla) => {
        [...tabla.tBodies[0].rows].forEach((tr, indice) => {
            tr.querySelectorAll('.crudo').forEach((hidden) => {
                const clave = hidden.closest('td').dataset.clave;
                hidden.name = 'lineas[' + indice + '][celdas][' + clave + ']';
                hidden.value = hidden.closest('td').querySelector('.celda')?.dataset.raw || '';
            });
        });
    };

    const iniciar = (tabla) => {
        const form = tabla.closest('form');
        tabla.addEventListener('input', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda')) return;
            input.dataset.raw = input.value;
            const hidden = input.parentElement.querySelector('.crudo');
            if (hidden) hidden.value = input.value;
            if (!input.dataset.texto) recalcular(tabla, true);
        });
        tabla.addEventListener('focusin', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda')) return;
            if (input.dataset.raw) input.value = input.dataset.raw;
            input.select();
        });
        tabla.addEventListener('focusout', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda') || input.dataset.texto) return;
            recalcular(tabla, true);
        });
        tabla.addEventListener('click', (evento) => {
            const boton = evento.target.closest('.quitar-col');
            if (!boton || !tabla.contains(boton)) return;
            const th = boton.closest('th');
            if (!th || th.dataset.clave === 'total') return;
            const nombre = th.querySelector('.etiqueta')?.value || 'esta columna';
            if (!window.confirm('¿Quitar la columna ' + nombre + '?')) return;
            const indice = [...th.parentElement.children].indexOf(th);
            th.remove();
            [...tabla.tBodies[0].rows].forEach((tr) => tr.children[indice]?.remove());
            letras(tabla);
            recalcular(tabla, true);
        });
        tabla.addEventListener('keydown', (evento) => {
            const campo = evento.target;
            if (!(campo instanceof HTMLInputElement)) return;
            if (campo.classList.contains('etiqueta') && evento.key === 'Enter') {
                evento.preventDefault();
                campo.blur();
                return;
            }
            if (!campo.classList.contains('celda') || evento.altKey || evento.ctrlKey || evento.metaKey) return;
            const mover = evento.key === 'Enter' || evento.key === 'ArrowUp' || evento.key === 'ArrowDown' || evento.key === 'ArrowLeft' || evento.key === 'ArrowRight';
            if (!mover) return;
            const horizontal = evento.key === 'ArrowLeft' || evento.key === 'ArrowRight';
            if (horizontal && campo.selectionStart != null) {
                const completo = campo.selectionStart === 0 && campo.selectionEnd === campo.value.length;
                const izquierda = evento.key === 'ArrowLeft' && campo.selectionStart === 0 && campo.selectionEnd === 0;
                const derecha = evento.key === 'ArrowRight' && campo.selectionStart === campo.value.length;
                if (!completo && !izquierda && !derecha) return;
            }
            const fila = campo.closest('tr');
            const filas = [...tabla.tBodies[0].rows];
            const posicion = filas.indexOf(fila);
            if (!fila || posicion < 0) return;
            const campos = [...fila.querySelectorAll('.celda')];
            const columna = campos.indexOf(campo);
            let destinoFila = posicion;
            let destinoCol = columna;
            if (evento.key === 'ArrowUp' || evento.key === 'Enter' && evento.shiftKey) destinoFila -= 1;
            else if (evento.key === 'ArrowDown' || evento.key === 'Enter') destinoFila += 1;
            else if (evento.key === 'ArrowLeft') destinoCol -= 1;
            else if (evento.key === 'ArrowRight') destinoCol += 1;
            const filaDestino = filas[destinoFila];
            if (!filaDestino) return;
            const destino = [...filaDestino.querySelectorAll('.celda')][destinoCol];
            if (!destino) return;
            evento.preventDefault();
            destino.focus();
            destino.select();
        });
        form?.querySelector('[data-agregar-columna]')?.addEventListener('click', () => {
            const clave = 'c' + Date.now().toString(36);
            const th = crearEncabezado(clave, 'Columna');
            const total = tabla.querySelector('th[data-clave="total"]');
            if (total) total.before(th);
            else tabla.querySelector('thead tr').append(th);
            [...tabla.tBodies[0].rows].forEach((tr, indice) => {
                const td = crearCelda(clave, indice);
                const celdaTotal = tr.querySelector('td[data-clave="total"]');
                if (celdaTotal) celdaTotal.before(td);
                else tr.append(td);
            });
            letras(tabla);
        });
        form?.querySelector('[data-agregar-fila]')?.addEventListener('click', () => {
            const indice = tabla.tBodies[0].rows.length;
            const tr = document.createElement('tr');
            columnasDe(tabla).forEach((clave) => tr.append(crearCelda(clave, indice)));
            tabla.tBodies[0].append(tr);
            tr.querySelector('.celda')?.focus();
        });
        form?.addEventListener('submit', () => reindexar(tabla));
        recalcular(tabla, false);
    };

    document.querySelectorAll('table[data-hoja]').forEach(iniciar);
}());
