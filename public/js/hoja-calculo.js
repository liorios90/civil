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

    const patronNombre = (etiqueta) => {
        let patron = '';
        for (const caracter of etiqueta.trim()) {
            if (/\s/.test(caracter)) {
                patron += '\\s+';
                continue;
            }
            const base = caracter.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const clases = { a: '[aáàä]', e: '[eéèë]', i: '[iíìï]', o: '[oóòö]', u: '[uúùü]', n: '[nñ]' };
            if (clases[base]) patron += clases[base];
            else if (/^[a-z0-9]$/.test(base)) patron += base;
            else patron += caracter.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        return patron;
    };

    function Motor(crudas, claves, val, tipo, etiquetas) {
        this.crudas = crudas;
        this.claves = claves;
        this.val = val;
        this.tipo = tipo;
        this.etiquetas = etiquetas || [];
        this.filaFormula = 0;
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
            const filaFormula = this.filaFormula;
            this.filaFormula = fila;
            this.src = texto.slice(1);
            this.i = 0;
            let valor = this.expresion();
            this.espacios();
            if (this.ciclo || this.i < this.src.length) valor = null;
            this.src = src;
            this.i = pos;
            this.filaFormula = filaFormula;
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
        const columna = this.consumirColumna();
        if (columna != null) {
            const referida = this.leer(this.filaFormula, columna, true);
            if (this.ciclo) return null;
            return referida == null ? 0 : referida;
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

    Motor.prototype.consumirColumna = function () {
        const resto = this.src.slice(this.i);
        let mejor = null;
        let mejorLargo = 0;
        this.etiquetas.forEach((etiqueta, columna) => {
            const nombre = String(etiqueta || '').trim();
            if (!nombre || nombre.startsWith('=')) return;
            const coincidencia = resto.match(new RegExp('^' + patronNombre(nombre), 'i'));
            if (!coincidencia) return;
            const largo = coincidencia[0].length;
            const siguiente = resto[largo] || '';
            if (siguiente === '(' || /[A-Za-z0-9_]/.test(siguiente)) return;
            if (largo > mejorLargo) {
                mejorLargo = largo;
                mejor = columna;
            }
        });
        if (mejor == null) return null;
        this.i += mejorLargo;
        return mejor;
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

    const resolver = (crudas, claves, tipo, etiquetas) => {
        let val = crudas.map(() => Object.fromEntries(claves.map((clave) => [clave, null])));
        const motor = new Motor(crudas, claves, val, tipo, etiquetas);
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

    const columnasDe = (tabla) => [...tabla.querySelectorAll('thead th')].map((th) => th.dataset.clave).filter(Boolean);
    const crudasDe = (tabla) => [...tabla.tBodies[0].rows].map((tr) => {
        const fila = {};
        tr.querySelectorAll('td').forEach((td) => {
            if (!td.dataset.clave) return;
            fila[td.dataset.clave] = td.querySelector('.celda')?.dataset.raw || '';
        });
        return fila;
    });

    const pintar = (input, raw, valor) => {
        const td = input.closest('td');
        if (td) td.classList.toggle('con-formula', raw.startsWith('='));
        if (document.activeElement === input || input.dataset.texto) return;
        if (raw.startsWith('=')) {
            const mala = valor == null || Number.isNaN(valor);
            input.value = mala ? raw : texto(valor);
            input.classList.toggle('malo', mala);
            return;
        }
        input.classList.remove('malo');
        input.value = raw;
    };

    const recalcular = (tabla, conAutomaticos) => {
        const claves = columnasDe(tabla);
        const etiquetas = [...tabla.querySelectorAll('thead th .etiqueta')].map((input) => input.value);
        const crudas = crudasDe(tabla);
        const valores = resolver(crudas, claves, tabla.dataset.tipo || 'numero', etiquetas);
        [...tabla.tBodies[0].rows].forEach((tr, r) => {
            tr.querySelectorAll('td').forEach((td) => {
                const input = td.querySelector('.celda');
                if (!input || input.dataset.texto) return;
                const raw = input.dataset.raw || '';
                if (!conAutomaticos && !raw.startsWith('=')) return;
                pintar(input, raw, valores[r][td.dataset.clave]);
            });
        });
        pintarSuma(tabla, valores);
    };

    const pintarSuma = (tabla, valores) => {
        const claves = columnasDe(tabla);
        const ultima = claves[claves.length - 1];
        let suma = 0;
        if (ultima && ultima !== 'descripcion') {
            [...tabla.tBodies[0].rows].forEach((tr, r) => {
                const raw = String(tr.querySelector('td[data-clave="' + ultima + '"] .celda')?.dataset.raw || '').trim();
                if (raw.startsWith('=')) {
                    const valor = valores[r] ? valores[r][ultima] : null;
                    if (valor != null && !Number.isNaN(valor)) suma += valor;
                } else if (raw !== '') {
                    const valor = numero(raw);
                    if (valor != null) suma += valor;
                }
            });
        }
        let pie = tabla.tFoot?.rows[0];
        if (!pie) {
            pie = (tabla.tFoot || tabla.createTFoot()).insertRow();
        }
        while (pie.cells.length < claves.length + 1) pie.insertCell();
        while (pie.cells.length > claves.length + 1) pie.deleteCell(-1);
        [...pie.cells].forEach((td, indice) => {
            td.className = indice === 0 ? 'suma-etiqueta' : (indice === pie.cells.length - 1 ? 'suma-final' : '');
            td.textContent = indice === 0 ? 'Suma' : (indice === pie.cells.length - 1 ? texto(suma) : '');
            if (indice === pie.cells.length - 1) td.dataset.suma = '1';
        });
        const general = document.querySelector('[data-suma-general]');
        if (general) {
            let total = 0;
            document.querySelectorAll('table[data-hoja] [data-suma]').forEach((td) => {
                const valor = numero(td.textContent);
                if (valor != null) total += valor;
            });
            general.textContent = texto(total);
        }
    };

    const campo = (tabla, cola) => (tabla.dataset.prefijo || '') + cola;

    const letras = (tabla) => {
        let indice = 0;
        [...tabla.querySelectorAll('thead th')].forEach((th) => {
            const marca = th.querySelector('.letra');
            if (!marca) return;
            const texto = letra(indice);
            marca.textContent = texto;
            if (tabla.dataset.letras === '1' && th.dataset.clave !== 'descripcion' && th.dataset.clave !== 'total') {
                const nombre = th.querySelector('.etiqueta');
                if (nombre && (nombre.value === '' || nombre.value === 'Columna' || /^[A-Z]+$/.test(nombre.value))) {
                    nombre.value = texto;
                }
            }
            indice += 1;
        });
    };

    const numerar = (tabla) => {
        [...tabla.tBodies[0].rows].forEach((tr, indice) => {
            const marca = tr.querySelector('.num-fila');
            if (marca) marca.textContent = String(indice + 1);
        });
    };

    const crearMarcaFila = (numero) => {
        const td = document.createElement('td');
        td.className = 'fila-marca';
        const num = document.createElement('span');
        num.className = 'num-fila';
        num.textContent = String(numero);
        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'quitar-fila';
        quitar.title = 'Quitar fila';
        quitar.textContent = '×';
        td.append(num, quitar);
        return td;
    };

    const ajustarFormulasAlBorrar = (tabla, filaBorrada) => {
        tabla.querySelectorAll('.celda').forEach((celda) => {
            const raw = celda.dataset.raw || '';
            if (!raw.startsWith('=')) return;
            const ajustada = raw.replace(/([A-Za-z]+)(\d+)/g, (todo, letras, numero) => {
                const fila = Number(numero);
                if (fila === filaBorrada) return letras + '#';
                if (fila > filaBorrada) return letras + String(fila - 1);
                return todo;
            });
            if (ajustada === raw) return;
            celda.dataset.raw = ajustada;
            const hidden = celda.parentElement.querySelector('.crudo');
            if (hidden) hidden.value = ajustada;
            if (document.activeElement !== celda) celda.value = ajustada;
        });
    };

    const formulaEnFila = (formula, fila) => formula.replace(/([A-Za-z]+)1(?!\d)/g, (_, letras) => letras + String(fila));

    const normalizarFormula = (texto) => {
        let nueva = String(texto || '').trim();
        if (nueva !== '' && !nueva.startsWith('=')) nueva = '=' + nueva;
        return nueva;
    };

    const actualizarVistaFormula = (th) => {
        if (!th) return;
        const formula = th.querySelector('.formula-col');
        let vista = th.querySelector('.formula-vista');
        if (!formula) return;
        if (!vista) {
            vista = document.createElement('span');
            vista.className = 'formula-vista';
            formula.after(vista);
        }
        vista.textContent = formula.value || '';
    };

    const aplicarFormulaColumna = (tabla, th, nueva, anterior) => {
        nueva = normalizarFormula(nueva);
        anterior = normalizarFormula(anterior ?? (th.dataset.formula || ''));
        th.dataset.formula = nueva;
        const formula = th.querySelector('.formula-col');
        if (formula) {
            formula.value = nueva;
            formula.dataset.anterior = nueva;
        }
        actualizarVistaFormula(th);
        const clave = th.dataset.clave;
        [...tabla.tBodies[0].rows].forEach((tr, indice) => {
            const celda = tr.querySelector('td[data-clave="' + clave + '"] .celda');
            if (!celda || celda.dataset.texto) return;
            const raw = celda.dataset.raw || '';
            const esperada = anterior ? formulaEnFila(anterior, indice + 1) : '';
            if (raw !== '' && raw !== esperada) return;
            const siguiente = nueva ? formulaEnFila(nueva, indice + 1) : '';
            celda.dataset.raw = siguiente;
            const hidden = celda.parentElement.querySelector('.crudo');
            if (hidden) hidden.value = siguiente;
            if (document.activeElement !== celda) celda.value = siguiente;
        });
        recalcular(tabla, true);
    };

    const crearCelda = (clave, indice, formula, soloLetra, prefijo) => {
        const td = document.createElement('td');
        td.dataset.clave = clave;
        const input = document.createElement('input');
        input.className = clave === 'descripcion' ? 'celda' : 'n celda';
        if (clave === 'descripcion') input.dataset.texto = '1';
        const raw = formula ? formulaEnFila(formula, indice + 1) : '';
        if (clave === 'descripcion' && soloLetra) input.placeholder = 'Descripción';
        input.dataset.raw = raw;
        input.value = raw;
        input.autocomplete = 'off';
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.className = 'crudo';
        hidden.name = (prefijo || '') + '[lineas][' + indice + '][celdas][' + clave + ']';
        hidden.value = raw;
        td.append(input, hidden);
        return td;
    };

    const crearEncabezado = (clave, etiqueta, soloLetra, prefijo) => {
        const th = document.createElement('th');
        th.dataset.clave = clave;
        const orden = document.createElement('input');
        orden.type = 'hidden';
        orden.name = (prefijo || '') + '[orden_columnas][]';
        orden.value = clave;
        const marca = document.createElement('span');
        marca.className = 'letra';
        const nombre = document.createElement('input');
        nombre.className = 'etiqueta';
        nombre.name = (prefijo || '') + '[etiquetas][' + clave + ']';
        nombre.value = etiqueta;
        nombre.maxLength = 40;
        nombre.autocomplete = 'off';
        if (soloLetra) nombre.type = 'hidden';
        th.append(orden, marca, nombre);
        if (clave !== 'descripcion') {
            const formula = document.createElement('input');
            formula.className = 'formula-col';
            formula.name = (prefijo || '') + '[formulas][' + clave + ']';
            formula.placeholder = 'Fórmula';
            formula.maxLength = 200;
            formula.autocomplete = 'off';
            formula.tabIndex = -1;
            formula.setAttribute('aria-hidden', 'true');
            const vista = document.createElement('span');
            vista.className = 'formula-vista';
            th.append(formula, vista);
        }
        if (clave !== 'total' && clave !== 'descripcion') {
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
                hidden.name = campo(tabla, '[lineas][' + indice + '][celdas][' + clave + ']');
                hidden.value = hidden.closest('td').querySelector('.celda')?.dataset.raw || '';
            });
        });
    };

    const iniciar = (tabla) => {
        const form = tabla.closest('form');
        const bloque = tabla.closest('[data-tabla-medicion]') || form;
        const barra = bloque?.querySelector('[data-fx-input]');
        const marcaRef = bloque?.querySelector('[data-fx-ref]');
        let celdaActiva = null;

        const escribirCelda = (input, valor) => {
            input.dataset.raw = valor;
            const hidden = input.parentElement.querySelector('.crudo');
            if (hidden) hidden.value = valor;
            if (!input.dataset.texto) recalcular(tabla, true);
        };

        const mostrarBarra = (input) => {
            celdaActiva = input;
            tabla.querySelectorAll('td.activa').forEach((td) => td.classList.remove('activa'));
            const td = input.closest('td');
            if (td) td.classList.add('activa');
            const filas = [...tabla.tBodies[0].rows];
            const fila = filas.indexOf(input.closest('tr')) + 1;
            const columna = columnasDe(tabla).indexOf(td?.dataset.clave);
            if (marcaRef) marcaRef.textContent = (columna >= 0 ? letra(columna) : '') + (fila > 0 ? fila : '');
            if (barra && document.activeElement !== barra) barra.value = input.dataset.raw || '';
        };

        tabla.addEventListener('input', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda')) return;
            if (barra && input === celdaActiva && document.activeElement === input) barra.value = input.value;
            escribirCelda(input, input.value);
        });
        tabla.addEventListener('focusin', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda')) return;
            mostrarBarra(input);
            if (input.dataset.raw) input.value = input.dataset.raw;
            input.select();
        });
        tabla.addEventListener('focusout', (evento) => {
            const input = evento.target;
            if (!input.classList || !input.classList.contains('celda') || input.dataset.texto) return;
            if (evento.relatedTarget === barra) return;
            recalcular(tabla, true);
        });
        barra?.addEventListener('input', () => {
            if (!celdaActiva) return;
            escribirCelda(celdaActiva, barra.value);
        });
        barra?.addEventListener('keydown', (evento) => {
            if (evento.key !== 'Enter') return;
            evento.preventDefault();
            celdaActiva?.focus();
        });
        tabla.querySelectorAll('thead th').forEach(actualizarVistaFormula);
        tabla.addEventListener('click', (evento) => {
            const boton = evento.target.closest('.quitar-col');
            if (!boton || !tabla.contains(boton)) return;
            const th = boton.closest('th');
            if (!th || th.dataset.clave === 'total' || th.dataset.clave === 'descripcion') return;
            const nombre = th.querySelector('.etiqueta')?.value || 'esta columna';
            if (!window.confirm('¿Quitar la columna ' + nombre + '?')) return;
            const indice = [...th.parentElement.children].indexOf(th);
            th.remove();
            [...tabla.tBodies[0].rows].forEach((tr) => tr.children[indice]?.remove());
            letras(tabla);
            recalcular(tabla, true);
        });
        tabla.addEventListener('click', (evento) => {
            const boton = evento.target.closest('.quitar-fila');
            if (!boton || !tabla.contains(boton)) return;
            const tr = boton.closest('tr');
            const filas = [...tabla.tBodies[0].rows];
            const indice = filas.indexOf(tr);
            if (!tr || indice < 0) return;
            if (!window.confirm('¿Quitar la fila ' + (indice + 1) + '?')) return;
            if (filas.length === 1) {
                tr.querySelectorAll('td[data-clave]').forEach((td) => {
                    const encabezado = tabla.querySelector('th[data-clave="' + td.dataset.clave + '"]');
                    const formula = encabezado?.dataset.formula || '';
                    const raw = formula ? formulaEnFila(formula, 1) : '';
                    const celda = td.querySelector('.celda');
                    const hidden = td.querySelector('.crudo');
                    if (celda) {
                        celda.dataset.raw = raw;
                        celda.value = raw;
                    }
                    if (hidden) hidden.value = raw;
                });
            } else {
                ajustarFormulasAlBorrar(tabla, indice + 1);
                tr.remove();
            }
            numerar(tabla);
            reindexar(tabla);
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
        bloque?.querySelector('[data-agregar-columna]')?.addEventListener('click', () => {
            const clave = 'c' + Date.now().toString(36);
            const th = crearEncabezado(clave, tabla.dataset.letras === '1' ? '' : 'Columna', tabla.dataset.letras === '1', tabla.dataset.prefijo || '');
            const total = tabla.querySelector('th[data-clave="total"]');
            if (total) total.before(th);
            else tabla.querySelector('thead tr').append(th);
            [...tabla.tBodies[0].rows].forEach((tr, indice) => {
                const td = crearCelda(clave, indice, '', tabla.dataset.letras === '1', tabla.dataset.prefijo || '');
                const celdaTotal = tr.querySelector('td[data-clave="total"]');
                if (celdaTotal) celdaTotal.before(td);
                else tr.append(td);
            });
            letras(tabla);
        });
        bloque?.querySelector('[data-agregar-fila]')?.addEventListener('click', () => {
            const indice = tabla.tBodies[0].rows.length;
            const tr = document.createElement('tr');
            tr.append(crearMarcaFila(indice + 1));
            columnasDe(tabla).forEach((clave) => {
                const encabezado = tabla.querySelector('th[data-clave="' + clave + '"]');
                tr.append(crearCelda(clave, indice, encabezado?.dataset.formula || '', tabla.dataset.letras === '1', tabla.dataset.prefijo || ''));
            });
            tabla.tBodies[0].append(tr);
            numerar(tabla);
            recalcular(tabla, true);
            tr.querySelector('.celda')?.focus();
        });
        form?.addEventListener('submit', () => reindexar(tabla));
        recalcular(tabla, false);
    };

    document.querySelectorAll('table[data-hoja]').forEach(iniciar);

    const modalFormulas = document.getElementById('modal-formulas');
    const listaFormulas = modalFormulas?.querySelector('[data-lista-formulas]');
    let tablaFormulas = null;

    const columnasEditables = (tabla) => [...tabla.querySelectorAll('thead th')].filter((th) => {
        const clave = th.dataset.clave;
        return clave && clave !== 'descripcion';
    });

    const insertarEnCampo = (campo, texto) => {
        const inicio = campo.selectionStart ?? campo.value.length;
        const fin = campo.selectionEnd ?? campo.value.length;
        const antes = campo.value.slice(0, inicio);
        const despues = campo.value.slice(fin);
        const necesitaIgual = antes.trim() === '' && !texto.startsWith('=');
        const pieza = (necesitaIgual ? '=' : '') + texto;
        campo.value = antes + pieza + despues;
        const cursor = (antes + pieza).length;
        campo.focus();
        campo.setSelectionRange(cursor, cursor);
    };

    const armarChips = (bloque, campo, columnas, propia) => {
        const chips = document.createElement('div');
        chips.className = 'chips';
        columnas.forEach((col) => {
            if (col.clave === propia) return;
            const nombre = col.etiqueta || col.clave;
            if (!nombre || nombre.startsWith('=')) return;
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.textContent = nombre;
            boton.title = 'Insertar ' + nombre;
            boton.addEventListener('click', () => insertarEnCampo(campo, nombre));
            chips.append(boton);
        });
        ['+', '-', '*', '/', '(', ')'].forEach((op) => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'op';
            boton.textContent = op;
            boton.addEventListener('click', () => insertarEnCampo(campo, op));
            chips.append(boton);
        });
        const limpia = document.createElement('button');
        limpia.type = 'button';
        limpia.className = 'fn';
        limpia.textContent = 'Borrar';
        limpia.addEventListener('click', () => {
            campo.value = '';
            campo.focus();
        });
        chips.append(limpia);
        bloque.append(chips);
    };

    const abrirModalFormulas = (tabla) => {
        if (!modalFormulas || !listaFormulas) return;
        tablaFormulas = tabla;
        const columnas = columnasEditables(tabla).map((th, indice) => ({
            th,
            clave: th.dataset.clave,
            etiqueta: th.querySelector('.etiqueta')?.value || th.dataset.clave,
            formula: th.querySelector('.formula-col')?.value || th.dataset.formula || '',
            letra: letra(indice + (tabla.querySelector('th[data-clave="descripcion"]') ? 1 : 0)),
        }));
        // Recalculate letters from full header order
        const todas = [...tabla.querySelectorAll('thead th')].filter((th) => th.dataset.clave);
        columnas.forEach((col) => {
            const indice = todas.findIndex((th) => th.dataset.clave === col.clave);
            col.letra = letra(indice);
        });

        listaFormulas.innerHTML = '';
        columnas.forEach((col) => {
            const bloque = document.createElement('div');
            bloque.className = 'dato-formula';
            bloque.dataset.clave = col.clave;

            const tope = document.createElement('div');
            tope.className = 'dato-tope';
            const titulo = document.createElement('strong');
            titulo.textContent = col.etiqueta;
            const marca = document.createElement('span');
            marca.className = 'letra-chip';
            marca.textContent = col.letra;
            tope.append(titulo, marca);

            const campoWrap = document.createElement('div');
            campoWrap.className = 'campo-formula';
            const label = document.createElement('label');
            label.textContent = col.clave === 'total'
                ? 'Fórmula del total (cantidad a facturar)'
                : 'Fórmula (deje vacío si se escribe a mano)';
            const campo = document.createElement('input');
            campo.type = 'text';
            campo.maxLength = 200;
            campo.autocomplete = 'off';
            campo.placeholder = '=altura*peso';
            campo.value = col.formula;
            campo.dataset.clave = col.clave;
            campoWrap.append(label, campo);

            const ayuda = document.createElement('p');
            ayuda.className = 'ayuda-formula';
            ayuda.textContent = 'Toque un nombre o un signo para armar la fórmula. Ejemplo: =altura*peso';

            bloque.append(tope, campoWrap);
            armarChips(bloque, campo, columnas.map((c) => ({ clave: c.clave, etiqueta: c.etiqueta })), col.clave);
            bloque.append(ayuda);

            campo.addEventListener('focus', () => {
                listaFormulas.querySelectorAll('.dato-formula').forEach((el) => el.classList.remove('activo'));
                bloque.classList.add('activo');
            });

            listaFormulas.append(bloque);
        });

        modalFormulas.hidden = false;
        listaFormulas.querySelector('input')?.focus();
    };

    const cerrarModalFormulas = () => {
        if (!modalFormulas) return;
        modalFormulas.hidden = true;
        tablaFormulas = null;
    };

    const aplicarModalFormulas = () => {
        if (!tablaFormulas || !listaFormulas) return;
        listaFormulas.querySelectorAll('.dato-formula').forEach((bloque) => {
            const campo = bloque.querySelector('input[data-clave]');
            const th = tablaFormulas.querySelector('th[data-clave="' + bloque.dataset.clave + '"]');
            if (!campo || !th) return;
            const anterior = th.querySelector('.formula-col')?.value || th.dataset.formula || '';
            aplicarFormulaColumna(tablaFormulas, th, campo.value, anterior);
        });
        cerrarModalFormulas();
    };

    document.querySelectorAll('[data-abrir-formulas]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const tabla = boton.closest('[data-tabla-medicion]')?.querySelector('table[data-hoja]');
            if (tabla) abrirModalFormulas(tabla);
        });
    });
    document.querySelectorAll('[data-cerrar-formulas]').forEach((boton) => {
        boton.addEventListener('click', cerrarModalFormulas);
    });
    document.querySelector('[data-aplicar-formulas]')?.addEventListener('click', aplicarModalFormulas);
    modalFormulas?.addEventListener('click', (evento) => {
        if (evento.target === modalFormulas) cerrarModalFormulas();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && modalFormulas && !modalFormulas.hidden) {
            cerrarModalFormulas();
        }
    });
}());
