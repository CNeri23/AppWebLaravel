<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Columnas nuevas (username todavía nullable para poder migrar los datos)
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('id');
            $table->boolean('activo')->default(true)->after('password');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->nullable()
                ->after('direccion_id')
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
        });

        // 2) Migrar los usuarios existentes: username + persona con el nombre y correo
        $usados = [];

        foreach (DB::table('users')->orderBy('id')->get() as $usuario) {
            $base = Str::of(Str::before((string) $usuario->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9._-]/', '')
                ->limit(40, '')
                ->toString();

            if ($base === '') {
                $base = 'usuario' . $usuario->id;
            }

            $username = $base;
            $n = 1;

            while (in_array($username, $usados, true)) {
                $username = $base . $n++;
            }

            $usados[] = $username;

            DB::table('users')->where('id', $usuario->id)->update([
                'username' => $username,
            ]);

            // ¿Ya existe una persona (p. ej. miembro) con ese correo y sin usuario?
            $persona = DB::table('personas')
                ->where('email', $usuario->email)
                ->whereNull('usuario_id')
                ->orderBy('id')
                ->first();

            if ($persona) {
                DB::table('personas')->where('id', $persona->id)->update([
                    'usuario_id' => $usuario->id,
                ]);

                continue;
            }

            [$nombre, $paterno, $materno] = $this->separarNombre((string) $usuario->name);

            $correoLibre = !DB::table('personas')->where('email', $usuario->email)->exists();

            DB::table('personas')->insert([
                'nombre' => $nombre,
                'apellido_paterno' => $paterno,
                'apellido_materno' => $materno,
                'email' => $correoLibre ? $usuario->email : null,
                'usuario_id' => $usuario->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) username obligatorio y único
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->unique()->change();
        });

        // 4) Quitar lo que ahora vive en persona
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('email')->nullable()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        foreach (DB::table('users')->get() as $usuario) {
            $persona = DB::table('personas')->where('usuario_id', $usuario->id)->first();

            $nombre = $persona
                ? trim($persona->nombre . ' ' . $persona->apellido_paterno . ' ' . ($persona->apellido_materno ?? ''))
                : $usuario->username;

            DB::table('users')->where('id', $usuario->id)->update([
                'name' => $nombre,
                'email' => $persona->email ?? ($usuario->username . '@example.invalid'),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->unique()->change();
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropUnique(['usuario_id']);
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('usuario_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'activo']);
        });
    }

    /**
     * "Cesar Neri Sánchez" => [Cesar, Neri, Sánchez]
     *
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function separarNombre(string $completo): array
    {
        $partes = preg_split('/\s+/', trim($completo), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return match (true) {
            count($partes) === 0 => ['Sin nombre', '-', null],
            count($partes) === 1 => [$partes[0], '-', null],
            count($partes) === 2 => [$partes[0], $partes[1], null],
            default => [
                implode(' ', array_slice($partes, 0, -2)),
                $partes[count($partes) - 2],
                $partes[count($partes) - 1],
            ],
        };
    }
};
