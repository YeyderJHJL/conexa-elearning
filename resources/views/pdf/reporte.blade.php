<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de capacitación - {{ $usuario->name }}</title>
    <style>
        @page { margin: 90px 40px 70px 40px; }

        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }

        .encabezado { position: fixed; top: -70px; left: 0; right: 0; height: 56px; border-bottom: 2px solid #D7A743; }
        .encabezado table { width: 100%; border-collapse: collapse; }
        .logo { width: 150px; height: 44px; }
        .logo img { max-height: 44px; max-width: 150px; }
        .logo-texto { font-size: 15px; font-weight: bold; letter-spacing: 1px; color: #0E1A34; line-height: 1.1; padding-top: 6px; }
        .logo-texto span { display: block; font-size: 7px; font-weight: normal; letter-spacing: 2px; color: #8C8C8E; }
        .titulo { text-align: right; vertical-align: middle; }
        .titulo h1 { margin: 0; font-size: 17px; color: #0E1A34; }
        .titulo p { margin: 2px 0 0; color: #64748b; font-size: 9px; }

        .pie { position: fixed; bottom: -45px; left: 0; right: 0; height: 24px; border-top: 1px solid #e2e8f0; padding-top: 6px; color: #94a3b8; font-size: 8px; }
        .pie table { width: 100%; border-collapse: collapse; }
        .pagina:before { content: counter(page); }

        h2 { font-size: 12px; color: #0E1A34; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
        h3 { font-size: 11px; margin: 0; color: #111827; }

        table.datos { width: 100%; border-collapse: collapse; }
        table.datos td { padding: 4px 6px; border: 1px solid #e2e8f0; }
        table.datos td.etiqueta { width: 18%; background: #f8fafc; color: #475569; font-weight: bold; }

        table.tabla { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.tabla th { background: #F2F5FA; color: #0E1A34; text-align: left; padding: 5px 6px; border: 1px solid #C9D5E8; font-size: 9px; }
        table.tabla td { padding: 5px 6px; border: 1px solid #e2e8f0; vertical-align: top; }
        table.tabla tr { page-break-inside: avoid; }
        .centro { text-align: center; }

        .barra { height: 7px; background: #e2e8f0; width: 100%; }
        .barra-relleno { height: 7px; background: #D7A743; }
        .barra-completa { background: #D7A743; }

        .resumen td { vertical-align: middle; }
        .porcentaje { font-size: 24px; font-weight: bold; color: #0E1A34; }

        .estado-completado { color: #B98A2E; font-weight: bold; }
        .estado-en_curso { color: #4A6FA5; font-weight: bold; }
        .estado-bloqueado { color: #6b7280; }
        .ok { color: #047857; }
        .mal { color: #b45309; }
        .suave { color: #6b7280; }

        .area { margin-top: 14px; page-break-inside: avoid; }
        .nota { margin-top: 6px; padding: 6px 8px; border: 1px solid #fde68a; background: #fffbeb; color: #78350f; }
        .vacio { color: #6b7280; font-style: italic; margin: 6px 0; }
    </style>
</head>
<body>
    @php
        $etiquetasEstado = ['completado' => 'Completado', 'en_curso' => 'En curso', 'bloqueado' => 'Bloqueado'];
        $logo = public_path('images/'.config('marca.logos.oscuro'));
        // Dompdf necesita la extensión GD para dibujar un PNG; sin ella se usa el nombre en texto.
        $conLogo = extension_loaded('gd') && is_file($logo);
    @endphp

    <div class="encabezado">
        <table>
            <tr>
                <td style="width: 160px;">
                    @if ($conLogo)
                        <div class="logo"><img src="{{ $logo }}" alt="{{ config('marca.nombre') }}"></div>
                    @else
                        <div class="logo-texto">CONEXA<span>CAPITAL CENTRAL</span></div>
                    @endif
                </td>
                <td class="titulo">
                    <h1>Reporte de capacitación</h1>
                    <p>Programa de inducción · Conexa</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="pie">
        <table>
            <tr>
                <td>Documento generado automáticamente por la plataforma de capacitación de Conexa.</td>
                <td style="text-align: right;">Página <span class="pagina"></span></td>
            </tr>
        </table>
    </div>

    <h2>Datos del trabajador</h2>
    <table class="datos">
        <tr>
            <td class="etiqueta">Nombre</td>
            <td>{{ $usuario->name }}</td>
            <td class="etiqueta">Cargo</td>
            <td>{{ $usuario->cargo ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha de ingreso</td>
            <td>{{ $usuario->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
            <td class="etiqueta">Generado el</td>
            <td>{{ $generadoEn->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <h2>Resumen</h2>
    <table class="datos resumen">
        <tr>
            <td class="etiqueta">Avance global</td>
            <td style="width: 32%;">
                <span class="porcentaje">{{ $global }}%</span>
                <div class="barra" style="margin-top: 4px;">
                    <div class="barra-relleno {{ $global >= 100 ? 'barra-completa' : '' }}" style="width: {{ $global }}%;"></div>
                </div>
            </td>
            <td class="etiqueta">Estado final</td>
            <td class="{{ $estadoFinal === 'Completado' ? 'estado-completado' : 'estado-en_curso' }}">{{ $estadoFinal }}</td>
        </tr>
    </table>

    <h2>Avance por área</h2>
    @forelse ($areas as $fila)
        <div class="area">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td><h3>{{ $fila['area']->nombre }}</h3></td>
                    <td style="text-align: right; width: 60px;"><strong>{{ $fila['avance'] }}%</strong></td>
                </tr>
            </table>
            <div class="barra" style="margin-top: 3px;">
                <div class="barra-relleno {{ $fila['avance'] >= 100 ? 'barra-completa' : '' }}" style="width: {{ $fila['avance'] }}%;"></div>
            </div>

            @if ($fila['modulos']->isEmpty())
                <p class="vacio">Esta área aún no tiene módulos activos.</p>
            @else
                <table class="tabla">
                    <thead>
                        <tr>
                            <th style="width: 5%;">N.º</th>
                            <th>Módulo</th>
                            <th style="width: 14%;">Estado</th>
                            <th style="width: 25%;">Quiz (mejor intento)</th>
                            <th class="centro" style="width: 11%;">Intentos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fila['modulos'] as $modulo)
                            <tr>
                                <td class="centro">{{ $loop->iteration }}</td>
                                <td>{{ $modulo['modulo']->titulo }}</td>
                                <td class="estado-{{ $modulo['estado'] }}">{{ $etiquetasEstado[$modulo['estado']] ?? $modulo['estado'] }}</td>
                                <td>
                                    @if (! $modulo['tieneQuiz'])
                                        <span class="suave">Sin quiz</span>
                                    @elseif ($modulo['intentos'] === 0)
                                        <span class="suave">No rendido</span>
                                    @else
                                        <strong>{{ $modulo['mejorNota'] }}%</strong>
                                        <span class="{{ $modulo['aprobado'] ? 'ok' : 'mal' }}">{{ $modulo['aprobado'] ? 'Aprobado' : 'No aprobado' }}</span>
                                    @endif
                                </td>
                                <td class="centro">{{ $modulo['tieneQuiz'] ? $modulo['intentos'] : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @empty
        <p class="vacio">No tienes áreas de capacitación asignadas.</p>
    @endforelse

    <h2>Puntos a reforzar</h2>
    @if ($reforzar->isEmpty())
        <p class="vacio">No hay quizzes rendidos sin aprobar.</p>
    @else
        <p class="suave" style="margin: 0 0 4px;">Módulos cuyo quiz fue rendido y aún no alcanza la nota mínima, de menor a mayor nota.</p>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Área</th>
                    <th>Módulo</th>
                    <th class="centro" style="width: 14%;">Mejor nota</th>
                    <th class="centro" style="width: 14%;">Nota mínima</th>
                    <th class="centro" style="width: 11%;">Intentos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reforzar as $punto)
                    <tr>
                        <td>{{ $punto['area']->nombre }}</td>
                        <td>{{ $punto['modulo']->titulo }}</td>
                        <td class="centro mal"><strong>{{ $punto['mejorNota'] }}%</strong></td>
                        <td class="centro">{{ $punto['notaMinima'] }}%</td>
                        <td class="centro">{{ $punto['intentos'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($leccionesNuevas->isNotEmpty())
        <h2>Contenido agregado después de aprobar</h2>
        @foreach ($leccionesNuevas as $nota)
            <div class="nota">
                <strong>{{ $nota['area']->nombre }} · {{ $nota['modulo']->titulo }}:</strong>
                se {{ $nota['nuevas'] === 1 ? 'agregó 1 lección' : 'agregaron '.$nota['nuevas'].' lecciones' }}
                después de aprobar el módulo ({{ $nota['aprobadoEn']->format('d/m/Y') }}).
                @if ($nota['pendientes'] > 0)
                    {{ $nota['pendientes'] === 1 ? 'Queda 1 pendiente' : 'Quedan '.$nota['pendientes'].' pendientes' }} de ver.
                @else
                    Ya las viste todas.
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>
