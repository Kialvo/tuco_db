{{-- resources/views/storages/partials/url-modal.blade.php --}}
<div id="urlModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white w-80 rounded shadow p-4 space-y-3">
        <h2 class="font-semibold text-lg text-gray-700">Link actions</h2>

        <input id="urlModalInput"
               class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
               readonly>

        {{-- Buttons --}}
        <div class="flex justify-end gap-2">
            <a id="urlModalOpen"
               href="#"
               target="_blank"
               class="text-white px-3 py-1 rounded text-xs"
               style="background-color: #35aa3e;">
                Open
            </a>

            <button id="urlModalCopy"
                    class="text-white px-3 py-1 rounded text-xs"
                    style="background-color: #000000;">
                Copy
            </button>

            <button id="urlModalClose"
                    class="text-white px-3 py-1 rounded text-xs"
                    style="background-color: #aa35a1;">
                Close
            </button>
        </div>

    </div>
</div>


