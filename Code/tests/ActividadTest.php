<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Models\Actividad;

class ActividadTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private Actividad $actividadModel;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->actividadModel = new Actividad($this->pdoMock);
    }

    public function testCrearActividad(): void
    {
        $datos = [
            'id_materia' => 1,
            'id_tipo_actividad' => 2,
            'id_usuario' => 1,
            'nombre_actividad' => 'Test ADA',
            'fecha_entrega' => '2025-11-10',
            'estado' => 'pendiente',
            'puntos_posibles' => 10.0,
            'puntos_obtenidos' => null
        ];

        $esperadoLastInsertId = '123';

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO ACTIVIDAD'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                $datos['id_materia'],
                $datos['id_tipo_actividad'],
                $datos['id_usuario'],
                $datos['nombre_actividad'],
                $datos['fecha_entrega'],
                $datos['estado'],
                $datos['puntos_posibles'],
                $datos['puntos_obtenidos']
            ]);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($esperadoLastInsertId);

        $id_devuelto = $this->actividadModel->crear($datos);

        $this->assertEquals($esperadoLastInsertId, $id_devuelto);
    }

    public function testActualizarActividad(): void
    {
        $id_actividad = 45;
        $datos = [
            'id_materia' => 1,
            'id_tipo_actividad' => 2,
            'id_usuario' => 1,
            'nombre_actividad' => 'Test Editado',
            'fecha_entrega' => '2025-11-11',
            'estado' => 'en proceso',
            'puntos_posibles' => null,
            'puntos_obtenidos' => null
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE ACTIVIDAD'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                $datos['id_materia'],
                $datos['id_tipo_actividad'],
                $datos['nombre_actividad'],
                $datos['fecha_entrega'],
                $datos['estado'],
                $datos['puntos_posibles'],
                $datos['puntos_obtenidos'],
                $id_actividad,
                $datos['id_usuario']
            ]);

        $this->stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $resultado = $this->actividadModel->actualizar($id_actividad, $datos);

        $this->assertTrue($resultado);
    }

    public function testEliminarActividadConExito(): void
    {
        $id_actividad = 30;
        $id_usuario = 1;
        $id_materia_esperado = 5;

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $stmtDeleteMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheckMock, $stmtDeleteMock);

        $stmtCheckMock->expects($this->once())
            ->method('execute')
            ->with([$id_actividad, $id_usuario]);
        $stmtCheckMock->method('fetch')->willReturn([
            'id_materia' => $id_materia_esperado,
            'puntos_posibles' => null
        ]);

        $stmtDeleteMock->expects($this->once())
            ->method('execute')
            ->with([$id_actividad, $id_usuario]);

        $id_materia_devuelto = $this->actividadModel->eliminar($id_actividad, $id_usuario);

        $this->assertEquals($id_materia_esperado, $id_materia_devuelto);
    }

    public function testEliminarActividadFallaSiEsCalificable(): void
    {
        $id_actividad = 31;
        $id_usuario = 1;

        $stmtCheckMock = $this->createMock(PDOStatement::class);
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT id_materia, puntos_posibles'))
            ->willReturn($stmtCheckMock);

        $stmtCheckMock->method('execute')->with([$id_actividad, $id_usuario]);
        $stmtCheckMock->method('fetch')->willReturn([
            'id_materia' => 5,
            'puntos_posibles' => 10.0
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/RF-003/');
        $this->expectExceptionCode(400);

        $this->actividadModel->eliminar($id_actividad, $id_usuario);
    }

    public function testObtenerPorMateria(): void
    {
        $id_materia = 1;
        $id_usuario = 1;
        $actividadesEsperadas = [
            [
                'id_actividad' => 1,
                'nombre_actividad' => 'Tarea 1',
                'estado' => 'pendiente',
                'nombre_tipo' => 'Tarea'
            ]
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT\s+a\.\*,\s+t\.nombre_tipo/s'))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([$id_materia, $id_usuario]);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($actividadesEsperadas);

        $resultado = $this->actividadModel->obtenerPorMateria($id_materia, $id_usuario);

        $this->assertEquals($actividadesEsperadas, $resultado);
    }
}
