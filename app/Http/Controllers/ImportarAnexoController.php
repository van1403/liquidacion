<?php

namespace App\Http\Controllers;

use App\Imports\AnexoAImport;
use App\Models\Anexo;
use App\Services\Anexo\AnexoExcelMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportarAnexoController extends Controller
{
    public function create(Request $request): View
    {
        return view('importaciones.create', [
            'anexos' => Anexo::query()->withCount('registros')->orderByDesc('id')->get(),
            'anexoSeleccionado' => $request->integer('anexo_id') ?: null,
        ]);
    }

    public function store(Request $request, AnexoExcelMapper $mapper): RedirectResponse
    {
        $data = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls'],
            'anexo_id' => ['nullable', 'integer', 'exists:anexos,id'],
            'modo' => ['nullable', 'in:agregar,reemplazar'],
        ], [], ['anexo_id' => 'anexo', 'archivo' => 'archivo Excel']);

        if (! App::bound('excel')) {
            return back()->with('error', 'La importación Excel no está disponible porque falta instalar maatwebsite/excel.');
        }

        try {
            $import = new AnexoAImport();
            Excel::import($import, $data['archivo']);

            $mapped = $mapper->map($import->sheets());

            if (empty($mapped['registros'])) {
                return back()->withInput()->with('error', 'No se encontraron filas de detalle después de la cabecera ITEM.');
            }

            [$anexo, $total, $esNuevo] = DB::transaction(function () use ($mapped, $data) {
                $cabecera = $mapped['cabecera'];
                $base = 0;
                $renumerar = false;

                if (! empty($data['anexo_id'])) {
                    $anexo = Anexo::query()->findOrFail($data['anexo_id']);
                    $esNuevo = false;

                    // Completar solo los datos de cabecera que estén vacíos en el anexo elegido
                    $completar = [
                        'periodo_ejecucion_contractual' => $cabecera['periodo_ejecucion_contractual'] ?? null,
                        'fecha_contractual_ingreso_entregable' => $cabecera['fecha_ingreso_entregable'] ?? null,
                        'responsable' => $cabecera['responsable'] ?? null,
                    ];
                    foreach ($completar as $campo => $valor) {
                        if (blank($anexo->{$campo}) && filled($valor)) {
                            $anexo->{$campo} = $valor;
                        }
                    }
                    $anexo->save();

                    if (($data['modo'] ?? 'agregar') === 'reemplazar') {
                        $anexo->registros()->delete();
                    } else {
                        $base = (int) $anexo->registros()->max('item');
                        $renumerar = $base > 0;
                    }
                } else {
                    $esNuevo = true;
                    $anexo = Anexo::query()->create([
                        'titulo' => $cabecera['titulo'] ?? 'ANEXO IMPORTADO '.now()->format('Ymd_His'),
                        'periodo_ejecucion_contractual' => $cabecera['periodo_ejecucion_contractual'] ?? null,
                        'fecha_contractual_ingreso_entregable' => $cabecera['fecha_ingreso_entregable'] ?? null,
                        'responsable' => $cabecera['responsable'] ?? null,
                        'estado' => 'activo',
                    ]);
                }

                $total = 0;
                foreach (array_values($mapped['registros']) as $index => $registro) {
                    $anexo->registros()->create([
                        ...$registro,
                        'item' => $renumerar ? $base + $index + 1 : ($registro['item'] ?: ($index + 1)),
                        'visitado' => (bool) ($registro['visitado'] ?? false),
                        'efectividad' => (bool) ($registro['efectividad'] ?? false),
                    ]);
                    $total++;
                }

                return [$anexo, $total, $esNuevo];
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'No se pudo procesar el archivo. Revisa la estructura del Excel e inténtalo nuevamente.');
        }

        $mensaje = $esNuevo
            ? "Se creó el anexo «{$anexo->titulo}» con {$total} filas importadas."
            : "Se importaron {$total} filas al anexo «{$anexo->titulo}».";

        return redirect()->route('anexos.show', $anexo)->with('success', $mensaje.' Revísalas antes de continuar.');
    }
}
