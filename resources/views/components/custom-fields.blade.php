@props(['fields', 'values' => []])
@if ($fields->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-4 sm:grid-cols-2']) }}>
        @foreach ($fields as $field)
            @php $value = $values[$field->name] ?? null; $name = 'custom_fields['.$field->name.']'; @endphp
            @switch($field->type)
                @case('textarea')
                    <x-form.textarea :name="$name" :label="$field->label" :value="$value" :required="$field->is_required" class="sm:col-span-2"/>
                    @break
                @case('select')
                    <x-form.select :name="$name" :label="$field->label" :value="$value" :required="$field->is_required" placeholder="Seleccione..."
                        :options="collect($field->options ?? [])->mapWithKeys(fn ($o) => [$o => $o])->all()"/>
                    @break
                @case('checkbox')
                    <x-form.checkbox :name="$name" :label="$field->label" :checked="(bool) $value" class="pt-6"/>
                    @break
                @default
                    <x-form.input :name="$name" :label="$field->label" :value="$value" :required="$field->is_required"
                        :type="$field->type === 'number' ? 'number' : ($field->type === 'date' ? 'date' : 'text')" step="any"/>
            @endswitch
        @endforeach
    </div>
@endif
