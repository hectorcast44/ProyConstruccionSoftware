<?php
// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Usar las clases de PHPUnit
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

// Cargar la clase que vamos a probar
require_once __DIR__ . '/../php/src/TipoActividadService.php';

class TipoActividadServiceTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private TipoActividadService $tipoActividadService;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->tipoActividadService = new TipoActividadService($this->pdoMock);
    }

    public function testObtenerTiposActividad(): void
    {
        // ARRANGE
        $id_usuario = 1;
        $tiposEsperados = [
            ['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea'],
            ['id_tipo_actividad' => 2, 'nombre_tipo' => 'Examen']
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT id_tipo_actividad, nombre_tipo'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario]);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($tiposEsperados);

        // ACT
        $resultado = $this->tipoActividadService->obtenerTiposActividad($id_usuario);

        // ASSERT
        $this->assertEquals($tiposEsperados, $resultado);
    }

    public function testObtenerTipoActividadPorId(): void
    {
        // ARRANGE
        $id_tipo = 1;
        $id_usuario = 1;
        $tipoEsperado = ['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea'];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_tipo, $id_usuario]);

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn($tipoEsperado);

        // ACT
        $resultado = $this->tipoActividadService->obtenerTipoActividadPorId($id_tipo, $id_usuario);

        // ASSERT
        $this->assertEquals($tipoEsperado, $resultado);
    }

    public function testCrearTipoActividad(): void
    {
        // ARRANGE
        $datos = [
            'nombre_tipo' => 'Proyecto',
            'id_usuario' => 1
        ];

        // Mock para verificar que no existe el nombre (retorna 0)
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetch')->willReturn(['total' => 0]);

        // Mock para el INSERT
        $stmtInsertMock = $this->createMock(PDOStatement::class);
        $stmtInsertMock->method('execute');

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckMock, $stmtInsertMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('10');

        // ACT
        $id_creado = $this->tipoActividadService->crearTipoActividad($datos);

        // ASSERT
        $this->assertEquals(10, $id_creado);
    }

    public function testCrearTipoActividadFallaPorNombreDuplicado(): void
    {
        // ARRANGE
        $datos = [
            'nombre_tipo' => 'Tarea',
            'id_usuario' => 1
        ];

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetch')->willReturn(['total' => 1]); // Ya existe

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtCheckMock);

        // ASSERT
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ya existe un tipo de actividad con ese nombre.');

        // ACT
        $this->tipoActividadService->crearTipoActividad($datos);
    }

    public function testEditarTipoActividad(): void
    {
        // ARRANGE
        $id_tipo = 1;
        $datos = [
            'nombre_tipo' => 'Tarea Actualizada',
            'id_usuario' => 1
        ];

        // Mock para obtener tipo actual
        $stmtGetMock = $this->createMock(PDOStatement::class);
        $stmtGetMock->method('execute');
        $stmtGetMock->method('fetch')->willReturn(['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea']);

        // Mock para verificar nombre duplicado
        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetch')->willReturn(['total' => 0]);

        // Mock para UPDATE
        $stmtUpdateMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock->method('execute');
        $stmtUpdateMock->method('rowCount')->willReturn(1);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGetMock, $stmtCheckMock, $stmtUpdateMock);

        // ACT
        $resultado = $this->tipoActividadService->editarTipoActividad($id_tipo, $datos);

        // ASSERT
        $this->assertTrue($resultado);
    }

    public function testEliminarTipoActividadConExito(): void
    {
        // ARRANGE
        $id_tipo = 1;
        $id_usuario = 1;

        // Mock para obtener el tipo
        $stmtGetMock = $this->createMock(PDOStatement::class);
        $stmtGetMock->method('execute');
        $stmtGetMock->method('fetch')->willReturn(['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea']);

        // Mock para verificar actividades (0 resultados)
        $stmtActMock = $this->createMock(PDOStatement::class);
        $stmtActMock->method('execute');
        $stmtActMock->method('fetch')->willReturn(['total' => 0]);

        // Mock para verificar ponderaciones (0 resultados)
        $stmtPondMock = $this->createMock(PDOStatement::class);
        $stmtPondMock->method('execute');
        $stmtPondMock->method('fetch')->willReturn(['total' => 0]);

        // Mock para DELETE
        $stmtDelMock = $this->createMock(PDOStatement::class);
        $stmtDelMock->method('execute');
        $stmtDelMock->method('rowCount')->willReturn(1);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGetMock, $stmtActMock, $stmtPondMock, $stmtDelMock);

        // ACT
        $resultado = $this->tipoActividadService->eliminarTipoActividad($id_tipo, $id_usuario);

        // ASSERT
        $this->assertTrue($resultado);
    }

    public function testEliminarTipoActividadFallaSiTieneActividades(): void
    {
        // ARRANGE
        $id_tipo = 1;
        $id_usuario = 1;

        // Mock para obtener el tipo
        $stmtGetMock = $this->createMock(PDOStatement::class);
        $stmtGetMock->method('execute');
        $stmtGetMock->method('fetch')->willReturn(['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea']);

        // Mock para verificar actividades (tiene 5)
        $stmtActMock = $this->createMock(PDOStatement::class);
        $stmtActMock->method('execute');
        $stmtActMock->method('fetch')->willReturn(['total' => 5]);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGetMock, $stmtActMock);

        // ASSERT
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/está siendo usado en/');

        // ACT
        $this->tipoActividadService->eliminarTipoActividad($id_tipo, $id_usuario);
    }
}
