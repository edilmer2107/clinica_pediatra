<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violación de una regla de negocio. Se muestra al usuario como mensaje de error.
 */
class ReglaNegocioException extends RuntimeException {}
