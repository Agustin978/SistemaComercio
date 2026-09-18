<?php

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * Error de negocio con mensaje apto para mostrar al usuario. Los componentes Livewire lo capturan
 * y lo exponen como error de formulario; cualquier otra excepción se propaga.
 */
class DomainException extends RuntimeException {}
