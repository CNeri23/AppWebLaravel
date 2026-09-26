<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión</title>
</head>

<body>
    <h1>Iniciar sesión</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="email">Correo electrónico</label>

            <input type="email" id="email" name="email" required>
        </div>

        <div>
            <label for="password">Contraseña</label>

            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">
            Iniciar sesión
        </button>
    </form>
</body>

</html>