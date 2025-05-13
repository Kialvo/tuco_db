<!-- Client Details Modal (initially hidden) -->
<div
    id="clientModal"
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
            id="closeClientModal"
            class="absolute top-3 right-3 text-2xl text-gray-600 hover:text-gray-800"
        >&times;</button>

        <!-- Heading -->
        <h2 id="modalClientTitle" class="text-xl font-semibold mb-4 text-gray-800">
            Client Details
        </h2>

        <!-- Details -->
        <div class="flex flex-col space-y-2">
            <p>
                <strong>Name:</strong>
                <span id="modalClientName" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Email:</strong>
                <span id="modalClientEmail" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Company:</strong>
                <span id="modalClientCompany" class="ml-1 text-gray-700"></span>
            </p>

            <!-- Related storages / websites -->
            <div class="mt-4">
                <strong>Storages:</strong>
                <div id="modalClientStorages" class="text-sm mt-1"></div>

                <strong class="block mt-3">Websites:</strong>
                <div id="modalClientWebsites" class="text-sm mt-1"></div>
            </div>
        </div>

        <!-- Bottom Close -->
        <div class="mt-6 text-right">
            <button
                type="button"
                id="closeClientModalBottom"
                class="inline-block px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700 focus:outline-none"
            >
                Close
            </button>
        </div>
    </div>
</div>
