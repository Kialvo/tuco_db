{{-- Note modal (websites). Open via JS by toggling .hidden on #noteModal --}}
<div id="noteModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 flex">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col"
         @click.outside="$('#noteModal').addClass('hidden')">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800">Note</h2>
            <button type="button" id="closeNoteModal"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <x-icon name="x" size="lg" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4 max-h-[60vh] overflow-y-auto slim-scroll">
            <p id="modalNoteBody" class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"></p>
        </div>

        {{-- Footer --}}
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end rounded-b-2xl bg-white">
            <button type="button" id="closeNoteModalBottom"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Close
            </button>
        </div>
    </div>
</div>
