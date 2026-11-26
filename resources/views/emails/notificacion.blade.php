<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; background:#f1f5f9; padding:24px; color:#1e293b;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:24px;">
        <h2 style="color:#133a63; margin-top:0;">Clínica Pediátrica</h2>
        <p style="white-space:pre-line;">{{ $mensaje }}</p>
        @if ($urlAccion)
            <p><a href="{{ $urlAccion }}" style="display:inline-block; background:#1f6fc0; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none;">Ver en el portal de tutores</a></p>
        @endif
        <p style="font-size:12px; color:#64748b;">Este es un mensaje automático. No responda a este correo.</p>
    </div>
    @if ($urlSeguimiento)
        <img src="{{ $urlSeguimiento }}" width="1" height="1" alt="" style="display:none;">
    @endif
</body>
</html>
