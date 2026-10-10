<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\PermissionService;
use App\Services\SystemSettings;
use App\Services\UserPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    private const CLAVES_NEGOCIO = [
        'business_name',
        'business_rfc',
        'business_address',
        'business_phone',
        'business_email',
        'business_website',
        'business_schedule',
    ];

    private const CLAVES_SEGURIDAD = [
        'session_timeout',
        'password_min_length',
        'password_complexity',
        'max_login_attempts',
        'lockout_minutes',
        'max_login_attempts_ip',
        'login_ip_window_seconds',
        'login_ip_lockout_minutes',
        'registration_enabled',
    ];

    private const PERMISOS_CONFIGURACION = [
        'system_name' => 'configuracion.nombre',
        'logo' => 'configuracion.logotipo',
        'accent_color' => 'configuracion.apariencia',
        'currency' => 'configuracion.moneda',
        'timezone' => 'configuracion.zona_horaria',
        'date_format' => 'configuracion.formato_fecha',
        'time_format' => 'configuracion.formato_hora',
        'business_name' => 'configuracion.negocio_nombre',
        'business_rfc' => 'configuracion.negocio_rfc',
        'business_address' => 'configuracion.negocio_direccion',
        'business_phone' => 'configuracion.negocio_telefono',
        'business_email' => 'configuracion.negocio_correo',
        'business_website' => 'configuracion.negocio_sitio_web',
        'business_schedule' => 'configuracion.negocio_horario',
        'session_timeout' => 'configuracion.tiempo_sesion',
        'password_min_length' => 'configuracion.longitud_password',
        'password_complexity' => 'configuracion.complejidad_password',
        'max_login_attempts' => 'configuracion.intentos_login',
        'lockout_minutes' => 'configuracion.bloqueo_usuario',
        'max_login_attempts_ip' => 'configuracion.intentos_ip',
        'login_ip_window_seconds' => 'configuracion.ventana_ip',
        'login_ip_lockout_minutes' => 'configuracion.bloqueo_ip',
        'registration_enabled' => 'configuracion.registro_publico',
    ];

    private const CLAVES_BOOLEANAS = [
        'password_complexity',
        'registration_enabled',
    ];

    public function index(SystemSettings $settings): View
    {
        return view('configuracion.index', [
            'settings' => $settings->all(),
        ]);
    }

    public function update(
        Request $request,
        SystemSettings $settings
    ): JsonResponse {
        $this->autorizarCambios($request);

        $datos = $request->validate([
            // General
            'system_name' => ['sometimes', 'required', 'string', 'max:100'],

            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],

            // Apariencia
            'accent_color' => [
                'sometimes',
                'required',
                Rule::in([
                    'blue',
                    'green',
                    'orange',
                    'purple',
                    'red',
                    'rosa',
                    'indigo',
                    'petroleo',
                    'petroleo',
                ]),
            ],

            // Regional
            'currency' => ['sometimes', 'required', Rule::in(['MXN', 'USD', 'EUR'])],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'date_format' => ['sometimes', 'required', Rule::in(['d/m/Y', 'm/d/Y', 'Y-m-d'])],
            'time_format' => ['sometimes', 'required', Rule::in(['H:i', 'h:i A'])],

            // Datos del negocio
            'business_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'business_rfc' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/iu',
            ],
            'business_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_phone' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[0-9+()\-\s]{7,30}$/',
            ],
            'business_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'business_website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'business_schedule' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Seguridad
            'session_timeout' => [
                'sometimes',
                'required',
                'integer',
                'between:0,1440',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ((int) $value > 0 && (int) $value < 5) {
                        $fail('El tiempo mínimo de inactividad es de 5 minutos (usa 0 para desactivarlo).');
                    }
                },
            ],
            'password_min_length' => ['sometimes', 'required', 'integer', 'between:8,32'],
            'password_complexity' => ['sometimes', 'required', 'boolean'],
            'max_login_attempts' => ['sometimes', 'required', 'integer', 'between:3,10'],
            'lockout_minutes' => ['sometimes', 'required', 'integer', 'between:1,60'],
            'max_login_attempts_ip' => ['sometimes', 'required', 'integer', 'between:5,1000'],
            'login_ip_window_seconds' => ['sometimes', 'required', 'integer', 'between:10,3600'],
            'login_ip_lockout_minutes' => ['sometimes', 'required', 'integer', 'between:1,1440'],
            'registration_enabled' => ['sometimes', 'required', 'boolean'],
        ], [
            'system_name.required' => 'El nombre del sistema es obligatorio.',
            'system_name.string' => 'El nombre del sistema no es válido.',
            'system_name.max' => 'El nombre del sistema no puede superar los 100 caracteres.',

            'logo.image' => 'El logotipo debe ser una imagen válida.',
            'logo.mimes' => 'El logotipo debe estar en formato PNG, JPG, JPEG o WEBP.',
            'logo.max' => 'El logotipo no puede superar los 2 MB.',

            'accent_color.required' => 'El color de acento es obligatorio.',
            'accent_color.in' => 'El color de acento seleccionado no es válido.',

            'currency.required' => 'La moneda es obligatoria.',
            'currency.in' => 'La moneda seleccionada no es válida.',

            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',

            'date_format.required' => 'El formato de fecha es obligatorio.',
            'date_format.in' => 'El formato de fecha seleccionado no es válido.',

            'time_format.required' => 'El formato de hora es obligatorio.',
            'time_format.in' => 'El formato de hora seleccionado no es válido.',

            'business_name.max' => 'La razón social no puede superar los 150 caracteres.',
            'business_rfc.regex' => 'El RFC no tiene un formato válido (12 o 13 caracteres, por ejemplo XAXX010101000).',
            'business_address.max' => 'La dirección no puede superar los 255 caracteres.',
            'business_phone.regex' => 'Ingresa un teléfono válido (solo números, espacios, + , - y paréntesis).',
            'business_email.email' => 'Ingresa un correo electrónico válido.',
            'business_email.max' => 'El correo no puede superar los 255 caracteres.',
            'business_website.url' => 'Ingresa una dirección web válida, por ejemplo https://tusitio.com.',
            'business_website.max' => 'La dirección web no puede superar los 255 caracteres.',
            'business_schedule.max' => 'El horario no puede superar los 500 caracteres.',

            'session_timeout.required' => 'Indica los minutos de inactividad (0 para desactivar).',
            'session_timeout.integer' => 'Los minutos de inactividad deben ser un número entero.',
            'session_timeout.between' => 'Los minutos de inactividad deben estar entre 0 y 1440.',

            'password_min_length.required' => 'La longitud mínima de contraseña es obligatoria.',
            'password_min_length.integer' => 'La longitud mínima debe ser un número entero.',
            'password_min_length.between' => 'La longitud mínima debe estar entre 8 y 32 caracteres.',

            'password_complexity.boolean' => 'La opción de complejidad de contraseña no es válida.',
            'registration_enabled.boolean' => 'La opción de registro público no es válida.',

            'max_login_attempts.required' => 'El número de intentos permitidos es obligatorio.',
            'max_login_attempts.integer' => 'Los intentos permitidos deben ser un número entero.',
            'max_login_attempts.between' => 'Los intentos permitidos deben estar entre 3 y 10.',

            'lockout_minutes.required' => 'Los minutos de bloqueo son obligatorios.',
            'lockout_minutes.integer' => 'Los minutos de bloqueo deben ser un número entero.',
            'lockout_minutes.between' => 'Los minutos de bloqueo deben estar entre 1 y 60.',

            'max_login_attempts_ip.required' => 'El límite de intentos por IP es obligatorio.',
            'max_login_attempts_ip.integer' => 'El límite de intentos por IP debe ser un número entero.',
            'max_login_attempts_ip.between' => 'El límite de intentos por IP debe estar entre 5 y 1000.',

            'login_ip_window_seconds.required' => 'La ventana de tiempo por IP es obligatoria.',
            'login_ip_window_seconds.integer' => 'La ventana de tiempo por IP debe ser un número entero.',
            'login_ip_window_seconds.between' => 'La ventana de tiempo por IP debe estar entre 10 y 3600 segundos.',
            'login_ip_lockout_minutes.required' => 'Los minutos de bloqueo por IP son obligatorios.',
            'login_ip_lockout_minutes.integer' => 'Los minutos de bloqueo por IP deben ser un número entero.',
            'login_ip_lockout_minutes.between' => 'El bloqueo por IP debe estar entre 1 y 1440 minutos.',
        ]);

        foreach (self::CLAVES_NEGOCIO as $clave) {
            if (array_key_exists($clave, $datos)) {
                $datos[$clave] = trim((string) ($datos[$clave] ?? ''));
            }
        }

        if (array_key_exists('business_rfc', $datos)) {
            $datos['business_rfc'] = Str::upper($datos['business_rfc']);
        }

        foreach (self::CLAVES_BOOLEANAS as $clave) {
            if (array_key_exists($clave, $datos)) {
                $datos[$clave] = $request->boolean($clave) ? '1' : '0';
            }
        }

        $logoAnterior = $settings->get('logo_path');

        unset($datos['logo']);

        if ($request->hasFile('logo')) {
            $datos['logo_path'] = $request->file('logo')
                ->store('settings', 'public');
        }

        $claveActualizada = array_key_first($datos);

        $esNegocio = array_diff(array_keys($datos), self::CLAVES_NEGOCIO) === [];
        $esSeguridad = array_diff(array_keys($datos), self::CLAVES_SEGURIDAD) === [];
        $esSeccion = count($datos) > 1 && ($esNegocio || $esSeguridad);

        $settings->update($datos);

        if (
            isset($datos['logo_path']) &&
            $logoAnterior &&
            $logoAnterior !== $datos['logo_path']
        ) {
            Storage::disk('public')->delete($logoAnterior);
        }

        $usuario = $request->user();

        $partes = [];

        if (in_array($claveActualizada, [
            'accent_color',
        ], true)) {
            $partes[] = 'la configuración de apariencia';
        }

        if (in_array($claveActualizada, [
            'currency',
            'timezone',
            'date_format',
            'time_format',
        ], true)) {
            $partes[] = 'la configuración regional';
        }

        if (in_array($claveActualizada, self::CLAVES_SEGURIDAD, true)) {
            $partes[] = 'la configuración de seguridad';
        }

        if (in_array($claveActualizada, self::CLAVES_NEGOCIO, true)) {
            $partes[] = 'los datos del negocio';
        }

        if ($claveActualizada === 'system_name') {
            $partes[] = 'la configuración general';
        }

        if ($claveActualizada === 'logo_path') {
            $partes[] = 'el logotipo';
        }

        if ($partes === []) {
            $partes[] = 'la configuración general';
        }

        AuditLogService::log(
            module: 'configuracion',
            action: 'ACTUALIZAR',
            description: 'El usuario "' .
            $usuario->name .
            '" actualizó ' . implode(' y ', $partes) .
            ' del sistema IronPulse.',
            entity: $usuario
        );

        $mensajes = [
            'accent_color' => 'El color de acento se actualizó correctamente.',

            'system_name' => 'El nombre del sistema se actualizó correctamente.',
            'logo_path' => 'El logotipo se actualizó correctamente.',

            'currency' => 'La moneda se actualizó correctamente.',
            'timezone' => 'La zona horaria se actualizó correctamente.',
            'date_format' => 'El formato de fecha se actualizó correctamente.',
            'time_format' => 'El formato de hora se actualizó correctamente.',

            'business_name' => 'La razón social se actualizó correctamente.',
            'business_rfc' => 'El RFC se actualizó correctamente.',
            'business_address' => 'La dirección se actualizó correctamente.',
            'business_phone' => 'El teléfono se actualizó correctamente.',
            'business_email' => 'El correo electrónico se actualizó correctamente.',
            'business_website' => 'El sitio web se actualizó correctamente.',
            'business_schedule' => 'El horario se actualizó correctamente.',

            'session_timeout' => 'El tiempo de sesión se actualizó correctamente.',
            'password_min_length' => 'La longitud mínima de contraseña se actualizó correctamente.',
            'password_complexity' => 'La configuración de complejidad de contraseña se actualizó correctamente.',
            'max_login_attempts' => 'El número máximo de intentos se actualizó correctamente.',
            'lockout_minutes' => 'El tiempo de bloqueo se actualizó correctamente.',
            'max_login_attempts_ip' => 'El límite de intentos por IP se actualizó correctamente.',
            'login_ip_window_seconds' => 'La ventana de tiempo por IP se actualizó correctamente.',
            'login_ip_lockout_minutes' => 'El tiempo de bloqueo por IP se actualizó correctamente.',
            'registration_enabled' => 'La configuración de registro se actualizó correctamente.',
        ];

        $mensaje = $mensajes[$claveActualizada] ?? 'La configuración se actualizó correctamente.';

        if ($esSeccion) {
            $mensaje = $esSeguridad
                ? 'La configuración de seguridad se guardó correctamente.'
                : 'Los datos del negocio se guardaron correctamente.';
        }

        return response()->json([
            'success' => true,
            'mensaje' => $mensaje,
            'settings' => $settings->all(),
        ]);
    }


    /**
     * Cada grupo de ajustes requiere su propia acción de permiso.
     * Si una petición incluye varios campos, el usuario debe tener todos
     * los permisos correspondientes antes de que se valide o guarde.
     */
    private function autorizarCambios(Request $request): void
    {
        $permissionService = app(PermissionService::class);
        $clavesSolicitadas = array_keys($request->all());

        if ($request->hasFile('logo')) {
            $clavesSolicitadas[] = 'logo';
        }

        foreach (array_unique($clavesSolicitadas) as $clave) {
            $slug = self::PERMISOS_CONFIGURACION[$clave] ?? null;

            if (
                $slug !== null &&
                !$permissionService->tieneAccionPorSlug($request->user(), $slug)
            ) {
                abort(403, 'No tienes permiso para modificar esta configuración.');
            }
        }
    }

    public function updateTheme(
        Request $request,
        UserPreferences $preferences
    ): JsonResponse {
        $datos = $request->validate([
            'theme_mode' => [
                'required',
                Rule::in(['light', 'dark']),
            ],

            'light_theme_style' => [
                'required',
                Rule::in(['white', 'mist', 'sky']),
            ],

            'dark_theme_style' => [
                'required',
                Rule::in(['graphite', 'charcoal', 'black']),
            ],
        ], [
            'theme_mode.required' => 'El modo del tema es obligatorio.',
            'theme_mode.in' => 'El modo del tema seleccionado no es válido.',

            'light_theme_style.required' => 'El estilo del tema claro es obligatorio.',
            'light_theme_style.in' => 'La variante clara del tema seleccionada no es válida.',

            'dark_theme_style.required' => 'El estilo del tema oscuro es obligatorio.',
            'dark_theme_style.in' => 'La variante oscura del tema seleccionada no es válida.',
        ]);

        $usuario = $request->user();

        $preferencias = $preferences->update($usuario, $datos);

        AuditLogService::log(
            module: 'configuracion',
            action: 'ACTUALIZAR',
            description: 'El usuario "' .
            $usuario->name .
            '" actualizó sus preferencias de tema de IronPulse.',
            entity: $usuario
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'El tema se actualizó correctamente.',
            'preferences' => $preferencias,
        ]);
    }
}
