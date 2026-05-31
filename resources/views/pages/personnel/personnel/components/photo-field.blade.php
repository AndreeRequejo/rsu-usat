<div>
    <label class="block text-sm font-medium text-[#333333] mb-2">Fotografia de Perfil</label>
    <div class="flex items-center gap-4">
        <div class="shrink-0">
            @if ($photo)
                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7]">
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                </div>
            @elseif ($editingId)
                @php
                $currentProfile = null;
                if ($editingId) {
                    $emp = \App\Models\Employee::with('employeeImages')->find($editingId);
                    if ($emp) {
                        $currentProfile = $emp->employeeImages->where('profile', true)->first();
                    }
                }
                @endphp
                @if ($currentProfile)
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7]">
                        <img src="{{ url('storage/' . $currentProfile->image) }}" alt="Current" class="w-full h-full object-cover">
                    </div>
                @else
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7] bg-[#A5D6A7] flex items-center justify-center">
                        <span class="text-xl font-bold text-[#2E8B57]">{{ $first_name ? strtoupper(substr($first_name, 0, 1)) : '?' }}</span>
                    </div>
                @endif
            @else
                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-dashed border-[#A5D6A7] bg-[#F5F5F5] flex items-center justify-center">
                    <svg class="h-8 w-8 text-[#999999]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        </div>
        <div class="flex-1">
            <input type="file" wire:model="photo" accept="image/jpeg,image/png" class="block w-full text-sm text-[#333333] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#2E8B57] file:text-white hover:file:bg-[#257046]" />
            @if ($photo)
                <button type="button" wire:click="$set('photo', null)" class="mt-2 text-sm text-[#E53935] hover:underline">Quitar</button>
            @endif
            <p class="text-xs text-[#999999] mt-1">JPG, JPEG o PNG (max. 2MB)</p>
        </div>
    </div>
    @error('photo') <span class="text-xs text-[#E53935] mt-1 block">{{ $message }}</span> @enderror
</div>