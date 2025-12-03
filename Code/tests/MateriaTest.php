<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Models\Materia;

class MateriaTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private Materia $materiaModel;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->materiaModel = new Materia($this->pdoMock);
    }

    public function testCrearMateriaExitoso(): void
    {
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software';
        $calif_minima = 70;
        $esperadoLastInsertId = '10';

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtInsertMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckMock, $stmtInsertMock);

        $stmtCheckMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario, $nombre_materia]);
        $stmtCheckMock->method('fetchColumn')->willReturn(0);

        $stmtInsertMock->expects($this->once())
            ->method('execute')
            ->with([
                $id_usuario,
                $nombre_materia,
                (float) $calif_minima,
                0.0, // puntos_ganados
                0.0, // puntos_perdidos
                0.0, // puntos_pendientes
                0.0  // calificacion_actual
            ]);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($esperadoLastInsertId);

        $id_devuelto = $this->materiaModel->crear($id_usuario, $nombre_materia, $calif_minima);

        $this->assertEquals($esperadoLastInsertId, $id_devuelto);
    }

    public function testCrearMateriaFallaSiYaExiste(): void
    {
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software';
        $calif_minima = 70;

        $stmtCheckMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT\s+COUNT\(\*\)\s+FROM\s+MATERIA/i'))
            ->willReturn($stmtCheckMock);

        $stmtCheckMock->method('execute')->with([$id_usuario, $nombre_materia]);
        $stmtCheckMock->method('fetchColumn')->willReturn(1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ya existe una materia con ese nombre.');

        $this->materiaModel->crear($id_usuario, $nombre_materia, $calif_minima);
    }

    public function testActualizarMateriaExitoso(): void
    {
        $id_materia = 5;
        $id_usuario = 1;
        $nombre_materia = 'Construcción de Software II';
        $calif_minima = 80;

        $stmtCheckPropiedadMock = $this->createMock(PDOStatement::class);
        $stmtCheckNombreMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckPropiedadMock,
                $stmtCheckNombreMock,
                $stmtUpdateMock
            );

        $stmtCheckPropiedadMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckPropiedadMock->method('fetchColumn')->willReturn(1);

        $stmtCheckNombreMock->method('execute')->with([$id_usuario, $nombre_materia, $id_materia]);
        $stmtCheckNombreMock->method('fetchColumn')->willReturn(0);

        $stmtUpdateMock->expects($this->once())
            ->method('execute')
            ->with([$nombre_materia, $calif_minima, $id_materia, $id_usuario]);
        $stmtUpdateMock->method('rowCount')->willReturn(1);

        $resultado = $this->materiaModel->actualizar($id_materia, $id_usuario, $nombre_materia, $calif_minima);

        $this->assertTrue($resultado);
    }

    public function testEliminarMateriaExitoso(): void
    {
        $id_materia = 5;
        $id_usuario = 1;

        $stmtCheckPropiedadMock = $this->createMock(PDOStatement::class);
        $stmtCheckActividadesMock = $this->createMock(PDOStatement::class);
        $stmtCheckPonderacionesMock = $this->createMock(PDOStatement::class);
        $stmtDeleteMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtCheckPropiedadMock,
                $stmtCheckActividadesMock,
                $stmtCheckPonderacionesMock,
                $stmtDeleteMock
            );

        $stmtCheckPropiedadMock->method('execute')->with([$id_materia, $id_usuario]);
        $stmtCheckPropiedadMock->method('fetchColumn')->willReturn(1);

        $stmtCheckActividadesMock->method('execute')->with([$id_materia]);
        $stmtCheckActividadesMock->method('fetchColumn')->willReturn(0);

        $stmtCheckPonderacionesMock->method('execute')->with([$id_materia]);
        $stmtCheckPonderacionesMock->method('fetchColumn')->willReturn(0);

        $stmtDeleteMock->expects($this->once())
            ->method('execute')
            ->with([$id_materia, $id_usuario]);
        $stmtDeleteMock->method('rowCount')->willReturn(1);

        $resultado = $this->materiaModel->eliminar($id_materia, $id_usuario);

        $this->assertTrue($resultado);
    }

    public function testObtenerPorUsuarioExitoso(): void
    {
        $id_usuario = 1;
        $materiasEsperadas = [
            [
                'id_materia' => 1,
                'nombre_materia' => 'Construcción de Software'
            ]
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT\s+\*\s+FROM\s+MATERIA/i'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_usuario]);

        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($materiasEsperadas);

        $materias = $this->materiaModel->obtenerPorUsuario($id_usuario);

        $this->assertEquals($materiasEsperadas, $materias);
    }
}
