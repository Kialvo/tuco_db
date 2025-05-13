<!-- Copy Details Modal (initially hidden) -->
<div
    id="copyModal"
    class="fixed inset-0 z-50 flex items-center justify-center hidden"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Modal panel -->
    <div class="bg-white rounded shadow-lg relative w-auto max-w-sm p-6">
        <!-- Close (X) -->
        <button
            type="button"
            id="closeCopyModal"
            class="absolute top-3 right-3 text-2xl text-gray-600 hover:text-gray-800"
        >&times;</button>

        <!-- Heading -->
        <h2 id="modalCopyTitle" class="text-xl font-semibold mb-4 text-gray-800">
            Copy Details
        </h2>

        <!-- Details -->
        <div class="flex flex-col space-y-2">
            <p>
                <strong>Copywriter:</strong>
                <span id="modalCopyVal" class="ml-1 text-gray-700"></span>
            </p>
        </div>

        <!-- Bottom Close -->
        <div class="mt-6 text-right">
            <button
                type="button"
                id="closeCopyModalBottom"
                class="inline-block px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700 focus:outline-none"
            >
                Close
            </button>
        </div>
    </div>
</div>
