{{--
    Detalle de avance de un trabajador dentro del panel de Filament.
    Usa solo componentes de Filament y estilos locales (el panel no compila el Tailwind de la app).
--}}
<style>
    .rt { display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.875rem; }
    .rt h3 { font-weight: 600; font-size: 1rem; margin: 0; }
    .rt p { margin: 0; }
    .rt .rt-suave { opacity: 0.65; }
    .rt .rt-resumen { display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; align-items: center; }
    .rt .rt-grande { font-size: 1.5rem; font-weight: 700; }
    .rt .rt-area-cabecera { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; margin-bottom: 0.4rem; }
    .rt .rt-barra { height: 0.4rem; border-radius: 9999px; background: rgba(128, 128, 128, 0.25); overflow: hidden; margin-bottom: 0.6rem; }
    .rt .rt-barra > span { display: block; height: 100%; background: rgb(var(--primary-500)); }
    .rt .rt-barra.rt-completa > span { background: rgb(var(--success-500)); }
    .rt table { width: 100%; border-collapse: collapse; }
    .rt th, .rt td { padding: 0.45rem 0.5rem; text-align: left; vertical-align: top; border-bottom: 1px solid rgba(128, 128, 128, 0.25); }
    .rt th { font-weight: 600; font-size: 0.75rem; opacity: 0.75; }
    .rt .rt-centro { text-align: center; }
    .rt ul { margin: 0; padding-left: 1.1rem; }
</style>

@php
    $estados = [
        'completado' => ['Completado', 'success'],
        'en_curso' => ['En curso', 'primary'],
        'bloqueado' => ['Bloqueado', 'gray'],
    ];
@endphp

<div class="rt">
    <section>
        <div class="rt-resumen">
            <div>
                <p class="rt-suave">Avance global</p>
                <p class="rt-grande">{{ $global }}%</p>
            </div>
            <x-filament::badge :color="$estadoFinal === 'Completado' ? 'success' : 'primary'">{{ $estadoFinal }}</x-filament::badge>
            <p class="rt-suave">
                {{ $usuario->cargo ?: 'Sin cargo' }}
                @if ($usuario->fecha_ingreso)
                    · Ingreso {{ $usuario->fecha_ingreso->format('d/m/Y') }}
                @endif
            </p>
        </div>
    </section>

    @forelse ($areas as $fila)
        <section>
            <div class="rt-area-cabecera">
                <h3>{{ $fila['area']->nombre }}</h3>
                <strong>{{ $fila['avance'] }}%</strong>
            </div>
            <div class="rt-barra {{ $fila['avance'] >= 100 ? 'rt-completa' : '' }}"><span style="width: {{ $fila['avance'] }}%"></span></div>

            @if ($fila['modulos']->isEmpty())
                <p class="rt-suave">Esta área aún no tiene módulos activos.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            <th>Estado</th>
                            <th>Quiz (mejor nota)</th>
                            <th class="rt-centro">Intentos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fila['modulos'] as $modulo)
                            @php [$etiqueta, $color] = $estados[$modulo['estado']] ?? [$modulo['estado'], 'gray']; @endphp
                            <tr>
                                <td>{{ $modulo['modulo']->titulo }}</td>
                                <td><x-filament::badge :color="$color">{{ $etiqueta }}</x-filament::badge></td>
                                <td>
                                    @if (! $modulo['tieneQuiz'])
                                        <span class="rt-suave">Sin quiz</span>
                                    @elseif ($modulo['intentos'] === 0)
                                        <span class="rt-suave">No rendido</span>
                                    @else
                                        <strong>{{ $modulo['mejorNota'] }}%</strong>
                                        <x-filament::badge :color="$modulo['aprobado'] ? 'success' : 'warning'" size="sm">
                                            {{ $modulo['aprobado'] ? 'Aprobado' : 'No aprobado' }}
                                        </x-filament::badge>
                                    @endif
                                </td>
                                <td class="rt-centro">{{ $modulo['tieneQuiz'] ? $modulo['intentos'] : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @empty
        <p class="rt-suave">Este trabajador no tiene áreas activas asignadas.</p>
    @endforelse

    <section>
        <h3>Puntos a reforzar</h3>
        @if ($reforzar->isEmpty())
            <p class="rt-suave">No hay quizzes rendidos sin aprobar.</p>
        @else
            <ul>
                @foreach ($reforzar as $punto)
                    <li>
                        {{ $punto['area']->nombre }} · {{ $punto['modulo']->titulo }}:
                        mejor nota <strong>{{ $punto['mejorNota'] }}%</strong> (mínima {{ $punto['notaMinima'] }}%, {{ $punto['intentos'] }} {{ $punto['intentos'] === 1 ? 'intento' : 'intentos' }})
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($leccionesNuevas->isNotEmpty())
        <section>
            <h3>Contenido agregado después de aprobar</h3>
            <ul>
                @foreach ($leccionesNuevas as $nota)
                    <li>
                        {{ $nota['area']->nombre }} · {{ $nota['modulo']->titulo }}:
                        {{ $nota['nuevas'] === 1 ? '1 lección nueva' : $nota['nuevas'].' lecciones nuevas' }}
                        desde el {{ $nota['aprobadoEn']->format('d/m/Y') }}
                        ({{ $nota['pendientes'] === 0 ? 'ya las vio todas' : $nota['pendientes'].' sin ver' }}).
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
