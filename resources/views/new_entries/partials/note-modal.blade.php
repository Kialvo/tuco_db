<!-- Note Details Modal (hidden by default) -->
<div id="noteModal"
     class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="bg-white rounded shadow-lg relative w-auto max-w-md p-6">
        <!-- Close (X) -->
        <button type="button" id="closeNoteModal"
                class="absolute top-3 right-3 text-2xl text-gray-600 hover:text-gray-800">&times;</button>

        <h2 class="text-xl font-semibold mb-4 text-gray-800">Extra Note</h2>

        <p id="modalNoteBody" class="text-gray-700 whitespace-pre-line"></p>

        <div class="mt-6 text-right">
            <button type="button" id="closeNoteModalBottom"
                    class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none">
                Close
            </button>
        </div>
    </div>
</div>
