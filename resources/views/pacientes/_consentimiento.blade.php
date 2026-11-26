<div class="card">
    <div class="card-header"><h2 class="card-title">Consentimiento informado (Ley N° 29733 - Protección de Datos Personales)</h2></div>
    <div class="card-body space-y-3 text-sm">
        <p class="rounded-lg bg-slate-50 p-3 text-slate-600">El tutor autoriza a la clínica a tratar los datos personales y de salud del menor con la finalidad exclusiva de brindarle atención médica, farmacéutica y administrativa. Los datos se conservan con medidas de seguridad, no se comparten con terceros sin autorización (salvo obligación legal ante la autoridad sanitaria) y el tutor puede ejercer sus derechos de acceso, rectificación, cancelación y oposición. Tratándose de un menor de edad, el consentimiento lo otorga su padre, madre o tutor legal.</p>
        <label class="flex items-start gap-2"><input type="checkbox" name="acepta_tratamiento_datos" value="1" @checked(old('acepta_tratamiento_datos')) required> <span><strong>Acepta el tratamiento de datos personales del menor</strong> (obligatorio)</span></label>
        <label class="flex items-start gap-2"><input type="checkbox" name="acepta_atencion_medica" value="1" @checked(old('acepta_atencion_medica', true))> <span>Acepta la atención médica y procedimientos de rutina (vacunación, controles)</span></label>
        <label class="flex items-start gap-2"><input type="checkbox" name="acepta_notificaciones" value="1" @checked(old('acepta_notificaciones', true))> <span>Acepta recibir recordatorios y notificaciones por correo y SMS</span></label>
    </div>
</div>
