<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Models\TipoActividad;

class TipoActividadTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private TipoActividad $tipoActividadModel;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->tipoActividadModel = new TipoActividad($this->pdoMock);
    }

    public function testObtenerTodos(): void
    {
        $id_usuario = 1;
        $tiposEsperados = [
            ['id_tipo_actividad' => 1, 'nombre_tipo' => 'Tarea'],
            ['id_tipo_actividad' => 2, 'nombre_tipo' => 'Examen']
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT *, (id_usuario = ?) as es_propio'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario, $id_usuario]);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($tiposEsperados);

        $resultado = $this->tipoActividadModel->obtenerTodos($id_usuario);

        $this->assertEquals($tiposEsperados, $resultado);
    }

    public function testCrearTipoActividad(): void
    {
        $nombre = 'Proyecto';
        $id_usuario = 1;

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetchColumn')->willReturn(0);

        $stmtInsertMock = $this->createMock(PDOStatement::class);
        $stmtInsertMock->method('execute');

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckMock, $stmtInsertMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('10');

        $id_creado = $this->tipoActividadModel->crear($nombre, $id_usuario);

        $this->assertEquals('10', $id_creado);
    }

    public function testEditarTipoActividad(): void
    {
        $id_tipo = 1;
        $nombre = 'Tarea Actualizada';
        $id_usuario = 1;

        $stmtGetMock = $this->createMock(PDOStatement::class);
        $stmtGetMock->method('execute');
        $stmtGetMock->method('fetchColumn')->willReturn($id_usuario); // Es propietario

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtCheckMock->method('execute');
        $stmtCheckMock->method('fetchColumn')->willReturn(0); // No duplicado

        $stmtUpdateMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock->method('execute');
        $stmtUpdateMock->method('rowCount')->willReturn(1);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGetMock, $stmtCheckMock, $stmtUpdateMock);

        $resultado = $this->tipoActividadModel->actualizar($id_tipo, $nombre, $id_usuario);

        $this->assertTrue($resultado);
    }

    public function testEliminarTipoActividadConExito(): void
    {
        $id_tipo = 1;
        $id_usuario = 1;

        $stmtGetMock = $this->createMock(PDOStatement::class);
        $stmtGetMock->method('execute');
        $stmtGetMock->method('fetchColumn')->willReturn($id_usuario);

        $stmtActMock = $this->createMock(PDOStatement::class);
        $stmtActMock->method('execute');
        $stmtActMock->method('fetchColumn')->willReturn(0);

        $stmtPondMock = $this->createMock(PDOStatement::class);
        $stmtPondMock->method('execute');
        $stmtPondMock->method('fetchColumn')->willReturn(0);

        $stmtDelMock = $this->createMock(PDOStatement::class);
        $stmtDelMock->method('execute');

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGetMock, $stmtActMock, $stmtPondMock, $stmtDelMock);

        $resultado = $this->tipoActividadModel->eliminar($id_tipo, $id_usuario);

        $this->assertTrue($resultado);
    }
}
