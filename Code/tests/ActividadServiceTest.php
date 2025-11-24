<?php
// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Usar las clases de PHPUnit
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

// Cargar la clase que vamos a probar
require_once __DIR__ . '/../php/src/ActividadService.php';

class ActividadServiceTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private ActividadService $actividadService;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class); // Mock genérico
        $this->actividadService = new ActividadService($this->pdoMock);
    }

    public function testCrearActividad(): void
    {
        // --- ARRANGE ---
        $datos = [
            'id_materia' => 1, 'id_tipo_actividad' => 2, 'id_usuario' => 1,
            'nombre_actividad' => 'Test ADA', 'fecha_entrega' => '2025-11-10',
            'estado' => 'pendiente', 'puntos_posibles' => 10.0, 'puntos_obtenidos' => null
        ];
        
        // --- CORRECCIÓN DE ERROR 1 ---
        $esperadoLastInsertId_int = 123; // El INT que el servicio debe devolver
        $esperadoLastInsertId_str = '123'; // El STRING que el mock de PDO debe devolver

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO ACTIVIDAD'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                $datos['id_materia'], $datos['id_tipo_actividad'], $datos['id_usuario'],
                $datos['nombre_actividad'], $datos['fecha_entrega'], $datos['estado'],
                $datos['puntos_posibles'], $datos['puntos_obtenidos']
            ]);
        
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($esperadoLastInsertId_str); // <<< FIX: Devolver un STRING

        // --- ACT ---
        $id_devuelto = $this->actividadService->crearActividad($datos);

        // --- ASSERT ---
        // Comparamos el INT 123 con el INT que devuelve el servicio
        $this->assertEquals($esperadoLastInsertId_int, $id_devuelto);
    }

    public function testEditarActividad(): void
    {
        // --- ARRANGE ---
        $id_actividad_a_editar = 45;
        $datos = [
            'id_materia' => 1, 'id_tipo_actividad' => 2, 'id_usuario' => 1,
            'nombre_actividad' => 'Test Editado', 'fecha_entrega' => '2025-11-11',
            'estado' => 'en proceso', 'puntos_posibles' => null, 'puntos_obtenidos' => null
        ];

        // Mock para el SELECT que obtiene el estado actual
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute')->with([$id_actividad_a_editar, $datos['id_usuario']]);
        $stmtCheckMock->method('fetch')->willReturn(['estado' => 'pendiente']);

        // Mock para el UPDATE
        $stmtUpdateMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock->expects($this->once())
            ->method('execute')
            ->with([
                $datos['id_materia'], $datos['id_tipo_actividad'], $datos['nombre_actividad'],
                $datos['fecha_entrega'], 'en proceso', $datos['puntos_posibles'],
                $datos['puntos_obtenidos'], $id_actividad_a_editar, $datos['id_usuario']
            ]);
        $stmtUpdateMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        // Configurar prepare para devolver los mocks consecutivamente
        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckMock, $stmtUpdateMock);

        // --- ACT ---
        $resultado = $this->actividadService->editarActividad($id_actividad_a_editar, $datos);

        // --- ASSERT ---
        $this->assertTrue($resultado);
    }

    /**
     * Prueba que eliminar una actividad NO CALIFICABLE funciona.
     */
    public function testEliminarActividadConExito(): void
    {
        // --- ARRANGE ---
        $id_actividad = 30;
        $id_usuario = 1;
        $id_materia_esperado = 5;

        // --- CORRECCIÓN DE FALLA 2 ---
        
        // 1. Mocks para los DOS statements diferentes
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtDeleteMock = $this->createMock(PDOStatement::class);

        // 2. Configurar el PDO mock para llamadas CONSECUTIVAS
        // Esto es más simple y no depende de string matching.
        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckMock,   // 1ra llamada (el SELECT)
                $stmtDeleteMock   // 2da llamada (el DELETE)
            );

        // 3. Configurar el mock del SELECT
        $stmtCheckMock->expects($this->once())
            ->method('execute')
            ->with([$id_actividad, $id_usuario]);
        $stmtCheckMock->method('fetch')->willReturn([
            'id_materia' => $id_materia_esperado,
            'puntos_posibles' => null 
        ]);

        // 4. Configurar el mock del DELETE
        $stmtDeleteMock->expects($this->once())
            ->method('execute')
            ->with([$id_actividad, $id_usuario]);

        // --- ACT ---
        $id_materia_devuelto = $this->actividadService->eliminarActividad($id_actividad, $id_usuario);

        // --- ASSERT ---
        $this->assertEquals($id_materia_esperado, $id_materia_devuelto);
    }

    /**
     * Prueba que eliminar una actividad CALIFICABLE falla (RF-003).
     */
    public function testEliminarActividadFallaSiEsCalificable(): void
    {
        // --- ARRANGE ---
        $id_actividad = 31;
        $id_usuario = 1;

        // 1. Configurar el CHECK (SELECT)
        // (Este test está bien porque SOLO llama a prepare UNA vez)
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT id_materia, puntos_posibles'))
            ->willReturn($stmtCheckMock);
        
        $stmtCheckMock->method('execute')->with([$id_actividad, $id_usuario]);
        $stmtCheckMock->method('fetch')->willReturn([
            'id_materia' => 5,
            'puntos_posibles' => 10.0 // ¡Es calificable!
        ]);

        // --- ASSERT (Afirmar la Excepción) ---
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/RF-003/');
        $this->expectExceptionCode(400);

        // --- ACT (Actuar) ---
        $this->actividadService->eliminarActividad($id_actividad, $id_usuario);
    }

    /**
     * Prueba validación de estado válido.
     */
    public function testValidarEstadoValido(): void
    {
        // ACT & ASSERT - Estados válidos
        $this->assertTrue($this->actividadService->validarEstado('pendiente'));
        $this->assertTrue($this->actividadService->validarEstado('en proceso'));
        $this->assertTrue($this->actividadService->validarEstado('completado'));
        $this->assertTrue($this->actividadService->validarEstado('PENDIENTE')); // Case insensitive
    }

    /**
     * Prueba validación de estado inválido.
     */
    public function testValidarEstadoInvalido(): void
    {
        // ASSERT
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido');

        // ACT
        $this->actividadService->validarEstado('estado_invalido');
    }

    /**
     * Prueba transición de estado válida.
     */
    public function testValidarTransicionEstadoValida(): void
    {
        // ACT & ASSERT - Todas las transiciones son válidas
        $this->assertTrue($this->actividadService->validarTransicionEstado('pendiente', 'en proceso'));
        $this->assertTrue($this->actividadService->validarTransicionEstado('pendiente', 'completado'));
        $this->assertTrue($this->actividadService->validarTransicionEstado('en proceso', 'completado'));
        $this->assertTrue($this->actividadService->validarTransicionEstado('completado', 'completado'));
        $this->assertTrue($this->actividadService->validarTransicionEstado('completado', 'pendiente'));
        $this->assertTrue($this->actividadService->validarTransicionEstado('completado', 'en proceso'));
    }

    /**
     * Prueba que la transición falla si el estado es inválido.
     */
    public function testValidarTransicionEstadoFallaConEstadoInvalido(): void
    {
        // ASSERT
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido');

        // ACT
        $this->actividadService->validarTransicionEstado('completado', 'estado_invalido');
    }

    /**
     * Prueba obtener actividades sin filtros.
     */
    public function testObtenerActividadesSinFiltros(): void
    {
        // ARRANGE
        $id_usuario = 1;
        $actividadesEsperadas = [
            [
                'id_actividad' => 1,
                'nombre_actividad' => 'Tarea 1',
                'estado' => 'pendiente',
                'nombre_tipo' => 'Tarea',
                'nombre_materia' => 'Matemáticas'
            ]
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT a.*, t.nombre_tipo, m.nombre_materia'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario]);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($actividadesEsperadas);

        // ACT
        $resultado = $this->actividadService->obtenerActividades($id_usuario);

        // ASSERT
        $this->assertEquals($actividadesEsperadas, $resultado);
    }

    /**
     * Prueba obtener actividades con filtros.
     */
    public function testObtenerActividadesConFiltros(): void
    {
        // ARRANGE
        $id_usuario = 1;
        $filtros = [
            'id_materia' => 2,
            'estado' => 'pendiente',
            'buscar' => 'Tarea'
        ];
        $actividadesEsperadas = [
            ['id_actividad' => 1, 'nombre_actividad' => 'Tarea 1', 'estado' => 'pendiente']
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->logicalAnd(
                $this->stringContains('WHERE a.id_usuario = ?'),
                $this->stringContains('AND a.id_materia = ?'),
                $this->stringContains('AND LOWER(a.estado) = LOWER(?)'),
                $this->stringContains('AND a.nombre_actividad LIKE ?')
            ))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario, 2, 'pendiente', '%Tarea%']);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($actividadesEsperadas);

        // ACT
        $resultado = $this->actividadService->obtenerActividades($id_usuario, $filtros);

        // ASSERT
        $this->assertEquals($actividadesEsperadas, $resultado);
    }

    /**
     * Prueba que crear actividad valida el estado.
     */
    public function testCrearActividadValidaEstado(): void
    {
        // ARRANGE
        $datos = [
            'id_materia' => 1,
            'id_tipo_actividad' => 2,
            'id_usuario' => 1,
            'nombre_actividad' => 'Test',
            'fecha_entrega' => '2025-11-10',
            'estado' => 'estado_invalido', // Estado inválido
            'puntos_posibles' => 10.0,
            'puntos_obtenidos' => null
        ];

        // ASSERT
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido');

        // ACT
        $this->actividadService->crearActividad($datos);
    }

    /**
     * Prueba que editar actividad valida transición de estado.
     */
    public function testEditarActividadValidaTransicionEstado(): void
    {
        // ARRANGE
        $id_actividad = 45;
        $datos = [
            'id_materia' => 1,
            'id_tipo_actividad' => 2,
            'id_usuario' => 1,
            'nombre_actividad' => 'Test Editado',
            'fecha_entrega' => '2025-11-11',
            'estado' => 'estado_invalido', // Estado inválido
            'puntos_posibles' => null,
            'puntos_obtenidos' => null
        ];

        // Mock para obtener estado actual (completado)
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute')->with([$id_actividad, $datos['id_usuario']]);
        $stmtCheckMock->method('fetch')->willReturn(['estado' => 'completado']);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheckMock);

        // ASSERT - Debe fallar por estado inválido, no por transición
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido');

        // ACT
        $this->actividadService->editarActividad($id_actividad, $datos);
    }
}