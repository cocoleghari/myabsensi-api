<div>
    <label class="form-label">
        {{ $label }}
        @if (!empty($required))
            <span class="text-red-400">*</span>
        @endif
    </label>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value ?? '' }}" class="form-input">
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
