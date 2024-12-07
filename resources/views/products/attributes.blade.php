@foreach ($attributes as $attribute)
    <div class="mb-4">
        <label class="block text-gray-400 font-medium mb-2">{{ $attribute->name }}</label>
        <select name="attributes[{{ $attribute->id }}]" class="w-full p-3 rounded-lg border border-gray-900 bg-black text-white">
            @foreach ($attribute->values as $value)
                <option value="{{ $value->id }}">{{ $value->value }}</option>
            @endforeach
        </select>
    </div>
@endforeach
