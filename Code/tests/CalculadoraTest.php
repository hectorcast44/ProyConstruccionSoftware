<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Models\Calculadora;

class CalculadoraTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtPondMock;
    private MockObject $stmtActMock;
    private MockObject $stmtGuardarMock;

    private Calculadora $calculadora;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtPondMock = $this->createMock(PDOStatement::class);
        $this->stmtActMock = $this->createMock(PDOStatement::class);
        $this->stmtGuardarMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $this->stmtPondMock,
                $this->stmtActMock,
                $this->stmtGuardarMock
            );

        $this->calculadora = new Calculadora($this->pdoMock);
    }

    public function testCalculoCompletoYCorrecto(): void
    {
        $id_materia = 1;
        $id_usuario = 1;

        $ponderacionesData = [
            1 => 40.00,
            2 => 60.00
        ];
        $this->stmtPondMock->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)->willReturn($ponderacionesData);

        $actividadesData = [
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 10.00],
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 8.00],
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => 90.00],
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => null]
        ];
        $this->stmtActMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($actividadesData);

        $this->stmtGuardarMock->expects($this->once())
            ->method('execute')
            ->with([
                90.0,
                45.0,
                5.0,
                50.0,
                $id_materia,
                $id_usuario
            ]);

        $this->stmtGuardarMock->method('rowCount')->willReturn(1);

        $resultado = $this->calculadora->recalcularMateria($id_materia, $id_usuario);

        $this->assertTrue($resultado);
    }

    public function testCalculoConTipoSinActividadesCalificadas(): void
    {
        $id_materia = 2;
        $id_usuario = 1;

        $ponderacionesData = [1 => 40.00, 2 => 60.00];
        $this->stmtPondMock->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)->willReturn($ponderacionesData);

        $actividadesData = [
            ['id_tipo_actividad' => 1, 'puntos_posibles' => 10.00, 'puntos_obtenidos' => 8.00],
            ['id_tipo_actividad' => 2, 'puntos_posibles' => 100.00, 'puntos_obtenidos' => null]
        ];
        $this->stmtActMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($actividadesData);

        $this->stmtGuardarMock->expects($this->once())
            ->method('execute')
            ->with([
                32.0,
                8.0,
                2.0,
                90.0,
                $id_materia,
                $id_usuario
            ]);

        $this->stmtGuardarMock->method('rowCount')->willReturn(1);

        $resultado = $this->calculadora->recalcularMateria($id_materia, $id_usuario);

        $this->assertTrue($resultado);
    }
}
