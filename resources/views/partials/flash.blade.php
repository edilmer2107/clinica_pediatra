@if (session('success'))
    <div class="alert-success mb-4" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert-error mb-4" role="alert">{{ session('error') }}</div>
@endif
@if (session('warning'))
    <div class="alert-warning mb-4" role="alert">{{ session('warning') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error mb-4" role="alert">
        <p class="font-semibold">Revise los datos ingresados:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
