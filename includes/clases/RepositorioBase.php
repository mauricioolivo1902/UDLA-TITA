<?php
// includes/clases/RepositorioBase.php
// Clase padre de todos los repositorios. La conexión se recibe por
// constructor (inversión de dependencias): ningún repositorio decide
// de dónde sale el PDO, por lo que puede probarse con otra base.

abstract class RepositorioBase
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
