<?php

declare(strict_types=1);

class LoginController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        $this->view(
            'auth/login',
            [
                'tituloPagina' => 'Ingreso al sistema',
                'error' => flash('error'),
            ]
        );
    }

    public function autenticar(): void
    {
        if (!$this->isPost()) {
            redirect('/login');
        }

        exigirCsrf();

        $login = trim((string) $this->post('login', ''));
        $password = (string) $this->post('password', '');

        if ($login === '' || $password === '') {
            flash(
                'error',
                'Por favor ingrese su usuario y contraseña.'
            );

            redirect('/login');
        }

        $usuarioModel = new UsuarioModel();

        $usuario = $usuarioModel->buscarParaLogin($login);

        if (!$usuario) {
            flash(
                'error',
                'Las credenciales ingresadas no son correctas.'
            );

            redirect('/login');
        }

        if (
            !isset($usuario['password'])
            || !password_verify(
                $password,
                (string) $usuario['password']
            )
        ) {
            flash(
                'error',
                'Las credenciales ingresadas no son correctas.'
            );

            redirect('/login');
        }

        $roles = $usuarioModel->obtenerRoles(
            (int) $usuario['id']
        );

        $permisos = $usuarioModel->obtenerPermisos(
            (int) $usuario['id']
        );

        $datosUsuario = [
            'id' => (int) $usuario['id'],
            'id_persona' => (int) $usuario['id_persona'],
            'username' => $usuario['username'],
            'nombres' => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'documento' => $usuario['documento'],
            'correo' => $usuario['usuario_correo']
                ?: $usuario['persona_correo'],
            'telefono' => $usuario['telefono'],
        ];

        Auth::login(
            (int) $usuario['id'],
            $datosUsuario
        );

        Auth::setRoles($roles);
        Auth::setPermissions($permisos);

        $usuarioModel->actualizarUltimoAcceso(
            (int) $usuario['id']
        );

        redirect('/dashboard');
    }

    public function salir(): void
    {
        Auth::logout();

        redirect('/login');
    }
}