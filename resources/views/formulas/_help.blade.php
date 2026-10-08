<div class="card card-body h-fit text-sm">
    <h2 class="card-title mb-3">Cómo escribir fórmulas</h2>
    <p class="mb-2 text-slate-600">Variables disponibles:</p>
    <ul class="mb-4 space-y-1">
        @foreach (\App\Models\Formula::VARIABLES as $var => $desc)
            <li><code class="rounded bg-indigo-50 px-1.5 text-indigo-700">{{ $var }}</code> <span class="text-slate-500">{{ $desc }}</span></li>
        @endforeach
        <li class="text-slate-500">…y el <strong>código</strong> de cualquier fórmula anterior.</li>
    </ul>
    <p class="mb-2 text-slate-600">Operadores: <code>+ - * / % ^ ( )</code> y comparaciones <code>&gt; &lt; &gt;= &lt;= == !=</code></p>
    <p class="mb-2 text-slate-600">Funciones: <code>min()</code> <code>max()</code> <code>round(x, d)</code> <code>abs()</code> <code>ceil()</code> <code>floor()</code> <code>if(cond, si, no)</code></p>
    <p class="mb-1 mt-4 font-medium text-slate-700">Ejemplos</p>
    <ul class="space-y-1.5 text-xs">
        <li><code class="block rounded bg-slate-100 p-1.5">subtotal * 0.025</code> Retención del 2,5 %</li>
        <li><code class="block rounded bg-slate-100 p-1.5">if(subtotal &lt; 100, 8, 0)</code> Envío gratis desde 100</li>
        <li><code class="block rounded bg-slate-100 p-1.5">round(total * 0.10, 0)</code> Propina sugerida (informativa)</li>
        <li><code class="block rounded bg-slate-100 p-1.5">max(0, (subtotal - 1000) * 0.01)</code> Tasa sobre el excedente</li>
    </ul>
</div>
