<?php
// includes/clases/CredencialesEstudiante.php
// Genera las credenciales institucionales del estudiante, que son las
// mismas para el sistema NEO y para Moodle. Regla definida por la
// institución:
//   Usuario:    primerapellido + primernombre (minúsculas, sin tildes)
//   Contraseña: InicialApellido(mayús) + inicialnombre(minús) + cédula + '*'
//   Ejemplo:    ALVARADO RODRIGUEZ KARLA MICAELA / CI 2350159147
//               -> usuario: alvaradokarla   contraseña: Ak2350159147*
// Si el usuario ya existe se añade un número: alvaradokarla2, alvaradokarla3...

class CredencialesEstudiante
{
    public function __construct(private UsuarioRepository $usuarios)
    {
    }

    // Devuelve ['usuario' => ..., 'contrasena' => ...] garantizando unicidad
    public function generar(string $apellidos, string $nombres, string $cedula): array
    {
        $base = $this->normalizar($this->primeraPalabra($apellidos))
              . $this->normalizar($this->primeraPalabra($nombres));

        return [
            'usuario'    => $this->usuarioUnico($base),
            'contrasena' => $this->contrasena($apellidos, $nombres, $cedula),
        ];
    }

    private function contrasena(string $apellidos, string $nombres, string $cedula): string
    {
        $inicialApellido = strtoupper($this->normalizar(mb_substr($this->primeraPalabra($apellidos), 0, 1, 'UTF-8')));
        $inicialNombre   = $this->normalizar(mb_substr($this->primeraPalabra($nombres), 0, 1, 'UTF-8'));

        // 'X'/'x' evitan credenciales vacías ante nombres atípicos
        return ($inicialApellido !== '' ? $inicialApellido : 'X')
             . ($inicialNombre !== '' ? $inicialNombre : 'x')
             . $cedula . '*';
    }

    private function usuarioUnico(string $base): string
    {
        if ($base === '') {
            $base = 'estudiante';
        }

        $candidato = $base;
        $sufijo = 2;
        while ($this->usuarios->usernameExiste($candidato)) {
            $candidato = $base . $sufijo;
            $sufijo++;
        }
        return $candidato;
    }

    // Quita tildes/ñ y deja solo letras y números en minúsculas
    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
        return preg_replace('/[^a-z0-9]/', '', $texto);
    }

    private function primeraPalabra(string $texto): string
    {
        $partes = preg_split('/\s+/', trim($texto));
        return $partes[0] ?? '';
    }
}
