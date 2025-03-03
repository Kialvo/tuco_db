<!-- Contact Details Modal (initially hidden) -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center hidden"
    id="contactModal"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- The modal panel (no backdrop) -->
    <div
        class="bg-white w-full max-w-lg mx-4 sm:mx-0 rounded shadow-lg p-6 relative"
    >
        <!-- Close button (X) in top-right corner -->
        <button
            type="button"
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl"
            id="closeContactModal"
        >
            &times;
        </button>

        <!-- Modal heading -->
        <h2
            class="text-xl font-semibold mb-4 text-gray-800"
            id="modalContactTitle"
        >
            Contact Details
        </h2>

        <!-- Contact Fields -->
        <div class="space-y-2">
            <p>
                <strong>Name:</strong>
                <span id="modalContactName" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Email:</strong>
                <span id="modalContactEmail" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Phone:</strong>
                <span id="modalContactPhone" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Facebook:</strong>
                <span id="modalContactFacebook" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Instagram:</strong>
                <span id="modalContactInstagram" class="ml-1 text-gray-700"></span>
            </p>
        </div>

        <!-- Footer with a secondary close button if desired -->
        <div class="mt-6 text-right">
            <button
                type="button"
                class="inline-block px-4 py-2 bg-blue-600 text-white
                       font-medium rounded hover:bg-blue-700 focus:outline-none"
                id="closeContactModalBottom"
            >
                Close
            </button>
        </div>
    </div>
</div>
