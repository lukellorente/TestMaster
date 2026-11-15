<?php

namespace App\Questions;

use App\Models\Question;

/**
 * Todo lo específico de un tipo de pregunta: cómo se valida y guarda desde el
 * editor, qué ve el alumno, cómo se corrige y cómo se muestra en resultados.
 */
interface QuestionType
{
    /** Mensaje de error para el autor, o null si los datos del editor son válidos. */
    public function validate(array $data): ?string;

    /** Columnas extra de `questions` que aporta este tipo (por ejemplo, la respuesta numérica). */
    public function attributes(array $data): array;

    /** Crea o sincroniza las filas auxiliares (opciones, huecos, elementos) de la pregunta. */
    public function store(Question $question, array $data): void;

    /** Datos que recibe el alumno para responder. Nunca debe revelar la solución. */
    public function forTaking(Question $question, bool $shuffle): array;

    /** Fracción de acierto entre 0 y 1. Solo se llama con respuestas no vacías. */
    public function grade(Question $question, mixed $answer): float;

    /** Texto legible de la respuesta del alumno. */
    public function answerLabel(Question $question, mixed $answer): string;

    /** Texto legible de la respuesta correcta. */
    public function correctLabel(Question $question): string;
}
