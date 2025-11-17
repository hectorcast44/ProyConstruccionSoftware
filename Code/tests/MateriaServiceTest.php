<?php
// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Usar las clases de PHPUnit
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

// Cargar la clase que vamos a probar
require_once __DIR__ . '/../php/src/MateriaService.php';

class MateriaServiceTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private MateriaService $materiaService;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->materiaService = new MateriaService($this->pdoMock);
    }

    /**
     * Prueba que crear una materia funciona correctamente
     */
    public function testCrearMateriaExitoso(): void
    {
        // --- ARRANGE ---
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software';
        $calif_minima = 70;
        $esperadoLastInsertId_int = 10;
        $esperadoLastInsertId_str = '10';

        // Mock para verificar que no existe materia con el mismo nombre
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtInsertMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckMock,   // 1ra llamada (verificar existencia)
                $stmtInsertMock   // 2da llamada (insertar)
            );

        // Configurar el mock de verificación
        $stmtCheckMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario, $nombre_materia]);
        $stmtCheckMock->method('fetchColumn')->willReturn(0); // No existe

        // Configurar el mock de inserción
        $stmtInsertMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario, $nombre_materia, $calif_minima]);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($esperadoLastInsertId_str);

        // --- ACT ---
        $id_devuelto = $this->materiaService->crear($id_usuario, $nombre_materia, $calif_minima);

        // --- ASSERT ---
        $this->assertEquals($esperadoLastInsertId_int, $id_devuelto);
    }

    /**
     * Prueba que crear una materia falla si ya existe una con el mismo nombre
     */
    public function testCrearMateriaFallaSiYaExiste(): void
    {
        // --- ARRANGE ---
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software';
        $calif_minima = 70;

        $stmtCheckMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT COUNT(*) FROM MATERIA'))
            ->willReturn($stmtCheckMock);

        $stmtCheckMock->method('execute')->with([$id_usuario, $nombre_materia]);
        $stmtCheckMock->method('fetchColumn')->willReturn(1); // Ya existe

        // --- ASSERT (Afirmar la Excepción) ---
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ya existe una materia con ese nombre.');

        // --- ACT ---
        $this->materiaService->crear($id_usuario, $nombre_materia, $calif_minima);
    }

    /**
     * Prueba que crear una materia falla si la calificación mínima es inválida
     */
    public function testCrearMateriaFallaSiCalificacionMinimaInvalida(): void
    {
        // --- ARRANGE ---
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software';
        $calif_minima = 150; // Inválida

        $stmtCheckMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheckMock);

        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetchColumn')->willReturn(0); // No existe

        // --- ASSERT ---
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La calificación mínima debe estar entre 0 y 100.');

        // --- ACT ---
        $this->materiaService->crear($id_usuario, $nombre_materia, $calif_minima);
    }

    /**
     * Prueba que actualizar una materia funciona correctamente
     */
    public function testActualizarMateriaExitoso(): void
    {
        // --- ARRANGE ---
        $id_materia = 5;
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software II';
        $calif_minima = 80;

        $stmtCheckPropiedadMock = $this->createMock(PDOStatement::class);
        $stmtCheckNombreMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckPropiedadMock,  // 1ra: verificar propiedad
                $stmtCheckNombreMock,      // 2da: verificar nombre duplicado
                $stmtUpdateMock            // 3ra: actualizar
            );

        // Mock verificación de propiedad
        $stmtCheckPropiedadMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckPropiedadMock->method('fetchColumn')->willReturn(1); // Sí pertenece

        // Mock verificación de nombre duplicado
        $stmtCheckNombreMock->method('execute')->with([$id_usuario, $nombre_materia, $id_materia]);
        $stmtCheckNombreMock->method('fetchColumn')->willReturn(0); // No hay duplicado

        // Mock actualización
        $stmtUpdateMock->expects($this->once())
            ->method('execute')
            ->with([$nombre_materia, $calif_minima, $id_materia, $id_usuario]);
        $stmtUpdateMock->method('rowCount')->willReturn(1);

        // --- ACT ---
        $resultado = $this->materiaService->actualizar($id_materia, $id_usuario, $nombre_materia, $calif_minima);

        // --- ASSERT ---
        $this->assertTrue($resultado);
    }

    /**
     * Prueba que actualizar una materia falla si no pertenece al usuario
     */
    public function testActualizarMateriaFallaSiNoEsPropietario(): void
    {
        // --- ARRANGE ---
        $id_materia = 5;
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software II';
        $calif_minima = 80;

        $stmtCheckMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheckMock);

        $stmtCheckMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckMock->method('fetchColumn')->willReturn(0); // No pertenece

        // --- ASSERT ---
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No tiene permisos para modificar esta materia.');

        // --- ACT ---
        $this->materiaService->actualizar($id_materia, $id_usuario, $nombre_materia, $calif_minima);
    }

    /**
     * Prueba que actualizar estadísticas funciona correctamente
     */
    public function testActualizarEstadisticasExitoso(): void
    {
        // --- ARRANGE ---
        $id_materia = 5;
        $calificacion_actual = 85.50;
        $puntos_ganados = 100.00;
        $puntos_perdidos = 15.00;
        $puntos_pendientes = 50.00;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE MATERIA'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                $calificacion_actual,
                $puntos_ganados,
                $puntos_perdidos,
                $puntos_pendientes,
                $id_materia
            ]);

        $this->stmtMock->method('rowCount')->willReturn(1);

        // --- ACT ---
        $resultado = $this->materiaService->actualizarEstadisticas(
            $id_materia,
            $calificacion_actual,
            $puntos_ganados,
            $puntos_perdidos,
            $puntos_pendientes
        );

        // --- ASSERT ---
        $this->assertTrue($resultado);
    }

    /**
     * Prueba que eliminar una materia funciona correctamente
     */
    public function testEliminarMateriaExitoso(): void
    {
        // --- ARRANGE ---
        $id_materia = 5;
        $id_usuario = 1;

        $stmtCheckPropiedadMock = $this->createMock(PDOStatement::class);
        $stmtCheckActividadesMock = $this->createMock(PDOStatement::class);
        $stmtDeleteMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckPropiedadMock,    // 1ra: verificar propiedad
                $stmtCheckActividadesMock,  // 2da: verificar actividades
                $stmtDeleteMock             // 3ra: eliminar
            );

        // Mock verificación de propiedad
        $stmtCheckPropiedadMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckPropiedadMock->method('fetchColumn')->willReturn(1); // Sí pertenece

        // Mock verificación de actividades
        $stmtCheckActividadesMock->method('execute')->with([$id_materia]);
        $stmtCheckActividadesMock->method('fetchColumn')->willReturn(0); // No tiene actividades

        // Mock eliminación
        $stmtDeleteMock->expects($this->once())
            ->method('execute')
            ->with([$id_materia, $id_usuario]);
        $stmtDeleteMock->method('rowCount')->willReturn(1);

        // --- ACT ---
        $resultado = $this->materiaService->eliminar($id_materia, $id_usuario);

        // --- ASSERT ---
        $this->assertTrue($resultado);
    }

    /**
     * Prueba que eliminar una materia falla si tiene actividades asociadas
     */
    public function testEliminarMateriaFallaSiTieneActividades(): void
    {
        // --- ARRANGE ---
        $id_materia = 5;
        $id_usuario = 1;

        $stmtCheckPropiedadMock = $this->createMock(PDOStatement::class);
        $stmtCheckActividadesMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckPropiedadMock,
                $stmtCheckActividadesMock
            );

        // Mock verificación de propiedad
        $stmtCheckPropiedadMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckPropiedadMock->method('fetchColumn')->willReturn(1); // Sí pertenece

        // Mock verificación de actividades
        $stmtCheckActividadesMock->method('execute')->with([$id_materia]);
        $stmtCheckActividadesMock->method('fetchColumn')->willReturn(3); // Tiene 3 actividades

        // --- ASSERT ---
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se puede eliminar una materia que tiene actividades asociadas.');

        // --- ACT ---
        $this->materiaService->eliminar($id_materia, $id_usuario);
    }

    /**
     * Prueba que obtener materias por usuario funciona correctamente
     */
    public function testObtenerPorUsuarioExitoso(): void
    {
        // --- ARRANGE ---
        $id_usuario = 1;
        $materiasEsperadas = [
            [
                'id_materia' => 1,
                'id_usuario' => 1,
                'nombre_materia' => 'Construcción de Software',
                'calif_minima' => 70,
                'calificacion_actual' => 85.50,
                'puntos_ganados' => 100.00,
                'puntos_perdidos' => 15.00,
                'puntos_pendientes' => 50.00
            ],
            [
                'id_materia' => 2,
                'id_usuario' => 1,
                'nombre_materia' => 'Matemáticas',
                'calif_minima' => 70,
                'calificacion_actual' => 90.00,
                'puntos_ganados' => 120.00,
                'puntos_perdidos' => 10.00,
                'puntos_pendientes' => 20.00
            ]
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario]);

        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($materiasEsperadas);

        // --- ACT ---
        $materias = $this->materiaService->obtenerPorUsuario($id_usuario);

        // --- ASSERT ---
        $this->assertCount(2, $materias);
        $this->assertEquals($materiasEsperadas, $materias);
        $this->assertEquals('Construcción de Software', $materias[0]['nombre_materia']);
        $this->assertEquals(85.50, $materias[0]['calificacion_actual']);
    }

    /**
     * Prueba que obtener una materia por ID funciona correctamente
     */
    public function testObtenerPorIdExitoso(): void
    {
        // --- ARRANGE ---
        $id_materia = 1;
        $id_usuario = 1;
        $materiaEsperada = [
            'id_materia' => 1,
            'id_usuario' => 1,
            'nombre_materia' => 'Construcción de Software',
            'calif_minima' => 70,
            'calificacion_actual' => 85.50,
            'puntos_ganados' => 100.00,
            'puntos_perdidos' => 15.00,
            'puntos_pendientes' => 50.00
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_materia, $id_usuario]);

        $this->stmtMock->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($materiaEsperada);

        // --- ACT ---
        $materia = $this->materiaService->obtenerPorId($id_materia, $id_usuario);

        // --- ASSERT ---
        $this->assertIsArray($materia);
        $this->assertEquals($materiaEsperada, $materia);
        $this->assertEquals('Construcción de Software', $materia['nombre_materia']);
        $this->assertEquals(85.50, $materia['calificacion_actual']);
    }

    /**
     * Prueba que obtener una materia por ID retorna false si no existe
     */
    public function testObtenerPorIdRetornaFalseSiNoExiste(): void
    {
        // --- ARRANGE ---
        $id_materia = 999;
        $id_usuario = 1;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_materia, $id_usuario]);

        $this->stmtMock->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        // --- ACT ---
        $materia = $this->materiaService->obtenerPorId($id_materia, $id_usuario);

        // --- ASSERT ---
        $this->assertFalse($materia);
    }
}
