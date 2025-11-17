<?php

class CalculadoraService {
    private $pdo;

    /**
     * @param PDO $pdo - La conexión a la base de datos
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Tarea Principal: Recalcula todas las métricas de una materia
     * * @param int $id_materia - El ID de la materia a recalcular
     * @param int $id_usuario - El ID del usuario (para seguridad de datos)
     * @return bool - true si el recálculo fue exitoso
     * @throws PDOException si falla la base de datos
     */
    public function recalcularMateria(int $id_materia, int $id_usuario): bool {
        
        try {
            // --- PASO 1: Obtener Ponderaciones (RF-038) ---
            // Trae los porcentajes configurados para esta materia
            $stmt_pond = $this->pdo->prepare(
                "SELECT id_tipo_actividad, porcentaje 
                 FROM PONDERACION 
                 WHERE id_materia = ?"
            );
            $stmt_pond->execute([$id_materia]);
            
            // Convertir a un mapa [id_tipo => porcentaje] para fácil acceso
            $ponderaciones = $stmt_pond->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // --- PASO 2: Obtener Actividades "Calificables" ---
            //una actividad es "calificable si el usuario le asignó puntos_posibles).
            $stmt_act = $this->pdo->prepare(
                "SELECT id_tipo_actividad, puntos_posibles, puntos_obtenidos 
                 FROM ACTIVIDAD 
                 WHERE id_materia = ? 
                   AND id_usuario = ? 
                   AND puntos_posibles IS NOT NULL" // Clave: así identificamos "calificables"
            );
            $stmt_act->execute([$id_materia, $id_usuario]);
            $actividades = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

            // --- PASO 3: Calcular Métricas (RF-045, 046, 047) ---
            
            $puntos_ganados = 0;
            $puntos_perdidos = 0;
            $puntos_pendientes = 0;
            
            // Array para guardar totales por tipo (para el Paso 4)
            // Formato: $data_por_tipo[id_tipo]['suma_obtenidos']
            $data_por_tipo = [];

            foreach ($actividades as $act) {
                $tipo_id = $act['id_tipo_actividad'];

                // Inicializar el array para este tipo si no existe
                if (!isset($data_por_tipo[$tipo_id])) {
                    $data_por_tipo[$tipo_id] = ['suma_obtenidos' => 0, 'suma_posibles' => 0];
                }
                
                // RF-042: Una actividad está "calificada" si puntos_obtenidos no es NULL
                if ($act['puntos_obtenidos'] !== null) {
                    $puntos_ganados += (float)$act['puntos_obtenidos'];
                    $puntos_perdidos += ((float)$act['puntos_posibles'] - (float)$act['puntos_obtenidos']);
                    
                    // Acumular para el cálculo de la calificación final
                    $data_por_tipo[$tipo_id]['suma_obtenidos'] += (float)$act['puntos_obtenidos'];
                    $data_por_tipo[$tipo_id]['suma_posibles'] += (float)$act['puntos_posibles'];
                    
                } else {
                    // RF-047: Actividad pendiente (calificable pero sin nota)
                    $puntos_pendientes += (float)$act['puntos_posibles'];
                }
            }
            
            // --- PASO 4: Calcular Calificación Final ---
            $calificacion_final = 0;

            foreach ($ponderaciones as $tipo_id => $porcentaje) {
                //Si hay actividades calificadas de este tipo...
                if (isset($data_por_tipo[$tipo_id]) && $data_por_tipo[$tipo_id]['suma_posibles'] > 0) {
                    
                    $suma_obtenidos = $data_por_tipo[$tipo_id]['suma_obtenidos'];
                    $suma_posibles = $data_por_tipo[$tipo_id]['suma_posibles'];
                    
                    // (suma puntos / total puntos) * ponderación
                    $contribucion = ($suma_obtenidos / $suma_posibles) * (float)$porcentaje;
                    
                    // Sumar a la calificación total
                    $calificacion_final += $contribucion;
                }
                // else: Si no hay actividades de este tipo, su contribución es 0 (no se suma nada)
            }

            // --- PASO 5: Guardar Resultados en la BD (en tabla MATERIA) ---
            $stmt_guardar = $this->pdo->prepare(
                "UPDATE MATERIA 
                 SET calificacion_actual = ?, 
                     puntos_ganados = ?, 
                     puntos_perdidos = ?, 
                     puntos_pendientes = ? 
                 WHERE id_materia = ? 
                   AND id_usuario = ?" // Seguridad: solo actualizar si es del usuario
            );
            
            $stmt_guardar->execute([
                $calificacion_final,
                $puntos_ganados,
                $puntos_perdidos,
                $puntos_pendientes,
                $id_materia,
                $id_usuario
            ]);

            return $stmt_guardar->rowCount() > 0; // Retorna true si la fila se actualizó

        } catch (PDOException $e) {
            error_log("Error en CalculadoraService: " . $e->getMessage());
            // Relanzar la excepción para que la API la maneje
            throw $e; 
        }
    }
}
?>