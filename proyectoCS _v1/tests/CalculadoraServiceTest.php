<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;


require_once __DIR__ . '/../php/src/CalculadoraService.php';

class CalculadoraServiceTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtPondMock;
    private MockObject $stmtActMock;
    private MockObject $stmtGuardarMock;
    
    private CalculadoraService $calculadora;
    
    protected function setUp(): void
    {
        // 1. Creamos "Mocks" (objetos falsos) de PDO y PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtPondMock = $this->createMock(PDOStatement::class);
        $this->stmtActMock = $this->createMock(PDOStatement::class);
        $this->stmtGuardarMock = $this->createMock(PDOStatement::class);

        // 2. Le decimos al PDO falso qué debe devolver
        // Le diremos que devuelva los "statements" falsos en el orden
        // exacto en que el servicio los llama.
        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $this->stmtPondMock,  // 1ra llamada: (SELECT ... FROM PONDERACION)
                $this->stmtActMock,   // 2da llamada: (SELECT ... FROM ACTIVIDAD)
                $this->stmtGuardarMock // 3ra llamada: (UPDATE MATERIA ...)
            );
            
        // !!! ESTA LÍNEA FALTABA !!!
        // 3. Crear la instancia del servicio usando el PDO falso
        $this->calculadora = new CalculadoraService($this->pdoMock);
    }

    /**
     * Prueba el "camino feliz": un cálculo estándar completo.
     * Tareas (40%) y Exámenes (60%)
     */
    public function testCalculoCompletoYCorrecto(): void
    {
        // --- ARRANGE (Organizar los datos falsos) ---
        $id_materia = 1;
        $id_usuario = 1;

        // 1. Datos falsos para PONDERACIONES (40% Tareas, 60% Exámenes)
        // (Formato [id_tipo => porcentaje])
        $ponderacionesData = [
            1 => 40.00, // Tareas
            2 => 60.00  // Exámenes
        ];
        // Le decimos al mock de Ponderaciones que devuelva esto
        $this->stmtPondMock->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)->willReturn($ponderacionesData);

        // 2. Datos falsos para ACTIVIDADES
        $actividadesData = [
            // Tareas (Tipo 1)
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 10.00], // Tarea 1 (10/10)
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 8.00],  // Tarea 2 (8/10)
            // Exámenes (Tipo 2)
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => 90.00], // Examen 1 (90/100)
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => null]  // Examen 2 (Pendiente)
        ];
        $this->stmtActMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($actividadesData);

        // 3. ¡LA PRUEBA CLAVE!
        // Esperamos que el mock de "GUARDAR" (el UPDATE) sea llamado
        // exactamente una vez, y con estos parámetros específicos:
        $this->stmtGuardarMock->expects($this->once())
            ->method('execute')
            ->with([
                90.0,  // calificacion_actual: (18/20 * 40) + (90/100 * 60) = 36 + 54 = 90
                108.0, // puntos_ganados: 10 + 8 + 90
                12.0,  // puntos_perdidos: (10-10) + (10-8) + (100-90)
                100.0, // puntos_pendientes: 100 (del examen 2)
                $id_materia,
                $id_usuario
            ]);
        
        // Simular que la consulta afectó 1 fila
        $this->stmtGuardarMock->method('rowCount')->willReturn(1);

        // --- ACT (Ejecutar el método a probar) ---
        $resultado = $this->calculadora->recalcularMateria($id_materia, $id_usuario);

        // --- ASSERT (Verificar que el resultado es el esperado) ---
        $this->assertTrue($resultado); // Esperamos que devuelva true
    }

    /**
     * Prueba el Caso Borde (RF-049): ¿Qué pasa si un tipo (Exámenes)
     * tiene ponderación (60%) pero no tiene actividades calificadas?
     */
    public function testCalculoConTipoSinActividadesCalificadas(): void
    {
        // --- ARRANGE ---
        $id_materia = 2;
        $id_usuario = 1;

        // 1. Ponderaciones: Siguen siendo 40/60
        $ponderacionesData = [1 => 40.00, 2 => 60.00];
        $this->stmtPondMock->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)->willReturn($ponderacionesData);

        // 2. Actividades: Solo 1 Tarea y 1 Examen pendiente
        $actividadesData = [
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 8.00],  // Tarea 1 (8/10)
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => null] // Examen 1 (Pendiente)
        ];
        $this->stmtActMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($actividadesData);

        // 3. LA PRUEBA CLAVE
        $this->stmtGuardarMock->expects($this->once())
            ->method('execute')
            ->with([
                32.0,  // calificacion_actual: (8/10 * 40) + 0 = 32
                8.0,   // puntos_ganados: 8
                2.0,   // puntos_perdidos: (10-8)
                100.0, // puntos_pendientes: 100
                $id_materia,
                $id_usuario
            ]);
        
        $this->stmtGuardarMock->method('rowCount')->willReturn(1);

        // --- ACT ---
        $resultado = $this->calculadora->recalcularMateria($id_materia, $id_usuario);

        // --- ASSERT ---
        $this->assertTrue($resultado);
    }
}